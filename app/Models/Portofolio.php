<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portofolio extends Model
{
    use HasFactory;

    protected $table = 'portofolio';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
  
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function kinerja_portofolio()
    {
        return $this->belongsTo(KinerjaPortofolio::class, 'kinerja_portofolio_id', 'id');
    }
}