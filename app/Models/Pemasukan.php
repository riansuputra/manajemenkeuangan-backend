<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\KategoriPengeluaran;

class Pemasukan extends Model
{
    protected $table = 'pemasukans';
    protected $primaryKey = 'id_pemasukan';

    protected $fillable = ['user_id','tanggal','jumlah','catatan','id_kategori_pemasukan'];

    public function kategori_pemasukan()
    {
        return $this->belongsTo(KategoriPemasukan::class, 'id_kategori_pemasukan', 'id_kategori_pemasukan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
