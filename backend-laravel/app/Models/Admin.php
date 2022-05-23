<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;
    public $fillable = [
        'user_id',
        'username',
        'profile_image_name',
        'profile_image_src'
    ];
}
