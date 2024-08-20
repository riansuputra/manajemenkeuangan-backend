<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admin';
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token', 'api_token', 'created_at', 'updated_at'];
    protected $casts = ['password' => 'hashed'];

    public function permintaan_kategori()
    {
        return $this->hasMany(PermintaanKategori::class, 'admin_id', 'id');
    }
}