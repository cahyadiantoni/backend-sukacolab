<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'skills';
    
    protected $fillable = ['user_id', 'name', 'description'];
}
