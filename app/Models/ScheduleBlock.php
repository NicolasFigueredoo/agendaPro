<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueo de agenda de un profesional (vacaciones, trámite, etc.).
 */
class ScheduleBlock extends Model
{
    protected $fillable = [
        'professional_id',
        'start_at',
        'end_at',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }
}
