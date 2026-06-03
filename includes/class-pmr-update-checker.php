<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Update_Checker
{
    private const PACKAGE_SCHEME = 'pmr-github-update://';
    private const CACHE_TTL = 21600;

    public static function init(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'check_for_updates']);
        add_filter('plugins_api', [__CLASS__, 'plugin_information'], 10, 3);
        add_filter('upgrader_pre_download', [__CLASS__, 'download_package'], 10, 4);
        add_filter('upgrader_source_selection', [__CLASS__, 'rename_github_source_folder'], 10, 4);
    }

    public static function clear_cache(): void
    {
        $settings = PMR_Admin_Settings::get_settings();
        delete_site_transient(self::cache_key($settings));
    }

    public static function check_for_updates($transient)
    {
        if (! is_object($transient)) {
            return $transient;
        }

        $plugin_file = plugin_basename(PMR_PLUGIN_FILE);

        if (empty($transient->checked) || ! isset($transient->checked[$plugin_file])) {
            return $transient;
        }

        $release = self::latest_release();

        if (! $release || version_compare($release['version'], PMR_VERSION, '<=')) {
            return $transient;
        }

        $transient->response[$plugin_file] = (object) [
            'id' => $release['html_url'],
            'slug' => PMR_PLUGIN_SLUG,
            'plugin' => $plugin_file,
            'new_version' => $release['version'],
            'url' => $release['html_url'],
            'package' => self::package_marker($release['tag']),
            'requires_php' => '8.0',
        ];

        return $transient;
    }

    public static function plugin_information($result, string $action, $args)
    {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== PMR_PLUGIN_SLUG) {
            return $result;
        }

        $release = self::latest_release();

        if (! $release) {
            return $result;
        }

        return (object) [
            'name' => 'Pedraza Mahou Reservations',
            'slug' => PMR_PLUGIN_SLUG,
            'version' => $release['version'],
            'author' => '<a href="https://grancastillodepedraza.com/">Wonderland Group</a>',
            'homepage' => $release['html_url'],
            'requires_php' => '8.0',
            'download_link' => self::package_marker($release['tag']),
            'sections' => [
                'description' => '<p>Gestiona reservas de cestas picnic Mahou mediante shortcodes para WordPress y Elementor.</p>',
                'changelog' => wpautop(esc_html($release['body'] ?: 'Release ' . $release['tag'])),
            ],
        ];
    }

    public static function download_package($reply, string $package, $upgrader, array $hook_extra)
    {
        if (strpos($package, self::PACKAGE_SCHEME) !== 0) {
            return $reply;
        }

        $release = self::latest_release(true);

        if (! $release || empty($release['zipball_url'])) {
            return new WP_Error('pmr_update_missing_package', __('No se encontró el paquete de actualización en GitHub.', 'pedraza-mahou-reservations'));
        }

        $response = wp_remote_get(
            $release['zipball_url'],
            [
                'timeout' => 60,
                'redirection' => 5,
                'headers' => self::github_headers(),
            ]
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            return new WP_Error(
                'pmr_update_download_failed',
                sprintf(
                    /* translators: %d HTTP status code */
                    __('GitHub no permitió descargar el paquete de actualización. Código HTTP: %d.', 'pedraza-mahou-reservations'),
                    $code
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $tmp_file = wp_tempnam(PMR_PLUGIN_SLUG . '-' . $release['version'] . '.zip');

        if (! $tmp_file) {
            return new WP_Error('pmr_update_temp_failed', __('No se pudo crear un archivo temporal para la actualización.', 'pedraza-mahou-reservations'));
        }

        if (file_put_contents($tmp_file, $body) === false) {
            @unlink($tmp_file);

            return new WP_Error('pmr_update_write_failed', __('No se pudo escribir el paquete temporal de actualización.', 'pedraza-mahou-reservations'));
        }

        return $tmp_file;
    }

    public static function rename_github_source_folder($source, string $remote_source, $upgrader, array $hook_extra)
    {
        $plugin_file = plugin_basename(PMR_PLUGIN_FILE);

        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $plugin_file) {
            return $source;
        }

        $plugin_dir = dirname($plugin_file);

        if (basename(untrailingslashit($source)) === $plugin_dir) {
            return $source;
        }

        global $wp_filesystem;

        if (! $wp_filesystem) {
            return $source;
        }

        $new_source = trailingslashit($remote_source) . $plugin_dir;

        if ($wp_filesystem->exists($new_source)) {
            $wp_filesystem->delete($new_source, true);
        }

        if ($wp_filesystem->move($source, $new_source, true)) {
            return $new_source;
        }

        return $source;
    }

    private static function latest_release(bool $force = false)
    {
        $settings = PMR_Admin_Settings::get_settings();
        $owner = self::owner($settings);
        $repo = self::repo($settings);

        if ($owner === '' || $repo === '') {
            return false;
        }

        $cache_key = self::cache_key($settings);

        if (! $force) {
            $cached = get_site_transient($cache_key);

            if (is_array($cached) && ! empty($cached['tag'])) {
                return $cached;
            }
        }

        $response = wp_remote_get(
            sprintf('https://api.github.com/repos/%s/%s/releases/latest', rawurlencode($owner), rawurlencode($repo)),
            [
                'timeout' => 15,
                'headers' => self::github_headers(),
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (! is_array($body) || empty($body['tag_name'])) {
            return false;
        }

        $version = self::normalize_version((string) $body['tag_name']);

        if ($version === '') {
            return false;
        }

        $release = [
            'owner' => $owner,
            'repo' => $repo,
            'tag' => (string) $body['tag_name'],
            'version' => $version,
            'name' => (string) ($body['name'] ?? $body['tag_name']),
            'body' => (string) ($body['body'] ?? ''),
            'html_url' => (string) ($body['html_url'] ?? sprintf('https://github.com/%s/%s/releases', $owner, $repo)),
            'zipball_url' => (string) ($body['zipball_url'] ?? ''),
            'published_at' => (string) ($body['published_at'] ?? ''),
        ];

        set_site_transient($cache_key, $release, self::CACHE_TTL);

        return $release;
    }

    private static function owner(array $settings): string
    {
        if (defined('PMR_GITHUB_OWNER') && PMR_GITHUB_OWNER) {
            return (string) PMR_GITHUB_OWNER;
        }

        return (string) ($settings['github_owner'] ?? '');
    }

    private static function repo(array $settings): string
    {
        if (defined('PMR_GITHUB_REPO') && PMR_GITHUB_REPO) {
            return (string) PMR_GITHUB_REPO;
        }

        return (string) ($settings['github_repo'] ?? '');
    }

    private static function token(array $settings): string
    {
        if (defined('PMR_GITHUB_TOKEN') && PMR_GITHUB_TOKEN) {
            return (string) PMR_GITHUB_TOKEN;
        }

        return (string) ($settings['github_token'] ?? '');
    }

    private static function github_headers(): array
    {
        $settings = PMR_Admin_Settings::get_settings();
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Pedraza-Mahou-Reservations/' . PMR_VERSION,
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        $token = self::token($settings);

        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    private static function normalize_version(string $tag): string
    {
        $version = ltrim(trim($tag), 'vV');

        if (! preg_match('/^\d+(?:\.\d+){0,3}(?:[-+][A-Za-z0-9.-]+)?$/', $version)) {
            return '';
        }

        return $version;
    }

    private static function package_marker(string $tag): string
    {
        return self::PACKAGE_SCHEME . rawurlencode($tag);
    }

    private static function cache_key(array $settings): string
    {
        return 'pmr_github_release_' . md5(self::owner($settings) . '/' . self::repo($settings));
    }
}
