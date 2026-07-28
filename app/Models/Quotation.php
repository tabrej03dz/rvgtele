<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model { protected $guarded=[]; protected function casts():array{return ['quotation_date'=>'date','valid_until'=>'date'];} public function customer(){return $this->belongsTo(Customer::class);} public function items(){return $this->hasMany(QuotationItem::class);} }
