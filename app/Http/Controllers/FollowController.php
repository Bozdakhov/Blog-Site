<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewFollowerNotification;

class FollowController extends Controller
{
    public function follow($id)
    {
        $user = User::findOrFail($id);

        // Prevent users from following themselves
        if (Auth::id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot follow yourself.');
        }

        // Check if the user is already following the target user
        if (!auth()->user()->following()->where('followed_id', $user->id)->exists()) {
            auth()->user()->following()->attach($user->id);
            $user->notify(new NewFollowerNotification(auth()->user()));
            return redirect()->back()->with('success', 'You are now following ' . $user->name . '!');
        }

        return redirect()->back()->with('info', 'You are already following this user.');
    }

    public function unfollow($id)
    {
        $user = User::findOrFail($id);

        // Prevent users from unfollowing themselves
        if (Auth::id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot unfollow yourself.');
        }

        // Detach the following relationship if it exists
        if (auth()->user()->following()->where('followed_id', $user->id)->exists()) {
            auth()->user()->following()->detach($user->id);
            return redirect()->back()->with('success', 'You have unfollowed ' . $user->name . '.');
        }

        return redirect()->back()->with('info', 'You are not following this user.');
    }
}
