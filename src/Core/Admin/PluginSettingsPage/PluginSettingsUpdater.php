<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core\Admin\PluginSettingsPage;

use Inpsyde\MultilingualPress\Common\Admin\SettingsPage;
use Inpsyde\MultilingualPress\Common\Nonce\Nonce;
use Inpsyde\MultilingualPress\Module\ModuleManager;

/**
 * Plugin settings updater.
 *
 * @package Inpsyde\MultilingualPress\Core\Admin\PluginSettingsPage
 * @since   3.0.0
 */
class PluginSettingsUpdater {

	/**
	 * Action used for updating plugin settings.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const ACTION = 'update_multilingualpress_settings';

	/**
	 * @var ModuleManager
	 */
	private $module_manager;

	/**
	 * @var Nonce
	 */
	private $nonce;

	/**
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param ModuleManager $module_manager Module manager object.
	 * @param Nonce         $nonce          Nonce object.
	 * @param SettingsPage  $settings_page  Settings page object.
	 */
	public function __construct( ModuleManager $module_manager, Nonce $nonce, SettingsPage $settings_page ) {

		$this->module_manager = $module_manager;

		$this->nonce = $nonce;

		$this->settings_page = $settings_page;
	}

	/**
	 * Updates the plugin settings according to the data in the request.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function update_settings() {

		\Inpsyde\MultilingualPress\check_admin_referer( $this->nonce );

		array_walk( array_keys( $this->module_manager->get_modules() ), [ $this, 'update_module' ] );

		$this->module_manager->save_modules();

		/**
		 * Runs before the redirect.
		 *
		 * Process your fields in the $_POST superglobal here and then call update_site_option().
		 *
		 * @param array $_POST
		 */
		do_action( 'mlp_modules_save_fields', $_POST );

		wp_safe_redirect( add_query_arg( 'message', 'updated', $this->settings_page->url() ) );
		\Inpsyde\MultilingualPress\call_exit();
	}

	/**
	 * Updates a single module according to the data in the request.
	 *
	 * @param string $id Module ID.
	 *
	 * @return void
	 */
	private function update_module( $id ) {

		if ( empty( $_POST['multilingualpress_modules'][ $id ] ) ) {
			$this->module_manager->deactivate_module( $id );
		} else {
			$this->module_manager->activate_module( $id );
		}
	}
}
