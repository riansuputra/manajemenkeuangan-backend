<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiDana extends Model
{
    use HasFactory;

    protected $table = 'mutasi_dana';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function kinerja_portofolio()
    {
        return $this->hasMany(KinerjaPortofolio::class, 'mutasi_dana_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
