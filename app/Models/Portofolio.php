<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portofolio extends Model
{
    use HasFactory;

    protected $table = 'portofolio';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
  
    public function saham()
    {
        return $this->belongsTo(Saham::class, 'saham_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}