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
            [],
            PMR_VERSION
        );

        wp_register_script(
            'pmr-scripts',
            PMR_PLUGIN_URL . 'assets/js/pmr-scripts.js',
            [],
            PMR_VERSION,
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
                    'submit' => __('Enviar reserva', 'pedraza-mahou-reservations'),
                    'login' => __('Acceder', 'pedraza-mahou-reservations'),
                    'error' => __('Ha ocurrido un error. Inténtalo de nuevo.', 'pedraza-mahou-reservations'),
                    'deleteConfirm' => __('¿Seguro que quieres eliminar esta reserva? Esta acción no se puede deshacer.', 'pedraza-mahou-reservations'),
                    'logoutConfirm' => __('¿Cerrar sesión del panel privado?', 'pedraza-mahou-reservations'),
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
