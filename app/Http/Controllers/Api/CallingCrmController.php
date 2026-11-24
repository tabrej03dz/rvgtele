<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use App\Models\CallHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CallingCrmController extends Controller
{
    // public function index(Request $request, $category = null)
    // {
    //     $user = $request->user();

    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthenticated.',
    //         ], 401);
    //     }

    //     // Query parameter या request body से category लें।
    //     $requestCategory = $request->input('category');

    //     // गलत format जैसे category[]=1 आने पर स्पष्ट error दें।
    //     if ($requestCategory !== null && !is_scalar($requestCategory)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Category must be a single value.',
    //         ], 422);
    //     }

    //     $requestCategory = $requestCategory !== null
    //         ? trim((string) $requestCategory)
    //         : '';

    //     // Request में category खाली हो तो URL वाली category लें।
    //     $category = $requestCategory !== ''
    //         ? $requestCategory
    //         : trim((string) ($category ?? ''));

    //     $contacts = Lead::query()
    //         ->where('leads.assigned_to', $user->id)
    //         ->where('leads.call_status', 'pending')
    //         ->when($category !== '', function ($query) use ($category) {
    //             $query->where('leads.category', $category);
    //         })
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $contacts,
    //     ], 200);
    // }


    public function index(Request $request, $category = null)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    // Get category from query parameter or request body
    $requestCategory = $request->input('category');

    // Validate category format
    if (
        $requestCategory !== null &&
        !is_string($requestCategory) &&
        !is_int($requestCategory)
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Category must be a valid ID or name.',
        ], 422);
    }

    // Normalize request category
    $requestCategory = $requestCategory !== null
        ? trim((string) $requestCategory)
        : '';

    // Request category gets priority over URL category
    $category = $requestCategory !== ''
        ? $requestCategory
        : trim((string) ($category ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Get user's assigned pending leads
    |--------------------------------------------------------------------------
    */

    $query = Lead::query()
        ->where('leads.assigned_to', $user->id)
        ->where('leads.call_status', 'pending');

    /*
    |--------------------------------------------------------------------------
    | Category ID and Category Name Support
    |--------------------------------------------------------------------------
    */

    if ($category !== '') {

        if (ctype_digit($category)) {

            // Numeric value: match category_id
            $query->where(
                'leads.category_id',
                (int) $category
            );

        } else {

            // String value: match category name
            $query->whereRaw(
                'LOWER(TRIM(leads.category)) = ?',
                [mb_strtolower($category)]
            );

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch leads
    |--------------------------------------------------------------------------
    */

    $contacts = $query->get();

    return response()->json([
        'success' => true,
        'data' => $contacts,
    ], 200);
}



    public function callStatus(Request $request, ?Lead $leadContact = null)
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $companyId = (int) $user->company_id;

        $isLeadStatusUpdate = $request->route('lead_contact') !== null;

        if ($isLeadStatusUpdate) {
            $leadContact = Lead::query()
                ->where('company_id', $companyId)
                ->whereKey($leadContact?->getKey())
                ->firstOrFail();
        }

        $validated = $request->validate([
            'call_status' => 'nullable|string|max:255',
            'recording' => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm,mp4,3gp,amr|max:20480',
            'remarks' => 'nullable|string',
            'call_duration' => 'nullable|integer',
            'mobile' => 'nullable|string|max:30',
            'direction' => 'nullable|in:incoming,outgoing',
            'call_type' => 'nullable|in:incoming,outgoing,missed,rejected',
            'call_at' => 'nullable|date_format:Y-m-d H:i:s',
            'client_call_id' => 'nullable|uuid',
        ]);

        if ($isLeadStatusUpdate && empty($validated['call_status'])) {
            return response()->json([
                'success' => false,
                'message' => 'call_status is required.',
            ], 422);
        }

        if (!$isLeadStatusUpdate) {
            if (empty($validated['mobile']) || empty($validated['client_call_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'mobile and client_call_id are required.',
                ], 422);
            }

            if (empty($validated['direction']) && empty($validated['call_type'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'direction or call_type is required.',
                ], 422);
            }
        }

        $normalize = static function ($number) {
            $digits = preg_replace('/\D+/', '', (string) $number);

            if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $digits = substr($digits, 2);
            } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }

            return preg_match('/^[6-9][0-9]{9}$/', $digits) ? $digits : '';
        };

        $mobile = $normalize($validated['mobile'] ?? $leadContact?->mobile);

        if ($mobile === '') {
            return response()->json([
                'success' => false,
                'message' => 'Valid 10-digit mobile number required.',
            ], 422);
        }

        if (
            $isLeadStatusUpdate &&
            isset($validated['mobile']) &&
            $mobile !== $normalize($leadContact->mobile)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile does not match the lead.',
            ], 422);
        }

        if (!$isLeadStatusUpdate) {
            $possibleNumbers = [
                $mobile,
                '91' . $mobile,
                '+91' . $mobile,
                '0' . $mobile,
            ];

            $leadContact = Lead::query()
                ->where('company_id', $companyId)
                ->whereIn('mobile', $possibleNumbers)
                ->orderByRaw(
                    'CASE WHEN assigned_to = ? THEN 0 ELSE 1 END',
                    [$userId]
                )
                ->orderBy('id')
                ->first();
        }

        $direction = $validated['direction'] ?? null;
        $callType = $validated['call_type'] ?? null;

        $typeDirection = match ($callType) {
            'outgoing' => 'outgoing',
            'incoming', 'missed', 'rejected' => 'incoming',
            default => null,
        };

        if ($direction && $typeDirection && $direction !== $typeDirection) {
            return response()->json([
                'success' => false,
                'message' => 'direction and call_type do not match.',
            ], 422);
        }

        $direction ??= $typeDirection;
        $callType ??= $direction;
        $clientCallId = $validated['client_call_id'] ?? null;

        if ($clientCallId) {
            $existing = CallHistory::query()
                ->where('user_id', $userId)
                ->where('client_call_id', $clientCallId)
                ->first();

            if ($existing) {
                if ($existing->mobile !== $mobile) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Call ID belongs to another number.',
                    ], 409);
                }

                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Call already saved.',
                    'data' => $existing,
                ]);
            }
        }

        $recordingPath = null;

        try {
            if ($request->hasFile('recording')) {
                $recordingPath = $request->file('recording')
                    ->store('call_recordings', 'public');

                if (!$recordingPath) {
                    throw new \RuntimeException('Recording upload failed.');
                }
            }

            $status = $validated['call_status']
                ?? (in_array($callType, ['missed', 'rejected'], true) ? $callType : 'completed');

            $callAt = isset($validated['call_at'])
                ? \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $validated['call_at'])
                : now();

            $history = DB::transaction(function () use (
                $leadContact,
                $isLeadStatusUpdate,
                $status,
                $userId,
                $mobile,
                $direction,
                $callType,
                $clientCallId,
                $callAt,
                $recordingPath,
                $validated
            ) {
                if ($isLeadStatusUpdate) {
                    $leadContact->update([
                        'call_status' => $status,
                    ]);
                }

                return CallHistory::create([
                    'lead_id' => $leadContact?->id,
                    'user_id' => $userId,
                    'mobile' => $mobile,
                    'direction' => $direction,
                    'call_type' => $callType,
                    'client_call_id' => $clientCallId,
                    'call_status' => $status,
                    'call_at' => $callAt,
                    'recording' => $recordingPath,
                    'remarks' => $validated['remarks'] ?? null,
                    'call_duration' => $validated['call_duration'] ?? null,
                ]);
            });

            if ($isLeadStatusUpdate) {
                $leadContact->refresh();
            }

            return response()->json([
                'success' => true,
                'duplicate' => false,
                'message' => 'Call history saved successfully.',
                'data' => [
                    'id' => $leadContact?->id,
                    'lead_id' => $history->lead_id,
                    'mobile' => $history->mobile,
                    'direction' => $history->direction,
                    'call_type' => $history->call_type,
                    'call_status' => $status,
                    'call_status_updated_at' => $isLeadStatusUpdate
                        ? $leadContact->call_status_updated_at
                        : null,
                    'user_id' => $userId,
                    'history_id' => $history->id,
                    'call_at' => $history->call_at?->format('Y-m-d H:i:s'),
                    'call_duration' => $history->call_duration,
                    'remarks' => $history->remarks,
                    'recording' => $history->recording,
                    'recording_url' => $this->recordingUrl($history->recording),
                    'client_call_id' => $clientCallId,
                    'has_lead' => $history->lead_id !== null,
                ],
            ], 200);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            if ($recordingPath) {
                Storage::disk('public')->delete($recordingPath);
            }

            $existing = $clientCallId
                ? CallHistory::where('user_id', $userId)
                    ->where('client_call_id', $clientCallId)
                    ->first()
                : null;

            if ($existing && $existing->mobile === $mobile) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Call already saved.',
                    'data' => $existing,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Call ID conflict.',
            ], 409);
        } catch (\Throwable $e) {
            if ($recordingPath) {
                Storage::disk('public')->delete($recordingPath);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save call history.',
            ], 500);
        }
    }


    private function recordingUrl(?string $path): ?string
    {
        return $path
            ? asset('storage/' . ltrim($path, '/'))
            : null;
    }


    public function checkCallStatus(Request $request, $id)
    {
        $contact = \App\Models\Lead::query()
            ->where('id', $id)
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found',
                'is_pending' => false,
            ], 404);
        }

        // Only pending status returns true
        $isPending = $contact->call_status === 'pending';

        return response()->json([
            'success' => true,
            'contact_id' => $contact->id,
            'call_status' => $contact->call_status,
            'is_pending' => $isPending,
        ], 200);
    }


}
