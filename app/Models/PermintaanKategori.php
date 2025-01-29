<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanKategori extends Model
{
    use HasFactory;

    protected $table = 'permintaan_kategori';
    protected $guarded = [];

    public function admin() 
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}