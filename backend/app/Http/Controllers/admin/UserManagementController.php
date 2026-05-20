<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
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
            if($user->role === 'locataire'){
                $activeReservations = $user->reservations()
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();
                if($activeReservations){
                    return response()->json([
                        'message'=>'Compte locataire avec réservations actives'
                    ], 422);
                }
            }
            if($user->role === 'proprietaire'){
                $activeReservations = Reservation::whereIn('status', ['pending', 'accepted'])
                ->whereHas('appartement', fn($q) =>
                    $q->where('proprietaire_id', $user->id)
                )
                ->exists();
                $activePayments = Reservation::where('payment_status', 'pending')
                ->where('status', 'completed')
                ->whereHas('appartement', fn($q) =>
                    $q->where('proprietaire_id', $user->id)
                )
                ->exists();
                if($activeReservations || $activePayments){
                    return response()->json([
                        'message'=>'Impossible de suspendre ce compte'
                    ], 422);
                }
            }
        }
        $user->update($validated);
        return response()->json([
            'message' => "Le compte de {$user->name} est mis à jour",
            'status' => $user->status
        ]);
    }
}
