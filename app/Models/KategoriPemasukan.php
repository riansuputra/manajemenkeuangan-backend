<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPemasukan extends Model
{
    use HasFactory;

    protected $table = 'kategori_pemasukans';

    protected $primaryKey = 'id_kategori_pemasukan';

    protected $fillable = ['nama_kategori_pemasukan'];

    public $timestamps = false;

}