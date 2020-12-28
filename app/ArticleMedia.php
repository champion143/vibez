<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleMedia extends Model
{
    protected $table = 'article_media';

    protected $fillable = [
        'id','article_id','media_type','media_url','width', 'height','created_by','updated_by'
    ];

}
