<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';
    protected $guarded = [];

    protected $primaryKey = 'invoice_number';
    public $incrementing = false;//set false increment
    protected $keyType ='string';

    public function product(){
        return $this->hasMany(ProductTransaction::class, 'invoice_number', 'invoice_number');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
