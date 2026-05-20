<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);
        $user = User::where('email',$validated['email'])->first();
        if(!$user || !Hash::check($validated['password'], $user->password)){
            return response()->json(['message'=>'Informations invalides'], 401);
        }
        if ($user->status !== 'active') {
            return response()->json([
                'message' => "Votre compte est suspendu"
                ], 403);
        }
        $token = $user->createToken('token')->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token
        ], 200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion avec succès'
        ], 200);
    }
}