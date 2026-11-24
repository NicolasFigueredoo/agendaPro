<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo_path',
    ];

    public function hours(): HasMany
    {
        return $this->hasMany(LocationHour::class)->orderBy('weekday');
    }

    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class);
    }

    /**
     * La UI trabaja con un solo local. El modelo ya soporta varios.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->first()
            ?? static::create(['name' => config('app.name')]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
