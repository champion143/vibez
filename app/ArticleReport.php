<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleReport extends Model
{
    protected $table = 'article_report';

    protected $fillable = [
        'id','article_id','reported_by'
    ];

    public $timestamps = false;
}
