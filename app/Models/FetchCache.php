<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FetchCache extends Model
{
    protected $table = 'fetch_cache';

    public $timestamps = false;

    protected $fillable = [
        'url_hash',
        'original_url',
        'content_blocks',
        'expires_at',
    ];

    protected $casts = [
        'content_blocks' => 'array',
        'expires_at' => 'datetime',
    ];
}
