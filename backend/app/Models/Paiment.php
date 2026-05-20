<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiment extends Model
{
    protected $fillable = [
        'reservation_id',
        'transaction_id',
        'price',
        'type',
        'status',
        'facture_url',
        'paid_at',
    ];
    public function reservation(){
        return $this->belongsTo(Reservation::class);
    }
}
