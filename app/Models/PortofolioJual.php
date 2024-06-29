<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortofolioJual extends Model
{
    protected $table = "portofolio_juals";
    protected $fillable = [
        'id_saham',
        'user_id',
        'volume_jual',
        'tanggal_jual',
        'harga_jual',
        'harga_total',
        'penjualan',
        'id_sekuritas',
    ];

    public $timestamps = false;
    protected $primaryKey = 'id_portofolio_jual';
    public function emiten()
    {
        return $this->hasMany(Saham::class, 'id_saham', 'id_saham');
    }
    public function sekuritas()
    {
        return $this->hasMany('Sekuritas');
    }
    public function saham()
    {
        return $this->belongsTo(Saham::class, 'user_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
