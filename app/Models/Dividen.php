<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dividen extends Model
{
    use HasFactory;

    protected $table = 'dividen';
    protected $guarded = [];

    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id', 'id');
    }
}