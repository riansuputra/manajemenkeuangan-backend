<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KinerjaPortofolio extends Model
{
    use HasFactory;

    protected $table = 'kinerja_portofolio';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function mutasi_dana()
    {
        return $this->belongsTo(MutasiDana::class, 'mutasi_dana_id', 'id');
    }
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'kinerja_portofolio_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
