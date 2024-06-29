<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Berita extends Model
{
    protected $table = "beritas";
    protected $primaryKey = 'id_berita';

    protected $fillable = [
        'id_berita',
        'title',
        'published_at',
        'image',
        'url',
        'description',
        'publisher_name',
        'publisher_logo',
    ];
}
