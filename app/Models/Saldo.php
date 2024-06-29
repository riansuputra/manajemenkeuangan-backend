<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saldo extends Model
{
    protected $table = "saldos";
    protected $primaryKey = 'id_saldo';

    protected $fillable = [
        'saldo',
        'user_id',
    ];
}
