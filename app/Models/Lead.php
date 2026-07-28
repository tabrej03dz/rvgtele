<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'next_follow_up_at'    => 'datetime',
            'expected_closing_date' => 'date',
            'last_contact_at'      => 'datetime',
            'custom_data'          => 'array',
            'do_not_call'          => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function calls()
    {
        return $this->hasMany(CallLog::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function assignments()
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function pipelineStage()
    {
        return $this->belongsTo(
            PipelineStage::class,
            'pipeline_stage_id'
        );
    }

    public function assignedUser()
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    public function source()
    {
        return $this->belongsTo(
            LeadSource::class,
            'lead_source_id'
        );
    }

    public function status()
    {
        return $this->belongsTo(
            LeadStatus::class,
            'lead_status_id'
        );
    }

    public function team()
    {
        return $this->belongsTo(
            Team::class,
            'team_id'
        );
    }

    public function stage()
    {
        return $this->belongsTo(
            PipelineStage::class,
            'pipeline_stage_id'
        );
    }
}