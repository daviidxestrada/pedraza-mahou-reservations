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

                        <div class="pmr-info-card">
                            <h3><span class="pmr-card-icon pmr-card-icon--red" aria-hidden="true"></span><?php echo esc_html__('¿Qué incluye La Cesta del Castillo?', 'pedraza-mahou-reservations'); ?></h3>
                            <ul class="pmr-feature-list">
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo esc_html__('2 Cervezas Mahou bien frías (o alternativa sin alcohol)', 'pedraza-mahou-reservations'); ?></li>
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo esc_html__('Selección de ibéricos de la tierra y queso curado', 'pedraza-mahou-reservations'); ?></li>
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo esc_html__('Hogaza de pan rústico artesano y picos crujientes', 'pedraza-mahou-reservations'); ?></li>
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo esc_html__('Cesta de mimbre tradicional y menaje necesario', 'pedraza-mahou-reservations'); ?></li>
                            </ul>
                        </div>

                        <div class="pmr-price-card">
                            <div class="pmr-price-icon" aria-hidden="true"></div>
                            <div>
                                <span><?php echo esc_html__('Precio por cesta', 'pedraza-mahou-reservations'); ?></span>
                                <strong><?php echo esc_html__('15 €', 'pedraza-mahou-reservations'); ?></strong>
                            </div>
                            <p><?php echo esc_html__('IVA incluido', 'pedraza-mahou-reservations'); ?></p>
                        </div>

                        <div class="pmr-info-card">
                            <h3><span class="pmr-card-icon pmr-card-icon--red" aria-hidden="true"></span><?php echo esc_html__('Pago', 'pedraza-mahou-reservations'); ?></h3>
                            <ul class="pmr-feature-list">
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo wp_kses_post(__('La reserva se realiza <strong>sin pago online</strong>.', 'pedraza-mahou-reservations')); ?></li>
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo wp_kses_post(__('El importe se abonará el día de la recogida en taquilla, mediante <strong>efectivo o tarjeta</strong>.', 'pedraza-mahou-reservations')); ?></li>
                                <li><span class="pmr-check" aria-hidden="true"></span><?php echo esc_html__('Pago presencial en taquilla del castillo.', 'pedraza-mahou-reservations'); ?></li>
                            </ul>
                        </div>

                        <div class="pmr-info-card">
                            <h3><span class="pmr-card-icon pmr-card-icon--red" aria-hidden="true"></span><?php echo esc_html__('Cómo funciona', 'pedraza-mahou-reservations'); ?></h3>
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
                            <h3><span class="pmr-card-icon pmr-card-icon--blue" aria-hidden="true"></span><?php echo esc_html__('Recogida', 'pedraza-mahou-reservations'); ?></h3>
                            <p><?php echo wp_kses_post(__('La cesta se retirará el día seleccionado en la <strong>taquilla del Gran Castillo de Pedraza</strong>.', 'pedraza-mahou-reservations')); ?></p>
                        </div>

                        <figure class="pmr-page-image">
                            <img src="https://images.unsplash.com/photo-1528495612343-9ca9f4a4de28?q=80&w=1200&auto=format&fit=crop" alt="<?php echo esc_attr__('Cesta de picnic', 'pedraza-mahou-reservations'); ?>" loading="lazy">
                        </figure>
                    </aside>

                    <div class="pmr-form-column">
                        <div class="pmr-form-panel">
                            <header class="pmr-form-panel__header">
                                <div>
                                    <h2><?php echo esc_html__('Completa tu reserva', 'pedraza-mahou-reservations'); ?></h2>
                                    <p><?php echo esc_html__('Selecciona fecha y detalles de tu cesta', 'pedraza-mahou-reservations'); ?></p>
                                </div>
                                <span class="pmr-calendar-badge" aria-hidden="true"></span>
                            </header>

                            <form class="pmr-form" data-pmr-public-form novalidate>
                                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('pmr_public_reservation')); ?>">
                                <div class="pmr-honeypot" aria-hidden="true">
                                    <label for="<?php echo esc_attr($uid); ?>-website"><?php echo esc_html__('Web', 'pedraza-mahou-reservations'); ?></label>
                                    <input type="text" id="<?php echo esc_attr($uid); ?>-website" name="pmr_website" tabindex="-1" autocomplete="off">
                                </div>

                                <section class="pmr-section">
                                    <h3><?php echo esc_html__('Reserva', 'pedraza-mahou-reservations'); ?></h3>
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
                                    <h3><?php echo esc_html__('Datos de contacto', 'pedraza-mahou-reservations'); ?></h3>
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
                                    <h3><?php echo esc_html__('Protección de datos y consentimientos', 'pedraza-mahou-reservations'); ?></h3>
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
