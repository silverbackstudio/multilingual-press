<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Installation;

use Inpsyde\MultilingualPress\Common\PluginProperties;
use Inpsyde\MultilingualPress\Common\Type\VersionNumber;
use Inpsyde\MultilingualPress\Factory\TypeFactory;

/**
 * Performs various system-specific checks.
 *
 * @package Inpsyde\MultilingualPress\Installation
 * @since   3.0.0
 */
class SystemChecker {

	/**
	 * Installation check status.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	const WRONG_PAGE_FOR_CHECK = 1;

	/**
	 * Installation check status.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	const INSTALLATION_OK = 2;

	/**
	 * Installation check status.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	const PLUGIN_DEACTIVATED = 3;

	/**
	 * Version check status.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	const VERSION_OK = 4;

	/**
	 * Version check status.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	const NEEDS_INSTALLATION = 5;

	/**
	 * Version check status.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	const NEEDS_UPGRADE = 6;

	/**
	 * Required minimum PHP version.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const MINIMUM_PHP_VERSION = '5.4.0';

	/**
	 * Required minimum WordPress version.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const MINIMUM_WORDPRESS_VERSION = '4.4.0';

	/**
	 * @var string[]
	 */
	private $errors = [];

	/**
	 * @var PluginProperties
	 */
	private $plugin_properties;

	/**
	 * @var SiteRelationsChecker
	 */
	private $site_relations_checker;

	/**
	 * @var TypeFactory
	 */
	private $type_factory;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param PluginProperties     $plugin_properties      Plugin properties object.
	 * @param TypeFactory          $type_factory           Type factory object.
	 * @param SiteRelationsChecker $site_relations_checker Site relations checkerobject.
	 */
	public function __construct(
		PluginProperties $plugin_properties,
		TypeFactory $type_factory,
		SiteRelationsChecker $site_relations_checker
	) {

		$this->plugin_properties = $plugin_properties;

		$this->type_factory = $type_factory;

		$this->site_relations_checker = $site_relations_checker;
	}

	/**
	 * Checks the installation for compliance with the system requirements.
	 *
	 * @since 3.0.0
	 *
	 * @return int The status of the installation check.
	 */
	public function check_installation() {

		if ( ! $this->is_plugins_page() ) {
			return self::WRONG_PAGE_FOR_CHECK;
		}

		$this->check_php_version();

		$this->check_wordpress_version();

		$this->check_multisite();

		$this->check_plugin_activation();

		if ( ! $this->errors ) {
			$this->site_relations_checker->check_relations();

			return self::INSTALLATION_OK;
		}

		$deactivator = new PluginDeactivator(
			$this->plugin_properties->plugin_base_name(),
			$this->plugin_properties->plugin_name(),
			$this->errors
		);

		add_action( 'admin_notices', [ $deactivator, 'deactivate_plugin' ], 0 );
		add_action( 'network_admin_notices', [ $deactivator, 'deactivate_plugin' ], 0 );

		return self::PLUGIN_DEACTIVATED;
	}

	/**
	 * Checks the installed plugin version.
	 *
	 * @since 3.0.0
	 *
	 * @param VersionNumber $installed_version Installed MultilingualPress version.
	 * @param VersionNumber $current_version   Current MultilingualPress version.
	 *
	 * @return int The status of the version check.
	 */
	public function check_version( VersionNumber $installed_version, VersionNumber $current_version ) {

		if ( version_compare( $installed_version, $current_version, '>=' ) ) {
			return self::VERSION_OK;
		}

		// TODO: Is this really what we want to check here?
		$languages = get_network_option( null, 'inpsyde_multilingual', [] );
		if ( $languages ) {
			return self::NEEDS_UPGRADE;
		}

		return self::NEEDS_INSTALLATION;
	}

	/**
	 * Checks if this is the plugins page in the (Network) Admin.
	 *
	 * @return bool Whether or not this is the plugins page in the (Network) Admin.
	 */
	private function is_plugins_page() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		if ( ! is_admin() ) {
			return false;
		}

		return 'plugins.php' === $GLOBALS['pagenow'];
	}

	/**
	 * Checks if the current PHP version is the required version higher, and collects potential error messages.
	 *
	 * @return void
	 */
	private function check_php_version() {

		$current_version = $this->type_factory->create_version_number( [
			phpversion(),
		] );

		$required_version = $this->type_factory->create_version_number( [
			self::MINIMUM_PHP_VERSION,
		] );

		if ( version_compare( $current_version, $required_version, '>=' ) ) {
			return;
		}

		/* translators: 1: required PHP version, 2: current PHP version */
		$message = esc_html__(
			'This plugin requires PHP version %1$s, your version %2$s is too old. Please upgrade.',
			'multilingual-press'
		);

		$this->errors[] = sprintf( $message, $required_version, $current_version );
	}

	/**
	 * Checks if the current WordPress version is the required version higher, and collects potential error messages.
	 *
	 * @return void
	 */
	private function check_wordpress_version() {

		$current_version = $this->type_factory->create_version_number( [
			$GLOBALS['wp_version'],
		] );

		$required_version = $this->type_factory->create_version_number( [
			self::MINIMUM_WORDPRESS_VERSION,
		] );

		if ( version_compare( $current_version, $required_version, '>=' ) ) {
			return;
		}

		/* translators: 1: required WordPress version, 2: current WordPress version */
		$message = esc_html__(
			'This plugin requires WordPress version %1$s, your version %2$s is too old. Please upgrade.',
			'multilingual-press'
		);

		$this->errors[] = sprintf( $message, $required_version, $current_version );
	}

	/**
	 * Checks if this is a multisite installation, and collects potential error messages.
	 *
	 * @return void
	 */
	private function check_multisite() {

		if ( is_multisite() ) {
			return;
		}

		/* translators: %s: link to installation instructions */
		$message = __(
			'This plugin needs to run in a multisite. Please <a href="%s">convert this WordPress installation to multisite</a>.',
			'multilingual-press'
		);

		$this->errors[] = sprintf( $message, 'http://make.multilingualpress.org/2014/02/how-to-install-multi-site/' );
	}

	/**
	 * Checks if MultilingualPress has been activated network-wide, and collects potential error messages.
	 *
	 * @return void
	 */
	private function check_plugin_activation() {

		$plugin_file_path = wp_normalize_path( realpath( $this->plugin_properties->plugin_file_path() ) );

		foreach ( wp_get_active_network_plugins() as $plugin ) {
			if ( $plugin_file_path === wp_normalize_path( realpath( $plugin ) ) ) {
				return;
			}
		}

		/* translators: %s: link to network plugin screen */
		$message = __(
			'This plugin must be activated for the network. Please use the <a href="%s">network plugin administration</a>.',
			'multilingual-press'
		);

		$this->errors[] = sprintf( $message, esc_url( network_admin_url( 'plugins.php' ) ) );
	}
}
