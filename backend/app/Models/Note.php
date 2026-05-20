<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'reservation_id',
        'appartement_id',
        'locataire_id',
        'rating',
        'comment',
    ];
    public function reservation(){
        return $this->belongsTo(Reservation::class);
    }
    public function appartement()
    {
        return $this->belongsTo(Appartement::class);
    }
    public function locataire(){
        return $this->belongsTo(User::class, 'locataire_id');
    }
}
