<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user';
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token', 'api_token', 'updated_at'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function anggaran()
    {
        return $this->hasMany(Anggaran::class, 'user_id', 'id');
    }
    public function beli_saham()
    {
        return $this->hasMany(BeliSaham::class, 'user_id', 'id');
    }
    public function jual_saham()
    {
        return $this->hasMany(JualSaham::class, 'user_id', 'id');
    }
    public function pemasukan()
    {
        return $this->hasMany(Pemasukan::class, 'user_id', 'id');
    }
    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'user_id', 'id');
    }
    public function permintaan_kategori()
    {
        return $this->hasMany(PermintaanKategori::class, 'user_id', 'id');
    }
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'user_id', 'id');
    }
    public function saldo()
    {
        return $this->hasMany(Saldo::class, 'user_id', 'id');
    }
    public function historis_bulanan()
    {
        return $this->hasMany(HistorisBulanan::class, 'user_id', 'id');
    }
    public function historis_tahunan()
    {
        return $this->hasMany(HistorisTahunan::class, 'user_id', 'id');
    }
    public function kinerja_portofolio()
    {
        return $this->hasMany(KinerjaPortofolio::class, 'user_id', 'id');
    }
    public function mutasi_dana()
    {
        return $this->hasMany(MutasiDana::class, 'user_id', 'id');
    }
    public function catatan()
    {
        return $this->hasMany(Catatan::class, 'user_id', 'id');
    }
    public function historis()
    {
        return $this->hasMany(Historis::class, 'user_id', 'id');
    }
    public function kategori_pemasukan()
    {
        return $this->hasMany(KategoriPemasukan::class, 'user_id', 'id');
    }
    public function kategori_pengeluaran()
    {
        return $this->hasMany(KategoriPengeluaran::class, 'user_id', 'id');
    }
    public function perubahan_harga()
    {
        return $this->hasMany(PerubahanHarga::class, 'user_id', 'id');
    }
    
}