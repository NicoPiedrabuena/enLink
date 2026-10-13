<?php

namespace App\Console\Commands;

use App\Actions\Credits\CreateCreditTransactionAction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AddCreditsCommand extends Command
{
    protected $signature = 'enlink:credits:add
        {email : Correo electrónico de la cuenta}
        {credits : Cantidad positiva de créditos a sumar}
        {--reason= : Motivo que quedará registrado en el historial}';

    protected $description = 'Suma créditos a una cuenta buscándola por correo electrónico';

    public function handle(CreateCreditTransactionAction $createTransaction): int
    {
        $email = trim((string) $this->argument('email'));
        $credits = filter_var($this->argument('credits'), FILTER_VALIDATE_INT);

        if ($email === '') {
            $this->error('El correo electrónico es obligatorio.');

            return self::FAILURE;
        }

        if ($credits === false || $credits < 1 || $credits > 10000) {
            $this->error('La cantidad de créditos debe ser un número entero entre 1 y 10000.');

            return self::FAILURE;
        }

        $user = User::query()
            ->whereRaw('lower(email) = ?', [Str::lower($email)])
            ->first();

        if (! $user) {
            $this->error("No existe una cuenta con el correo {$email}.");

            return self::FAILURE;
        }

        $reason = trim((string) $this->option('reason'));
        $reason = $reason !== '' ? $reason : 'Acreditación manual mediante Artisan.';

        $transaction = $createTransaction->execute(
            $user,
            'admin_adjustment',
            $credits,
            $reason,
            'artisan-credit-add:'.Str::uuid(),
            ['source' => 'artisan'],
        );

        $this->info("Se acreditaron {$transaction->amount} créditos a {$user->email}. Saldo actual: {$transaction->balance_after}.");

        return self::SUCCESS;
    }
}
