<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\NewFollowerNotification;

class FollowController extends Controller
{
    public function follow($id){
        $user = User::findOrFail($id);
        auth()->user()->following()->attach($user->id);
        $user->notify(new NewFollowerNotification(auth()->user()));
        return redirect()->back();
    }
    public function unfollow($id){
        $user = User::findOrFail($id);
        auth()->user()->following()->detach($user->id);
        return redirect()->back();
    }
}
