<?php

namespace App\Http\Controllers\locataire;

use App\Http\Controllers\Controller;
use App\Models\Appartement;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function showAppartement(string $id)
    {
        $appartement = Appartement::with(['images','proprietaire'])
            ->where('status', 'active')
            ->findOrFail($id);
        $booked_dates = $appartement->reservations()
            ->where('status','accepted')
            ->where('end_date','>=',now()->toDateString())
            ->select('start_date','end_date')
            ->latest()
            ->get();
        return response()->json([
            'appartement' => $appartement,
            'booked_dates' => $booked_dates,
        ], 200);
    }
}
