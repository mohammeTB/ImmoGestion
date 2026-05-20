<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favori extends Model
{
    protected $fillable = [
        'locataire_id',
        'appartement_id',
    ];
    public function locataire()
    {
        return $this->belongsTo(User::class, 'locataire_id');
    }
    public function appartement()
    {
        return $this->belongsTo(Appartement::class);
    }
}
