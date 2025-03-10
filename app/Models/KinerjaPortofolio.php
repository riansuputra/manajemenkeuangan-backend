<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KinerjaPortofolio extends Model
{
    use HasFactory;

    protected $table = 'kinerja_portofolio';
    protected $guarded = [];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id', 'id');
    }
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'kinerja_portofolio_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
