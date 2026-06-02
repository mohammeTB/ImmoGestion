<?php

namespace App\Http\Controllers\proprietaire;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function allAppartements(Request $request)
    {
        $proprietaire = $request->user();
        $appartements = $proprietaire->appartements()->with('images')->latest()->paginate(10);
        return response()->json([
            'appartements' => $appartements
        ], 200);
    }
    public function getHistoriqueReservations(Request $request)
    {
        $proprietaire = $request->user();
        $historique = Reservation::whereHas('appartement', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })
            ->with(['locataire:id,name,email', 'appartement:id,title,price', 'paiment'])
            ->latest()
            ->paginate(10); 
        return response()->json([
            'historique' => $historique
        ], 200);
    }
    public function pendingReservations(Request $request)
    {
        $proprietaire = $request->user();
        $reservations = Reservation::whereHas('appartement', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })
            ->where('status', 'pending')
            ->with(['locataire:id,name,email', 'appartement:id,title,price', 'paiment'])
            ->latest()
            ->paginate(10);
        return response()->json(['reservations' => $reservations], 200);
    }
    public function statsProprietaire(Request $request)
    {
        $proprietaire = $request->user();
        $currentDate = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $allPaidReservations = Reservation::whereHas('appartement', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })
            ->whereIn('status', ['accepted', 'completed'])
            ->whereHas('paiment', function($q) {
                $q->where('status', 'paid');
            })
            ->get();
        $gainsTotaux = $allPaidReservations->sum('proprietaire_amount');
        $gainsCeMois = $allPaidReservations->where('start_date', '>=', $startOfMonth)->sum('proprietaire_amount');
        $gainsAVenir = Reservation::whereHas('appartement', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })
            ->where('status', 'accepted')
            ->where('start_date', '>', $currentDate)
            ->whereHas('paiment', function($q) {
                $q->where('status', 'paid');
            })
            ->sum('proprietaire_amount');
        $totalAppartements = $proprietaire->appartements()->count(); 
        $totalReservations = Reservation::whereHas('appartement', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })->count();
        $pendingRequests = Reservation::whereHas('appartement', function($q) use ($proprietaire) {
                $q->where('proprietaire_id', $proprietaire->id);
            })
            ->where('status', 'pending')
            ->count();
        return response()->json([
            'gains' => [
                'total' => $gainsTotaux,
                'ce_mois' => $gainsCeMois,
                'a_venir' => $gainsAVenir,
            ],
            'statistiques' => [
                'total_appartements' => $totalAppartements,
                'total_reservations' => $totalReservations,
                'demandes_en_attente' => $pendingRequests,
            ]
        ], 200);
    }
}
