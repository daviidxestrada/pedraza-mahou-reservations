# Pedraza Mahou Reservations

Plugin WordPress para gestionar reservas de cestas picnic Mahou en la web del Gran Castillo de Pedraza. Funciona mediante shortcodes insertables en contenedores de Elementor y no crea cabeceras, pies ni estructura global de página.

## Requisitos

- WordPress con PHP 8.0 o superior.
- Permisos para instalar y activar plugins.
- Configuración correcta de envío de correo en WordPress. Se recomienda SMTP transaccional en producción.

## Instalación

1. Comprime la carpeta `pedraza-mahou-reservations` en un archivo ZIP.
2. En WordPress, ve a `Plugins > Añadir nuevo > Subir plugin`.
3. Sube el ZIP, instala y activa el plugin.
4. Al activar el plugin se crea la tabla personalizada de reservas mediante `dbDelta`.

## Shortcodes

Formulario público de reserva:

```php
[pedraza_mahou_reservations]
```

Panel privado de gestión:

```php
[pedraza_mahou_reservations_admin]
```

Ambos shortcodes imprimen únicamente el contenido funcional del plugin, por lo que pueden insertarse dentro de contenedores de Elementor.

## Configuración inicial

En el admin de WordPress, entra en `Ajustes > Reservas Pedraza Mahou` y configura:

- Usuario del panel privado.
- Nueva contraseña del panel privado.
- Email interno de recepción.
- Nombre del remitente.
- Email del remitente.
- Asunto del email al cliente.
- Asunto del email interno.
- Enlace a Política de Privacidad.
- Enlace a Política de Cookies.
- Enlace a Aviso Legal.
- Intervalo de refresco automático del panel privado.
- Propietario y repositorio de GitHub para actualizaciones.
- Token GitHub opcional si el repositorio es privado.

Los enlaces legales vienen precargados con las URLs oficiales del Gran Castillo de Pedraza y puedes cambiarlos desde ajustes si más adelante hiciera falta.

La contraseña del panel privado se guarda con `password_hash()` usando bcrypt. El campo de contraseña nunca muestra el valor actual y solo cambia si se rellena una nueva contraseña.

## Actualizaciones desde GitHub

El plugin usa `YahnisElsts/plugin-update-checker` instalado por Composer y empaquetado dentro de `vendor/`. No hace falta instalar nada adicional en WordPress.

Configuración por defecto:

- Propietario: `daviidxestrada`
- Repositorio: `pedraza-mahou-reservations`

Para publicar una nueva versión:

1. Actualiza la cabecera `Version` del plugin y la constante `PMR_VERSION` en `pedraza-mahou-reservations.php`.
2. Actualiza `Stable tag` y el changelog en `readme.txt`.
3. Regenera el ZIP del plugin.
4. Haz commit y push a GitHub.
5. Crea una release con tag semántico superior a la versión instalada, por ejemplo `v1.0.6`, adjuntando el asset `pedraza-mahou-reservations.zip`.
6. WordPress detectará la nueva versión desde `Escritorio > Actualizaciones` o desde la pantalla de plugins. PUC ignora releases marcadas como pre-release.

Si el repositorio es privado, configura un token con permiso de lectura del repositorio. Puedes hacerlo de dos formas:

```php
define('PMR_GITHUB_TOKEN', 'github_pat_xxx');
```

También puedes definir el propietario y el repositorio por constantes:

```php
define('PMR_GITHUB_OWNER', 'daviidxestrada');
define('PMR_GITHUB_REPO', 'pedraza-mahou-reservations');
```

Si no usas constantes, puedes guardar estos valores desde `Ajustes > Reservas Pedraza Mahou`. El token queda almacenado en las opciones de WordPress.

## Funcionamiento de reservas

El formulario público permite solicitar una reserva sin pago online. Al enviar una solicitud:

- Se valida nonce, honeypot, fecha, email, teléfono, número de cestas y consentimiento RGPD obligatorio.
- Se aplica rate limit básico por IP.
- Se genera una referencia correlativa con prefijo `A`, comenzando en `A101`.
- Se guarda la reserva en tabla personalizada.
- Se envía email HTML al cliente.
- Se envía email HTML interno al equipo.
- Se muestra al usuario una confirmación con su referencia.

El pago se realiza presencialmente en taquilla el día de la recogida.

## Panel privado

El shortcode `[pedraza_mahou_reservations_admin]` muestra un login propio independiente del login estándar de WordPress.

El panel autenticado permite:

- Abrir directamente la vista de reservas del día actual.
- Ver cuántas cestas quedan por preparar.
- Ver totales de cestas, reservas y reservas completadas según los filtros activos.
- Cambiar rápidamente entre hoy, mañana, todas las fechas o una fecha concreta.
- Filtrar por estado mediante controles rápidos.
- Buscar por referencia, nombre, teléfono o email.
- Ver reservas ordenadas por fecha de creación descendente.
- Actualizar manualmente la tabla.
- Refrescar automáticamente la tabla.
- Marcar reservas como completadas.
- Marcar reservas como canceladas.
- Volver reservas a pendiente.
- Eliminar reservas con confirmación.
- Cerrar sesión.

La sesión privada se mantiene mediante cookie firmada con HMAC, `HttpOnly`, `SameSite=Lax` y `Secure` cuando la web usa HTTPS.

## Seguridad

El plugin aplica:

- Nonces en formularios y acciones AJAX.
- Sanitización de entradas.
- Escape de salidas HTML.
- Queries dinámicas con `$wpdb->prepare()`.
- Validación de email, teléfono, fecha y cantidad.
- Honeypot antispam.
- Rate limit básico de reservas por IP.
- Rate limit básico de login privado por IP.
- Contraseñas privadas con bcrypt.
- Verificación de contraseña con `password_verify()`.
- Cookies firmadas para el panel privado.
- `DONOTCACHEPAGE` y cabeceras no-cache en el panel privado.

## Campos guardados

La tabla personalizada guarda:

- `id`
- `reference`
- `pickup_date`
- `basket_count`
- `observations`
- `full_name`
- `email`
- `phone`
- `rgpd_consent`
- `marketing_consent`
- `status`
- `ip_address`
- `created_at`
- `updated_at`

Estados disponibles:

- `pending`
- `completed`
- `cancelled`

## Archivos principales

```txt
pedraza-mahou-reservations/
├── pedraza-mahou-reservations.php
├── composer.json
├── composer.lock
├── readme.txt
├── includes/
│   ├── class-pmr-activator.php
│   ├── class-pmr-database.php
│   ├── class-pmr-shortcodes.php
│   ├── class-pmr-reservations.php
│   ├── class-pmr-auth.php
│   ├── class-pmr-emails.php
│   ├── class-pmr-admin-settings.php
│   ├── class-pmr-update-checker.php
│   └── class-pmr-assets.php
├── assets/
│   ├── css/
│   │   └── pmr-styles.css
│   └── js/
│       └── pmr-scripts.js
└── README.md
```

## Notas de producción

- Configura SMTP para mejorar la entregabilidad de emails.
- Revisa que los enlaces legales apunten a páginas publicadas.
- Evita cachear la página donde insertes el panel privado.
- Para RGPD, valida el texto legal final con el responsable legal del proyecto antes de publicar.
