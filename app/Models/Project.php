<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';
    
    protected $fillable = ['name', 'position', 'location', 'tipe', 'is_paid', 'time', 'description', 'requirements', 'user_id', 'is_active', 'created_at', 'updated_at'];
}
