<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPengeluaran extends Model
{
    use HasFactory;

    protected $table = 'kategori_pengeluaran';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function anggaran()
    {
        return $this->hasMany(Anggaran::class, 'kategori_pengeluaran_id', 'id');
    }
    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'kategori_pengeluaran_id', 'id');
    }
}