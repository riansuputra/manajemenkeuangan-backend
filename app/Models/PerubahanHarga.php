<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerubahanHarga extends Model
{
    use HasFactory;

    protected $table = 'perubahan_harga';
    protected $guarded = [];
  
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}