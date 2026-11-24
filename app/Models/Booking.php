<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ONLINE = 'online';

    protected $fillable = [
        'client_id',
        'professional_id',
        'service_id',
        'location_id',
        'start_at',
        'end_at',
        'price',
        'status',
        'payment_status',
        'source',
        'hold_expires_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'price' => 'integer',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    protected $attributes = [
        'status' => 'reservado',
        'payment_status' => 'sin_pagar',
        'source' => 'manual',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(BookingStatusLog::class)->latest('created_at')->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(MpPaymentLink::class)->latest('id');
    }

    /** Turnos que no están cancelados (para contar y listar). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', BookingStatus::Cancelado->value);
    }

    /**
     * Turnos que ocupan el horario: no cancelados y, si son reservas online esperando la seña,
     * solo mientras no venció la retención.
     */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->where('status', '!=', BookingStatus::Cancelado->value)
            ->where(function (Builder $q) {
                $q->whereNull('hold_expires_at')
                    ->orWhere('hold_expires_at', '>', now())
                    ->orWhere('payment_status', '!=', PaymentStatus::SinPagar->value);
            });
    }

    /** Turnos que se superponen con [from, to). */
    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->where('start_at', '<', $to)->where('end_at', '>', $from);
    }

    public function scopeOnDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereBetween('start_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
    }

    public function isOnline(): bool
    {
        return $this->source === self::SOURCE_ONLINE;
    }

    /** Reserva online que todavía espera la seña. */
    public function isAwaitingDeposit(): bool
    {
        return $this->hold_expires_at !== null
            && $this->payment_status === PaymentStatus::SinPagar
            && $this->status !== BookingStatus::Cancelado;
    }

    /** Cambia el estado y guarda el historial (quién y cuándo). $userId null = sistema. */
    public function changeStatus(BookingStatus $to, ?int $userId = null): void
    {
        $from = $this->status;

        if ($from === $to) {
            return;
        }

        $this->status = $to;
        $this->save();

        $this->statusLogs()->create([
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'user_id' => $userId,
        ]);
    }

    public function durationMinutes(): int
    {
        return (int) $this->start_at->diffInMinutes($this->end_at);
    }

    public function paidAmount(): int
    {
        return (int) ($this->relationLoaded('payments')
            ? $this->payments->sum('amount')
            : $this->payments()->sum('amount'));
    }

    public function balance(): int
    {
        return max(0, $this->price - $this->paidAmount());
    }
}
