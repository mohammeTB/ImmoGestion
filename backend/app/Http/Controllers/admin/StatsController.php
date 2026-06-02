<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Appartement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function locataires()
    {
        return response()->json([
            'locataires'=> User::where('role','locataire')->paginate(10)
        ]);
    }
    public function proprietaires()
    {
        return response()->json([
            'proprietaires'=> User::where('role','proprietaire')->paginate(10)
        ]);
    }
    public function pendingAppartements()
    {
        $appartements = Appartement::with(['proprietaire', 'images'])
        ->where('status', 'pending')
        ->paginate(10);
        return response()->json(['pending_appartements' => $appartements]);
    }
    public function showPendingAppartement(string $id)
    {
        $appartement = Appartement::with(['proprietaire', 'images'])
        ->where('status', 'pending')
        ->findOrFail($id);
        return response()->json(['pending_appartement' => $appartement]);
    }
    public function allAppartements()
    {
        $appartements = Appartement::with('proprietaire')->latest()->paginate(10);
        return response()->json([
            'appartements' => $appartements
        ], 200);
    }
    public function allReservations()
    {
        $reservations = Reservation::with(['locataire','appartement.proprietaire'])->latest()->paginate(10);
        return response()->json([
            'reservations' => $reservations
        ], 200);
    }
    public function statsAppartements()
    {
        $currentDate = now()->toDateString();
        $statsStatus = Appartement::selectRaw("
            count(*) as total,
            count(case when status = 'active' then 1 end) as active,
            count(case when status = 'suspended' then 1 end) as suspended,
            count(case when status = 'rejected' then 1 end) as rejected
        ")->first();
        $statsReservations = Reservation::selectRaw("
            count(*) as total_res,
            count(case when status = 'pending' then 1 end) as pending_res,
            count(case when status = 'accepted' then 1 end) as accepted_res,
            count(case when status = 'completed' then 1 end) as completed_res,
            count(case when status = 'canceled' then 1 end) as canceled_res
        ")->first();
        $topLogements = Appartement::withCount(['reservations' => function ($q) {
                $q->whereIn('status', ['accepted', 'completed']);
            }])
            ->orderBy('reservations_count', 'desc')
            ->take(5)
            ->get();
        $occupiedAppartementsCount = Appartement::whereHas('reservations', function ($q) use ($currentDate) {
                $q->where('status', 'accepted')
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate);
            })->count();
        $financialStats = Reservation::join('paiments', 'reservations.id', '=', 'paiments.reservation_id')
            ->selectRaw("
                SUM(case when paiments.status = 'paid' then reservations.total_price else 0 end) as total_revenue,
                SUM(case when paiments.status = 'paid' then reservations.platform_fee else 0 end) as platform_earnings,
                SUM(case when paiments.status = 'pending' then paiments.price else 0 end) as pending_revenue
            ")->first();
        $byCity = Appartement::select('ville', DB::raw('count(*) as count'))
            ->groupBy('ville')
            ->get();
        $byType = Appartement::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();
        return response()->json([
            'chiffres_globaux' => [
                'total' => (int) ($statsStatus->total ?? 0),
                'active' => (int) ($statsStatus->active ?? 0),
                'suspended' => (int) ($statsStatus->suspended ?? 0),
                'rejected' => (int) ($statsStatus->rejected ?? 0),
            ],
            'occupation' => [
                'total_logements' => (int) ($statsStatus->total ?? 0),
                'occupied_now' => $occupiedAppartementsCount,
                'free_now' => ((int) ($statsStatus->total ?? 0)) - $occupiedAppartementsCount,
            ],
            'top_logements' => $topLogements,
            'finances' => [
                'total_volume_transactions' => round((float) ($financialStats->total_revenue ?? 0), 2),
                'platform_net_earnings' => round((float) ($financialStats->platform_earnings ?? 0), 2), // أرباح الأدمن الصافية
                'pending_volume' => round((float) ($financialStats->pending_revenue ?? 0), 2),
            ],
            'repartition' => [
                'by_city' => $byCity,
                'by_type' => $byType
            ],
            'chiffres_reservations' => [
                'total' => (int) ($statsReservations->total_res ?? 0),
                'pending' => (int) ($statsReservations->pending_res ?? 0),
                'accepted' => (int) ($statsReservations->accepted_res ?? 0),
                'completed' => (int) ($statsReservations->completed_res ?? 0),
                'canceled' => (int) ($statsReservations->canceled_res ?? 0),
            ],
        ], 200);
    }
}
