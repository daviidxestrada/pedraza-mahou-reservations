<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Reservations
{
    private const PRICE_PER_BASKET = 15;
    private const MAX_BASKETS = 50;
    private const PUBLIC_LIMIT = 5;
    private const PUBLIC_WINDOW = 600;

    public static function init(): void
    {
        add_action('wp_ajax_pmr_submit_reservation', [__CLASS__, 'handle_public_submission']);
        add_action('wp_ajax_nopriv_pmr_submit_reservation', [__CLASS__, 'handle_public_submission']);

        add_action('wp_ajax_pmr_admin_login', [__CLASS__, 'handle_admin_login']);
        add_action('wp_ajax_nopriv_pmr_admin_login', [__CLASS__, 'handle_admin_login']);
        add_action('wp_ajax_pmr_admin_logout', [__CLASS__, 'handle_admin_logout']);
        add_action('wp_ajax_nopriv_pmr_admin_logout', [__CLASS__, 'handle_admin_logout']);
        add_action('wp_ajax_pmr_admin_list_reservations', [__CLASS__, 'handle_admin_list_reservations']);
        add_action('wp_ajax_nopriv_pmr_admin_list_reservations', [__CLASS__, 'handle_admin_list_reservations']);
        add_action('wp_ajax_pmr_admin_update_reservation', [__CLASS__, 'handle_admin_update_reservation']);
        add_action('wp_ajax_nopriv_pmr_admin_update_reservation', [__CLASS__, 'handle_admin_update_reservation']);
        add_action('wp_ajax_pmr_admin_delete_reservation', [__CLASS__, 'handle_admin_delete_reservation']);
        add_action('wp_ajax_nopriv_pmr_admin_delete_reservation', [__CLASS__, 'handle_admin_delete_reservation']);
    }

    public static function handle_public_submission(): void
    {
        if (! self::verify_nonce('pmr_public_reservation')) {
            wp_send_json_error(['message' => __('La sesión ha caducado. Recarga la página e inténtalo de nuevo.', 'pedraza-mahou-reservations')], 403);
        }

        $honeypot = isset($_POST['pmr_website']) ? trim((string) wp_unslash($_POST['pmr_website'])) : '';

        if ($honeypot !== '') {
            wp_send_json_error(['message' => __('No se pudo procesar la reserva.', 'pedraza-mahou-reservations')], 400);
        }

        $ip_address = PMR_Auth::get_client_ip();

        if (self::is_public_rate_limited($ip_address)) {
            wp_send_json_error(['message' => __('Has enviado varias solicitudes seguidas. Espera unos minutos antes de intentarlo de nuevo.', 'pedraza-mahou-reservations')], 429);
        }

        self::record_public_attempt($ip_address);

        $validation = self::validate_public_payload($ip_address);

        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message(), 'errors' => $validation->get_error_data()], 400);
        }

        $reservation = PMR_Database::insert_reservation($validation);

        if (is_wp_error($reservation)) {
            wp_send_json_error(['message' => $reservation->get_error_message()], 500);
        }

        PMR_Emails::send_customer_email($reservation);
        PMR_Emails::send_internal_email($reservation);

        wp_send_json_success([
            'reference' => $reservation['reference'],
            'message' => sprintf(
                /* translators: %s reservation reference */
                __('Solicitud recibida. Tu referencia es %s. La reserva está sujeta a disponibilidad y el pago se realizará presencialmente en taquilla.', 'pedraza-mahou-reservations'),
                $reservation['reference']
            ),
        ]);
    }

    public static function handle_admin_login(): void
    {
        if (! self::verify_nonce('pmr_admin_panel')) {
            wp_send_json_error(['message' => __('La sesión ha caducado. Recarga la página e inténtalo de nuevo.', 'pedraza-mahou-reservations')], 403);
        }

        $username = isset($_POST['username']) ? sanitize_user((string) wp_unslash($_POST['username']), true) : '';
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $result = PMR_Auth::authenticate($username, $password);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 401);
        }

        wp_send_json_success(['message' => __('Acceso correcto.', 'pedraza-mahou-reservations')]);
    }

    public static function handle_admin_logout(): void
    {
        if (! self::verify_nonce('pmr_admin_panel')) {
            wp_send_json_error(['message' => __('La sesión ha caducado. Recarga la página e inténtalo de nuevo.', 'pedraza-mahou-reservations')], 403);
        }

        PMR_Auth::logout();

        wp_send_json_success(['message' => __('Sesión cerrada.', 'pedraza-mahou-reservations')]);
    }

    public static function handle_admin_list_reservations(): void
    {
        self::require_admin_ajax();

        $filters = self::admin_filters_from_request();
        $reservations = PMR_Database::get_reservations($filters);
        $totals = PMR_Database::get_totals($filters);

        wp_send_json_success([
            'html' => self::render_admin_table($reservations, $totals),
            'totalReservations' => $totals['total_reservations'],
            'totalBaskets' => $totals['total_baskets'],
        ]);
    }

    public static function handle_admin_update_reservation(): void
    {
        self::require_admin_ajax();

        $reservation_id = isset($_POST['reservation_id']) ? absint($_POST['reservation_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key((string) wp_unslash($_POST['status'])) : '';

        if ($reservation_id < 1 || ! in_array($status, PMR_Database::valid_statuses(), true)) {
            wp_send_json_error(['message' => __('Acción no válida.', 'pedraza-mahou-reservations')], 400);
        }

        if (! PMR_Database::update_status($reservation_id, $status)) {
            wp_send_json_error(['message' => __('No se pudo actualizar la reserva.', 'pedraza-mahou-reservations')], 500);
        }

        wp_send_json_success(['message' => __('Reserva actualizada.', 'pedraza-mahou-reservations')]);
    }

    public static function handle_admin_delete_reservation(): void
    {
        self::require_admin_ajax();

        $reservation_id = isset($_POST['reservation_id']) ? absint($_POST['reservation_id']) : 0;

        if ($reservation_id < 1) {
            wp_send_json_error(['message' => __('Reserva no válida.', 'pedraza-mahou-reservations')], 400);
        }

        if (! PMR_Database::delete_reservation($reservation_id)) {
            wp_send_json_error(['message' => __('No se pudo eliminar la reserva.', 'pedraza-mahou-reservations')], 500);
        }

        wp_send_json_success(['message' => __('Reserva eliminada.', 'pedraza-mahou-reservations')]);
    }

    public static function render_admin_table(array $reservations, array $totals): string
    {
        $total_reservations = (int) ($totals['total_reservations'] ?? 0);
        $total_baskets = (int) ($totals['total_baskets'] ?? 0);
        $pending_reservations = (int) ($totals['pending_reservations'] ?? 0);
        $pending_baskets = (int) ($totals['pending_baskets'] ?? 0);

        ob_start();
        ?>
        <div class="pmr-admin-summary" aria-live="polite">
            <div class="pmr-admin-summary__item pmr-admin-summary__item--primary">
                <span class="pmr-admin-summary__icon" aria-hidden="true"><?php echo self::admin_icon('shopping-basket'); ?></span>
                <div>
                    <span><?php echo esc_html__('Cestas por preparar', 'pedraza-mahou-reservations'); ?></span>
                    <strong><?php echo esc_html((string) $pending_baskets); ?></strong>
                    <small><?php echo esc_html(sprintf(_n('%d reserva pendiente', '%d reservas pendientes', $pending_reservations, 'pedraza-mahou-reservations'), $pending_reservations)); ?></small>
                </div>
            </div>
            <div class="pmr-admin-summary__item">
                <span class="pmr-admin-summary__icon" aria-hidden="true"><?php echo self::admin_icon('clipboard-list'); ?></span>
                <div>
                    <span><?php echo esc_html__('Reservas del filtro', 'pedraza-mahou-reservations'); ?></span>
                    <strong><?php echo esc_html((string) $total_reservations); ?></strong>
                    <small><?php echo esc_html(sprintf(_n('%d cesta en total', '%d cestas en total', $total_baskets, 'pedraza-mahou-reservations'), $total_baskets)); ?></small>
                </div>
            </div>
        </div>

        <div class="pmr-admin-results-heading">
            <h2><?php echo esc_html__('Reservas', 'pedraza-mahou-reservations'); ?></h2>
            <span><?php echo esc_html(sprintf(_n('%d reserva', '%d reservas', $total_reservations, 'pedraza-mahou-reservations'), $total_reservations)); ?></span>
        </div>

        <?php if (! $reservations) : ?>
            <div class="pmr-empty">
                <span aria-hidden="true"><?php echo self::admin_icon('calendar-check'); ?></span>
                <strong><?php echo esc_html__('No hay reservas con estos filtros', 'pedraza-mahou-reservations'); ?></strong>
                <p><?php echo esc_html__('Prueba con otra fecha, estado o término de búsqueda.', 'pedraza-mahou-reservations'); ?></p>
            </div>
        <?php else : ?>
            <div class="pmr-table-wrap">
                <table class="pmr-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Reserva', 'pedraza-mahou-reservations'); ?></th>
                            <th><?php echo esc_html__('Recogida y cestas', 'pedraza-mahou-reservations'); ?></th>
                            <th><?php echo esc_html__('Cliente', 'pedraza-mahou-reservations'); ?></th>
                            <th><?php echo esc_html__('Detalles', 'pedraza-mahou-reservations'); ?></th>
                            <th><?php echo esc_html__('Estado', 'pedraza-mahou-reservations'); ?></th>
                            <th><?php echo esc_html__('Acciones', 'pedraza-mahou-reservations'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation) : ?>
                            <?php $status = (string) $reservation['status']; ?>
                            <tr class="pmr-reservation-row pmr-reservation-row--<?php echo esc_attr($status); ?>">
                                <td class="pmr-col-reservation" data-label="<?php echo esc_attr__('Reserva', 'pedraza-mahou-reservations'); ?>">
                                    <div class="pmr-reservation-heading">
                                        <strong class="pmr-reservation-reference"><?php echo esc_html($reservation['reference']); ?></strong>
                                        <span class="pmr-status pmr-status--<?php echo esc_attr($status); ?> pmr-status--mobile"><?php echo esc_html(self::status_label($status)); ?></span>
                                    </div>
                                    <span class="pmr-table-meta"><?php echo esc_html(sprintf(__('Recibida %s', 'pedraza-mahou-reservations'), self::format_datetime((string) $reservation['created_at']))); ?></span>
                                </td>
                                <td class="pmr-col-pickup" data-label="<?php echo esc_attr__('Recogida y cestas', 'pedraza-mahou-reservations'); ?>">
                                    <strong class="pmr-pickup-date"><?php echo esc_html(self::format_date((string) $reservation['pickup_date'])); ?></strong>
                                    <span class="pmr-basket-count"><?php echo self::admin_icon('shopping-basket'); ?><strong><?php echo esc_html((string) (int) $reservation['basket_count']); ?></strong> <?php echo esc_html(_n('cesta', 'cestas', (int) $reservation['basket_count'], 'pedraza-mahou-reservations')); ?></span>
                                </td>
                                <td class="pmr-col-customer" data-label="<?php echo esc_attr__('Cliente', 'pedraza-mahou-reservations'); ?>">
                                    <strong class="pmr-customer-name"><?php echo esc_html($reservation['full_name']); ?></strong>
                                    <a class="pmr-contact-link" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', (string) $reservation['phone'])); ?>"><?php echo self::admin_icon('phone'); ?><span><?php echo esc_html($reservation['phone']); ?></span></a>
                                    <a class="pmr-contact-link pmr-contact-link--email" href="mailto:<?php echo esc_attr($reservation['email']); ?>"><?php echo self::admin_icon('mail'); ?><span><?php echo esc_html($reservation['email']); ?></span></a>
                                </td>
                                <td class="pmr-col-details<?php echo $reservation['observations'] === '' ? ' pmr-col-details--empty' : ''; ?>" data-label="<?php echo esc_attr__('Detalles', 'pedraza-mahou-reservations'); ?>">
                                    <div class="pmr-observations<?php echo $reservation['observations'] === '' ? ' pmr-observations--empty' : ''; ?>">
                                        <?php echo $reservation['observations'] !== '' ? nl2br(esc_html($reservation['observations'])) : esc_html__('Sin observaciones', 'pedraza-mahou-reservations'); ?>
                                    </div>
                                    <span class="pmr-marketing-consent"><?php echo esc_html__('Comercial:', 'pedraza-mahou-reservations'); ?> <strong><?php echo ! empty($reservation['marketing_consent']) ? esc_html__('Sí', 'pedraza-mahou-reservations') : esc_html__('No', 'pedraza-mahou-reservations'); ?></strong></span>
                                </td>
                                <td class="pmr-col-status" data-label="<?php echo esc_attr__('Estado', 'pedraza-mahou-reservations'); ?>"><span class="pmr-status pmr-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(self::status_label($status)); ?></span></td>
                                <td class="pmr-col-actions" data-label="<?php echo esc_attr__('Acciones', 'pedraza-mahou-reservations'); ?>">
                                    <div class="pmr-row-actions">
                                        <?php if ($status !== 'completed') : ?>
                                            <button type="button" class="pmr-admin-action pmr-admin-action--complete" data-pmr-action="status" data-status="completed" data-id="<?php echo esc_attr((string) $reservation['id']); ?>"><?php echo esc_html__('Completar', 'pedraza-mahou-reservations'); ?></button>
                                        <?php endif; ?>
                                        <?php if ($status !== 'cancelled') : ?>
                                            <button type="button" class="pmr-admin-action pmr-admin-action--cancel" data-pmr-action="status" data-status="cancelled" data-id="<?php echo esc_attr((string) $reservation['id']); ?>"><?php echo esc_html__('Cancelar', 'pedraza-mahou-reservations'); ?></button>
                                        <?php endif; ?>
                                        <?php if ($status !== 'pending') : ?>
                                            <button type="button" class="pmr-admin-action pmr-admin-action--pending" data-pmr-action="status" data-status="pending" data-id="<?php echo esc_attr((string) $reservation['id']); ?>"><?php echo esc_html__('Volver a pendiente', 'pedraza-mahou-reservations'); ?></button>
                                        <?php endif; ?>
                                        <button type="button" class="pmr-admin-action pmr-admin-action--danger" data-pmr-action="delete" data-id="<?php echo esc_attr((string) $reservation['id']); ?>"><?php echo esc_html__('Eliminar', 'pedraza-mahou-reservations'); ?></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php

        return (string) ob_get_clean();
    }

    public static function status_label(string $status): string
    {
        $labels = [
            'pending' => __('Pendiente', 'pedraza-mahou-reservations'),
            'completed' => __('Completada', 'pedraza-mahou-reservations'),
            'cancelled' => __('Cancelada', 'pedraza-mahou-reservations'),
        ];

        return $labels[$status] ?? $status;
    }

    private static function validate_public_payload(string $ip_address)
    {
        $errors = [];

        $pickup_date = isset($_POST['pickup_date']) ? sanitize_text_field((string) wp_unslash($_POST['pickup_date'])) : '';
        $basket_count = isset($_POST['basket_count']) ? absint($_POST['basket_count']) : 0;
        $observations = isset($_POST['observations']) ? sanitize_textarea_field((string) wp_unslash($_POST['observations'])) : '';
        $full_name = isset($_POST['full_name']) ? sanitize_text_field((string) wp_unslash($_POST['full_name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email((string) wp_unslash($_POST['email'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field((string) wp_unslash($_POST['phone'])) : '';
        $rgpd_consent = ! empty($_POST['rgpd_consent']);
        $marketing_consent = ! empty($_POST['marketing_consent']);

        if (! PMR_Database::is_valid_date($pickup_date)) {
            $errors['pickup_date'] = __('Selecciona una fecha de recogida válida.', 'pedraza-mahou-reservations');
        } elseif ($pickup_date < current_time('Y-m-d')) {
            $errors['pickup_date'] = __('La fecha de recogida no puede ser anterior a hoy.', 'pedraza-mahou-reservations');
        }

        if ($basket_count < 1 || $basket_count > self::MAX_BASKETS) {
            $errors['basket_count'] = sprintf(
                /* translators: %d maximum basket count */
                __('El número de cestas debe estar entre 1 y %d.', 'pedraza-mahou-reservations'),
                self::MAX_BASKETS
            );
        }

        if ($full_name === '' || strlen($full_name) < 2 || strlen($full_name) > 190) {
            $errors['full_name'] = __('Introduce tu nombre y apellidos.', 'pedraza-mahou-reservations');
        }

        if (! is_email($email)) {
            $errors['email'] = __('Introduce un correo electrónico válido.', 'pedraza-mahou-reservations');
        }

        if (! preg_match('/^[0-9+\s().-]{6,25}$/', $phone)) {
            $errors['phone'] = __('Introduce un teléfono de contacto válido.', 'pedraza-mahou-reservations');
        }

        if (! $rgpd_consent) {
            $errors['rgpd_consent'] = __('Debes aceptar la Política de Privacidad y el tratamiento de tus datos.', 'pedraza-mahou-reservations');
        }

        if (strlen($observations) > 2000) {
            $observations = substr($observations, 0, 2000);
        }

        if ($errors) {
            return new WP_Error(
                'pmr_validation_failed',
                __('Revisa los campos marcados antes de enviar la reserva.', 'pedraza-mahou-reservations'),
                $errors
            );
        }

        return [
            'pickup_date' => $pickup_date,
            'basket_count' => $basket_count,
            'observations' => $observations,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'rgpd_consent' => 1,
            'marketing_consent' => $marketing_consent ? 1 : 0,
            'ip_address' => $ip_address,
        ];
    }

    private static function require_admin_ajax(): void
    {
        if (! self::verify_nonce('pmr_admin_panel')) {
            wp_send_json_error(['message' => __('La sesión ha caducado. Recarga la página e inténtalo de nuevo.', 'pedraza-mahou-reservations')], 403);
        }

        if (! PMR_Auth::is_authenticated()) {
            wp_send_json_error(['message' => __('Acceso no autorizado.', 'pedraza-mahou-reservations')], 401);
        }
    }

    private static function verify_nonce(string $action): bool
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) wp_unslash($_POST['nonce'])) : '';

        return (bool) wp_verify_nonce($nonce, $action);
    }

    private static function admin_filters_from_request(): array
    {
        $filters = [];
        $pickup_date = isset($_POST['pickup_date']) ? sanitize_text_field((string) wp_unslash($_POST['pickup_date'])) : '';
        $status = isset($_POST['status']) ? sanitize_key((string) wp_unslash($_POST['status'])) : '';
        $search = isset($_POST['search']) ? sanitize_text_field((string) wp_unslash($_POST['search'])) : '';

        if ($pickup_date !== '' && PMR_Database::is_valid_date($pickup_date)) {
            $filters['pickup_date'] = $pickup_date;
        }

        if ($status !== '' && in_array($status, PMR_Database::valid_statuses(), true)) {
            $filters['status'] = $status;
        }

        if ($search !== '') {
            $filters['search'] = substr($search, 0, 100);
        }

        return $filters;
    }

    private static function admin_icon(string $name): string
    {
        $icons = [
            'calendar-check' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
            'circle-check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
            'circle-x' => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
            'clipboard-list' => '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M9 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><path d="M8 11h.01"/><path d="M12 11h4"/><path d="M8 16h.01"/><path d="M12 16h4"/>',
            'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
            'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>',
            'shopping-basket' => '<path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4A2 2 0 0 0 7.1 20h9.8a2 2 0 0 0 2-1.6l1.6-7.4"/><path d="M5 11 9 4"/><path d="m9 11 1 9"/>',
            'trash-2' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6 18 21H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
            'undo-2' => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
        ];

        if (! isset($icons[$name])) {
            return '';
        }

        return sprintf(
            '<svg class="pmr-admin-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
            $icons[$name]
        );
    }

    private static function is_public_rate_limited(string $ip_address): bool
    {
        $attempts = get_transient(self::public_rate_key($ip_address));

        return is_array($attempts)
            && isset($attempts['count'])
            && (int) $attempts['count'] >= self::PUBLIC_LIMIT;
    }

    private static function record_public_attempt(string $ip_address): void
    {
        $key = self::public_rate_key($ip_address);
        $attempts = get_transient($key);
        $count = is_array($attempts) && isset($attempts['count']) ? (int) $attempts['count'] : 0;

        set_transient($key, ['count' => $count + 1], self::PUBLIC_WINDOW);
    }

    private static function public_rate_key(string $ip_address): string
    {
        return 'pmr_public_' . md5($ip_address ?: 'unknown');
    }

    private static function format_date(string $date): string
    {
        $timestamp = strtotime($date);

        if (! $timestamp) {
            return $date;
        }

        return date_i18n(get_option('date_format'), $timestamp);
    }

    private static function format_datetime(string $datetime): string
    {
        $timestamp = strtotime($datetime);

        if (! $timestamp) {
            return $datetime;
        }

        return date_i18n(get_option('date_format') . ' H:i', $timestamp);
    }
}
