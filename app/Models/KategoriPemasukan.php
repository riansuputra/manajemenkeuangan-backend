<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getNamaKategoriAttribute()
    {
        $locale = App::getLocale(); // atau session('locale', 'id');
        
        // Kembalikan nama sesuai locale
        if ($locale === 'en' && $this->nama_kategori_pemasukan_en) {
            return $this->nama_kategori_pemasukan_en;
        }

        return $this->nama_kategori_pemasukan;
    }
}