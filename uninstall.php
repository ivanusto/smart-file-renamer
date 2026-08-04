<?php
/**
 * Uninstall handler for Smart File Renamer.
 *
 * Settings are kept on deactivation and only removed when the plugin
 * is deleted through the WordPress admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'sfr_add_date_prefix' );
