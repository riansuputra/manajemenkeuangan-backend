<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPemasukan extends Model
{
    use HasFactory;

    protected $table = 'kategori_pemasukan';
    protected $guarded = [];

    public function anggaran()
    {
        return $this->hasMany(Anggaran::class, 'kategori_pemasukan_id', 'id');
    }
    public function pemasukan()
    {
        return $this->hasMany(Pemasukan::class, 'kategori_pemasukan_id', 'id');
    }
}