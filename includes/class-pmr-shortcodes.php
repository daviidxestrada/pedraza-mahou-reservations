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
        <section class="pmr-reservations pmr-reservation-page" data-pmr-public>
            <div class="pmr-page-pattern" aria-hidden="true"></div>
            <div class="pmr-page-inner">
                <div class="pmr-page-grid">
                    <aside class="pmr-page-copy">
                        <h1><?php echo esc_html__('Reserva tu cesta', 'pedraza-mahou-reservations'); ?></h1>
                        <p class="pmr-page-subtitle"><?php echo esc_html__('El castillo también se saborea', 'pedraza-mahou-reservations'); ?></p>

                        <div class="pmr-page-intro">
                            <p><?php echo esc_html__('Completa tu visita al Gran Castillo de Pedraza con una experiencia gastronómica única. Prepara tu pausa perfecta entre murallas cargadas de historia y unas vistas inigualables.', 'pedraza-mahou-reservations'); ?></p>
                            <p><?php echo esc_html__('Al reservar tu cesta, te garantizas tener todo listo a tu llegada. Solo tendrás que recogerla, elegir tu rincón favorito y disfrutar del momento.', 'pedraza-mahou-reservations'); ?></p>
                        </div>

                        <figure class="pmr-page-image">
                            <img src="https://grancastillodepedraza.com/wp-content/uploads/2026/05/Cesta.jpg" width="1200" height="900" alt="<?php echo esc_attr__('Cesta de picnic Mahou', 'pedraza-mahou-reservations'); ?>" loading="eager">
                        </figure>

                        <div class="pmr-info-card">
                            <h3><?php echo self::svg_icon('shopping-basket', 'pmr-icon pmr-icon--red'); ?><?php echo esc_html__('¿Qué incluye La Cesta del Castillo?', 'pedraza-mahou-reservations'); ?></h3>
                            <ul class="pmr-feature-list">
                                <li><?php echo self::svg_icon('check', 'pmr-icon pmr-icon--check'); ?><span><?php echo esc_html__('2 Cervezas Mahou bien frías (o alternativa sin alcohol)', 'pedraza-mahou-reservations'); ?></span></li>
                                <li><?php echo self::svg_icon('check', 'pmr-icon pmr-icon--check'); ?><span><?php echo esc_html__('Aceitunas', 'pedraza-mahou-reservations'); ?></span></li>
                                <li><?php echo self::svg_icon('check', 'pmr-icon pmr-icon--check'); ?><span><?php echo esc_html__('Patatas fritas', 'pedraza-mahou-reservations'); ?></span></li>
                                <li><?php echo self::svg_icon('check', 'pmr-icon pmr-icon--check'); ?><span><?php echo esc_html__('Mini fuet', 'pedraza-mahou-reservations'); ?></span></li>
                            </ul>
                        </div>

                        <div class="pmr-price-card">
                            <span class="pmr-price-icon" aria-hidden="true"><?php echo self::svg_icon('tag', 'pmr-icon pmr-icon--gold'); ?></span>
                            <div>
                                <span><?php echo esc_html__('Precio por cesta', 'pedraza-mahou-reservations'); ?></span>
                                <strong><?php echo esc_html__('15 €', 'pedraza-mahou-reservations'); ?></strong>
                            </div>
                            <p><?php echo esc_html__('IVA incluido', 'pedraza-mahou-reservations'); ?></p>
                        </div>

                        <div class="pmr-info-card">
                            <h3><?php echo self::svg_icon('credit-card', 'pmr-icon pmr-icon--red'); ?><?php echo esc_html__('Pago', 'pedraza-mahou-reservations'); ?></h3>
                            <ul class="pmr-feature-list">
                                <li><?php echo self::svg_icon('check-circle-2', 'pmr-icon pmr-icon--check'); ?><span><?php echo wp_kses_post(__('La reserva se realiza <strong>sin pago online</strong>.', 'pedraza-mahou-reservations')); ?></span></li>
                                <li><?php echo self::svg_icon('check-circle-2', 'pmr-icon pmr-icon--check'); ?><span><?php echo wp_kses_post(__('El importe se abonará el día de la recogida en taquilla, mediante <strong>efectivo o tarjeta</strong>.', 'pedraza-mahou-reservations')); ?></span></li>
                                <li><?php echo self::svg_icon('check-circle-2', 'pmr-icon pmr-icon--check'); ?><span><?php echo esc_html__('Pago presencial en taquilla del castillo.', 'pedraza-mahou-reservations'); ?></span></li>
                            </ul>
                        </div>

                        <div class="pmr-info-card">
                            <h3><?php echo self::svg_icon('list-checks', 'pmr-icon pmr-icon--red'); ?><?php echo esc_html__('Cómo funciona', 'pedraza-mahou-reservations'); ?></h3>
                            <ol class="pmr-steps">
                                <li><span>1</span><?php echo esc_html__('Selecciona la fecha de recogida.', 'pedraza-mahou-reservations'); ?></li>
                                <li><span>2</span><?php echo esc_html__('Indica el número de cestas.', 'pedraza-mahou-reservations'); ?></li>
                                <li><span>3</span><?php echo esc_html__('Rellena tus datos de contacto.', 'pedraza-mahou-reservations'); ?></li>
                                <li><span>4</span><?php echo esc_html__('Recibe una referencia automática de reserva.', 'pedraza-mahou-reservations'); ?></li>
                                <li><span>5</span><?php echo esc_html__('Presenta tu referencia y tu nombre para retirar tu cesta.', 'pedraza-mahou-reservations'); ?></li>
                                <li><span>6</span><?php echo esc_html__('Abona el importe presencialmente en taquilla.', 'pedraza-mahou-reservations'); ?></li>
                            </ol>
                        </div>

                        <div class="pmr-pickup-card">
                            <h3><?php echo self::svg_icon('beer-bottle', 'pmr-icon pmr-icon--teal'); ?><?php echo esc_html__('Recogida', 'pedraza-mahou-reservations'); ?></h3>
                            <p><?php echo wp_kses_post(__('La cesta se retirará el día seleccionado en la <strong>taquilla del Gran Castillo de Pedraza</strong>.', 'pedraza-mahou-reservations')); ?></p>
                        </div>
                    </aside>

                    <div class="pmr-form-column">
                        <div class="pmr-form-panel">
                            <header class="pmr-form-panel__header">
                                <div>
                                    <h2><?php echo esc_html__('Completa tu reserva', 'pedraza-mahou-reservations'); ?></h2>
                                    <p><?php echo esc_html__('Selecciona fecha y detalles de tu cesta', 'pedraza-mahou-reservations'); ?></p>
                                </div>
                                <span class="pmr-calendar-badge" aria-hidden="true"><?php echo self::svg_icon('calendar-clock', 'pmr-icon'); ?></span>
                            </header>

                            <form class="pmr-form" data-pmr-public-form novalidate>
                                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('pmr_public_reservation')); ?>">
                                <div class="pmr-honeypot" aria-hidden="true">
                                    <label for="<?php echo esc_attr($uid); ?>-website"><?php echo esc_html__('Web', 'pedraza-mahou-reservations'); ?></label>
                                    <input type="text" id="<?php echo esc_attr($uid); ?>-website" name="pmr_website" tabindex="-1" autocomplete="off">
                                </div>

                                <section class="pmr-section">
                                    <h3><?php echo esc_html__('Reserva', 'pedraza-mahou-reservations'); ?></h3>
                                    <div class="pmr-grid pmr-grid--reservation">
                                        <div class="pmr-field">
                                            <label for="<?php echo esc_attr($uid); ?>-pickup-date"><?php echo esc_html__('Fecha de recogida', 'pedraza-mahou-reservations'); ?> <span class="pmr-required-marker" aria-hidden="true">*</span></label>
                                            <div class="pmr-date-control">
                                                <input type="text" id="<?php echo esc_attr($uid); ?>-pickup-date" name="pickup_date" min="<?php echo esc_attr($today); ?>" placeholder="<?php echo esc_attr__('Selecciona una fecha', 'pedraza-mahou-reservations'); ?>" autocomplete="off" data-pmr-datepicker required>
                                                <span class="pmr-date-control__icon" aria-hidden="true"><?php echo self::svg_icon('calendar-days', 'pmr-icon pmr-date-control__lucide'); ?></span>
                                            </div>
                                        </div>
                                        <div class="pmr-field">
                                            <label for="<?php echo esc_attr($uid); ?>-basket-count"><?php echo esc_html__('Número de cestas', 'pedraza-mahou-reservations'); ?> <span class="pmr-required-marker" aria-hidden="true">*</span></label>
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
                                    <h3><?php echo esc_html__('Datos de contacto', 'pedraza-mahou-reservations'); ?></h3>
                                    <div class="pmr-grid">
                                        <div class="pmr-field">
                                            <label for="<?php echo esc_attr($uid); ?>-full-name"><?php echo esc_html__('Nombre y apellidos', 'pedraza-mahou-reservations'); ?> <span class="pmr-required-marker" aria-hidden="true">*</span></label>
                                            <input type="text" id="<?php echo esc_attr($uid); ?>-full-name" name="full_name" maxlength="190" autocomplete="name" required>
                                        </div>
                                        <div class="pmr-field">
                                            <label for="<?php echo esc_attr($uid); ?>-email"><?php echo esc_html__('Correo electrónico', 'pedraza-mahou-reservations'); ?> <span class="pmr-required-marker" aria-hidden="true">*</span></label>
                                            <input type="email" id="<?php echo esc_attr($uid); ?>-email" name="email" maxlength="190" autocomplete="email" required>
                                        </div>
                                        <div class="pmr-field">
                                            <label for="<?php echo esc_attr($uid); ?>-phone"><?php echo esc_html__('Teléfono de contacto', 'pedraza-mahou-reservations'); ?> <span class="pmr-required-marker" aria-hidden="true">*</span></label>
                                            <div class="pmr-phone-control">
                                                <input type="tel" id="<?php echo esc_attr($uid); ?>-phone" name="phone_display" maxlength="30" placeholder="<?php echo esc_attr__('Tu teléfono', 'pedraza-mahou-reservations'); ?>" autocomplete="tel" data-pmr-phone required>
                                                <input type="hidden" name="phone" data-pmr-phone-value>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="pmr-section pmr-legal-section">
                                    <h3><?php echo esc_html__('Protección de datos y consentimientos', 'pedraza-mahou-reservations'); ?></h3>
                                    <label class="pmr-checkbox">
                                        <input type="checkbox" name="rgpd_consent" value="1" required>
                                        <span><?php echo wp_kses_post(sprintf(__('He leído y acepto la %s y el tratamiento de mis datos para la gestión de esta reserva.', 'pedraza-mahou-reservations'), $privacy_link)); ?> <span class="pmr-required-marker" aria-hidden="true">*</span></span>
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
                </div>
            </div>
        </section>
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

        $pending_reservations = PMR_Database::get_reservations(['status' => 'pending']);
        $pending_totals = PMR_Database::get_totals(['status' => 'pending']);
        $completed_reservations = PMR_Database::get_reservations(['status' => 'completed']);
        $completed_totals = PMR_Database::get_totals(['status' => 'completed']);

        ob_start();
        ?>
        <section class="pmr-admin pmr-admin-page" data-pmr-admin data-nonce="<?php echo esc_attr($nonce); ?>" data-refresh-interval="<?php echo esc_attr((string) $settings['refresh_interval']); ?>">
            <div class="pmr-page-pattern" aria-hidden="true"></div>
            <div class="pmr-admin-shell">
                <header class="pmr-admin-hero">
                    <div class="pmr-admin-brand">
                        <span class="pmr-admin-brand__icon" aria-hidden="true"><?php echo self::svg_icon('shopping-basket', 'pmr-icon'); ?></span>
                        <span><?php echo esc_html__('Gran Castillo de Pedraza', 'pedraza-mahou-reservations'); ?></span>
                    </div>
                    <div class="pmr-admin-hero__content">
                        <div>
                            <h1><?php echo esc_html__('Gestión de reservas', 'pedraza-mahou-reservations'); ?></h1>
                            <p><?php echo esc_html__('Organiza las cestas picnic y controla las recogidas del día.', 'pedraza-mahou-reservations'); ?></p>
                        </div>
                        <div class="pmr-admin-hero__actions">
                            <span class="pmr-admin-live">
                                <span class="pmr-admin-live__dot" aria-hidden="true"></span>
                                <span data-pmr-last-updated><?php echo esc_html(sprintf(__('Actualizado a las %s', 'pedraza-mahou-reservations'), current_time('H:i'))); ?></span>
                            </span>
                            <button type="button" class="pmr-secondary-button" data-pmr-logout><?php echo esc_html__('Cerrar sesión', 'pedraza-mahou-reservations'); ?></button>
                        </div>
                    </div>
                </header>

                <main class="pmr-admin-workspace">
                    <div class="pmr-admin-toolbar">
                        <div class="pmr-admin-filter-group pmr-admin-filter-group--search">
                            <label class="pmr-admin-filter-label" for="pmr-admin-search"><?php echo esc_html__('Buscar reserva', 'pedraza-mahou-reservations'); ?></label>
                            <input type="search" id="pmr-admin-search" data-pmr-filter-search placeholder="<?php echo esc_attr__('Referencia, nombre, teléfono o email', 'pedraza-mahou-reservations'); ?>" autocomplete="off">
                        </div>

                        <div class="pmr-admin-toolbar__actions">
                            <button type="button" class="pmr-secondary-button" data-pmr-clear-filters><?php echo esc_html__('Restablecer', 'pedraza-mahou-reservations'); ?></button>
                            <button type="button" class="pmr-submit" data-pmr-refresh><?php echo esc_html__('Actualizar', 'pedraza-mahou-reservations'); ?></button>
                        </div>
                    </div>

                    <div class="pmr-admin-message" data-pmr-admin-message role="status" aria-live="polite"></div>
                    <div class="pmr-admin-table" data-pmr-admin-table>
                        <?php echo PMR_Reservations::render_admin_lists($pending_reservations, $pending_totals, $completed_reservations, $completed_totals); ?>
                    </div>
                </main>
            </div>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_login_form(string $nonce, bool $needs_password): string
    {
        ob_start();
        ?>
        <section class="pmr-login pmr-admin-login-page" data-pmr-login>
            <div class="pmr-page-pattern" aria-hidden="true"></div>
            <div class="pmr-login-shell">
                <div class="pmr-login-brand">
                    <span class="pmr-login-brand__icon" aria-hidden="true"><?php echo self::svg_icon('shopping-basket', 'pmr-icon'); ?></span>
                    <p><?php echo esc_html__('Gran Castillo de Pedraza', 'pedraza-mahou-reservations'); ?></p>
                    <h1><?php echo esc_html__('Gestión de reservas', 'pedraza-mahou-reservations'); ?></h1>
                    <span><?php echo esc_html__('Acceso privado para el equipo del castillo.', 'pedraza-mahou-reservations'); ?></span>
                </div>

                <div class="pmr-login__box">
                    <span class="pmr-login-lock" aria-hidden="true"><?php echo self::svg_icon('lock-keyhole', 'pmr-icon'); ?></span>
                    <h2><?php echo esc_html__('Acceso del equipo', 'pedraza-mahou-reservations'); ?></h2>
                    <p><?php echo esc_html__('Introduce tus credenciales para consultar y gestionar las reservas.', 'pedraza-mahou-reservations'); ?></p>

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
                        <button type="submit" class="pmr-submit pmr-icon-button-text"><?php echo self::svg_icon('log-in', 'pmr-icon'); ?><span><?php echo esc_html__('Acceder', 'pedraza-mahou-reservations'); ?></span></button>
                        <div class="pmr-message" data-pmr-login-message role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </section>
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

    private static function svg_icon(string $name, string $class = 'pmr-icon'): string
    {
        $icons = [
            'beer-bottle' => '<path d="M10 2h4"/><path d="M11 2v4.5c0 .8-.3 1.5-.9 2.1l-.8.8A4.5 4.5 0 0 0 8 12.6V20a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-7.4a4.5 4.5 0 0 0-1.3-3.2l-.8-.8a3 3 0 0 1-.9-2.1V2"/><path d="M8 14h8"/><path d="M8 18h8"/>',
            'calendar-days' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/>',
            'calendar-clock' => '<path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><circle cx="16" cy="16" r="6"/><path d="M16 13v3l2 1"/>',
            'check' => '<path d="m20 6-11 11-5-5"/>',
            'check-circle-2' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
            'credit-card' => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
            'list-checks' => '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/>',
            'lock-keyhole' => '<circle cx="12" cy="16" r="1"/><rect width="18" height="12" x="3" y="10" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/>',
            'log-in' => '<path d="m10 17 5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
            'log-out' => '<path d="m9 17-5-5 5-5"/><path d="M4 12h12"/><path d="M16 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3"/>',
            'package-check' => '<path d="m16 16 2 2 4-4"/><path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"/><path d="m7.5 4.27 9 5.15"/><path d="M3.29 7 12 12l8.71-5"/><path d="M12 22V12"/>',
            'refresh-cw' => '<path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/>',
            'rotate-ccw' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
            'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
            'shopping-basket' => '<path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4A2 2 0 0 0 7.1 20h9.8a2 2 0 0 0 2-1.6l1.6-7.4"/><path d="M5 11 9 4"/><path d="m9 11 1 9"/>',
            'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        ];

        if (! isset($icons[$name])) {
            return '';
        }

        return sprintf(
            '<svg class="%s" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
            esc_attr(trim($class . ' pmr-lucide-icon lucide lucide-' . $name)),
            $icons[$name]
        );
    }
}
