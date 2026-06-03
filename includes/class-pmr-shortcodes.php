<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Shortcodes
{
    public static function init(): void
    {
        add_shortcode('pedraza_mahou_reservations', [__CLASS__, 'render_public_form']);
        add_shortcode('pedraza_mahou_reservations_admin', [__CLASS__, 'render_admin_panel']);
    }

    public static function render_public_form(): string
    {
        PMR_Assets::enqueue();

        $settings = PMR_Admin_Settings::get_settings();
        $uid = uniqid('pmr-', false);
        $today = current_time('Y-m-d');
        $privacy_link = self::legal_link($settings['privacy_url'], __('Política de Privacidad', 'pedraza-mahou-reservations'));
        $cookies_link = self::legal_link($settings['cookies_url'], __('Política de Cookies', 'pedraza-mahou-reservations'));
        $legal_link = self::legal_link($settings['legal_url'], __('Aviso Legal', 'pedraza-mahou-reservations'));

        ob_start();
        ?>
        <div class="pmr-reservations" data-pmr-public>
            <div class="pmr-card">
                <header class="pmr-header">
                    <h2><?php echo esc_html__('Reserva tu cesta picnic', 'pedraza-mahou-reservations'); ?></h2>
                    <p><?php echo esc_html__('Disfruta de la experiencia completa en el Gran Castillo de Pedraza reservando tu cesta picnic para acompañar tu visita o concierto.', 'pedraza-mahou-reservations'); ?></p>
                    <p class="pmr-price"><?php echo esc_html__('Precio: 15 € IVA incluido por cesta.', 'pedraza-mahou-reservations'); ?></p>
                    <p><?php echo esc_html__('La reserva se realiza sin pago online. El importe se abonará el día de la recogida en taquilla, mediante efectivo o tarjeta.', 'pedraza-mahou-reservations'); ?></p>
                    <p><?php echo esc_html__('Las reservas están sujetas a disponibilidad.', 'pedraza-mahou-reservations'); ?></p>
                </header>

                <form class="pmr-form" data-pmr-public-form novalidate>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('pmr_public_reservation')); ?>">
                    <div class="pmr-honeypot" aria-hidden="true">
                        <label for="<?php echo esc_attr($uid); ?>-website"><?php echo esc_html__('Web', 'pedraza-mahou-reservations'); ?></label>
                        <input type="text" id="<?php echo esc_attr($uid); ?>-website" name="pmr_website" tabindex="-1" autocomplete="off">
                    </div>

                    <section class="pmr-section">
                        <h3><?php echo esc_html__('Bloque 1 · Reserva', 'pedraza-mahou-reservations'); ?></h3>
                        <div class="pmr-grid">
                            <div class="pmr-field">
                                <label for="<?php echo esc_attr($uid); ?>-pickup-date"><?php echo esc_html__('Fecha de recogida', 'pedraza-mahou-reservations'); ?> <span aria-hidden="true">*</span></label>
                                <input type="date" id="<?php echo esc_attr($uid); ?>-pickup-date" name="pickup_date" min="<?php echo esc_attr($today); ?>" required>
                            </div>
                            <div class="pmr-field">
                                <label for="<?php echo esc_attr($uid); ?>-basket-count"><?php echo esc_html__('Número de cestas', 'pedraza-mahou-reservations'); ?> <span aria-hidden="true">*</span></label>
                                <div class="pmr-quantity" data-pmr-quantity>
                                    <button type="button" class="pmr-quantity__button" data-pmr-quantity-minus aria-label="<?php echo esc_attr__('Restar cesta', 'pedraza-mahou-reservations'); ?>">-</button>
                                    <input type="number" id="<?php echo esc_attr($uid); ?>-basket-count" name="basket_count" min="1" max="50" value="1" required>
                                    <button type="button" class="pmr-quantity__button" data-pmr-quantity-plus aria-label="<?php echo esc_attr__('Añadir cesta', 'pedraza-mahou-reservations'); ?>">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="pmr-field">
                            <label for="<?php echo esc_attr($uid); ?>-observations"><?php echo esc_html__('Observaciones', 'pedraza-mahou-reservations'); ?></label>
                            <textarea id="<?php echo esc_attr($uid); ?>-observations" name="observations" rows="4" maxlength="2000" placeholder="<?php echo esc_attr__('Alergias, indicaciones o comentarios adicionales.', 'pedraza-mahou-reservations'); ?>"></textarea>
                        </div>
                    </section>

                    <section class="pmr-section">
                        <h3><?php echo esc_html__('Bloque 2 · Datos de contacto', 'pedraza-mahou-reservations'); ?></h3>
                        <div class="pmr-grid">
                            <div class="pmr-field">
                                <label for="<?php echo esc_attr($uid); ?>-full-name"><?php echo esc_html__('Nombre y apellidos', 'pedraza-mahou-reservations'); ?> <span aria-hidden="true">*</span></label>
                                <input type="text" id="<?php echo esc_attr($uid); ?>-full-name" name="full_name" maxlength="190" autocomplete="name" required>
                            </div>
                            <div class="pmr-field">
                                <label for="<?php echo esc_attr($uid); ?>-email"><?php echo esc_html__('Correo electrónico', 'pedraza-mahou-reservations'); ?> <span aria-hidden="true">*</span></label>
                                <input type="email" id="<?php echo esc_attr($uid); ?>-email" name="email" maxlength="190" autocomplete="email" required>
                            </div>
                            <div class="pmr-field">
                                <label for="<?php echo esc_attr($uid); ?>-phone"><?php echo esc_html__('Teléfono de contacto', 'pedraza-mahou-reservations'); ?> <span aria-hidden="true">*</span></label>
                                <input type="tel" id="<?php echo esc_attr($uid); ?>-phone" name="phone" maxlength="25" autocomplete="tel" required>
                            </div>
                        </div>
                    </section>

                    <section class="pmr-section pmr-legal-section">
                        <h3><?php echo esc_html__('Bloque 3 · Protección de datos y consentimientos', 'pedraza-mahou-reservations'); ?></h3>
                        <label class="pmr-checkbox">
                            <input type="checkbox" name="rgpd_consent" value="1" required>
                            <span><?php echo wp_kses_post(sprintf(__('He leído y acepto la %s y el tratamiento de mis datos para la gestión de esta reserva.', 'pedraza-mahou-reservations'), $privacy_link)); ?></span>
                        </label>
                        <label class="pmr-checkbox">
                            <input type="checkbox" name="marketing_consent" value="1">
                            <span><?php echo esc_html__('Deseo recibir información sobre la programación, actividades y eventos del Gran Castillo de Pedraza y de otros espacios culturales y proyectos gestionados por Wonderland Group.', 'pedraza-mahou-reservations'); ?></span>
                        </label>

                        <div class="pmr-legal-text">
                            <h4><?php echo esc_html__('Información básica sobre protección de datos', 'pedraza-mahou-reservations'); ?></h4>
                            <p><strong><?php echo esc_html__('Responsable del tratamiento:', 'pedraza-mahou-reservations'); ?></strong> <?php echo esc_html__('Wonderland Group / entidad gestora del Gran Castillo de Pedraza.', 'pedraza-mahou-reservations'); ?></p>
                            <p><strong><?php echo esc_html__('Finalidad:', 'pedraza-mahou-reservations'); ?></strong> <?php echo esc_html__('Gestionar la solicitud de reserva de cesta picnic y, en caso de autorización expresa, enviar información comercial sobre programación cultural, espectáculos y actividades relacionadas.', 'pedraza-mahou-reservations'); ?></p>
                            <p><strong><?php echo esc_html__('Legitimación:', 'pedraza-mahou-reservations'); ?></strong> <?php echo esc_html__('Consentimiento del interesado y ejecución de la solicitud de reserva.', 'pedraza-mahou-reservations'); ?></p>
                            <p><strong><?php echo esc_html__('Destinatarios:', 'pedraza-mahou-reservations'); ?></strong> <?php echo esc_html__('No se cederán datos a terceros salvo obligación legal.', 'pedraza-mahou-reservations'); ?></p>
                            <p><strong><?php echo esc_html__('Derechos:', 'pedraza-mahou-reservations'); ?></strong> <?php echo esc_html__('Puedes acceder, rectificar y suprimir tus datos, así como ejercer otros derechos en materia de protección de datos mediante comunicación al correo electrónico indicado en la Política de Privacidad.', 'pedraza-mahou-reservations'); ?></p>
                            <p><strong><?php echo esc_html__('Información adicional:', 'pedraza-mahou-reservations'); ?></strong> <?php echo wp_kses_post(sprintf(__('Puedes consultar la información completa sobre protección de datos en nuestra %s.', 'pedraza-mahou-reservations'), $privacy_link)); ?></p>
                        </div>

                        <div class="pmr-legal-text">
                            <h4><?php echo esc_html__('Condiciones de reserva', 'pedraza-mahou-reservations'); ?></h4>
                            <p><?php echo esc_html__('La presente solicitud constituye únicamente una reserva previa de cesta picnic y no implica pago online ni confirmación automática de disponibilidad.', 'pedraza-mahou-reservations'); ?></p>
                            <p><?php echo esc_html__('El importe de la cesta será abonado presencialmente en taquilla el día de la recogida.', 'pedraza-mahou-reservations'); ?></p>
                            <p><?php echo esc_html__('Las reservas estarán sujetas a disponibilidad y podrán ser verificadas mediante el número de referencia generado y el nombre indicado en el formulario.', 'pedraza-mahou-reservations'); ?></p>
                            <p><?php echo esc_html__('La organización se reserva el derecho de cancelar o modificar reservas por causas organizativas o de disponibilidad, informando previamente al usuario a través de los datos de contacto facilitados.', 'pedraza-mahou-reservations'); ?></p>
                            <p><strong><?php echo esc_html__('Precio por cesta: 15 € IVA incluido.', 'pedraza-mahou-reservations'); ?></strong></p>
                            <p class="pmr-legal-links"><?php echo wp_kses_post($privacy_link); ?> · <?php echo wp_kses_post($cookies_link); ?> · <?php echo wp_kses_post($legal_link); ?></p>
                        </div>
                    </section>

                    <div class="pmr-actions">
                        <button type="submit" class="pmr-submit"><?php echo esc_html__('Enviar reserva', 'pedraza-mahou-reservations'); ?></button>
                    </div>

                    <div class="pmr-message" data-pmr-message role="status" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public static function render_admin_panel(): string
    {
        PMR_Assets::enqueue();

        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        if (! headers_sent()) {
            nocache_headers();
        }

        $settings = PMR_Admin_Settings::get_settings();
        $nonce = wp_create_nonce('pmr_admin_panel');

        if (! PMR_Auth::is_authenticated()) {
            return self::render_login_form($nonce, empty($settings['private_password_hash']));
        }

        $reservations = PMR_Database::get_reservations([]);
        $totals = PMR_Database::get_totals([]);

        ob_start();
        ?>
        <div class="pmr-admin" data-pmr-admin data-nonce="<?php echo esc_attr($nonce); ?>" data-refresh-interval="<?php echo esc_attr((string) $settings['refresh_interval']); ?>">
            <div class="pmr-admin__top">
                <div>
                    <h2><?php echo esc_html__('Panel de reservas', 'pedraza-mahou-reservations'); ?></h2>
                    <p><?php echo esc_html__('Gestión privada de reservas de cestas picnic Mahou.', 'pedraza-mahou-reservations'); ?></p>
                </div>
                <button type="button" class="pmr-secondary-button" data-pmr-logout><?php echo esc_html__('Cerrar sesión', 'pedraza-mahou-reservations'); ?></button>
            </div>

            <div class="pmr-admin-filters">
                <div class="pmr-field">
                    <label for="pmr-admin-pickup-date"><?php echo esc_html__('Filtrar por fecha de recogida', 'pedraza-mahou-reservations'); ?></label>
                    <input type="date" id="pmr-admin-pickup-date" data-pmr-filter-date>
                </div>
                <div class="pmr-field">
                    <label for="pmr-admin-status"><?php echo esc_html__('Filtrar por estado', 'pedraza-mahou-reservations'); ?></label>
                    <select id="pmr-admin-status" data-pmr-filter-status>
                        <option value=""><?php echo esc_html__('Todos los estados', 'pedraza-mahou-reservations'); ?></option>
                        <option value="pending"><?php echo esc_html__('Pendiente', 'pedraza-mahou-reservations'); ?></option>
                        <option value="completed"><?php echo esc_html__('Completada', 'pedraza-mahou-reservations'); ?></option>
                        <option value="cancelled"><?php echo esc_html__('Cancelada', 'pedraza-mahou-reservations'); ?></option>
                    </select>
                </div>
                <div class="pmr-admin-filters__actions">
                    <button type="button" class="pmr-secondary-button" data-pmr-clear-filters><?php echo esc_html__('Limpiar filtros', 'pedraza-mahou-reservations'); ?></button>
                    <button type="button" class="pmr-submit" data-pmr-refresh><?php echo esc_html__('Actualizar', 'pedraza-mahou-reservations'); ?></button>
                </div>
            </div>

            <div class="pmr-admin-message" data-pmr-admin-message role="status" aria-live="polite"></div>
            <div class="pmr-admin-table" data-pmr-admin-table>
                <?php echo PMR_Reservations::render_admin_table($reservations, $totals); ?>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_login_form(string $nonce, bool $needs_password): string
    {
        ob_start();
        ?>
        <div class="pmr-login" data-pmr-login>
            <div class="pmr-login__box">
                <h2><?php echo esc_html__('Acceso privado', 'pedraza-mahou-reservations'); ?></h2>
                <p><?php echo esc_html__('Introduce las credenciales configuradas para gestionar las reservas.', 'pedraza-mahou-reservations'); ?></p>

                <?php if ($needs_password) : ?>
                    <div class="pmr-message pmr-message--warning">
                        <?php echo esc_html__('El panel privado todavía no tiene contraseña configurada. Define una desde Ajustes > Reservas Pedraza Mahou.', 'pedraza-mahou-reservations'); ?>
                    </div>
                <?php endif; ?>

                <form class="pmr-login-form" data-pmr-login-form autocomplete="off">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <div class="pmr-field">
                        <label for="pmr-login-username"><?php echo esc_html__('Usuario', 'pedraza-mahou-reservations'); ?></label>
                        <input type="text" id="pmr-login-username" name="username" autocomplete="username" required>
                    </div>
                    <div class="pmr-field">
                        <label for="pmr-login-password"><?php echo esc_html__('Contraseña', 'pedraza-mahou-reservations'); ?></label>
                        <input type="password" id="pmr-login-password" name="password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="pmr-submit"><?php echo esc_html__('Acceder', 'pedraza-mahou-reservations'); ?></button>
                    <div class="pmr-message" data-pmr-login-message role="status" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function legal_link(string $url, string $label): string
    {
        if ($url === '') {
            return esc_html($label);
        }

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url($url),
            esc_html($label)
        );
    }
}
