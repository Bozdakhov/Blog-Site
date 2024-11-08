<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::orderBy("created_at", "desc")->paginate(6);
        return view("posts.index", compact("posts"));
    }

    public function create()
    {
        if (Auth::check() && Auth::user()->email_verified_at === null) {
            return redirect()->back()->with('error', 'Please verify your email before creating a post.');
        }
        return view("posts.create");
    }

    public function store(StorePostRequest $request)
    {
        $post = new Post();
        $post->user_id = Auth::id();
        $post->title = $request->title;
        $post->description = $request->description;
        $post->save();

        if ($request->hasFile('image')) {
            $uploadedImage = $this->uploadImage($request->file('image'));
            $post->image()->create([
                'image_path' => $uploadedImage
            ]);
        }

        return redirect()->route('my.profile')->with('success', 'Post created successfully!');
    }

    public function show(string $id)
    {
        $post = Post::findOrFail($id);
        return view("posts.show", compact("post"));
    }

    public function edit(string $id)
    {
        $post = Post::findOrFail($id);
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }
        return view("posts.edit", compact("post"));
    }

    public function update(UpdatePostRequest $request, string $id)
    {
        $post = Post::findOrFail($id);
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        $post->title = $request->title;
        $post->description = $request->description;
        $post->save();

        if ($request->hasFile("image")) {
            if ($post->image && $post->image->image_path) {
                $this->deleteImage($post->image->image_path);
            }
            $updatedImage = $this->uploadImage($request->file("image"));
            $post->image()->update([
                'image_path' => $updatedImage
            ]);
        }

        return redirect()->route('posts.show', $post->id)->with('success', 'Post updated successfully!');
    }

    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }

        if ($post->image && $post->image->image_path) {
            $this->deleteImage($post->image->image_path);
        }
        
        $post->delete();
        return redirect()->route('my.profile')->with('success', 'Post deleted successfully!');
    }

    public function uploadImage($image)
    {
        $imagePath = time() . "." . $image->getClientOriginalExtension();
        $uploadedImage = $image->storeAs("uploads", $imagePath, "public");
        return $uploadedImage;
    }

    public function deleteImage($image)
    {
        if ($image) {
            @unlink(storage_path("app/public/" . $image));
        }
    }

    public function userProfile($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        return view("users.profile", compact("user"));
    }
}
