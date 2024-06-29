<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anggaran extends Model
{
    use HasFactory;

    protected $table = 'anggarans';
    protected $primaryKey = 'id_anggaran';

    protected $fillable = ['user_id','anggaran','tanggal_mulai','tanggal_selesai','id_kategori_pengeluaran', 'periode'];

    public function kategori_pengeluaran()
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'id_kategori_pengeluaran', 'id_kategori_pengeluaran');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
