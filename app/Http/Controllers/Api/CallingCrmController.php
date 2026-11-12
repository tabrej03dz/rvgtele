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

    public function index(Request $request)
    {
        $userId = $request->user();
        $category = $request->query('category');

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category query parameter is required.',
            ], 400);
        }

        $contacts = Lead::where('category', $category)->where('assigned_to', $userId->id)
            ->where('call_status', 'pending')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    public function callStatus(Request $request, Lead $leadContact)
    {
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

            // 1. Recording upload
            if ($request->hasFile('recording')) {
                $recordingPath = $request->file('recording')
                    ->store('call_recordings', 'public');

                if (!$recordingPath) {
                    throw new \RuntimeException('Recording upload failed.');
                }
            }

            // 2. Update contact and save call history
            $history = DB::transaction(function () use (
                $leadContact,
                $validated,
                $now,
                $recordingPath,
                $callDuration
            ) {

                $leadContact->update([
                    'call_status' => $validated['call_status'],
                    'call_status_updated_at' => $now,
                ]);

                return CallHistory::create([
                    'lead_id' => $leadContact->id,
                    'call_status' => $validated['call_status'],
                    'call_at' => $now,
                    'recording' => $recordingPath,
                    'remarks' => $validated['remarks'] ?? null,
                    'call_duration' => $callDuration,
                ]);

            });

            $leadContact->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Call status updated successfully.',
                'data' => [
                    'id' => $leadContact->id,
                    'call_status' => $leadContact->call_status,
                    'call_status_updated_at' =>
                        $leadContact->call_status_updated_at,
                    'history_id' => $history->id,
                    'call_duration' => $history->call_duration,
                    'recording' => $recordingPath,
                    'recording_url' => $recordingPath
                        ? Storage::disk('public')->url($recordingPath)
                        : null,
                ],
            ], 200);

        } catch (\Throwable $e) {

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
                    'contact_id' => $leadContact->id,
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
}
