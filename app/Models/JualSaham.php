<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JualSaham extends Model
{
    use HasFactory;

    protected $table = 'jual_saham';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];
  
    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id', 'id');
    }
    public function sekuritas()
    {
        return $this->belongsTo(Sekuritas::class, 'sekuritas_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'jual_saham_id', 'id');
    }
}
