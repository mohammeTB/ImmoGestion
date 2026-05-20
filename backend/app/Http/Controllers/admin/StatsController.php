<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function locataires(Request $request){
        return response()->json([
            'locataires'=> User::where('role','locataire')->paginate(10)
        ]);
    }
    public function proprietaires(Request $request){
        return response()->json([
            'proprietaires'=> User::where('role','proprietaire')->paginate(10)
        ]);
    }
}
