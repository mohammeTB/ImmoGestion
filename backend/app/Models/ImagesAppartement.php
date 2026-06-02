<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagesAppartement extends Model
{
    protected $fillable = [
        'image_url',
        'appartement_id',
    ];
    public function appartement(){
        return $this->belongsTo(Appartement::class,'appartement_id');
    }
}
