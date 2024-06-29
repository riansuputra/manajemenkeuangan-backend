<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\KategoriPengeluaran;

class Pengeluaran extends Model
{
    protected $table = 'pengeluarans';
    protected $primaryKey = 'id_pengeluaran';

    protected $fillable = ['user_id','tanggal','jumlah','catatan','id_kategori_pengeluaran'];

    public function kategori_pengeluaran()
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'id_kategori_pengeluaran', 'id_kategori_pengeluaran');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
