<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallHistory;
use App\Models\Category;
use App\Models\Lead;
use App\Services\SmartLeadDistribution;
use App\Support\DistributionValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SmartDistributionController extends Controller
{
    private function company(Request $request): int
    {
        $companyId = (int) $request->user()->company_id;

        abort_if($companyId < 1, 403, 'Company is missing.');
        abort_unless($request->user()->is_active, 403, 'Employee is inactive.');

        return $companyId;
    }

    public function categories(Request $request)
    {
        $companyId = $this->company($request);

        return response()->json([
            'success' => true,
            'data' => Category::query()
                ->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                })
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function states(Request $request)
    {
        $companyId = $this->company($request);

        $input = $request->validate([
            'category_id' => ['nullable', 'integer'],
        ]);

        $states = Lead::query()
            ->where('company_id', $companyId)
            ->when(
                isset($input['category_id']),
                fn ($query) => $query->where('category_id', $input['category_id'])
            )
            ->whereNotNull('state')
            ->where('state', '<>', '')
            ->distinct()
            ->pluck('state')
            ->map(fn ($state) => DistributionValue::state($state))
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $states,
        ]);
    }

    public function preference(
        Request $request,
        SmartLeadDistribution $distribution
    ) {
        $companyId = $this->company($request);

        $input = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                }),
            ],
            'state' => ['required', 'string', 'max:120'],
        ]);

        $state = DistributionValue::state($input['state']);

        abort_unless(
            Lead::query()
                ->where('company_id', $companyId)
                ->where('category_id', $input['category_id'])
                ->whereRaw('LOWER(TRIM(state)) = ?', [$state])
                ->exists(),
            422,
            'No uploaded data exists for this category and state.'
        );

        $distribution->preference(
            $request->user(),
            (int) $input['category_id'],
            $state
        );

        $distribution->refill($companyId);

        return response()->json($distribution->current($request->user()));
    }

    public function more(
        Request $request,
        SmartLeadDistribution $distribution
    ) {
        $companyId = $this->company($request);

        abort_unless(
            DB::table('distribution_preferences')
                ->where('company_id', $companyId)
                ->where('user_id', $request->user()->id)
                ->where('enabled', true)
                ->exists(),
            422,
            'Select category and state first.'
        );

        $distribution->refill($companyId);

        return response()->json($distribution->current($request->user()));
    }

    public function current(
        Request $request,
        SmartLeadDistribution $distribution
    ) {
        $this->company($request);

        return response()->json($distribution->current($request->user()));
    }

    public function cities(
        Request $request,
        SmartLeadDistribution $distribution
    ) {
        $this->company($request);

        return response()->json([
            'success' => true,
            'data' => $distribution->cityStatus($request->user()),
        ]);
    }

    public function result(
        Request $request,
        Lead $leadContact,
        SmartLeadDistribution $distribution
    ) {
        $companyId = $this->company($request);

        $input = $request->validate([
            'client_call_id' => ['required', 'uuid'],
            'batch_item_id' => ['nullable', 'integer', 'min:1'],
            'call_status' => ['required', Rule::in(SmartLeadDistribution::OUTCOMES)],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'call_duration' => ['nullable', 'integer', 'min:0'],
            'recording' => [
                'nullable',
                'file',
                'mimes:mp3,wav,m4a,ogg,webm,mp4,3gp,amr',
                'max:20480',
            ],
        ]);

        $path = null;

        try {
            if ($request->hasFile('recording')) {
                $path = $request->file('recording')
                    ->store('call_recordings', 'public');

                abort_unless($path, 500, 'Recording upload failed.');
            }

            $input['recording'] = $path;

            $history = $distribution->result(
                $request->user(),
                (int) $leadContact->id,
                $input
            );
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        // A retry may return the previously saved history.
        if ($path && $history->recording !== $path) {
            Storage::disk('public')->delete($path);
        }

        $refillPending = false;

        try {
            $distribution->refill($companyId);
        } catch (\Throwable $exception) {
            report($exception);
            $refillPending = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'Call result saved.',
            'data' => [
                'id' => $leadContact->id,
                'history_id' => $history->id,
                'call_status' => $history->call_status,
                'client_call_id' => $history->client_call_id,
                'recording_url' => $history->recording
                    ? Storage::disk('public')->url($history->recording)
                    : null,
            ],
            'refill_pending' => $refillPending,
            'queue' => $distribution->current($request->user()),
        ]);
    }

    public function history(Request $request)
    {
        $companyId = $this->company($request);

        $input = $request->validate([
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'lead_id' => ['nullable', 'integer', 'min:1'],
            'call_status' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = CallHistory::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('lead', fn ($query) => $query->withTrashed()
                ->where('company_id', $companyId))
            ->with(['lead' => fn ($query) => $query->withTrashed()
                ->select('id', 'name', 'mobile', 'city', 'state')]);

        if (isset($input['batch_id'])) {
            $batch = DB::table('distribution_batches')
                ->where('id', $input['batch_id'])
                ->where('company_id', $companyId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $query->whereIn('distribution_item_id', function ($items) use ($batch) {
                $items->select('id')
                    ->from('distribution_items')
                    ->where('batch_id', $batch->id);
            });
        }

        if (isset($input['lead_id'])) {
            $query->where('lead_id', $input['lead_id']);
        }

        if (isset($input['call_status'])) {
            $query->where('call_status', $input['call_status']);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('id')
                ->paginate($input['per_page'] ?? 25)
                ->withQueryString(),
        ]);
    }
}