<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Mail\SendSmsToMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function index(){
        if (auth()->check()) {
            $followingId = auth()->user()->following()->pluck('users.id');
            $posts = Post::whereIn('user_id', $followingId)->latest()->get();
        } else {
            $posts = Post::latest()->get();
        }
        return view('welcome', compact('posts'));
    }
    public function registerForm(){
        if(Auth::check()){
            abort(403);
        }
        return view("auth.register");
    }
    public function handleRegister(RegisterRequest $request){
        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->verification_token = uniqid();
        $user->password = bcrypt($request->password);
        $user->save();

        $uploadedAvatar = $this->uploadAvatar($request->file('avatar'));
        $user->image()->create([
            'image_path'=> $uploadedAvatar,
        ]);
        Mail::to($user->email)->send(new SendSmsToMail($user));
        return redirect()->route('loginForm');

    }
    public function loginForm(){
        if(!Auth::check()){

            return view("auth.login");
        }
        return redirect()->back();
    }

    public function handleLogin(LoginRequest $request){
        $user = User::where("email", $request->email)->first();
        if($user && Hash::check($request->password, $user->password)){
            if($user->email_verified_at != null){
                Auth::attempt(["email"=> $request->email,"password"=> $request->password]);
                return redirect()->route("home");
            }
        }
        abort(403);
    }
    public function profile(){
        $posts = Auth::user()->posts()->orderBy("created_at","desc")->paginate(4);
        return view("auth.profile", compact("posts"));
    }
    public function editProfile(){
        return view("auth.edit");
    }

    /*
    public function updateProfile(UpdateProfileRequest $request){
        if(Auth::check()){
            $id = Auth::id();
        }
        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;

        if(!empty($request->old_password)){
            if(!Hash::check($request->old_password, $user->password)){
                    return redirect()->back()->with("error", "This password is incorrect");
                }
            $user->password = bcrypt($request->new_password);
        }
        $user->save();
        if($request->hasFile("avatar")){
            if($user->image->image_path){
                $this->deleteAvatar($user->image->image_path);
            }
            $uploadedAvatar = $this->uploadAvatar($request->file('avatar'));
            $user->image()->update([
                'image_path'=> $uploadedAvatar
            ]);
        }
        return redirect()->route('my.profile');
        */

        public function updateProfile(UpdateProfileRequest $request)
        {
            $user = Auth::user();

            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email;

            // Handle password update if the old password is provided
            if ($request->filled('old_password') && Hash::check($request->old_password, $user->password)) {
                $user->password = bcrypt($request->new_password);
            } elseif ($request->filled('old_password')) {
                return redirect()->back()->withErrors(['old_password' => 'This password is incorrect']);
            }

            // Update the avatar if a new one is uploaded
            if ($request->hasFile('avatar')) {
                if ($user->image && $user->image->image_path) {
                    $this->deleteAvatar($user->image->image_path);
                }
                $uploadedAvatar = $this->uploadAvatar($request->file('avatar'));
                $user->image()->updateOrCreate([], ['image_path' => $uploadedAvatar]);
            }

            $user->save();

            return redirect()->route('my.profile')->with('success', 'Profile updated successfully');
        }


    }
    public function logout(){
        Auth::logout();
        return redirect()->route('loginForm');
    }
    public function deleteAvatar($avatar){
        @unlink(storage_path('app/public/' . $avatar));
        return;
    }
    public function uploadAvatar($avatar){
        $avatarPath = time() . "." . $avatar->getClientOriginalExtension();
        $uploadedAvatar = $avatar->storeAs("uploads", $avatarPath, "public");
        return $uploadedAvatar;
    }

    public function emailVerify(Request $request){
        $user = User::where('verification_token', $request->token)->first();
        if(!$user || $user->verification_token !== $request->token){
            abort(404);
        }

        $user->email_verified_at = now();
        $user->save();
        return redirect()->route('loginForm');
    }
}
