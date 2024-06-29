<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    use HasFactory;

    protected $table = 'tagihans';
    protected $primaryKey = 'id_tagihan';

    protected $fillable = [
        'user_id',
        'nama_tagihan',
        'tanggal_tagihan',
        'tanggal_jatuh_tempo',
        'jumlah',
        'bunga',
        'total_tagihan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}