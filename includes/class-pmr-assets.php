<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Assets
{
    private static bool $registered = false;

    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
    }

    public static function register_assets(): void
    {
        if (self::$registered) {
            return;
        }

        wp_register_style(
            'pmr-fonts',
            'https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700&display=swap',
            [],
            null
        );

        wp_register_style(
            'pmr-styles',
            PMR_PLUGIN_URL . 'assets/css/pmr-styles.css',
            ['pmr-flatpickr', 'pmr-intl-tel-input'],
            PMR_VERSION
        );

        wp_register_style(
            'pmr-intl-tel-input',
            PMR_PLUGIN_URL . 'assets/vendor/intl-tel-input/css/intlTelInput.min.css',
            [],
            '29.0.3'
        );

        wp_register_style(
            'pmr-flatpickr',
            PMR_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.css',
            [],
            '4.6.13'
        );

        wp_register_script(
            'pmr-flatpickr',
            PMR_PLUGIN_URL . 'assets/vendor/flatpickr/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );

        wp_register_script(
            'pmr-flatpickr-es',
            PMR_PLUGIN_URL . 'assets/vendor/flatpickr/l10n/es.js',
            ['pmr-flatpickr'],
            '4.6.13',
            true
        );

        wp_register_script(
            'pmr-scripts',
            PMR_PLUGIN_URL . 'assets/js/pmr-scripts.js',
            ['pmr-flatpickr-es', 'pmr-intl-tel-input'],
            PMR_VERSION,
            true
        );

        wp_register_script(
            'pmr-intl-tel-input',
            PMR_PLUGIN_URL . 'assets/vendor/intl-tel-input/js/intlTelInputWithUtils.min.js',
            [],
            '29.0.3',
            true
        );

        wp_localize_script(
            'pmr-scripts',
            'pmrReservations',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'i18n' => [
                    'sending' => __('Enviando...', 'pedraza-mahou-reservations'),
                    'loading' => __('Cargando reservas...', 'pedraza-mahou-reservations'),
                    'updatedAt' => __('Actualizado a las', 'pedraza-mahou-reservations'),
                    'submit' => __('Enviar reserva', 'pedraza-mahou-reservations'),
                    'login' => __('Acceder', 'pedraza-mahou-reservations'),
                    'error' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'pedraza-mahou-reservations'),
                    'deleteConfirm' => __('¿Seguro que quieres eliminar esta reserva? Esta acción no se puede deshacer.', 'pedraza-mahou-reservations'),
                    'logoutConfirm' => __('¿Cerrar sesión del panel privado?', 'pedraza-mahou-reservations'),
                    'pickupDate' => __('Fecha de recogida', 'pedraza-mahou-reservations'),
                    'invalidPhone' => __('Introduce un teléfono válido para el país seleccionado.', 'pedraza-mahou-reservations'),
                    'phoneCountry' => __('País del teléfono', 'pedraza-mahou-reservations'),
                    'phoneSearch' => __('Buscar país', 'pedraza-mahou-reservations'),
                    'phoneNoResults' => __('No se encontraron países', 'pedraza-mahou-reservations'),
                ],
            ]
        );

        self::$registered = true;
    }

    public static function enqueue(): void
    {
        self::register_assets();

        wp_enqueue_style('pmr-fonts');
        wp_enqueue_style('pmr-styles');
        wp_enqueue_script('pmr-scripts');
    }
}
