<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParseRun extends Model
{
    protected $fillable = [
        'organization_id',
        'status',
        'reviews_found',
        'error_message',
        'snapshot',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}