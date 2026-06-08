=== Pedraza Mahou Reservations ===
Contributors: cloudari
Tags: reservations, bookings, elementor, picnic, mahou
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: v1.0.26
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestor de reservas de cestas picnic Mahou para el Gran Castillo de Pedraza.

== Description ==

Pedraza Mahou Reservations permite gestionar reservas de cestas picnic Mahou desde WordPress usando shortcodes compatibles con Elementor.

Incluye formulario publico, panel privado de reservas, emails transaccionales, tabla personalizada de base de datos y actualizaciones desde GitHub mediante YahnisElsts/plugin-update-checker.

== Installation ==

1. Sube el ZIP del plugin desde Plugins > Anadir nuevo > Subir plugin.
2. Activa el plugin.
3. Configura los ajustes desde Ajustes > Reservas Pedraza Mahou.
4. Inserta los shortcodes en tus paginas Elementor.

== Frequently Asked Questions ==

= Que shortcodes incluye? =

Formulario publico:

[pedraza_mahou_reservations]

Panel privado:

[pedraza_mahou_reservations_admin]

= Hay pago online? =

No. La reserva se realiza sin pago online y el importe se abona presencialmente en taquilla.

== Changelog ==

= 1.0.26 =
* Sustituye el mensaje de éxito del formulario por un popup centrado con la referencia destacada y resumen de la reserva.

= 1.0.25 =
* Mejora el espaciado del icono de búsqueda en el selector de país del teléfono.

= 1.0.24 =
* Añade un botón al email interno para abrir directamente el gestor privado de reservas.

= 1.0.23 =
* Actualiza el contenido visible del pack aperitivo en la página de reserva.

= 1.0.22 =
* Evita fuentes condensadas en los títulos de email cuando League Spartan no está disponible.
* Fija un peso y espaciado consistentes para los encabezados transaccionales.

= 1.0.21 =
* Separa la carga de Google Fonts de los estilos base para evitar que Gmail descarte las fuentes alternativas.
* Mejora las familias tipográficas alternativas en Gmail, iPhone, Outlook y otros clientes sin soporte de fuentes web.

= 1.0.20 =
* Corrige el reinicio del selector internacional de teléfono tras enviar una reserva.
* Evita que un fallo de limpieza visual oculte la confirmación de una reserva ya guardada.

= 1.0.19 =
* Elimina el indicador visual de última actualización del gestor para simplificar la interfaz.

= 1.0.18 =
* Añade separación responsive entre el número de cestas y las observaciones.

= 1.0.17 =
* Mejora el espaciado y tamaño de la flecha del selector internacional de teléfono.
* Añade el placeholder "Tu teléfono".

= 1.0.16 =
* Actualiza el contenido incluido en la cesta: cervezas Mahou, aceitunas, patatas fritas y mini fuet.

= 1.0.15 =
* Añade selector internacional de teléfono con países, banderas, prefijos y validación E.164.
* Marca visualmente todos los campos obligatorios con el rojo del sistema de diseño.
* Mejora las tarjetas del gestor en móvil y muestra siempre teléfono, email y observaciones.
* Añade iconos Lucide a los datos y acciones del gestor, aislados frente a Elementor y el tema.
* Cambia "Total orientativo" por "Total a pagar" y elimina el consentimiento comercial del email operativo.
* Intenta cargar League Spartan y Montserrat en clientes de correo compatibles, conservando fuentes de respaldo.

= 1.0.14 =
* Centra y aísla el icono Lucide del selector de fecha frente a estilos de Elementor y del tema.
* Añade clases Lucide explícitas a los iconos SVG y utiliza chevrons Lucide en el calendario.
* Refuerza los estilos únicos de iconos públicos con reglas específicas.

= 1.0.13 =
* Corrige el botón para vaciar reservas, que aparecía desactivado aunque hubiera reservas guardadas.

= 1.0.12 =
* Añade un calendario personalizado en español, responsive y adaptado al sistema visual del castillo.
* Empaqueta Flatpickr localmente para no depender de servicios externos durante la reserva.
* Rediseña los emails de cliente y equipo con una plantilla responsive y coherente con la web.

= 1.0.11 =
* Añade una herramienta segura en los ajustes de WordPress para vaciar todas las reservas de prueba.
* Protege la limpieza con permisos de administrador, nonce y confirmación.
* Reinicia las referencias para que la siguiente reserva después de limpiar sea A101.

= 1.0.10 =
* Sustituye filtros de fecha y estado por dos listas automáticas: Por preparar y Completadas.
* Mueve inmediatamente las reservas entre ambas listas al completar o recuperar.
* Carga todas las reservas pendientes ordenadas por fecha de recogida.
* Elimina contadores con iconos y simplifica todavía más la experiencia móvil.

= 1.0.9 =
* Simplifica el gestor para facilitar su uso diario, especialmente desde móvil.
* Reduce el resumen a cestas por preparar y reservas del filtro.
* Sustituye el filtro visual de estados por un desplegable sencillo.
* Corrige posiciones y tamaños de iconos afectados por estilos externos.
* Aísla botones, hovers y controles frente a estilos de WordPress, Elementor y el tema.

= 1.0.8 =
* Rediseña el panel privado y su login con el sistema visual de Reserva tu cesta.
* Muestra por defecto las reservas de hoy y añade filtros rápidos para fecha y estado.
* Añade búsqueda por referencia, nombre, teléfono o email.
* Destaca cestas por preparar, reservas pendientes, totales y completadas.
* Reorganiza la tabla para facilitar el trabajo diario en escritorio y móvil.

= 1.0.7 =
* Fuerza el color hover de los enlaces del shortcode a azul oscuro.
* Cambia el icono de recogida por una botella estilo cerveza.

= 1.0.6 =
* Añade enlaces legales oficiales como valores por defecto y fallback.
* Corrige el wrapping del texto destacado en el bloque de pago.
* Cambia el icono de recogida por una mano en SVG inline.

= 1.0.5 =
* Ajusta el encuadre visual de la imagen para integrarla mejor en la columna izquierda sin recortes.

= 1.0.4 =
* Mejora los iconos del shortcode publico usando SVG inline estilo Lucide.
* Mueve la imagen de la cesta a una posicion superior y usa la imagen oficial sin recorte.

= 1.0.3 =
* Migra el sistema de actualizaciones a YahnisElsts/plugin-update-checker.

= 1.0.2 =
* Actualiza el autor del plugin a Cloudari.

= 1.0.1 =
* Rediseña el shortcode publico como pagina completa de Reserva tu cesta.

= 1.0.0 =
* Version inicial.
