<?php

namespace App\Http\Controllers;

use App\Models\{CallLog, Lead, FollowUp};
use Illuminate\Http\Request;

class CallLogController extends Controller
{
    public function index(Request $r)
    {
        return view('calls.index', ['calls' => CallLog::with(['lead', 'user', 'disposition'])->where('company_id', $r->user()->company_id)->latest()->paginate(25)]);
    }
    public function store(Request $r, Lead $lead)
    {
        abort_unless($lead->company_id === $r->user()->company_id, 403);
        $d = $r->validate(['call_disposition_id' => 'required|exists:call_dispositions,id', 'duration_seconds' => 'nullable|integer|min:0', 'remarks' => 'required|string|max:3000', 'follow_up_at' => 'nullable|date|after:now']);
        $call = CallLog::create(['company_id' => $lead->company_id, 'lead_id' => $lead->id, 'user_id' => $r->user()->id, 'call_disposition_id' => $d['call_disposition_id'], 'started_at' => now()->subSeconds($d['duration_seconds'] ?? 0), 'ended_at' => now(), 'duration_seconds' => $d['duration_seconds'] ?? 0, 'remarks' => $d['remarks']]);
        $lead->update(['last_contact_at' => now()]);
        if (!empty($d['follow_up_at'])) {
            FollowUp::create(['company_id' => $lead->company_id, 'lead_id' => $lead->id, 'assigned_to' => $lead->assigned_to ?: $r->user()->id, 'created_by' => $r->user()->id, 'scheduled_at' => $d['follow_up_at'], 'notes' => $d['remarks']]);
            $lead->update(['next_follow_up_at' => $d['follow_up_at']]);
        }
        return back()->with('success', 'Call result saved.');
    }
}
