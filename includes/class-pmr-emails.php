<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Emails
{
    private const PRICE_PER_BASKET = 15;
    private const RESERVATIONS_MANAGER_URL = 'https://grancastillodepedraza.com/gestion-de-reservas-cestas-mahou/';

    public static function send_customer_email(array $reservation): bool
    {
        $settings = PMR_Admin_Settings::get_settings();
        $subject = self::replace_subject_tokens((string) $settings['customer_subject'], $reservation);
        $message = self::wrap_email(
            __('GRAN CASTILLO DE PEDRAZA', 'pedraza-mahou-reservations'),
            __('Reserva recibida', 'pedraza-mahou-reservations'),
            self::customer_email_body($reservation),
            $reservation
        );

        return wp_mail(
            $reservation['email'],
            $subject,
            $message,
            self::headers($settings)
        );
    }

    public static function send_internal_email(array $reservation): bool
    {
        $settings = PMR_Admin_Settings::get_settings();
        $subject = self::replace_subject_tokens((string) $settings['internal_subject'], $reservation);
        $message = self::wrap_email(
            __('NUEVA RESERVA DE CESTA', 'pedraza-mahou-reservations'),
            __('Nueva reserva recibida', 'pedraza-mahou-reservations'),
            self::internal_email_body($reservation),
            $reservation
        );

        return wp_mail(
            $settings['internal_email'],
            $subject,
            $message,
            self::headers($settings)
        );
    }

    private static function headers(array $settings): array
    {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $from_name = sanitize_text_field((string) $settings['from_name']);
        $from_email = sanitize_email((string) $settings['from_email']);

        if ($from_name !== '' && is_email($from_email)) {
            $headers[] = sprintf('From: %s <%s>', $from_name, $from_email);
        }

        return $headers;
    }

    private static function customer_email_body(array $reservation): string
    {
        $basket_count = (int) $reservation['basket_count'];
        $total = $basket_count * self::PRICE_PER_BASKET;

        return '<p style="margin:0 0 12px;color:#263340;font-size:16px;line-height:1.7;">'
            . sprintf(
                /* translators: %s customer name */
                esc_html__('Hola %s,', 'pedraza-mahou-reservations'),
                '<strong style="color:#002c3e;">' . esc_html((string) $reservation['full_name']) . '</strong>'
            )
            . '</p>'
            . '<p style="margin:0 0 22px;color:#596670;font-size:15px;line-height:1.7;">'
            . esc_html__('Hemos recibido tu solicitud de reserva de cesta picnic Mahou. Guarda esta referencia para presentarla junto a tu nombre el día de la recogida.', 'pedraza-mahou-reservations')
            . '</p>'
            . self::summary_cards([
                __('Fecha de recogida', 'pedraza-mahou-reservations') => self::format_date($reservation['pickup_date']),
                __('Cestas reservadas', 'pedraza-mahou-reservations') => (string) $basket_count,
                __('Total a pagar', 'pedraza-mahou-reservations') => $total . ' €',
            ])
            . self::notice_box(
                __('Solicitud recibida', 'pedraza-mahou-reservations'),
                __('La reserva está sujeta a disponibilidad. El equipo podrá contactar contigo si fuera necesario confirmar algún detalle.', 'pedraza-mahou-reservations')
            )
            . self::details_table([
            __('Referencia', 'pedraza-mahou-reservations') => $reservation['reference'],
            __('Nombre', 'pedraza-mahou-reservations') => $reservation['full_name'],
            __('Fecha de recogida', 'pedraza-mahou-reservations') => self::format_date($reservation['pickup_date']),
            __('Número de cestas', 'pedraza-mahou-reservations') => $basket_count,
            __('Precio por cesta', 'pedraza-mahou-reservations') => '15 € IVA incluido',
            __('Total a pagar', 'pedraza-mahou-reservations') => $total . ' € IVA incluido',
        ])
            . self::payment_box($total)
            . '<h2 class="pmr-email-heading" style="margin:26px 0 12px;color:#002c3e;font-family:&quot;League Spartan&quot;,&quot;Avenir Next&quot;,&quot;Segoe UI&quot;,Arial,Helvetica,sans-serif;font-size:19px;font-weight:700;line-height:1.3;letter-spacing:0;">'
            . esc_html__('El día de la recogida', 'pedraza-mahou-reservations')
            . '</h2>'
            . '<p style="margin:0;color:#596670;font-size:14px;line-height:1.7;">'
            . esc_html__('Presenta tu referencia y tu nombre en la taquilla del Gran Castillo de Pedraza. Allí podrás abonar el importe mediante efectivo o tarjeta y recoger tu cesta.', 'pedraza-mahou-reservations')
            . '</p>';
    }

    private static function internal_email_body(array $reservation): string
    {
        return '<p style="margin:0 0 18px;color:#596670;font-size:15px;line-height:1.7;">'
            . esc_html__('Ha entrado una nueva reserva pendiente de preparar. Estos son los datos facilitados por el cliente.', 'pedraza-mahou-reservations')
            . '</p>'
            . self::summary_cards([
                __('Fecha de recogida', 'pedraza-mahou-reservations') => self::format_date($reservation['pickup_date']),
                __('Cestas por preparar', 'pedraza-mahou-reservations') => (string) (int) $reservation['basket_count'],
                __('Referencia', 'pedraza-mahou-reservations') => (string) $reservation['reference'],
            ])
            . self::notice_box(
                __('Pendiente de preparar', 'pedraza-mahou-reservations'),
                __('La reserva ya está disponible en el gestor privado para su seguimiento.', 'pedraza-mahou-reservations'),
                '#fff9df',
                '#fbd652'
            )
            . self::details_table([
            __('Referencia', 'pedraza-mahou-reservations') => $reservation['reference'],
            __('Fecha de recogida', 'pedraza-mahou-reservations') => self::format_date($reservation['pickup_date']),
            __('Número de cestas', 'pedraza-mahou-reservations') => (int) $reservation['basket_count'],
            __('Nombre y apellidos', 'pedraza-mahou-reservations') => $reservation['full_name'],
            __('Email', 'pedraza-mahou-reservations') => $reservation['email'],
            __('Teléfono', 'pedraza-mahou-reservations') => $reservation['phone'],
            __('Observaciones', 'pedraza-mahou-reservations') => $reservation['observations'] ?: '-',
            __('Fecha y hora de creación', 'pedraza-mahou-reservations') => $reservation['created_at'],
        ])
            . self::manager_button();
    }

    private static function summary_cards(array $items): string
    {
        $html = '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:separate;border-spacing:6px;margin:0 -6px 20px;"><tr>';

        foreach ($items as $label => $value) {
            $html .= '<td class="pmr-email-summary-cell" width="33.33%" valign="top" style="padding:14px 12px;border:1px solid #e2e8eb;border-radius:6px;background:#f7fafb;">';
            $html .= '<span style="display:block;margin-bottom:5px;color:#71808a;font-size:10px;font-weight:700;line-height:1.3;text-transform:uppercase;">' . esc_html((string) $label) . '</span>';
            $html .= '<strong style="display:block;color:#002c3e;font-size:17px;line-height:1.25;">' . esc_html((string) $value) . '</strong>';
            $html .= '</td>';
        }

        return $html . '</tr></table>';
    }

    private static function notice_box(string $title, string $message, string $background = '#eaf6f7', string $accent = '#0087a3'): string
    {
        return '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:0 0 22px;border-collapse:collapse;"><tr>'
            . '<td style="padding:15px 16px;border-left:4px solid ' . esc_attr($accent) . ';border-radius:4px;background:' . esc_attr($background) . ';">'
            . '<strong style="display:block;margin-bottom:4px;color:#002c3e;font-size:14px;line-height:1.4;">' . esc_html($title) . '</strong>'
            . '<span style="display:block;color:#53636d;font-size:13px;line-height:1.6;">' . esc_html($message) . '</span>'
            . '</td></tr></table>';
    }

    private static function payment_box(int $total): string
    {
        return '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:22px 0;border-collapse:collapse;"><tr>'
            . '<td style="padding:19px 20px;border-radius:6px;background:#002c3e;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;"><tr>'
            . '<td valign="middle"><span style="display:block;margin-bottom:4px;color:#ffffff;font-size:14px;font-weight:700;">'
            . esc_html__('Pago presencial en taquilla', 'pedraza-mahou-reservations')
            . '</span><span style="display:block;color:#b8c7cd;font-size:12px;line-height:1.5;">'
            . esc_html__('Sin pago online · Efectivo o tarjeta', 'pedraza-mahou-reservations')
            . '</span></td>'
            . '<td align="right" valign="middle" style="padding-left:14px;color:#fbd652;font-size:25px;font-weight:800;white-space:nowrap;">'
            . esc_html($total . ' €')
            . '</td></tr></table></td></tr></table>';
    }

    private static function details_table(array $rows): string
    {
        $html = '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:18px 0;background:#ffffff;border:1px solid #e2e8eb;border-radius:6px;">';

        foreach ($rows as $label => $value) {
            $html .= '<tr>';
            $html .= '<th class="pmr-email-detail-label" align="left" valign="top" style="width:38%;padding:12px 14px;border-bottom:1px solid #e2e8eb;background:#f3f8f9;color:#002c3e;font-size:12px;font-weight:700;line-height:1.5;">' . esc_html((string) $label) . '</th>';
            $html .= '<td class="pmr-email-detail-value" valign="top" style="padding:12px 14px;border-bottom:1px solid #e2e8eb;color:#3f4b53;font-size:13px;line-height:1.55;">' . nl2br(esc_html((string) $value)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    private static function manager_button(): string
    {
        return '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;margin:24px 0 0;border-collapse:collapse;">'
            . '<tr><td align="center" style="padding:22px 18px;border-radius:6px;background:#f3f8f9;text-align:center;">'
            . '<p style="margin:0 0 14px;color:#53636d;font-size:13px;line-height:1.6;">'
            . esc_html__('Para ver todas las reservas y gestionarlas desde el panel privado, entra en el gestor haciendo clic aquí.', 'pedraza-mahou-reservations')
            . '</p>'
            . '<a href="' . esc_url(self::RESERVATIONS_MANAGER_URL) . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:13px 22px;border-radius:5px;background:#002c3e;color:#ffffff;font-size:14px;font-weight:700;line-height:1.2;text-decoration:none;">'
            . esc_html__('Abrir gestor de reservas', 'pedraza-mahou-reservations')
            . '</a>'
            . '</td></tr></table>';
    }

    private static function wrap_email(string $eyebrow, string $title, string $body, array $reservation): string
    {
        $settings = PMR_Admin_Settings::get_settings();
        $privacy_url = esc_url((string) $settings['privacy_url']);
        $reference = esc_html((string) ($reservation['reference'] ?? ''));

        return '<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<style>@import url("https://fonts.googleapis.com/css2?family=League+Spartan:wght@600;700;800&family=Montserrat:wght@400;500;600;700&display=swap");</style>'
            . '<style>.pmr-email-body,.pmr-email-content,.pmr-email-footer,.pmr-email-body table,.pmr-email-body td,.pmr-email-body th{font-family:"Montserrat","Avenir Next",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif!important}.pmr-email-heading{font-family:"League Spartan","Avenir Next","Segoe UI",Arial,Helvetica,sans-serif!important;font-weight:700!important;letter-spacing:0!important}@media only screen and (max-width:560px){.pmr-email-shell{padding:12px!important}.pmr-email-header,.pmr-email-content,.pmr-email-footer{padding-left:18px!important;padding-right:18px!important}.pmr-email-summary-cell{display:block!important;width:auto!important;margin-bottom:8px!important}.pmr-email-detail-label,.pmr-email-detail-value{display:block!important;width:auto!important}.pmr-email-detail-label{border-bottom:0!important;padding-bottom:3px!important}.pmr-email-detail-value{padding-top:3px!important}}</style>'
            . '</head><body class="pmr-email-body" style="margin:0;padding:0;background:#f4f7f8;font-family:&quot;Montserrat&quot;,&quot;Avenir Next&quot;,-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,Roboto,Helvetica,Arial,sans-serif;color:#263340;">'
            . '<div style="display:none;max-height:0;overflow:hidden;color:transparent;opacity:0;">' . esc_html__('Información de la reserva de cesta picnic.', 'pedraza-mahou-reservations') . '</div>'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;background:#f4f7f8;font-family:&quot;Montserrat&quot;,&quot;Avenir Next&quot;,-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,Roboto,Helvetica,Arial,sans-serif;"><tr><td class="pmr-email-shell" align="center" style="padding:32px 12px;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="max-width:680px;width:100%;overflow:hidden;border:1px solid #e0e7ea;border-radius:8px;background:#ffffff;box-shadow:0 12px 34px rgba(0,44,62,.09);font-family:&quot;Montserrat&quot;,&quot;Avenir Next&quot;,-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,Roboto,Helvetica,Arial,sans-serif;">'
            . '<tr><td style="height:6px;background:#e32322;font-size:0;line-height:0;">&nbsp;</td></tr>'
            . '<tr><td class="pmr-email-header" style="padding:28px 30px 26px;background:#002c3e;color:#ffffff;">'
            . '<span style="display:block;margin-bottom:12px;color:#fbd652;font-size:10px;font-weight:700;line-height:1.3;text-transform:uppercase;">' . esc_html($eyebrow) . '</span>'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;"><tr>'
            . '<td valign="middle"><h1 class="pmr-email-heading" style="margin:0;color:#ffffff;font-family:&quot;League Spartan&quot;,&quot;Avenir Next&quot;,&quot;Segoe UI&quot;,Arial,Helvetica,sans-serif;font-size:27px;font-weight:700;line-height:1.2;letter-spacing:0;">' . esc_html($title) . '</h1></td>'
            . '<td align="right" valign="middle" style="padding-left:16px;"><span style="display:inline-block;padding:9px 12px;border-radius:5px;background:#fbd652;color:#002c3e;font-size:14px;font-weight:800;white-space:nowrap;">' . $reference . '</span></td>'
            . '</tr></table></td></tr>'
            . '<tr><td class="pmr-email-content" style="padding:28px 30px 30px;font-family:&quot;Montserrat&quot;,&quot;Avenir Next&quot;,-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;">' . $body . '</td></tr>'
            . '<tr><td class="pmr-email-footer" style="padding:20px 30px;border-top:1px solid #e5eaed;background:#f3f8f9;color:#63727b;font-family:&quot;Montserrat&quot;,&quot;Avenir Next&quot;,-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,Roboto,Helvetica,Arial,sans-serif;font-size:11px;line-height:1.7;">'
            . '<strong style="display:block;color:#002c3e;font-size:12px;">Gran Castillo de Pedraza</strong>'
            . '<span>Reservas de cestas picnic Mahou · <a href="https://grancastillodepedraza.com/" style="color:#0087a3;text-decoration:underline;">grancastillodepedraza.com</a></span>'
            . ($privacy_url !== '' ? '<br><a href="' . $privacy_url . '" style="color:#0087a3;text-decoration:underline;">' . esc_html__('Política de Privacidad', 'pedraza-mahou-reservations') . '</a>' : '')
            . '</td></tr>'
            . '</table>'
            . '</td></tr></table>'
            . '</body></html>';
    }

    private static function replace_subject_tokens(string $subject, array $reservation): string
    {
        return strtr($subject, [
            '{reference}' => (string) ($reservation['reference'] ?? ''),
            '{name}' => (string) ($reservation['full_name'] ?? ''),
            '{pickup_date}' => (string) ($reservation['pickup_date'] ?? ''),
        ]);
    }

    private static function format_date(string $date): string
    {
        $timestamp = strtotime($date);

        if (! $timestamp) {
            return $date;
        }

        return date_i18n(get_option('date_format'), $timestamp);
    }
}
