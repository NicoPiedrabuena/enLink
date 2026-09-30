<?php

namespace App\Actions\Credits;

use App\Models\CreditBalance;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCreditTransactionAction
{
    /**
     * Record an immutable credit movement and update its cached balance atomically.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        User $user,
        string $type,
        int $amount,
        string $description,
        ?string $referenceKey = null,
        array $metadata = [],
    ): CreditTransaction {
        if ($amount === 0) {
            throw new \InvalidArgumentException('A credit movement cannot have a zero amount.');
        }

        return DB::transaction(function () use ($user, $type, $amount, $description, $referenceKey, $metadata): CreditTransaction {
            CreditBalance::query()->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

            $balance = CreditBalance::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceAfter = $balance->balance + $amount;

            if ($balanceAfter < 0) {
                throw new InsufficientCreditsException('The account does not have enough credits.');
            }

            $balance->update(['balance' => $balanceAfter]);

            return $user->creditTransactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reference_key' => $referenceKey,
                'description' => $description,
                'metadata' => $metadata ?: null,
            ]);
        });
    }
}
