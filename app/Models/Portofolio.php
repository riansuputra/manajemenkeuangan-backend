<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Portofolio extends Model
{

    protected $table = "transaksis";
    protected $fillable = [
        'id_transaksi',
        'user_id',
        'id_saham',
        'type',
        'tanggal_transaksi',
        'volume',
        'id_sekuritas'
    ];
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'id_transaksi';
   

}