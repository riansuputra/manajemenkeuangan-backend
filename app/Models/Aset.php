<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'aset';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
  
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'aset_id', 'id');
    }
    public function dividen()
    {
        return $this->hasMany(Dividen::class, 'aset_id', 'id');
    }
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'aset_id', 'id');
    }
    public function jual_saham()
    {
        return $this->hasMany(JualSaham::class, 'aset_id', 'id');
    }
}
