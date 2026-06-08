<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Admin_Settings
{
    public const OPTION_NAME = 'pmr_settings';

    public static function init(): void
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_pmr_clear_reservations', [__CLASS__, 'handle_clear_reservations']);
    }

    public static function defaults(): array
    {
        return [
            'private_username' => 'gestor',
            'private_password_hash' => '',
            'internal_email' => get_option('admin_email'),
            'from_name' => get_bloginfo('name') ?: 'Gran Castillo de Pedraza',
            'from_email' => get_option('admin_email'),
            'privacy_url' => 'https://grancastillodepedraza.com/politica-de-privacidad/',
            'cookies_url' => 'https://grancastillodepedraza.com/politica-de-cookies-ue/',
            'legal_url' => 'https://grancastillodepedraza.com/?page_id=137',
            'customer_subject' => 'Solicitud de reserva de cesta picnic recibida',
            'internal_subject' => 'Nueva reserva de cesta picnic Mahou',
            'refresh_interval' => 30,
            'security_protocols_enabled' => 1,
            'github_owner' => defined('PMR_GITHUB_OWNER') ? (string) PMR_GITHUB_OWNER : 'daviidxestrada',
            'github_repo' => defined('PMR_GITHUB_REPO') ? (string) PMR_GITHUB_REPO : 'pedraza-mahou-reservations',
            'github_token' => '',
        ];
    }

    public static function get_settings(): array
    {
        $defaults = self::defaults();
        $settings = get_option(self::OPTION_NAME, []);
        $settings = wp_parse_args(is_array($settings) ? $settings : [], $defaults);

        foreach (['privacy_url', 'cookies_url', 'legal_url'] as $legal_url_key) {
            if (empty($settings[$legal_url_key])) {
                $settings[$legal_url_key] = $defaults[$legal_url_key];
            }
        }

        return $settings;
    }

    public static function add_settings_page(): void
    {
        add_options_page(
            __('Pedraza Mahou Reservations', 'pedraza-mahou-reservations'),
            __('Reservas Pedraza Mahou', 'pedraza-mahou-reservations'),
            'manage_options',
            'pedraza-mahou-reservations',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting(
            'pmr_settings_group',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
                'default' => self::defaults(),
            ]
        );
    }

    public static function sanitize_settings($input): array
    {
        $input = is_array($input) ? $input : [];
        $old = self::get_settings();

        $username = sanitize_user((string) ($input['private_username'] ?? $old['private_username']), true);
        $password = (string) ($input['private_password'] ?? '');
        $github_token = (string) ($input['github_token'] ?? '');
        $internal_email = sanitize_email((string) ($input['internal_email'] ?? ''));
        $from_email = sanitize_email((string) ($input['from_email'] ?? ''));

        $settings = [
            'private_username' => $username !== '' ? $username : $old['private_username'],
            'private_password_hash' => $old['private_password_hash'],
            'internal_email' => is_email($internal_email) ? $internal_email : $old['internal_email'],
            'from_name' => sanitize_text_field((string) ($input['from_name'] ?? $old['from_name'])),
            'from_email' => is_email($from_email) ? $from_email : $old['from_email'],
            'privacy_url' => esc_url_raw((string) ($input['privacy_url'] ?? '')),
            'cookies_url' => esc_url_raw((string) ($input['cookies_url'] ?? '')),
            'legal_url' => esc_url_raw((string) ($input['legal_url'] ?? '')),
            'customer_subject' => sanitize_text_field((string) ($input['customer_subject'] ?? $old['customer_subject'])),
            'internal_subject' => sanitize_text_field((string) ($input['internal_subject'] ?? $old['internal_subject'])),
            'refresh_interval' => min(300, max(5, absint($input['refresh_interval'] ?? $old['refresh_interval']))),
            'security_protocols_enabled' => empty($input['security_protocols_enabled']) ? 0 : 1,
            'github_owner' => self::sanitize_github_owner((string) ($input['github_owner'] ?? $old['github_owner'])),
            'github_repo' => self::sanitize_github_repo((string) ($input['github_repo'] ?? $old['github_repo'])),
            'github_token' => $old['github_token'] ?? '',
        ];

        if ($password !== '') {
            $settings['private_password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if ($github_token !== '') {
            $settings['github_token'] = sanitize_text_field($github_token);
        }

        delete_site_transient('update_plugins');
        PMR_Update_Checker::clear_cache();

        return $settings;
    }

    public static function security_protocols_enabled(): bool
    {
        $settings = self::get_settings();

        return ! empty($settings['security_protocols_enabled']);
    }

    private static function sanitize_github_owner(string $owner): string
    {
        return preg_replace('/[^A-Za-z0-9-]/', '', $owner) ?: '';
    }

    private static function sanitize_github_repo(string $repo): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '', $repo) ?: '';
    }

    public static function handle_clear_reservations(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'pedraza-mahou-reservations'));
        }

        check_admin_referer('pmr_clear_reservations');

        $totals = PMR_Database::get_totals([]);
        $reservation_count = (int) ($totals['total_reservations'] ?? 0);
        $cleared = PMR_Database::clear_reservations();

        $redirect_url = add_query_arg(
            [
                'page' => 'pedraza-mahou-reservations',
                'pmr_clear_result' => $cleared ? 'success' : 'error',
                'pmr_cleared_count' => $cleared ? $reservation_count : 0,
            ],
            admin_url('options-general.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public static function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        $totals = PMR_Database::get_totals([]);
        $reservation_count = (int) ($totals['total_reservations'] ?? 0);
        $clear_result = isset($_GET['pmr_clear_result']) ? sanitize_key((string) wp_unslash($_GET['pmr_clear_result'])) : '';
        $cleared_count = isset($_GET['pmr_cleared_count']) ? absint($_GET['pmr_cleared_count']) : 0;
        $clear_button_attributes = $reservation_count < 1 ? ['disabled' => 'disabled'] : [];
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pedraza Mahou Reservations', 'pedraza-mahou-reservations'); ?></h1>

            <?php if ($clear_result === 'success') : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %d number of deleted reservations */
                                _n('%d reserva eliminada. La siguiente referencia será A101.', '%d reservas eliminadas. La siguiente referencia será A101.', $cleared_count, 'pedraza-mahou-reservations'),
                                $cleared_count
                            )
                        );
                        ?>
                    </p>
                </div>
            <?php elseif ($clear_result === 'error') : ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html__('No se pudieron eliminar las reservas. Revisa los permisos de la base de datos.', 'pedraza-mahou-reservations'); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($settings['private_password_hash'])) : ?>
                <div class="notice notice-warning">
                    <p><?php echo esc_html__('Configura una contraseña para activar el acceso al panel privado de reservas.', 'pedraza-mahou-reservations'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('pmr_settings_group'); ?>

                <h2><?php echo esc_html__('Acceso privado', 'pedraza-mahou-reservations'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="pmr_private_username"><?php echo esc_html__('Usuario del panel privado', 'pedraza-mahou-reservations'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="pmr_private_username" name="<?php echo esc_attr(self::OPTION_NAME); ?>[private_username]" value="<?php echo esc_attr($settings['private_username']); ?>" class="regular-text" autocomplete="off">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmr_private_password"><?php echo esc_html__('Nueva contraseña del panel privado', 'pedraza-mahou-reservations'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="pmr_private_password" name="<?php echo esc_attr(self::OPTION_NAME); ?>[private_password]" value="" class="regular-text" autocomplete="new-password">
                            <p class="description"><?php echo esc_html__('Déjalo vacío para mantener la contraseña actual. Se guarda siempre con bcrypt y nunca se muestra en claro.', 'pedraza-mahou-reservations'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmr_refresh_interval"><?php echo esc_html__('Refresco automático del panel', 'pedraza-mahou-reservations'); ?></label>
                        </th>
                        <td>
                            <input type="number" min="5" max="300" id="pmr_refresh_interval" name="<?php echo esc_attr(self::OPTION_NAME); ?>[refresh_interval]" value="<?php echo esc_attr((string) $settings['refresh_interval']); ?>" class="small-text"> <?php echo esc_html__('segundos', 'pedraza-mahou-reservations'); ?>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Pruebas y seguridad', 'pedraza-mahou-reservations'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Protocolos anti-abuso', 'pedraza-mahou-reservations'); ?></th>
                        <td>
                            <label for="pmr_security_protocols_enabled">
                                <input type="checkbox" id="pmr_security_protocols_enabled" name="<?php echo esc_attr(self::OPTION_NAME); ?>[security_protocols_enabled]" value="1" <?php checked(! empty($settings['security_protocols_enabled'])); ?>>
                                <?php echo esc_html__('Activar límites de seguridad en formularios y login privado', 'pedraza-mahou-reservations'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('Déjalo activado en producción. Desactívalo solo durante pruebas para evitar bloqueos por enviar muchas reservas o intentos de acceso seguidos. Nonces, validaciones, sanitización y RGPD siguen activos siempre.', 'pedraza-mahou-reservations'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Emails', 'pedraza-mahou-reservations'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pmr_internal_email"><?php echo esc_html__('Email interno de recepción', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="email" id="pmr_internal_email" name="<?php echo esc_attr(self::OPTION_NAME); ?>[internal_email]" value="<?php echo esc_attr($settings['internal_email']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_from_name"><?php echo esc_html__('Nombre del remitente', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="text" id="pmr_from_name" name="<?php echo esc_attr(self::OPTION_NAME); ?>[from_name]" value="<?php echo esc_attr($settings['from_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_from_email"><?php echo esc_html__('Email del remitente', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="email" id="pmr_from_email" name="<?php echo esc_attr(self::OPTION_NAME); ?>[from_email]" value="<?php echo esc_attr($settings['from_email']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_customer_subject"><?php echo esc_html__('Asunto email cliente', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="text" id="pmr_customer_subject" name="<?php echo esc_attr(self::OPTION_NAME); ?>[customer_subject]" value="<?php echo esc_attr($settings['customer_subject']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_internal_subject"><?php echo esc_html__('Asunto email interno', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="text" id="pmr_internal_subject" name="<?php echo esc_attr(self::OPTION_NAME); ?>[internal_subject]" value="<?php echo esc_attr($settings['internal_subject']); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Enlaces legales', 'pedraza-mahou-reservations'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pmr_privacy_url"><?php echo esc_html__('Política de Privacidad', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="url" id="pmr_privacy_url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[privacy_url]" value="<?php echo esc_url($settings['privacy_url']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_cookies_url"><?php echo esc_html__('Política de Cookies', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="url" id="pmr_cookies_url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[cookies_url]" value="<?php echo esc_url($settings['cookies_url']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_legal_url"><?php echo esc_html__('Aviso Legal', 'pedraza-mahou-reservations'); ?></label></th>
                        <td><input type="url" id="pmr_legal_url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[legal_url]" value="<?php echo esc_url($settings['legal_url']); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Actualizaciones desde GitHub', 'pedraza-mahou-reservations'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pmr_github_owner"><?php echo esc_html__('Propietario GitHub', 'pedraza-mahou-reservations'); ?></label></th>
                        <td>
                            <input type="text" id="pmr_github_owner" name="<?php echo esc_attr(self::OPTION_NAME); ?>[github_owner]" value="<?php echo esc_attr($settings['github_owner']); ?>" class="regular-text" autocomplete="off">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_github_repo"><?php echo esc_html__('Repositorio GitHub', 'pedraza-mahou-reservations'); ?></label></th>
                        <td>
                            <input type="text" id="pmr_github_repo" name="<?php echo esc_attr(self::OPTION_NAME); ?>[github_repo]" value="<?php echo esc_attr($settings['github_repo']); ?>" class="regular-text" autocomplete="off">
                            <p class="description"><?php echo esc_html__('El comprobador usa la última release publicada en GitHub. Ejemplo de tag: v1.0.1.', 'pedraza-mahou-reservations'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pmr_github_token"><?php echo esc_html__('Token GitHub opcional', 'pedraza-mahou-reservations'); ?></label></th>
                        <td>
                            <input type="password" id="pmr_github_token" name="<?php echo esc_attr(self::OPTION_NAME); ?>[github_token]" value="" class="regular-text" autocomplete="off">
                            <p class="description">
                                <?php
                                if (defined('PMR_GITHUB_TOKEN') && PMR_GITHUB_TOKEN) {
                                    echo esc_html__('Se está usando la constante PMR_GITHUB_TOKEN definida en wp-config.php. Este campo puede quedar vacío.', 'pedraza-mahou-reservations');
                                } elseif (! empty($settings['github_token'])) {
                                    echo esc_html__('Token guardado. Déjalo vacío para mantenerlo. Necesario si el repositorio es privado.', 'pedraza-mahou-reservations');
                                } else {
                                    echo esc_html__('Déjalo vacío si el repositorio es público. Para repos privados, usa un token con permiso de lectura del repositorio.', 'pedraza-mahou-reservations');
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php echo esc_html__('Herramientas de pruebas', 'pedraza-mahou-reservations'); ?></h2>
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %d current reservation count */
                        _n('Actualmente hay %d reserva guardada.', 'Actualmente hay %d reservas guardadas.', $reservation_count, 'pedraza-mahou-reservations'),
                        $reservation_count
                    )
                );
                ?>
            </p>
            <p class="description">
                <?php echo esc_html__('Vaciar reservas elimina permanentemente todas las reservas pendientes, completadas y canceladas. Conserva todos los ajustes del plugin y reinicia las referencias para que la siguiente reserva sea A101.', 'pedraza-mahou-reservations'); ?>
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('¿Seguro que quieres eliminar permanentemente todas las reservas? Esta acción no se puede deshacer.', 'pedraza-mahou-reservations')); ?>');">
                <input type="hidden" name="action" value="pmr_clear_reservations">
                <?php wp_nonce_field('pmr_clear_reservations'); ?>
                <?php
                submit_button(
                    __('Vaciar todas las reservas', 'pedraza-mahou-reservations'),
                    'delete',
                    'submit',
                    false,
                    $clear_button_attributes
                );
                ?>
            </form>
        </div>
        <?php
    }
}
