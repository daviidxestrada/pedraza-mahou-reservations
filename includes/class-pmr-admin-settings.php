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
    }

    public static function defaults(): array
    {
        return [
            'private_username' => 'gestor',
            'private_password_hash' => '',
            'internal_email' => get_option('admin_email'),
            'from_name' => get_bloginfo('name') ?: 'Gran Castillo de Pedraza',
            'from_email' => get_option('admin_email'),
            'privacy_url' => '',
            'cookies_url' => '',
            'legal_url' => '',
            'customer_subject' => 'Solicitud de reserva de cesta picnic recibida',
            'internal_subject' => 'Nueva reserva de cesta picnic Mahou',
            'refresh_interval' => 30,
            'github_owner' => defined('PMR_GITHUB_OWNER') ? (string) PMR_GITHUB_OWNER : 'daviidxestrada',
            'github_repo' => defined('PMR_GITHUB_REPO') ? (string) PMR_GITHUB_REPO : 'pedraza-mahou-reservations',
            'github_token' => '',
        ];
    }

    public static function get_settings(): array
    {
        $settings = get_option(self::OPTION_NAME, []);

        return wp_parse_args(is_array($settings) ? $settings : [], self::defaults());
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

    private static function sanitize_github_owner(string $owner): string
    {
        return preg_replace('/[^A-Za-z0-9-]/', '', $owner) ?: '';
    }

    private static function sanitize_github_repo(string $repo): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '', $repo) ?: '';
    }

    public static function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pedraza Mahou Reservations', 'pedraza-mahou-reservations'); ?></h1>

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
        </div>
        <?php
    }
}
