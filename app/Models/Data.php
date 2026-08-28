<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Data extends Model
{
    use SoftDeletes;

    protected $table = 'data';

    protected $fillable = [
        'company_id',

        'name',
        'company_name',

        'mobile',
        'alternate_mobile',
        'whatsapp_number',
        'email',

        'category',
        'lead_source',
        'campaign',

        'address',
        'city',
        'district',
        'state',
        'pincode',

        'industry',
        'required_product',
        'preferred_language',

        'estimated_budget',

        'remarks',

        'converted',
        'lead_id',
        'converted_at',

        'raw_data',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'converted_at' => 'datetime',
        'estimated_budget' => 'decimal:2',
        'raw_data' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}