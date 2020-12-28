<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $table = 'bookmark';

    protected $fillable = [
        'id','article_id','bookmark_by','bookmark_date'
    ];

    public $timestamps = false;
}
