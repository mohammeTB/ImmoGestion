<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|min:2',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|min:6|max:14|unique:users,phone',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'password' => 'required|min:4',
            'role' => 'required|in:locataire,proprietaire',
        ]);
        if ($validated['role'] === 'admin') {
            return response()->json(['message' => 'Informations invalides'], 403);
        }
        if($request->hasFile('photo')){
            $path = $request->file('photo')->store('users','public');
            $validated['photo'] = $path;
        }
        $user = User::create($validated);
        if ($validated['role'] === 'proprietaire') {
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                $admin->notify(new AdminNotification($user->name, 'register'));
            }
        }
        return response()->json([
            'user' => $user,
            'message' => "Inscription avec succès",
        ]);
    }
    public function profile(Request $request){
        $user = $request->user();
        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'photo' => $user->photo,
            'role' => $user->role,
            'status' => $user->status,
        ];
        if($user->role === 'proprietaire'){ 
            $profile['nb_appartements'] = $user->appartements()->count();
            $profile['revenus_total'] = 
                Reservation::where('status', 'completed')
                ->whereHas('appartement', fn($q) => 
                    $q->where('proprietaire_id', $user->id)
                )
                ->sum('proprietaire_amount');
        }
        if($user->role === 'locataire'){
            $profile['nb_reservations'] = $user->reservations()->count();
        }
        return response()->json(['profile' => $profile]);
    }
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:100|min:2',
            'email' => 'required|email|unique:users,email,'.$user->email,
            'phone' => 'nullable|string|min:6|max:14|unique:users,phone',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'password' => 'required|min:4',
        ]);
        if($user->role === 'admin'){
            return ;
        }
        if($request->hasFile('photo')){
            $path = $request->file('photo')->store('users','public');
            $validated['photo'] = $path;
        }
    }
}
