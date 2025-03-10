<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $guarded = [];
  
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id', 'id');
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
        return $this->hasMany(Portofolio::class, 'transaksi_id', 'id');
    }
    public function kinerja_portofolio()
    {
        return $this->hasMany(KinerjaPortofolio::class, 'transaksi_id', 'id');
    }
}