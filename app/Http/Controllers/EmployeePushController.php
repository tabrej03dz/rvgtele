<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmployeePush;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EmployeePushController extends Controller
{
    public function index(): View
    {
        $history = DB::table('employee_push_notifications as n')
            ->select('n.*')
            ->selectSub(
                DB::table('employee_push_recipients as r')
                    ->selectRaw('count(*)')
                    ->whereColumn('r.notification_id', 'n.id'),
                'participant_count',
            )
            ->orderByDesc('n.id')
            ->paginate(20);

        return view('employee-notifications.index', [
            'history' => $history,
            'editing' => null,
            'selectedIds' => [],
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function details(int $notification): View
    {
        $item = $this->record($notification);

        $participants = DB::table('employee_push_recipients as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.notification_id', $notification)
            ->orderBy('u.name')
            ->orderBy('u.id')
            ->paginate(25, ['u.id', 'u.name', 'u.employee_code']);

        return view('employee-notifications.details', compact('item', 'participants'));
    }

    public function edit(int $notification): View
    {
        $editing = $this->record($notification);

        $selectedIds = DB::table('employee_push_recipients')
            ->where('notification_id', $notification)
            ->pluck('user_id')
            ->all();

        return view('employee-notifications.index', [
            'history' => null,
            'editing' => $editing,
            'selectedIds' => $selectedIds,
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->publish($request);
    }

    public function resend(Request $request, int $notification): RedirectResponse
    {
        return $this->publish($request, $this->record($notification));
    }

    private function publish(Request $request, ?object $source = null): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', 'in:all,selected'],
            'recipient_ids' => [
                'exclude_unless:audience,selected',
                'required',
                'array',
                'min:1',
            ],
            'recipient_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'message' => ['nullable', 'string', 'max:600'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'video' => ['nullable', 'file', 'mimes:mp4', 'max:20480'],
            'remove_image' => ['sometimes', 'boolean'],
            'remove_video' => ['sometimes', 'boolean'],
        ]);

        $employees = $this->employees();
        $requestedIds = $data['recipient_ids'] ?? [];

        if ($data['audience'] === 'selected') {
            $employees->whereIn('users.id', $requestedIds);
        }

        $message = $data['message'] ?? '';
        $attachments = [
            'image' => $source?->image_path,
            'video' => $source?->video_path,
        ];
        $newFiles = [];

        try {
            foreach (['image', 'video'] as $kind) {
                if ($request->hasFile($kind)) {
                    $path = $request->file($kind)
                        ->store('employee-notifications', 'local');

                    if (!$path) {
                        throw new RuntimeException("Could not save the {$kind}.");
                    }

                    $newFiles[] = $path;
                    $attachments[$kind] = $path;
                } elseif ($request->boolean('remove_' . $kind)) {
                    $attachments[$kind] = null;
                }
            }

            if ($message === '' && !$attachments['image'] && !$attachments['video']) {
                throw ValidationException::withMessages([
                    'message' => 'Enter a message or attach an image or video.',
                ]);
            }

            [$id, $participantCount, $queuedCount] = DB::transaction(function () use (
                $request,
                $source,
                $message,
                $attachments,
                $employees,
                $requestedIds,
                $data,
            ): array {
                $now = now();

                $id = DB::table('employee_push_notifications')->insertGetId([
                    'created_by' => $request->user()->id,
                    'resent_from_id' => $source?->id,
                    'message' => $message,
                    'image_path' => $attachments['image'],
                    'video_path' => $attachments['video'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $participantCount = DB::table('employee_push_recipients')->insertUsing(
                    ['notification_id', 'user_id'],
                    $employees
                        ->selectRaw('? as notification_id, users.id as user_id', [$id])
                        ->toBase(),
                );

                if (
                    $participantCount === 0 ||
                    ($data['audience'] === 'selected' && $participantCount !== count($requestedIds))
                ) {
                    throw ValidationException::withMessages([
                        'recipient_ids' => 'Select eligible employees. One or more selected employees are unavailable.',
                    ]);
                }

                return [$id, $participantCount, $this->queuePushes($id)];
            });
        } catch (Throwable $exception) {
            if ($newFiles !== []) {
                Storage::disk('local')->delete($newFiles);
            }

            throw $exception;
        }

        $notice = $queuedCount > 0
            ? "Notification #{$id} saved for {$participantCount} participant(s). Push queued for {$queuedCount} device(s)."
            : "Notification #{$id} saved for {$participantCount} participant(s). No registered mobile devices were found.";

        return redirect()
            ->route('employee-notifications.index')
            ->with('success', $notice);
    }

    private function queuePushes(int $id): int
    {
        $queue = Queue::connection('database');
        $queued = 0;

        DeviceToken::query()
            ->whereIn('user_id', DB::table('employee_push_recipients')
                ->where('notification_id', $id)
                ->select('user_id'))
            ->whereIn('platform', ['android', 'ios'])
            ->where('token', '<>', '')
            ->select(['id', 'user_id'])
            ->chunkById(250, function (Collection $devices) use ($id, $queue, &$queued): void {
                $jobs = $devices->map(fn (DeviceToken $device) => new SendEmployeePush(
                    notificationId: $id,
                    deviceId: $device->id,
                    userId: $device->user_id,
                ));

                $queue->bulk($jobs->all(), '', 'broadcasts');
                $queued += $jobs->count();
            });

        return $queued;
    }

public function inbox(Request $request): JsonResponse
{
    $notifications = DB::table('employee_push_recipients as r')
        ->join('employee_push_notifications as n', 'n.id', '=', 'r.notification_id')
        ->where('r.user_id', $request->user()->id)
        ->orderByDesc('n.id')
        ->paginate(20, [
            'n.id',
            'n.message',
            'n.created_at',
            'n.updated_at',
            'n.image_path',
            'n.video_path',
        ])
        ->through(function (object $item): array {
            $hasImage = filled($item->image_path);
            $hasVideo = filled($item->video_path);
            $detailUrl = url("/api/employee-notifications/{$item->id}");

            return [
                'id' => (int) $item->id,
                'message' => $item->message,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'has_image' => $hasImage,
                'has_video' => $hasVideo,
                'image_url' => $hasImage ? "{$detailUrl}/image" : null,
                'video_url' => $hasVideo ? "{$detailUrl}/video" : null,
            ];
        });

    return response()->json([
        'status' => true,
        'data' => $notifications->items(),
        'pagination' => [
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'next_page' => $notifications->hasMorePages()
                ? $notifications->currentPage() + 1
                : null,
        ],
    ])->header('Cache-Control', 'private, no-store');
}

    public function show(Request $request, int $notification): JsonResponse
    {
        $item = $this->notificationFor($request, $notification);

        return response()->json([
            'id' => $item->id,
            'message' => $item->message,
            'created_at' => $item->created_at,
            'has_image' => $item->image_path !== null,
            'has_video' => $item->video_path !== null,
            'image_url' => $item->image_path
                ? route('api.employee-notifications.attachment', [
                    'notification' => $item->id,
                    'kind' => 'image',
                ])
                : null,
            'video_url' => $item->video_path
                ? route('api.employee-notifications.attachment', [
                    'notification' => $item->id,
                    'kind' => 'video',
                ])
                : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function attachment(
        Request $request,
        int $notification,
        string $kind,
    ): BinaryFileResponse {
        $item = $this->notificationFor($request, $notification);
        $path = $kind === 'image' ? $item->image_path : $item->video_path;
        $disk = Storage::disk('local');

        abort_unless($path && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $kind === 'video'
                ? 'video/mp4'
                : ($disk->mimeType($path) ?: 'application/octet-stream'),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }

    private function notificationFor(Request $request, int $id): object
    {
        $item = DB::table('employee_push_notifications as n')
            ->join('employee_push_recipients as r', 'r.notification_id', '=', 'n.id')
            ->where('n.id', $id)
            ->where('r.user_id', $request->user()->id)
            ->first(['n.id', 'n.message', 'n.image_path', 'n.video_path', 'n.created_at']);

        abort_if($item === null, 404);

        return $item;
    }

    private function record(int $id): object
    {
        $item = DB::table('employee_push_notifications')->where('id', $id)->first();

        abort_if($item === null, 404);

        return $item;
    }

    private function employeeOptions(): Collection
    {
        return $this->employees()
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->get(['users.id', 'users.name', 'users.employee_code']);
    }

    private function employees(): Builder
    {
        return User::query()
            ->whereNotNull('users.company_id')
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', [
                'employee',
                'telecaller',
                'sales_executive',
                'field_sales_executive',
                'quality_analyst',
                'customer_support',
                'accounts_user',
                'team_leader',
                'sales_manager',
                'branch_manager',
            ]));
    }
}