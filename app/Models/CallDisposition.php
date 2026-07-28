<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CallDisposition extends Model { protected $fillable=['company_id','name','type','requires_follow_up','requires_remarks','is_active']; protected function casts():array{return ['requires_follow_up'=>'boolean','requires_remarks'=>'boolean','is_active'=>'boolean'];} }
