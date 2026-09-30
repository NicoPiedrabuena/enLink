<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CreditTransaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'balance_after',
        'reference_key',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('A credit ledger movement cannot be changed.');
        });

        static::deleting(function (): void {
            throw new LogicException('A credit ledger movement cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
