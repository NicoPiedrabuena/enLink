<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ProductionCheckCommand extends Command
{
    protected $signature = 'enlink:production-check';

    protected $description = 'Comprueba si Enlink tiene una configuración mínima segura para producción';

    public function handle(): int
    {
        $checks = [
            ['Entorno de producción', app()->environment('production'), 'APP_ENV=production'],
            ['Errores detallados desactivados', ! config('app.debug'), 'APP_DEBUG=false'],
            ['URL pública con HTTPS', str_starts_with((string) config('app.url'), 'https://') && $this->isConfigured(config('app.url')), 'APP_URL=https://tu-dominio'],
            ['Clave de aplicación configurada', $this->isConfigured(config('app.key')), 'Generar una APP_KEY propia'],
            ['Cookies seguras', config('session.secure') === true, 'SESSION_SECURE_COOKIE=true'],
            ['Cookies inaccesibles desde JavaScript', config('session.http_only') === true, 'SESSION_HTTP_ONLY=true'],
            ['Correo real', ! in_array(config('mail.default'), ['log', 'array'], true) && $this->isConfigured(config('mail.mailers.smtp.host')), 'Configurar SMTP o un proveedor transaccional'],
            ['Token de Mercado Pago', $this->isConfigured(config('mercadopago.access_token')), 'Configurar MERCADOPAGO_ACCESS_TOKEN'],
            ['Firma de webhook', $this->isConfigured(config('mercadopago.webhook_secret')), 'Configurar MERCADOPAGO_WEBHOOK_SECRET'],
            ['Mercado Pago productivo', config('mercadopago.sandbox') === false, 'MERCADOPAGO_SANDBOX=false después de probar sandbox'],
            ['Base de datos persistente', config('database.default') !== 'sqlite', 'Usar MySQL en producción'],
            ['Contraseña de base segura', $this->isConfigured(config('database.connections.mysql.password')) && config('database.connections.mysql.password') !== 'secret', 'Definir una contraseña única para MySQL'],
            ['Cola asíncrona', ! in_array(config('queue.default'), ['sync', 'null'], true), 'Usar Redis para QUEUE_CONNECTION'],
            ['Trabajos fallidos conservados', config('queue.failed.driver') !== 'null', 'Configurar QUEUE_FAILED_DRIVER=database-uuids'],
            ['Retención de analíticas', (int) config('enlink.analytics_retention_days') >= 30, 'Definir ENLINK_ANALYTICS_RETENTION_DAYS con al menos 30 días'],
            ['Retención de webhooks', (int) config('enlink.webhook_retention_days') >= 30, 'Definir ENLINK_WEBHOOK_RETENTION_DAYS con al menos 30 días'],
            ['Ventana de conciliación de pagos', (int) config('enlink.payment_reconciliation_hours') >= 24, 'Definir ENLINK_PAYMENT_RECONCILIATION_HOURS con al menos 24 horas'],
            ['Proxies de confianza definidos', filled(config('enlink.trusted_proxies')), 'Definir TRUSTED_PROXIES con la IP o red del proxy TLS'],
            ['Configuración optimizada', app()->configurationIsCached(), 'Ejecutar php artisan optimize'],
        ];

        try {
            DB::select('select 1');
            Cache::put('production-check', true, 10);
            $checks[] = ['Base de datos y caché accesibles', Cache::get('production-check') === true, 'Revisar MySQL y Redis'];

            $admins = User::query()->where('role', 'admin')->get();
            $safeAdmins = $admins->isNotEmpty() && $admins->every(
                fn (User $admin): bool => ! Hash::check('password', $admin->password),
            );
            $checks[] = ['Administrador seguro', $safeAdmins, 'Crear un administrador y eliminar la contraseña de ejemplo'];
        } catch (Throwable) {
            $checks[] = ['Base de datos y caché accesibles', false, 'Revisar MySQL y Redis'];
            $checks[] = ['Administrador seguro', false, 'No se pudo comprobar el administrador'];
        }

        $this->table(
            ['Comprobación', 'Estado', 'Qué hacer'],
            array_map(fn (array $check): array => [$check[0], $check[1] ? 'OK' : 'PENDIENTE', $check[1] ? '—' : $check[2]], $checks),
        );

        if (collect($checks)->contains(fn (array $check): bool => ! $check[1])) {
            $this->error('Enlink todavía no está listo para producción. Completá los puntos pendientes.');

            return self::FAILURE;
        }

        $this->info('Enlink está listo para recibir la prueba final de publicación.');

        return self::SUCCESS;
    }

    private function isConfigured(mixed $value): bool
    {
        if (! is_string($value) || blank($value)) {
            return false;
        }

        $normalized = strtolower($value);

        return ! str_contains($normalized, 'completar')
            && ! str_contains($normalized, 'cambiar')
            && ! str_contains($normalized, 'tu-dominio');
    }
}
