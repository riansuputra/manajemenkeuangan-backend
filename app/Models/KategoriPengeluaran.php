<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class KategoriPengeluaran extends Model
{
    use HasFactory;

    protected $table = 'kategori_pengeluaran';
    protected $guarded = [];

    public function anggaran()
    {
        return $this->hasMany(Anggaran::class, 'kategori_pengeluaran_id', 'id');
    }
    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'kategori_pengeluaran_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getNamaKategoriAttribute()
    {
        $locale = App::getLocale(); // atau session('locale', 'id');
        
        // Kembalikan nama sesuai locale
        if ($locale === 'en' && $this->nama_kategori_pengeluaran_en) {
            return $this->nama_kategori_pengeluaran_en;
        }

        return $this->nama_kategori_pengeluaran;
    }
}