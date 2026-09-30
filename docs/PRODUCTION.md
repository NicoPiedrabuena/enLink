# Producción de Enlink

Esta guía deja una única superficie pública: el proxy TLS. La aplicación, MySQL,
Redis, Mailpit y Adminer no deben exponerse directamente a Internet.

## Antes del primer despliegue

1. Crear un repositorio Git con un commit inicial y desplegar sólo revisiones
   identificables. Registrar el hash de cada despliegue para poder volver atrás.
2. Preparar un servidor actualizado, con firewall que permita únicamente 80 y
   443 al proxy TLS. Docker publica Enlink sólo en `127.0.0.1:8080`.
3. Configurar un dominio y un proxy TLS administrado o local. Debe reemplazar,
   no aceptar del cliente, los encabezados `X-Forwarded-For` y
   `X-Forwarded-Proto`. Definir en `TRUSTED_PROXIES` sólo la IP o red de ese
   proxy; nunca usar `*`.
4. Copiar `.env.production.example` a `.env` exclusivamente en el servidor.
   Reemplazar cada `CAMBIAR`, `COMPLETAR` y `TU-DOMINIO`; generar una `APP_KEY`
   propia con `php artisan key:generate` una vez que el contenedor inicie.
5. Guardar `.env` en el almacén de secretos del proveedor. Nunca debe entrar a
   Git, a una imagen Docker ni a logs.
6. Configurar SMTP transaccional, Google OAuth (si se ofrece) y credenciales
   productivas de Mercado Pago. Registrar exactamente el callback HTTPS:
   `https://TU-DOMINIO/webhooks/mercado-pago`.

Ejemplo mínimo de proxy Caddy en el host (reemplazar el dominio):

```caddy
enlink.example.com {
    reverse_proxy 127.0.0.1:8080
}
```

Un balanceador administrado cumple la misma función. No exponer el puerto 8080
en el firewall para evitar que se pueda eludir TLS.

## Despliegue

```powershell
docker compose build --pull
docker compose run --rm --no-deps app php artisan key:generate --show
# Copiar el valor mostrado en APP_KEY dentro de .env antes de iniciar los servicios.
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=BusinessModelSeeder --force
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force
docker compose exec app php artisan optimize
docker compose exec app php artisan enlink:production-check
```

No usar `docker-compose.dev.yml` en el servidor. Ese archivo inicia Mailpit y
Adminer sólo para desarrollo.

Después de cada actualización, comprobar:

```powershell
docker compose ps
Invoke-WebRequest https://TU-DOMINIO/up
Invoke-WebRequest https://TU-DOMINIO/health/ready
docker compose exec app php artisan schedule:list
docker compose exec app php artisan queue:failed
```

Los contenedores `queue` y `scheduler` deben figurar activos. La cola usa Redis
para registrar analíticas fuera de la respuesta HTTP y reinicia el worker cada
hora de forma controlada.

## Pago y correo: validación obligatoria

Primero usar `MERCADOPAGO_SANDBOX=true` y credenciales de prueba. Verificar
todo el recorrido: registro, correo de verificación, compra, recepción del
webhook, créditos acreditados una sola vez, publicación y enlace público.

Sólo después cambiar a credenciales productivas y
`MERCADOPAGO_SANDBOX=false`, ejecutar `php artisan optimize` de nuevo y repetir
`enlink:production-check`. Mantener al menos una prueba de compra real de bajo
importe documentada tras cada cambio relevante de pagos.

El scheduler concilia cada diez minutos las órdenes pendientes creadas en los
últimos siete días. Consultar `php artisan enlink:reconcile-payments` y alertar
si devuelve error: una orden que no pueda consultarse se reintentará en el
siguiente ciclo. La conciliación no sustituye la prueba de compra real.

## Respaldo y restauración

Respaldar diariamente, cifrar antes de salir del servidor y conservar copias
fuera de él:

- MySQL: fuente de verdad de usuarios, publicaciones, créditos y pagos.
- Volumen `storage-data`: avatares públicos.
- Volumen `redis-data`: permite recuperar mensajes de cola pendientes; la
  aplicación puede recrear caché y sesiones si fuera necesario.

Aplicar, como mínimo, una rotación de 7 copias diarias, 4 semanales y 6
mensuales. El volcado manual sirve sólo para diagnóstico; la tarea real debe
usar secretos sin imprimirlos en comandos ni logs.

```powershell
New-Item -ItemType Directory -Force backups
docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysqldump -u"$MYSQL_USER" --single-transaction --routines --triggers "$MYSQL_DATABASE"' > backups\enlink.sql
```

Restaurar mensualmente el último respaldo en una base aislada. Confirmar acceso,
publicación, cobros históricos y `/health/ready`; registrar fecha, copia usada y
resultado. Nunca restaurar sobre la base activa.

## Monitoreo y retención

El proveedor de monitoreo debe consultar `/up` y `/health/ready` cada minuto y
alertar tras dos fallos consecutivos. También alertar por:

- contenedor no saludable o reiniciándose;
- trabajos en `failed_jobs`;
- respuestas HTTP 5xx, espacio en disco y uso anormal de memoria;
- fallos de respaldo;
- error de webhook o de correo transaccional.

El scheduler ejecuta `enlink:prune-data` todos los días a las 03:20. Conserva
las métricas diarias agregadas, pero elimina por defecto visitas/clics crudos a
los 90 días y recibos de webhook a los 180. Ajustar únicamente
`ENLINK_ANALYTICS_RETENTION_DAYS` y `ENLINK_WEBHOOK_RETENTION_DAYS` a valores
de 30 días o más, de acuerdo con la política de privacidad.

## Mantenimiento seguro

- Revisar semanalmente las actualizaciones propuestas por Dependabot y ejecutar
  CI antes de aceptarlas.
- La CI ejecuta formato, pruebas, build del frontend, auditorías de dependencias
  y validación de Docker Compose. Corregir alertas de severidad alta antes de
  publicar.
- Rotar inmediatamente los secretos si aparecen en un log, ticket o máquina no
  autorizada.
- No guardar IPs, contraseñas, tokens ni contenido privado en logs.
