<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    // 1. تسجيل الدخول (Login)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // التأكد من كلمة المرور وأن المستخدم أدمن
        if (! $user || ! Hash::check($request->password, $user->password) || $user->role !== 'admin') {
            return response()->json(['message' => 'بيانات الاعتماد غير صحيحة أو لست أدمن'], 401);
        }

        // إنشاء الـ Token (مهمة Lahcen الأساسية في الصورة image_92a763.png)
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'admin' => $user,
            'token' => $token
        ]);
    }

    // 2. تسجيل الخروج (Logout)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

        // 1. عرض جميع المستخدمين (باستثناء الأدمن نفسه إذا أردت)
    public function index()
    {
        $users = User::where('role', '!=', 'admin')->get();
        return response()->json($users);
    }

    // 2. دالة الحظر وإعادة التفعيل (Toggle Status)
public function toggleStatus(User $user)
{
    // تغيير الحالة: إذا كان 1 يصبح 0 والعكس
    $user->is_active = !$user->is_active;
    $user->save();

    $action = $user->is_active ? 'réactivé' : 'bloqué'; // لغة فرنسية كما في إعدادات مشروعك

    return response()->json([
        'message' => "Le compte de {$user->name} a été $action avec succès.",
        'user' => $user
    ]);
}

}
