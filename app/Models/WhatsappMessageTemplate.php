<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessageTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
