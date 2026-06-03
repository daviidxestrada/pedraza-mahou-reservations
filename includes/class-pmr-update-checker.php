<?php

if (! defined('ABSPATH')) {
    exit;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

final class PMR_Update_Checker
{
    private static $checker = null;

    public static function init(): void
    {
        $autoloader = PMR_PLUGIN_DIR . 'vendor/autoload.php';

        if (! file_exists($autoloader)) {
            return;
        }

        require_once $autoloader;

        if (! class_exists(PucFactory::class)) {
            return;
        }

        $settings = PMR_Admin_Settings::get_settings();
        $owner = self::owner($settings);
        $repo = self::repo($settings);

        if ($owner === '' || $repo === '') {
            return;
        }

        self::$checker = PucFactory::buildUpdateChecker(
            sprintf('https://github.com/%s/%s/', rawurlencode($owner), rawurlencode($repo)),
            PMR_PLUGIN_FILE,
            PMR_PLUGIN_SLUG
        );

        self::$checker->setBranch('main');

        $token = self::token($settings);

        if ($token !== '') {
            self::$checker->setAuthentication($token);
        }

        $api = self::$checker->getVcsApi();

        if (method_exists($api, 'enableReleaseAssets')) {
            $api->enableReleaseAssets('/pedraza-mahou-reservations\.zip$/i');
        }
    }

    public static function clear_cache(): void
    {
        if (self::$checker && method_exists(self::$checker, 'deleteCachedUpdate')) {
            self::$checker->deleteCachedUpdate();
        }

        delete_site_transient('update_plugins');
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
}
