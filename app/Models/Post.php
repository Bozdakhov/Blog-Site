<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Image;
use App\Models\User;
use App\Models\Comment;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description'
    ] ;

    public function image(){
        return $this->morphOne(Image::class,'imageable');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    
    public function comments(){
        return $this->hasMany(Comment::class);
    }
}
