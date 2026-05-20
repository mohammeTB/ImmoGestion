<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appartement extends Model
{
    protected $fillable = [
        'proprietaire_id',
        'title',
        'description',
        'city',
        'country',
        'address',
        'price',
        'capacity',
        'type',
        'wifi',
        'piscine',
        'parking',
        'climatisation',
        'animals',
    ];
    protected $casts = [
        'wifi' => 'boolean',
        'piscine' => 'boolean',
        'parking' => 'boolean',
        'climatisation' => 'boolean',
        'animals' => 'boolean',
    ];
    public function proprietaire(){
        return $this->belongsTo(User::class,'proprietaire_id');
    }
    public function images(){
        return $this->hasMany(ImageAppartement::class,'appartement_id');
    } 
    public function reservations(){
        return $this->hasMany(Reservation::class);
    }
    public function locataire(){
        return $this->belongsTo(User::class, 'locataire_id');
    }
    public function notes(){
        return $this->hasMany(Note::class);
    }
    public function favoris()
    {
        return $this->hasMany(Favori::class);
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
