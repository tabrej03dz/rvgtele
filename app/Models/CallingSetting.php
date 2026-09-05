<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallingSetting extends Model
{
    protected $guarded = ['id'];


    protected function casts(): array
    {
        return [
            'sim_slot' => 'integer',
            'subscription_id' => 'integer',
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
