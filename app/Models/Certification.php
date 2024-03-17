<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $table = 'certifications';
    
    protected $fillable = ['user_id', 'name', 'publisher', 'credential', 'publish_date', 'expire_date'];
}
