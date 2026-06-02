<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Appartement;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ProprietaireNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementController extends Controller
{
    public function changeStatus(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Impossible de modifier ce compte admin'
            ], 403);
        }
        $validated = $request->validate([
            'status' => 'required|in:active,suspended',
        ]);
        if ($user->status === $validated['status']) {
            return response()->json([
                'message' => 'Aucun changement'
            ]);
        }
        if ($validated['status'] === 'suspended') {
            $currentDate = now()->toDateString();
            if($user->role === 'locataire'){
                $activeReservations = Reservation::where('locataire_id', $user->id)
                    ->where('end_date', '>=', $currentDate)
                    ->where(function($q) {
                        $q->whereIn('status', ['pending', 'accepted'])
                        ->orWhereHas('paiment', function($sub) {
                            $sub->where('status', 'pending');
                        });
                    })
                    ->exists();
                if($activeReservations){
                    return response()->json(['message'=>'Compte locataire avec réservations ou paiements actifs'], 422);
                }
            }
            if($user->role === 'proprietaire'){
                $hasActiveResOrPendingPayments = Reservation::whereHas('appartement', function($q) use ($user) {
                        $q->where('proprietaire_id', $user->id);
                    })
                    ->where('end_date', '>=', $currentDate)
                    ->where(function($q) {
                        $q->whereIn('status', ['pending', 'accepted'])
                        ->orWhereHas('paiment', function($sub) {
                            $sub->where('status', 'pending');
                        });
                    })
                    ->exists();
                if($hasActiveResOrPendingPayments){
                    return response()->json(['message'=>'Impossible de suspendre ce compte : réservations actives en cours.'], 422);
                }
            }
        }
        DB::transaction(function () use (&$user, $validated){ 
            $user->update($validated);
            if($validated['status'] === 'suspended' && 
                $user->role === 'proprietaire')
            {
                $user->appartements()->update(['status' => 'suspended']);            
            }
        });
        return response()->json([
            'message' => "Le compte de {$user->name} est mis à jour",
            'status' => $user->status
        ]);
    }
    public function deleteLocataire(string $id)
    {
        $locataire = User::where('role','locataire')->findOrFail($id);
        $currentDate = now()->toDateString();
        $hasActiveResOrPendingPayments = Reservation::where('locataire_id', $id)
            ->where('end_date', '>=', $currentDate)
            ->where(function($q) {
                $q->whereIn('status', ['pending', 'accepted'])
                ->orWhereHas('paiment', function($sub) {
                    $sub->where('status', 'pending');
                });
            })
            ->exists();

        if ($hasActiveResOrPendingPayments) {
            return response()->json(['message' => 'Impossible de supprimer un locataire avec des réservations ou paiements actifs.'], 409);
        }
        $locataire->delete();
        return response()->json(['message'=>'Locataire supprimé avec succès.']);
    }
    public function deleteProprietaire(string $id)
    {
        $proprietaire = User::where('role','proprietaire')->findOrFail($id);
        $currentDate = now()->toDateString();
        $hasActiveResOrPendingPayments = Reservation::whereHas('appartement', function($q) use ($id){
                $q->where('proprietaire_id', $id);
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
            return response()->json(['message' => 'Impossible de supprimer un propriétaire avec des réservations أو مدفوعات نشطة.'], 409);
        }
        $proprietaire->appartements()->delete(); 
        $proprietaire->delete();
        return response()->json(['message'=>'Propriétaire et ses appartements supprimés avec succès.']);
    }
    public function rejectAppartement(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason'=>'required|string'
        ]);
        $appartement = Appartement::with('proprietaire')->findOrFail($id);
        $proprietaire = $appartement->proprietaire;
        $appartement->update(['status'=>'rejected']);
        if($proprietaire){
            $proprietaire->notify(new ProprietaireNotification('reject_appartement',$proprietaire->name,$validated['reason']));
        }
        return response()->json([
            'message' => 'L\'appartement a été refusée. Le propriétaire recevra une notification.',
            'appartement_status' => $appartement->status
        ], 200);
    }
    public function approveAppartement(string $id)
    {
        $appartement = Appartement::with('proprietaire')->findOrFail($id);
        $proprietaire = $appartement->proprietaire;
        if ($proprietaire && $proprietaire->status === 'suspended') {
            return response()->json([
                'message' => 'Impossible d\'approuver l\'appartement : Le compte de ce propriétaire est suspendu.'
            ], 422);
        }
        $appartement->update(['status'=>'active']);
        $appartement->refresh();
        if($proprietaire){
            $proprietaire->notify(new ProprietaireNotification('approve_appartement',$proprietaire->name));
        }
        return response()->json([
            'message' => 'L\'appartement a été approuvée et publiée avec succès.',
            'appartement_status' => $appartement->status
        ], 200);
    }
    public function suspendAppartement(Request $request, string $id)
    {
        $reason = $request->validate([
            'reason' => 'required|string'
        ]);
        $appartement = Appartement::with('proprietaire')->findOrFail($id);
        $currentDate = now()->toDateString();
        $hasReservations = Reservation::where('appartement_id', $appartement->id)
            ->where('end_date', '>=', $currentDate)
            ->where(function($q) {
                $q->whereIn('status', ['pending', 'accepted'])
                ->orWhereHas('paiment', function($sub) {
                    $sub->where('status', 'pending');
                });
            })
            ->exists();
        if ($hasReservations) {
            return response()->json([
                'message' => 'Impossible de suspendre l\'appartement car il y a des réservations ou paiements actifs en cours.'
            ], 422);
        }
        $appartement->update(['status' => 'suspended']);
        $proprietaire = $appartement->proprietaire;
        if ($proprietaire) {
            $proprietaire->notify(new ProprietaireNotification('suspend_appartement', $proprietaire->name, $reason['reason']));
        }        
        return response()->json([
            'message' => 'L\'appartement a été suspendu avec succès et une notification a été envoyée au propriétaire.'
        ], 200);
    }
}
