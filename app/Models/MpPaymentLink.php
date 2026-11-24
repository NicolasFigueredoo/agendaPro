<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MpPaymentLink extends Model
{
    public const TYPE_SENA = 'sena';

    public const TYPE_TOTAL = 'total';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'token',
        'booking_id',
        'type',
        'amount',
        'preference_id',
        'init_point',
        'status',
        'expires_at',
        'mp_payment_id',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MpPaymentLink $link) {
            $link->token ??= (string) Str::uuid();
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_SENA ? 'Seña' : 'Total';
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REJECTED], true)
            && $this->init_point
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
