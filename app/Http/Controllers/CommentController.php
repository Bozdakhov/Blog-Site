<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request)
    {
        $post = Post::findOrFail($request->post_id);

        $comment = $post->comments()->create([
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        // Notify post owner only if the commenter is not the post author
        if (Auth::id() !== $post->user_id) {
            $post->user->notify(new NewCommentNotification($post));
        }

        return redirect()->route('posts.show', $post->id)
                         ->with('success', 'Comment added successfully!');
    }

    public function destroy(string $id)
    {
        $comment = Comment::findOrFail($id);

        // Ensure only the comment owner can delete it
        if (Auth::id() !== $comment->user_id) {
            abort(403, 'Unauthorized action');
        }

        $comment->delete();

        return redirect()->route('posts.show', $comment->post_id)
                         ->with('success', 'Comment deleted successfully!');
    }
}
