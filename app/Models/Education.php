<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $table = 'educations';
    
    protected $fillable = ['user_id', 'instansi', 'major', 'start_date', 'end_date', 'is_now'];
}
