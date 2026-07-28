<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LeadAssignment extends Model { protected $guarded=[]; protected function casts():array{return ['assigned_at'=>'datetime'];} }
