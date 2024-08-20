<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeliSaham extends Model
{
    use HasFactory;

    protected $table = 'beli_saham';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
  
    public function saham()
    {
        return $this->belongsTo(Saham::class, 'saham_id', 'id');
    }
    public function sekuritas()
    {
        return $this->belongsTo(Sekuritas::class, 'sekuritas_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'beli_saham_id', 'id');
    }
}