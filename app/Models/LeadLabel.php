<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LeadLabel extends Model
{
    protected $guarded = [
        'id',
    ];

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(
            Lead::class,
            'lead_label_lead'
        )->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
