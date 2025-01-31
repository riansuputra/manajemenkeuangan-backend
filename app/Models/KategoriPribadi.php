<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPribadi extends Model
{
    use HasFactory;

    protected $table = 'kategori_pribadi';
    protected $guarded = [];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
