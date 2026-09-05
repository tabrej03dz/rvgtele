<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CallLog extends Model
{
    protected $guarded = ['id'];
    protected function casts()
    {
        return [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
            'next_followup_at' => 'datetime',
            'duration_seconds' => 'integer',
            'sim_slot' => 'integer',
            'subscription_id' => 'integer',
        ];
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function disposition()
    {
        return $this->belongsTo(CallDisposition::class, 'call_disposition_id');
    }




       protected $appends = [
        'recording_url',
    ];






    public function getRecordingUrlAttribute(): ?string
    {
        if (!$this->recording_path) {
            return null;
        }

        if (
            str_starts_with($this->recording_path, 'http://') ||
            str_starts_with($this->recording_path, 'https://')
        ) {
            return $this->recording_path;
        }

        return Storage::disk('public')->url($this->recording_path);
    }
}
