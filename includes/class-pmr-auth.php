<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Auth
{
    private const COOKIE_NAME = 'pmr_admin_auth';
    private const COOKIE_TTL = 604800;
    private const LOGIN_LIMIT = 5;
    private const LOGIN_WINDOW = 900;

    public static function authenticate(string $username, string $password)
    {
        if (self::is_login_rate_limited()) {
            return new WP_Error(
                'pmr_login_rate_limited',
                __('Demasiados intentos. Inténtalo de nuevo pasados unos minutos.', 'pedraza-mahou-reservations')
            );
        }

        $settings = PMR_Admin_Settings::get_settings();

        if (empty($settings['private_password_hash'])) {
            return new WP_Error(
                'pmr_login_not_configured',
                __('El acceso privado todavía no está configurado.', 'pedraza-mahou-reservations')
            );
        }

        $username_ok = hash_equals((string) $settings['private_username'], $username);
        $password_ok = password_verify($password, (string) $settings['private_password_hash']);

        if (! $username_ok || ! $password_ok) {
            self::record_failed_login();

            return new WP_Error(
                'pmr_login_failed',
                __('Usuario o contraseña incorrectos.', 'pedraza-mahou-reservations')
            );
        }

        self::clear_failed_logins();
        self::set_auth_cookie($username);

        return true;
    }

    public static function is_authenticated(): bool
    {
        $raw_cookie = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME])) : '';

        if ($raw_cookie === '' || strpos($raw_cookie, '.') === false) {
            return false;
        }

        [$encoded_payload, $signature] = explode('.', $raw_cookie, 2);
        $expected_signature = hash_hmac('sha256', $encoded_payload, self::secret());

        if (! hash_equals($expected_signature, $signature)) {
            return false;
        }

        $payload_json = self::base64_url_decode($encoded_payload);
        $payload = json_decode($payload_json, true);

        if (! is_array($payload) || empty($payload['u']) || empty($payload['exp'])) {
            return false;
        }

        if ((int) $payload['exp'] < time()) {
            return false;
        }

        $settings = PMR_Admin_Settings::get_settings();

        return ! empty($settings['private_password_hash'])
            && hash_equals((string) $settings['private_username'], (string) $payload['u']);
    }

    public static function logout(): void
    {
        self::clear_auth_cookie();
    }

    public static function get_client_ip(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '';
    }

    private static function set_auth_cookie(string $username): void
    {
        $expires = time() + self::COOKIE_TTL;
        $payload = [
            'u' => $username,
            'iat' => time(),
            'exp' => $expires,
        ];

        $encoded_payload = self::base64_url_encode((string) wp_json_encode($payload));
        $signature = hash_hmac('sha256', $encoded_payload, self::secret());
        $cookie_value = $encoded_payload . '.' . $signature;

        setcookie(
            self::COOKIE_NAME,
            $cookie_value,
            [
                'expires' => $expires,
                'path' => self::cookie_path(),
                'domain' => self::cookie_domain(),
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        $_COOKIE[self::COOKIE_NAME] = $cookie_value;
    }

    private static function clear_auth_cookie(): void
    {
        setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => self::cookie_path(),
                'domain' => self::cookie_domain(),
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    private static function cookie_path(): string
    {
        if (defined('COOKIEPATH') && COOKIEPATH) {
            return COOKIEPATH;
        }

        return '/';
    }

    private static function cookie_domain(): string
    {
        if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
            return COOKIE_DOMAIN;
        }

        return '';
    }

    private static function secret(): string
    {
        $settings = PMR_Admin_Settings::get_settings();

        return wp_salt('auth') . '|' . home_url('/') . '|' . (string) $settings['private_password_hash'];
    }

    private static function base64_url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64_url_decode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private static function is_login_rate_limited(): bool
    {
        $attempts = get_transient(self::login_rate_key());

        return is_array($attempts)
            && isset($attempts['count'])
            && (int) $attempts['count'] >= self::LOGIN_LIMIT;
    }

    private static function record_failed_login(): void
    {
        $key = self::login_rate_key();
        $attempts = get_transient($key);
        $count = is_array($attempts) && isset($attempts['count']) ? (int) $attempts['count'] : 0;

        set_transient($key, ['count' => $count + 1], self::LOGIN_WINDOW);
    }

    private static function clear_failed_logins(): void
    {
        delete_transient(self::login_rate_key());
    }

    private static function login_rate_key(): string
    {
        return 'pmr_login_' . md5(self::get_client_ip() ?: 'unknown');
    }
}
