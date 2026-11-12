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

    // public function index(Request $request)
    // {
    //     $userId = $request->user();
    //     $category = $request->query('category');

    //     if (!$category) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Category query parameter is required.',
    //         ], 400);
    //     }

    //     $contacts = Lead::where('category', $category)->where('assigned_to', $userId->id)
    //         ->where('call_status', 'pending')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $contacts,
    //     ]);
    // }


    public function index(Request $request)
    {
        $user = $request->user();
        $category = $request->query('category');

        $contacts = Lead::query()
            ->where('assigned_to', $user->id)
            ->where('call_status', 'pending')
            ->when($category !== null && $category !== '', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ], 200);
    }


    
public function callStatus(Request $request, Lead $leadContact)
{
    $userId = $request->user()->id;


    // Only assigned user can update this lead
    if ((int) $leadContact->assigned_to !== (int) $userId) {
        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to update this lead.',
        ], 403);
    }

    // Validate request
    $validated = $request->validate([
        'call_status' => 'required|string|max:255',
        'recording' => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm,mp4,3gp,amr|max:20480',
        'remarks' => 'nullable|string',
        'call_duration' => 'nullable|integer|min:0',
    ]);

    $now = Carbon::now();
    $recordingPath = null;

    $callDuration = $validated['call_duration'] ?? null;

    try {

        // 1. Upload recording (optional)
        if ($request->hasFile('recording')) {

            $recordingPath = $request->file('recording')
                ->store('call_recordings', 'public');

            if (!$recordingPath) {
                throw new \RuntimeException(
                    'Recording upload failed.'
                );
            }
        }

        // 2. Update lead and create call history
        $history = DB::transaction(function () use (
            $leadContact,
            $validated,
            $now,
            $recordingPath,
            $callDuration,
            $userId
        ) {

            // Update lead
            $leadContact->update([
                'call_status' => $validated['call_status'],
            ]);

            // Save call history
            return CallHistory::create([
                'lead_id' => $leadContact->id,
                'user_id' => $userId,
                'call_status' => $validated['call_status'],
                'call_at' => $now,
                'recording' => $recordingPath,
                'remarks' => $validated['remarks'] ?? null,
                'call_duration' => $callDuration,
            ]);

        });

        // 3. Refresh lead data
        $leadContact->refresh();

        // 4. Recording public URL
        $recordingUrl = $recordingPath
            ? Storage::disk('public')->url($recordingPath)
            : null;

        // 5. Success response
        return response()->json([
            'success' => true,
            'message' => 'Call status updated successfully.',
            'data' => [
                'id' => $leadContact->id,
                'user_id' => $userId,
                'call_status' => $leadContact->call_status,
                'call_status_updated_at' => $leadContact->call_status_updated_at,
                'history_id' => $history->id,
                'call_duration' => $history->call_duration,
                'remarks' => $history->remarks,
                'recording' => $recordingPath,
                'recording_url' => $recordingUrl,
            ],
        ], 200);

    } catch (\Throwable $e) {

        // Delete uploaded recording if database operation fails
        if ($recordingPath) {
            try {
                Storage::disk('public')->delete($recordingPath);
            } catch (\Throwable $cleanupError) {
                report($cleanupError);
            }
        }

        // Log actual error
        \Illuminate\Support\Facades\Log::error(
            'CallStatus API Failed',
            [
                'lead_id' => $leadContact->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' => 'Failed to update call status or save recording.',
        ], 500);
    }
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
