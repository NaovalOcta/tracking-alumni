<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryHash extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'query_hash',
        'original_query',
        'result_count',
        'executed_at',
    ];

    protected $casts = [
        'result_count' => 'integer',
        'executed_at' => 'datetime',
    ];
}
