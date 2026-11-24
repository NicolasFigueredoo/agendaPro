<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Time;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_category_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_minutes' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function durationLabel(): string
    {
        return Time::durationLabel($this->duration_minutes);
    }

    public function priceLabel(): string
    {
        return Money::format($this->price);
    }
}
