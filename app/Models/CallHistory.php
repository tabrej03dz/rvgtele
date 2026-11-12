<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallHistory extends Model
{
    protected $table = 'call_histories';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'call_at' => 'datetime',
        'call_duration' => 'integer',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
