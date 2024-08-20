<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sekuritas extends Model
{
    use HasFactory;
    
    protected $table = 'sekuritas';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
}