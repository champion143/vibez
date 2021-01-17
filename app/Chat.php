<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    public $table ='chat';

    public function tags()
    {
        return $this->belongsTo('App\ChatTag', 'tag', 'id');
    }
}
