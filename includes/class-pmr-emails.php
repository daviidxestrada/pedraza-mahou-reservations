<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Emails
{
    private const PRICE_PER_BASKET = 15;

    public static function send_customer_email(array $reservation): bool
    {
        $settings = PMR_Admin_Settings::get_settings();
        $subject = self::replace_subject_tokens((string) $settings['customer_subject'], $reservation);
        $message = self::wrap_email(
            __('Reserva recibida', 'pedraza-mahou-reservations'),
            self::customer_email_body($reservation)
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
            __('Nueva reserva recibida', 'pedraza-mahou-reservations'),
            self::internal_email_body($reservation)
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

        return self::details_table([
            __('Referencia', 'pedraza-mahou-reservations') => $reservation['reference'],
            __('Nombre', 'pedraza-mahou-reservations') => $reservation['full_name'],
            __('Fecha de recogida', 'pedraza-mahou-reservations') => self::format_date($reservation['pickup_date']),
            __('Número de cestas', 'pedraza-mahou-reservations') => $basket_count,
            __('Precio por cesta', 'pedraza-mahou-reservations') => '15 € IVA incluido',
            __('Total orientativo', 'pedraza-mahou-reservations') => $total . ' € IVA incluido',
        ])
        . '<p>Hemos recibido tu solicitud de reserva de cesta picnic Mahou para el Gran Castillo de Pedraza.</p>'
        . '<p>La reserva se realiza sin pago online. El importe se abonará presencialmente en taquilla el día de la recogida, mediante efectivo o tarjeta.</p>'
        . '<p>Esta solicitud está sujeta a disponibilidad y podrá ser verificada con la referencia indicada y el nombre facilitado.</p>';
    }

    private static function internal_email_body(array $reservation): string
    {
        return self::details_table([
            __('Referencia', 'pedraza-mahou-reservations') => $reservation['reference'],
            __('Fecha de recogida', 'pedraza-mahou-reservations') => self::format_date($reservation['pickup_date']),
            __('Número de cestas', 'pedraza-mahou-reservations') => (int) $reservation['basket_count'],
            __('Nombre y apellidos', 'pedraza-mahou-reservations') => $reservation['full_name'],
            __('Email', 'pedraza-mahou-reservations') => $reservation['email'],
            __('Teléfono', 'pedraza-mahou-reservations') => $reservation['phone'],
            __('Observaciones', 'pedraza-mahou-reservations') => $reservation['observations'] ?: '-',
            __('Consentimiento comercial', 'pedraza-mahou-reservations') => ! empty($reservation['marketing_consent']) ? 'Sí' : 'No',
            __('Fecha y hora de creación', 'pedraza-mahou-reservations') => $reservation['created_at'],
        ]);
    }

    private static function details_table(array $rows): string
    {
        $html = '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:18px 0;background:#ffffff;border:1px solid #e7e2d8;">';

        foreach ($rows as $label => $value) {
            $html .= '<tr>';
            $html .= '<th align="left" style="width:38%;padding:12px 14px;border-bottom:1px solid #e7e2d8;background:#f8f4ec;color:#41382f;font-weight:700;">' . esc_html((string) $label) . '</th>';
            $html .= '<td style="padding:12px 14px;border-bottom:1px solid #e7e2d8;color:#2a2724;">' . nl2br(esc_html((string) $value)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    private static function wrap_email(string $title, string $body): string
    {
        return '<!doctype html><html><body style="margin:0;padding:0;background:#f2eee7;font-family:Arial,Helvetica,sans-serif;color:#2a2724;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;background:#f2eee7;padding:28px 12px;"><tr><td align="center">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e2dccf;">'
            . '<tr><td style="padding:24px 28px;background:#c4161c;color:#ffffff;"><h1 style="margin:0;font-size:24px;line-height:1.25;">' . esc_html($title) . '</h1></td></tr>'
            . '<tr><td style="padding:26px 28px;font-size:15px;line-height:1.6;">' . $body . '</td></tr>'
            . '<tr><td style="padding:16px 28px;background:#f8f4ec;color:#635a50;font-size:12px;">Gran Castillo de Pedraza · Reservas de cestas picnic Mahou</td></tr>'
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
