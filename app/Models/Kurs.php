<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kurs extends Model
{
    protected $table = "kurs";
    protected $primaryKey = 'id_kurs';

    protected $fillable = [
        'id_kurs',
        'mata_uang',
        'nilai_tukar',
    ];
}
