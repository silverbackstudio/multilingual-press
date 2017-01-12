<?php # -*- coding: utf-8 -*-
/**
 * Uninstall routines.
 *
 * This file is called automatically when the plugin is deleted per user interface.
 *
 * @see https://developer.wordpress.org/plugins/the-basics/uninstall-methods/
 */

namespace Inpsyde\MultilingualPress;

defined( 'ABSPATH' ) or die();

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

if ( ! is_multisite() ) {
	return;
}

$main_plugin_file = __DIR__ . '/multilingual-press.php';

if (
	plugin_basename( $main_plugin_file ) !== WP_UNINSTALL_PLUGIN
	|| ! is_readable( $main_plugin_file )
) {
	unset( $main_plugin_file );

	return;
}

/** @noinspection PhpIncludeInspection
 * MultilingualPress main plugin file.
 */
require_once $main_plugin_file;

unset( $main_plugin_file );

if ( bootstrap() ) {
	return;
}

$uninstaller = MultilingualPress::resolve( 'multilingualpress.uninstaller' );

$uninstaller->uninstall_tables( [
	MultilingualPress::resolve( 'multilingualpress.content_relations_table' ),
	MultilingualPress::resolve( 'multilingualpress.languages_table' ),
	MultilingualPress::resolve( 'multilingualpress.site_relations_table' ),
] );

// TODO: Use class constants instead of hard-coded strings.
$uninstaller->delete_network_options( [
	'inpsyde_multilingual',
	'inpsyde_multilingual_cpt',
	'inpsyde_multilingual_quicklink_options',
	'mlp_version',
	'multilingual_press_check_db',
	'state_modules',
] );

// TODO: Use class constants instead of hard-coded strings.
$uninstaller->delete_site_options( [
	'inpsyde_license_status_MultilingualPress Pro',
	'inpsyde_multilingual_blog_relationship',
	'inpsyde_multilingual_default_actions',
	'inpsyde_multilingual_flag_url',
	'inpsyde_multilingual_redirect',
] );

unset( $uninstaller );
