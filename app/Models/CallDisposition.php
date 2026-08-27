<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CallDisposition extends Model 
{
     
    protected $guarded = ['id'];
    protected function casts():array
    {
        return ['requires_follow_up'=>'boolean','requires_remarks'=>'boolean','is_active'=>'boolean'];
    } 
}
