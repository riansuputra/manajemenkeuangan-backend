<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorisTahunan extends Model
{
    use HasFactory;

    protected $table = 'historis_tahunan';
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function historis_bulanan()
    {
        return $this->hasMany(HistorisBulanan::class, 'historis_tahunan_id', 'id');
    }
}