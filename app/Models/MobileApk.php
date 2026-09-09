<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileApk extends Model
{
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_active' => 'boolean',
        'images' => 'array',
    ];
}