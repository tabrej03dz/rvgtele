<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Company extends Model { protected $fillable=['name','code','email','phone','address','is_active']; protected function casts(): array{return ['is_active'=>'boolean'];} public function branches(){return $this->hasMany(Branch::class);} public function users(){return $this->hasMany(User::class);} public function leads(){return $this->hasMany(Lead::class);} }
