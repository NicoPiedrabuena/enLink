# Desarrollo local con Docker

Docker ejecuta cada parte del sistema en su propio contenedor. De este modo, Laravel puede referirse a MySQL como `mysql` y a Redis como `redis`, sin instalar ni configurar esos servicios en Windows.

## Iniciar el sistema

```powershell
docker compose up -d --build
```

Luego abre `http://localhost:8080`.

## Ejecutar comandos de Laravel

Los comandos se ejecutan dentro del contenedor de la aplicación:

```powershell
docker compose exec app php artisan migrate:status
```

No se debe usar `php artisan serve` para este entorno: ese comando inicia Laravel directamente en Windows y no puede resolver los nombres internos `mysql` y `redis`.

## Ejecutar pruebas

```powershell
docker compose --profile tools run --rm test
```

El contenedor `test` usa SQLite en memoria. Sus datos se destruyen al finalizar y no modifica la base MySQL local.

## Detener el sistema

```powershell
docker compose down
```

Esto detiene los contenedores, pero conserva los datos de MySQL. Para eliminar esos datos habría que borrar explícitamente el volumen, una operación que no se realiza como parte del trabajo cotidiano.
