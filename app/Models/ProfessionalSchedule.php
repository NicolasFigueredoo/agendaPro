<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalSchedule extends Model
{
    protected $fillable = [
        'professional_id',
        'weekday',
        'start_time',
        'end_time',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }
}
