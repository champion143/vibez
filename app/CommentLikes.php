<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommentLikes extends Model
{
    protected $table = 'comment_likes';

    protected $fillable = [
        'id','article_id','comment_id','like_by'
    ];

    public $timestamps = false;
}
