<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function all(Request $request)
    {
        $notifications = $request->user()->notifications;
        return response()->json([
            'notifications' => $notifications
        ]);
    }
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
        ->notifications()
        ->where('id',$id)
        ->first();
        if (!$notification) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $notification->markAsRead();
        return response()->json(['message' => 'Marked as read']);
    }
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read']);
    }
    public function unread(Request $request)
    {
        $unread = $request->user()->unreadNotifications;
        return response()->json(['unread_notifications' => $unread]);
    }
    public function deleteOne(Request $request, $id){
        $deleted = $request->user()
        ->notifications()
        ->where('id', $id)
        ->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['message' => 'Deleted']);
    }
    public function deleteAll(Request $request){
        $request->user()->notifications()->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
