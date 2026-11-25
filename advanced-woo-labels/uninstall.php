<?php
/**
 * Uninstall plugin
 * Deletes all the plugin data
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'awl_settings' );
delete_option( 'awl_plugin_ver' );
delete_option( 'awl_bf2026_offer' );

delete_metadata( 'user', 0, 'awl_hide_bf2026_banner', '', true );
delete_metadata( 'user', 0, 'awl_bf2026_page_seen', '', true );