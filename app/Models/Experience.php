<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $table = 'experiences';
    
    protected $fillable = ['user_id', 'title', 'company', 'role', 'start_date', 'end_date', 'is_now'];
}
