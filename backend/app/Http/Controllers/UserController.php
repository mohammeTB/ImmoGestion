<?php

namespace App\Http\Controllers;

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
            'photo' => 'nullable|image|mimes:png,jpg,jpeg',
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
}
