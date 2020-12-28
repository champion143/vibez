<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'event';

    protected $fillable = [
        'id','forum_id','event_title','event_tag_line','event_description','event_type','event_url', 'event_location', 'event_date','event_start_time','event_end_time','image_url','width','height','is_active','date_fo_inactive','in_activated_by','is_approve','is_approved_by','created_from','created_by','updated_by','created_at','updated_at'
    ];
}
