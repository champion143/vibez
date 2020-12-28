<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReplyLikes extends Model
{
    protected $table = 'reply_likes';

    protected $fillable = [
        'id','article_id','comment_id','reply_id','like_by'
    ];

    public $timestamps = false;
}
