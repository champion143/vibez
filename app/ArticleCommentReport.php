<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleCommentReport extends Model
{
    protected $table = 'article_comment_report';

    protected $fillable = [
        'id','article_id','comment_id','reported_by'
    ];

    public $timestamps = false;
}
