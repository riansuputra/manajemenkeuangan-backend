<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anggaran extends Model
{
    use HasFactory;

    protected $table = 'anggaran';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function kategori_pengeluaran() 
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}