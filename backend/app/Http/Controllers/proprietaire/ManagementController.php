<?php

namespace App\Http\Controllers\proprietaire;

use App\Http\Controllers\Controller;
use App\Models\Appartement;
use App\Models\ImagesAppartement;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\LocataireNotification;
use Illuminate\Http\Request;

class ManagementController extends Controller
{
    public function request_to_addAppartement(Request $request)
    {
        $proprietaire = $request->user();
        $validated = $request->validate([
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'title'         => 'required|string|max:150',
            'description'   => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'address'       => 'nullable|string|max:255',
            'price'         => 'required|numeric|min:0',
            'capacity'      => 'required|integer|min:1',
            'type'          => 'required|in:appartement,villa,studio,maison,chambre',
            'wifi'          => 'boolean',
            'piscine'       => 'boolean',
            'parking'       => 'boolean',
            'climatisation' => 'boolean',
            'animals'       => 'boolean',
            'images'       => 'required|array|min:1',
            'images.*'       => 'image|mimes:png,jpeg,jpg,webp|max:2048',
        ]);
        $appartementData = collect($validated)->except('images')->toArray();
        $appartementData['proprietaire_id'] = $proprietaire->id;
        $appartement = Appartement::create($appartementData);
        if($request->hasFile('images')){
            foreach($request->file('images') as $img){
                $path = $img->store('appartements','public');
                ImagesAppartement::create([
                    'appartement_id'=>$appartement->id,
                    'image_url'=>$path
                ]);
            }
        }
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new AdminNotification($proprietaire->name, 'add_appartement'));
        }
        return response()->json([
            'message' => 'Appartement enregistrée avec succès. Elle sera visible dès qu\'un administrateur aura validé votre annonce.',
            'appartement_status' => $appartement->status
        ], 201);
    }
    public function request_to_modifyAppartement(Request $request, string $id)
    {
        $proprietaire = $request->user();
        $appartement = Appartement::with(['images'])->findOrFail($id);
        if ($appartement->proprietaire_id !== $proprietaire->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        if ($appartement->status === 'suspended') {
            return response()->json(['message' => "Cet appartement n'est pas disponible à le modifier."], 400);
        }
        $currentDate = now()->toDateString();
        $hasActiveReservations = Reservation::where('appartement_id', $id)
            ->where('end_date', '>=', $currentDate)
            ->where(function($q) {
                $q->whereIn('status', ['pending', 'accepted'])
                ->orWhereHas('paiment', function($sub) {
                    $sub->where('status', 'pending');
                });
            })
            ->exists();

        if ($hasActiveReservations) {
            return response()->json(['message' => 'Impossible de modifier: Vous avez des réservations ou paiements en cours ou à venir.'], 400);
        }
        $validated = $request->validate([
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'title'         => 'required|string|max:150',
            'description'   => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'address'       => 'nullable|string|max:255',
            'price'         => 'required|numeric|min:0',
            'capacity'      => 'required|integer|min:1',
            'type'          => 'required|in:appartement,villa,studio,maison,chambre',
            'wifi'          => 'boolean',
            'piscine'       => 'boolean',
            'parking'       => 'boolean',
            'climatisation' => 'boolean',
            'animals'       => 'boolean',
            'images'        => 'nullable|array|min:1', // رديناها nullable إذا مبغاش يبدل التصاور
            'images.*'      => 'image|mimes:png,jpeg,jpg,webp|max:2048',
        ]);
        $appartementData = collect($validated)->except('images')->toArray();
        $appartement->update($appartementData);
        if($request->hasFile('images')){
            $appartement->images()->delete();
            foreach($request->file('images') as $img){
                $path = $img->store('appartements','public');
                $appartement->images()->create([
                    'image_url' => $path
                ]);
            }
        }
        $appartement->update(['status'=>'pending']);
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new AdminNotification($proprietaire->name, 'modify_appartement'));
        }
        return response()->json([
            'message' => 'Appartement modifiée avec succès. Elle sera visible dès qu\'un administrateur aura validé vos modifications.',
            'appartement_status' => $appartement->status
        ], 200);
    }
    public function deleteAppartement(Request $request, string $id)
    {
        $proprietaire = $request->user();
        $appartement = Appartement::with(['images','reservations'])->findOrFail($id);
        if ($appartement->proprietaire_id !== $proprietaire->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        if ($appartement->status === 'suspended') {
            return response()->json(['message' => 'Cet appartement est déjà suspendu par l\'admin.'], 400);
        }
        $currentDate = now()->toDateString();
        $hasActiveReservations = Reservation::where('appartement_id', $id)
            ->where('end_date', '>=', $currentDate)
            ->where(function($q) {
                $q->whereIn('status', ['pending', 'accepted'])
                ->orWhereHas('paiment', function($sub) {
                    $sub->where('status', 'pending');
                });
            })
            ->exists();
        if ($hasActiveReservations) {
            return response()->json(['message' => 'Impossible de supprimer: Vous avez des réservations ou paiements actifs en cours.'], 400);
        }
        $appartement->delete();
        return response()->json([
            'message' => 'Appartement supprimée avec succès (archivée).',
        ], 200);
    }
    public function changeStatusAppartement(Request $request, string $id)
    {
        $appartement = Appartement::findOrFail($id);
        $proprietaire = $request->user();
        if ($appartement->proprietaire_id !== $proprietaire->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        $validated = $request->validate([
            'status' => 'required|in:inactive,active'
        ]);
        if ($appartement->proprietaire_id !== $proprietaire->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        if ($appartement->status === 'suspended') {
            return response()->json(['message' => 'Cet appartement est déjà suspendu par l\'admin.'], 400);
        }
        $currentDate = now()->toDateString();
        $hasActiveResOrPendingPayments = Reservation::whereHas('appartement', function($q) use ($id){
            $q->where('id', $id);
        })
        ->where('end_date', '>=', $currentDate)
        ->where(function($q) {
            $q->whereIn('status', ['pending', 'accepted']) 
              ->orWhereHas('paiment', function($sub) {    
                  $sub->where('status', 'pending');
              });
        })
        ->exists();
        if ($hasActiveResOrPendingPayments) {
            return response()->json([
                'message' => 'Impossible de modifier le statut : Vous avez des réservations acceptées en cours ou à venir.'
            ], 400);
        }
        $appartement->update($validated);
        return response()->json([
            'message' => $validated['status'] === 'inactive' 
                ? 'Votre appartement est désormais masqué (Hors-ligne).' 
                : 'Votre appartement est de nouveau en ligne.',
            'status' => $appartement->status
        ], 200);
    }
    public function acceptReservation(Request $request, string $id)
    {
        $proprietaire = $request->user();
        $reservation = Reservation::with('appartement')->findOrFail($id);
        if ($reservation->appartement->proprietaire_id !== $proprietaire->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        if ($reservation->status !== 'pending') {
            return response()->json(['message' => 'Ce reservation n\'est plus en attente.'], 400);
        }
        $reservation->update([
            'status' => 'accepted',
            'confirmed_at' => now()
        ]);
        Reservation::where('appartement_id', $reservation->appartement_id)
            ->where('id', '!=', $reservation->id)
            ->where('status', 'pending')
            ->where('start_date', '<', $reservation->end_date)
            ->where('end_date', '>', $reservation->start_date)
        ->update(['status' => 'failed']);
        $locataire = $reservation->locataire;
        if($locataire){
            $locataire->notify(new LocataireNotification('accept_reservation',$proprietaire->name));
        }
        return response()->json(['message' => 'Réservation acceptée avec succès.'], 200);
    }
    public function refuseReservation(Request $request, string $id)
    {
        $proprietaire = $request->user();
        $reservation = Reservation::with('appartement')->findOrFail($id);
        if ($reservation->appartement->proprietaire_id !== $proprietaire->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }
        $reservation->update(['status'=>'failed']);
        $locataire = $reservation->locataire;
        if($locataire){
            $locataire->notify(new LocataireNotification('refuse_reservation',$proprietaire->name));
        }
        return response()->json(['message' => 'Réservation refusée.'], 200);
    }
}
