<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReferalCode extends Model
{
    protected $table = 'referal_code';

    protected $fillable = ['referal_code','is_used','is_used_by','date_of_used','created_at','updated_at','created_by','updated_by'];
}
