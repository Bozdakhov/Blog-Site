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
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function index() {
        $posts = auth()->check() 
            ? Post::whereIn('user_id', auth()->user()->following()->pluck('users.id'))->latest()->get()
            : Post::latest()->get();
        
        return view('welcome', compact('posts'));
    }

    public function registerForm() {
        if (Auth::check()) {
            abort(403);
        }
        return view("auth.register");
    }

    public function handleRegister(RegisterRequest $request) {
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'verification_token' => uniqid(),
            'password' => Hash::make($request->password),
        ]);

        $uploadedAvatar = $this->uploadAvatar($request->file('avatar'));
        $user->image()->create(['image_path' => $uploadedAvatar]);
        
        Mail::to($user->email)->send(new SendSmsToMail($user));
        
        return redirect()->route('loginForm');
    }

    public function loginForm() {
        return Auth::check() ? redirect()->back() : view("auth.login");
    }

    public function handleLogin(LoginRequest $request) {
        $user = User::where("email", $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->email_verified_at) {
                Auth::attempt($request->only('email', 'password'));
                return redirect()->route("home");
            }
            return redirect()->back()->withErrors(['email' => 'Email not verified.']);
        }

        return redirect()->back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function profile() {
        $posts = Auth::user()->posts()->latest()->paginate(4);
        return view("auth.profile", compact("posts"));
    }

    public function editProfile() {
        return view("auth.edit");
    }

    public function updateProfile(UpdateProfileRequest $request) {
        $user = Auth::user();
        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
        ]);

        if ($request->filled('old_password') && Hash::check($request->old_password, $user->password)) {
            $user->password = Hash::make($request->new_password);
            $user->save();
        } elseif ($request->filled('old_password')) {
            return redirect()->back()->with("error", "This password is incorrect");
        }

        if ($request->hasFile("avatar")) {
            if ($user->image && $user->image->image_path) {
                $this->deleteAvatar($user->image->image_path);
            }
            $uploadedAvatar = $this->uploadAvatar($request->file('avatar'));
            $user->image()->updateOrCreate([], ['image_path' => $uploadedAvatar]);
        }

        return redirect()->route('my.profile');
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('loginForm');
    }

    public function deleteAvatar($avatar) {
        Storage::disk('public')->delete($avatar);
    }

    public function uploadAvatar($avatar) {
        $avatarPath = time() . '.' . $avatar->getClientOriginalExtension();
        return $avatar->storeAs("uploads", $avatarPath, "public");
    }

    public function emailVerify(Request $request) {
        $user = User::where('verification_token', $request->token)->firstOrFail();
        $user->email_verified_at = now();
        $user->save();

        return redirect()->route('loginForm');
    }
}


