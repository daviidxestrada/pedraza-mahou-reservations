<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Activator
{
    public static function activate(): void
    {
        PMR_Database::create_table();

        $current_settings = get_option(PMR_Admin_Settings::OPTION_NAME, []);
        $current_settings = is_array($current_settings) ? $current_settings : [];

        update_option(
            PMR_Admin_Settings::OPTION_NAME,
            wp_parse_args($current_settings, PMR_Admin_Settings::defaults())
        );
    }
}
