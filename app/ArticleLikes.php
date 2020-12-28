<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleLikes extends Model
{
    protected $table = 'article_likes';

    protected $fillable = [
        'id','article_id','like_by'
    ];

    public $timestamps = false;

}
