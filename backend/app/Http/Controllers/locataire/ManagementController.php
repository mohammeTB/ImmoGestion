<?php

namespace App\Http\Controllers\locataire;

use App\Http\Controllers\Controller;
use App\Models\Appartement;
use App\Models\Reservation;
use App\Notifications\ProprietaireNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManagementController extends Controller
{
    public function reserverAppartement(Request $request, string $id)
    {
        $locataire = $request->user();
        $appartement = Appartement::where('status', 'active')->findOrFail($id);
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after:start_date',
            'nb_people'  => 'required|integer|min:1',
            'reference'  => 'required|string|max:50|unique:reservations,reference',
        ]);
        $hasOverlap = Reservation::where('appartement_id', $appartement->id)
            ->whereIn('status', ['pending', 'accepted']) 
            ->where('start_date', '<', $validated['end_date']) 
            ->where('end_date', '>', $validated['start_date']) 
            ->exists();
        if($hasOverlap){
            return response()->json(['message' => 'Cet appartement est déjà réservé pour ces dates.'], 422);
        }
        if($validated['nb_people'] > $appartement->capacity){
            return response()->json(['message' => 'Le nombre de personnes dépasse la capacité de l\'appartement.'], 422);
        }
        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);
        $days = $start->diffInDays($end);
        if($days == 0) $days = 1;
        $totalPrice = $appartement->price * $days;
        $platformFee = ($totalPrice * 10) / 100; 
        $proprietaireAmount = $totalPrice - $platformFee;
        do {
            $reference = 'RES-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Reservation::where('reference', $reference)->exists());
        $reservationData = [
            'appartement_id'      => $appartement->id,
            'locataire_id'        => $locataire->id,
            'start_date'          => $validated['start_date'],
            'end_date'            => $validated['end_date'],
            'nb_people'           => $validated['nb_people'],
            'reference'           => $reference,
            'total_price'         => $totalPrice,
            'platform_fee'        => $platformFee,
            'proprietaire_amount' => $proprietaireAmount,
        ];
        $reservation = Reservation::create($reservationData);
        $proprietaire = $appartement->proprietaire;
        if ($proprietaire) {
            $proprietaire->notify(new ProprietaireNotification('new_reservation_request', $locataire->name));
        }
        return response()->json([
            'message' => 'Votre demande de réservation a été envoyée au propriétaire.',
            'reservation' => $reservation
        ], 201);
    }
    public function searchAppartements(Request $request)
    {
        $validated = $request->validate([
            'start_date'    => 'nullable|date|after_or_equal:today',
            'end_date'      => 'nullable|date|after:start_date',
            'city'          => 'nullable|string|max:100',
            'type'          => 'nullable|string|in:appartement,villa,studio,maison,chambre',
            'capacity'      => 'nullable|integer|min:1',
            'wifi'          => 'nullable|boolean',
            'piscine'       => 'nullable|boolean',
            'parking'       => 'nullable|boolean',
            'climatisation' => 'nullable|boolean',
            'animals'       => 'nullable|boolean',
            'price_min'     => 'nullable|numeric|min:0',
            'price_max'     => 'nullable|numeric|gte:price_min',
        ]);
        $query = Appartement::with('images')->where('status','active');
        if($request->filled('city')){
            $query->where('city', 'like', "%" . $validated['city'] . "%");
        }
        if($request->filled('type')){
            $query->where('type', $validated['type']);
        }
        if($request->filled('capacity')){
            $query->where('capacity', '>=',$validated['capacity']);
        }
        if($request->has('wifi') && $request->boolean('wifi')){
            $query->where('wifi', true);
        }
        if($request->has('piscine') && $request->boolean('piscine')){
            $query->where('piscine', true);
        }
        if($request->has('parking') && $request->boolean('parking')){
            $query->where('parking', true);
        }
        if($request->has('climatisation') && $request->boolean('climatisation')){
            $query->where('climatisation', true);
        }
        if($request->has('animals') && $request->boolean('animals')){
            $query->where('animals', true);
        }
        if($request->filled('price_min')){
            $query->where('price', '>=', $validated['price_min']);
        }
        if($request->filled('price_max')){
            $query->where('price', '<=', $validated['price_max']);
        }
        if($request->filled('start_date') && $request->filled('end_date')){
            $busyAppartementsQuery = Reservation::select('appartement_id')
                ->whereIn('status',['accepted','pending'])
                ->where('start_date', '<', $validated['end_date'])
                ->where('end_date', '>', $validated['start_date']);
            $query->whereNotIn('id', $busyAppartementsQuery);
        }
        $appartements = $query->latest()->paginate(10);
        return response()->json([
            'appartements' => $appartements
        ], 200);
    }
}
