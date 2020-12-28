<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleCommentReplyReport extends Model
{
    protected $table = 'article_comment_reply_report';

    protected $fillable = [
        'id','article_id','comment_id','reply_id','reported_by'
    ];

    public $timestamps = false;
}
