<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
