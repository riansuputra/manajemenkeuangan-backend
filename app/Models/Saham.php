<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saham extends Model
{
    use HasFactory;

    protected $table = 'saham';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function beli_saham()
    {
        return $this->hasMany(BeliSaham::class, 'saham_id', 'id');
    }
    public function jual_saham()
    {
        return $this->hasMany(JualSaham::class, 'saham_id', 'id');
    }
    public function portofolio()
    {
        return $this->hasMany(Portofolio::class, 'saham_id', 'id');
    }
}