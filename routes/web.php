<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;

/*
Route::get('/', function () {
    return view('home');
});
*/

// Home route
Route::get('/', [AuthController::class, 'index'])->name('home');

// Authentication routes
Route::get('/register', [AuthController::class, 'registerForm'])->name('registerForm');
Route::post('/register', [AuthController::class, 'handleRegister'])->name('handleRegister');
Route::get('/login', [AuthController::class, 'loginForm'])->name('loginForm');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('handleLogin');
Route::put('/my/profile/update', [AuthController::class, 'updateProfile'])->name('update.profile');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/email-verify', [AuthController::class, 'emailVerify'])->name('email.verify');

// Profile and notification routes
Route::get('/users/profile/{username}', [PostController::class, 'userProfile'])->name('users.profile');
Route::patch('/read/notify/{id}', [NotificationController::class, 'readNotify'])->name('mark.notification.read');

// Authenticated routes with middleware
Route::middleware('checkAuth')->group(function () {
    Route::get('/my/profile', [AuthController::class, 'profile'])->name('my.profile');
    Route::get('/my/profile/edit', [AuthController::class, 'editProfile'])->name('edit.profile');
    Route::get('/follow/{id}', [FollowController::class, 'follow'])->name('follow');
    Route::get('/unfollow/{id}', [FollowController::class, 'unfollow'])->name('unfollow');
    Route::get('/notify/{username}', [NotificationController::class, 'unReadnotifications'])->name('follow.notify');
    Route::post('/comments/store', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/destroy/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// Post routes with resource controller
Route::resource('posts', PostController::class)->except(['index', 'show']);
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
