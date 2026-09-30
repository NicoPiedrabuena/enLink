# Enlink

Enlink es una plataforma para crear una página de enlaces personal, publicarla por créditos y medir visitas y clics. Incluye editor visual, estilos predeterminados, reservas por WhatsApp, Mercado Pago, analytics y administración.

## Desarrollo local con Docker

Docker ejecuta cada parte del sistema en un contenedor separado: Nginx recibe las visitas, Laravel procesa la aplicación, MySQL guarda los datos, Redis mantiene caché y sesiones, y los contenedores `queue` y `scheduler` realizan trabajos en segundo plano.

```powershell
Copy-Item .env.example .env
docker compose -f docker-compose.yml -f docker-compose.dev.yml build app
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm --no-deps app php artisan key:generate --show
# Copiá el valor mostrado en APP_KEY dentro de .env.
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker compose exec app php artisan migrate --seed
```

La aplicación queda disponible en `http://localhost:8080`.

## Administrador inicial

El proyecto ya no contiene una contraseña de administrador fija. Para crearlo, completar `ENLINK_ADMIN_EMAIL` y `ENLINK_ADMIN_PASSWORD` en `.env`; la clave debe tener al menos 12 caracteres. Luego ejecutar:

```powershell
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force
```

## Pruebas

```powershell
docker compose --profile tools build test
docker compose --profile tools run --rm test php artisan test
```

Las pruebas usan SQLite temporal y no modifican la base local de MySQL.

## Preparación para un servidor

La guía completa está en [docs/PRODUCTION.md](docs/PRODUCTION.md). Después de cargar los secretos y optimizar Laravel, ejecutar:

```powershell
docker compose exec app php artisan enlink:production-check
```

El comando revisa dominio HTTPS, depuración, cookies, correo, Mercado Pago, MySQL, Redis, cola, optimización y seguridad del administrador. Un resultado correcto habilita la prueba final, pero no reemplaza probar una compra real y una restauración del respaldo.

`docker-compose.yml` es exclusivamente productivo: la aplicación sólo se publica en `127.0.0.1:8080`, detrás de un proxy TLS. Mailpit y Adminer viven en `docker-compose.dev.yml` y no forman parte del despliegue.
