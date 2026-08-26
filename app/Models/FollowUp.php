<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',

          'reminder_notified_at' => 'datetime',
        ];
    }
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
