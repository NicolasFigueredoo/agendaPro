<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_number',
        'first_name',
        'last_name',
        'birth_date',
        'dni',
        'gender',
        'email',
        'phone',
        'address',
        'province',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'client_number' => 'integer',
            'birth_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (! $client->client_number) {
                $client->client_number = static::nextNumber();
            }
        });
    }

    public static function nextNumber(): int
    {
        return (int) static::withTrashed()->max('client_number') + 1;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Booking::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    /** Sin turnos previos (no cuenta los cancelados). */
    public function isNew(?int $exceptBookingId = null): bool
    {
        return ! $this->bookings()
            ->where('status', '!=', BookingStatus::Cancelado->value)
            ->when($exceptBookingId, fn ($q) => $q->whereKeyNot($exceptBookingId))
            ->exists();
    }

    /**
     * Busca por nombre, apellido, "nombre apellido", DNI, email o teléfono.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';
        $digits = preg_replace('/\D/', '', $term);

        $fullName = $query->getConnection()->getDriverName() === 'sqlite'
            ? "(first_name || ' ' || last_name)"
            : "CONCAT(first_name, ' ', last_name)";

        return $query->where(function (Builder $q) use ($like, $digits, $term, $fullName) {
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereRaw("{$fullName} like ?", [$like]);

            if (strlen($digits) >= 3) {
                $q->orWhere('dni', 'like', '%'.$digits.'%')
                    ->orWhere('phone', 'like', '%'.$digits.'%');
            }

            if (ctype_digit($term)) {
                $q->orWhere('client_number', (int) $term);
            }
        });
    }
}
