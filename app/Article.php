<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article';

    protected $fillable = [
        'id','forum_id','type','is_anonymous','article_title','article_description','likes', 'no_of_views', 'is_reported','no_of_people_reported','is_active','date_fo_inactive','created_from','created_by','updated_by'
    ];

    public function comments()
    {
        return $this->hasMany('App\Comment');
    }

}
