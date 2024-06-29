<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sekuritas extends Model
{
    protected $table = "sekuritass";
    public $timestamps = false;
    protected $primaryKey = 'id_sekuritas';

    protected $fillable = [
        'nama_sekuritas',
        'fee_beli',
        'fee_jual'
    ];

}