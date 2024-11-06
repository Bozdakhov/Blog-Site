<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function unReadnotifications($username)
    {
        $user = User::where('username', $username)->firstOrFail();

        // Ensure the user is viewing their own notifications
        if (Auth::id() !== $user->id) {
            abort(403, 'Unauthorized action');
        }

        return view('users.profile', compact('user'));
    }

    public function readNotify($id)
    {
        $notification = Auth::user()->unreadNotifications->where('id', $id)->first();

        if (!$notification) {
            abort(404);
        }

        $notification->markAsRead();
        $type = $notification->data['type'] ?? null;

        if ($type === 'follow') {
            return redirect()->route('users.profile', $notification->data['username'])
                             ->with('success', 'Notification marked as read.');
        } elseif ($type === 'comment') {
            return redirect()->route('posts.show', $notification->data['post_id'])
                             ->with('success', 'Notification marked as read.');
        } else {
            return redirect()->back()->with('info', 'Notification type not recognized.');
        }
    }
}
