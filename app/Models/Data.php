<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Data extends Model
{
    use SoftDeletes;

    protected $table = 'data';

    protected $guarded = [
        'id',
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

    /*
    |--------------------------------------------------------------------------
    | Category Relation
    |--------------------------------------------------------------------------
    |
    | Relation ka naam "category" nahi rakhenge kyunki data table me
    | already "category" naam ka string column hai.
    |
    */

    public function categoryInfo()
    {
        return $this->belongsTo(
            Category::class,
            'category_id'
        );
    }
}