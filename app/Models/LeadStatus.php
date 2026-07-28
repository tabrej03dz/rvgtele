<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model { protected $fillable=['company_id','name','slug','color','sort_order','is_converted','is_lost','is_active']; }
