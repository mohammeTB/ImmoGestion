<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'appartement_id',
        'locataire_id',
        'platform_fee',
        'proprietaire_amount',
        'start_date',
        'end_date',
        'nb_people',
        'status',
        'total_price',
        'payment_status',
        'reference',
        'confirmed_at',
    ];
    public function appartement()
    {
        return $this->belongsTo(Appartement::class);
    }
    public function locataire()
    {
        return $this->belongsTo(User::class, 'locataire_id');
    }
    public function paiment()
    {
        return $this->hasOne(Paiment::class);
    }
    public function notes(){
        return $this->hasMany(Note::class);
    }
}
