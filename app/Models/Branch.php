<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model { protected $fillable=['company_id','name','code','phone','address','is_active']; public function company(){return $this->belongsTo(Company::class);} public function users(){return $this->hasMany(User::class);} }
