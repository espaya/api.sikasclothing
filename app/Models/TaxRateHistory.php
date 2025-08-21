<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class TaxRateHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_rate_id',
        'action',
        'old_values',
        'new_values',
        'change_reason',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_at' => 'datetime',
    ];

    protected $dates = [
        'changed_at',
    ];

    // Relationships
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Accessors
    public function getFormattedChangesAttribute(): array
    {
        $changes = [];

        if ($this->action === 'created') {
            return ['action' => 'Tax rate created'];
        }

        if ($this->action === 'updated' && $this->new_values) {
            foreach ($this->new_values as $field => $newValue) {
                $oldValue = $this->old_values[$field] ?? null;

                // Format specific fields for better readability
                switch ($field) {
                    case 'rate':
                        $changes[$field] = [
                            'field_name' => 'Tax Rate',
                            'old' => $oldValue ? $oldValue . '%' : null,
                            'new' => $newValue . '%'
                        ];
                        break;
                    case 'status':
                        $changes[$field] = [
                            'field_name' => 'Status',
                            'old' => ucfirst($oldValue ?? ''),
                            'new' => ucfirst($newValue)
                        ];
                        break;
                    case 'effective_date':
                    case 'end_date':
                        $changes[$field] = [
                            'field_name' => str_replace('_', ' ', ucfirst($field)),
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                        break;
                    default:
                        $changes[$field] = [
                            'field_name' => str_replace('_', ' ', ucfirst($field)),
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                }
            }
        }

        return $changes;
    }

    // Scopes
    public function scopeForTaxRate($query, int $taxRateId)
    {
        return $query->where('tax_rate_id', $taxRateId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }

    // Static methods for easy history creation
    public static function logCreation(TaxRate $taxRate, $userId = null): self
    {
        return static::create([
            'tax_rate_id' => $taxRate->id,
            'action' => 'created',
            'new_values' => $taxRate->toArray(),
            'changed_by' => $userId ?? Auth::id(),
            'changed_at' => now(),
        ]);
    }

    public static function logUpdate(TaxRate $taxRate, array $oldValues, array $newValues, $userId = null, $reason = null): self
    {
        return static::create([
            'tax_rate_id' => $taxRate->id,
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'change_reason' => $reason,
            'changed_by' => $userId ?? Auth::id(),
            'changed_at' => now(),
        ]);
    }

    public static function logDeletion(TaxRate $taxRate, $userId = null, $reason = null): self
    {
        return static::create([
            'tax_rate_id' => $taxRate->id,
            'action' => 'deleted',
            'old_values' => $taxRate->toArray(),
            'change_reason' => $reason,
            'changed_by' => $userId ?? Auth::id(),
            'changed_at' => now(),
        ]);
    }

    // Helper method to get a human-readable description of the change
    public function getDescriptionAttribute(): string
    {
        $user = $this->changedBy ? $this->changedBy->name : 'System';
        $date = $this->changed_at->format('M j, Y g:i A');

        switch ($this->action) {
            case 'created':
                return "{$user} created this tax rate on {$date}";
            case 'updated':
                $changes = count($this->new_values ?? []);
                $changeText = $changes === 1 ? '1 field' : "{$changes} fields";
                return "{$user} updated {$changeText} on {$date}";
            case 'deleted':
                return "{$user} deleted this tax rate on {$date}";
            default:
                return "{$user} performed {$this->action} on {$date}";
        }
    }
}
