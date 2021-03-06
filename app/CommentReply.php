<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CommentReply extends Model
{
	protected $table = 'comment_reply';
	
    protected $fillable = [
        'id','article_id','comment_id','comment','comment_by','comment_date', 'likes', 'is_reported','no_of_people_reported','is_deleted','comment_deleted_date','comment_deleted_by'
    ];

    public $timestamps = false;

    public function user_details()
	{
		return $this->belongsTo('App\User','comment_by','id');
	}
}
