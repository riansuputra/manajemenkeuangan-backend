<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dividen extends Model
{
    protected $table = "dividens";
    public $timestamps = false;
    protected $primaryKey = 'id_dividen';

    protected $fillable = [
        'emiten',
        'dividen',
    ];
}
