<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'country_code',
        'state_code',
        'tax_name',
        'tax_type',
        'rate',
        'effective_date',
        'end_date',
        'status',
        'description',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
    ];

    protected $dates = [
        'effective_date',
        'end_date',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaxRateHistory::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });
    }

    public function scopeForCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    public function scopeForState(Builder $query, $stateCode = null): Builder
    {
        if ($stateCode) {
            return $query->where('state_code', strtoupper($stateCode));
        }

        return $query->whereNull('state_code');
    }

    // Static methods for easy access
    public static function getForLocation(string $countryCode, $stateCode = null): ?self
    {
        $cacheKey = "tax_rate.{$countryCode}." . ($stateCode ?? 'default');

        return Cache::remember($cacheKey, 86400, function () use ($countryCode, $stateCode) {
            // First try to find state-specific rate
            if ($stateCode) {
                $rate = static::active()
                    ->forCountry($countryCode)
                    ->forState($stateCode)
                    ->latest('effective_date')
                    ->first();

                if ($rate) {
                    return $rate;
                }
            }

            // Fall back to country-wide rate
            return static::active()
                ->forCountry($countryCode)
                ->forState()
                ->latest('effective_date')
                ->first();
        });
    }

    public static function clearCache(string $countryCode, $stateCode = null): void
    {
        $cacheKey = "tax_rate.{$countryCode}." . ($stateCode ?? 'default');
        Cache::forget($cacheKey);

        // Also clear country-wide cache if state-specific
        if ($stateCode) {
            Cache::forget("tax_rate.{$countryCode}.default");
        }
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($taxRate) {
            // Auto-set updated_by
            if (Auth::check()) {
                $taxRate->updated_by = Auth::id();

                if (!$taxRate->exists) {
                    $taxRate->created_by = Auth::id();
                }
            }
        });

        static::saved(function ($taxRate) {
            // Clear cache when tax rate is updated
            static::clearCache($taxRate->country_code, $taxRate->state_code);

            // Log the change
            $taxRate->logChange();
        });
    }

    // Helper methods
    public function getFormattedRateAttribute(): string
    {
        return $this->rate . '%';
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->effective_date <= now()
            && ($this->end_date === null || $this->end_date > now());
    }

    private function logChange(): void
    {
        if ($this->wasRecentlyCreated) {
            TaxRateHistory::create([
                'tax_rate_id' => $this->id,
                'action' => 'created',
                'new_values' => $this->toArray(),
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);
        } elseif ($this->wasChanged()) {
            TaxRateHistory::create([
                'tax_rate_id' => $this->id,
                'action' => 'updated',
                'old_values' => $this->getOriginal(),
                'new_values' => $this->getChanges(),
                'changed_by' => Auth::id(),
                'changed_at' => now(),
            ]);
        }
    }
}
