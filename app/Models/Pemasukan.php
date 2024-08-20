<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemasukan extends Model
{
    protected $table = 'pemasukan';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function kategori_pemasukan()
    {
        return $this->belongsTo(KategoriPemasukan::class, 'kategori_pemasukan_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
