<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model { protected $guarded=[]; protected function casts():array{return ['started_at'=>'datetime','ended_at'=>'datetime'];} public function lead(){return $this->belongsTo(Lead::class);} public function user(){return $this->belongsTo(User::class);} public function disposition(){return $this->belongsTo(CallDisposition::class,'call_disposition_id');} }
