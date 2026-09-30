# Decisiones de arquitectura del MVP

Este documento registra las decisiones aprobadas antes de construir las funciones de negocio de Enlink.

## Página pública

Las páginas públicas se renderizarán con Blade en el servidor. El dashboard autenticado seguirá usando Inertia y Vue.

Esto permite que `/{username}` cargue y sea indexable sin JavaScript, mientras que el editor conserva una experiencia interactiva.

## Username

El username se normaliza a minúsculas y acepta solamente letras ASCII y guiones. Debe tener entre 3 y 30 caracteres, no puede comenzar, terminar ni contener dos guiones consecutivos.

Patrón: `^[a-z]+(?:-[a-z]+)*$`

Las rutas operativas y administrativas se mantienen en la lista reservada de la aplicación.

## Moneda y créditos

La moneda inicial será ARS. Los paquetes iniciales se crearán en la fase de monetización con 10, 50 y 100 créditos.

Los importes, la política fiscal y las credenciales de Mercado Pago todavía deben definirse antes de habilitar cobros. La primera integración se realizará en el entorno sandbox de Mercado Pago.

## Entorno de desarrollo

El entorno estándar usa Docker Compose y PHP 8.4. La aplicación, MySQL, Redis, la cola y el scheduler se ejecutan como servicios separados. La aplicación se abre en `http://localhost:8080`. La imagen también incluye SQLite para que las pruebas usen una base temporal aislada, sin modificar los datos locales.
