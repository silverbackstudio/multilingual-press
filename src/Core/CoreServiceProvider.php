<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Common\Admin\ActionLink;
use Inpsyde\MultilingualPress\Common\Admin\AdminNotice;
use Inpsyde\MultilingualPress\Common\Admin\SettingsPage;
use Inpsyde\MultilingualPress\Common\Nonce\WPNonce;
use Inpsyde\MultilingualPress\Common\ConditionalAwareRequest;
use Inpsyde\MultilingualPress\Core\Admin\PluginSettingsPage;
use Inpsyde\MultilingualPress\Module;
use Inpsyde\MultilingualPress\Relations\Post\RelationshipContext;
use Inpsyde\MultilingualPress\Relations\Post\RelationshipController;
use Inpsyde\MultilingualPress\Relations\Post\RelationshipControlView;
use Inpsyde\MultilingualPress\Relations\Post\Search\RequestAwareSearch;
use Inpsyde\MultilingualPress\Relations\Post\Search\SearchController;
use Inpsyde\MultilingualPress\Relations\Post\Search\StatusAwareSearchResultsView;
use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;
use Inpsyde\MultilingualPress\Translation\FullRequestDataManipulator;
use Inpsyde\MultilingualPress\Translation\RequestDataManipulator;
use WP_Post;

/**
 * Service provider for all Core objects.
 *
 * @package Inpsyde\MultilingualPress\Core
 * @since   3.0.0
 */
final class CoreServiceProvider implements BootstrappableServiceProvider {

	/**
	 * Registers the provided services on the given container.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 *
	 * @return void
	 */
	public function register( Container $container ) {

		$container['multilingualpress.base_path_adapter'] = function () {

			return new CachingBasePathAdapter();
		};

		$container->share( 'multilingualpress.internal_locations', function () {

			return new InternalLocations();
		} );

		// TODO: Make a regular not shared service as soon as everything else has been adapted. Or remove from here?
		$container->share( 'multilingualpress.module_manager', function () {

			// TODO: Maybe store the option name somewhere? But then again, who else really needs to know it?
			// TODO: Migration: The old option name was "state_modules", and it stored "on" and "off" values, no bools.
			return new Module\NetworkOptionModuleManager( 'multilingualpress_modules' );
		} );

		$container['multilingualpress.plugin_settings_page'] = function ( Container $container ) {

			return SettingsPage::with_parent(
				SettingsPage::ADMIN_NETWORK,
				SettingsPage::PARENT_NETWORK_SETTINGS,
				__( 'MultilingualPress', 'multilingual-press' ),
				__( 'MultilingualPress', 'multilingual-press' ),
				'manage_network_options',
				'multilingualpress',
				$container['multilingualpress.plugin_settings_page_view']
			);
		};

		$container['multilingualpress.plugin_settings_page_view'] = function ( Container $container ) {

			return new PluginSettingsPage\View(
				$container['multilingualpress.module_manager'],
				$container['multilingualpress.update_plugin_settings_nonce'],
				$container['multilingualpress.asset_manager']
			);
		};

		$container['multilingualpress.plugin_settings_updater'] = function ( Container $container ) {

			return new PluginSettingsPage\PluginSettingsUpdater(
				$container['multilingualpress.module_manager'],
				$container['multilingualpress.update_plugin_settings_nonce'],
				$container['multilingualpress.plugin_settings_page']
			);
		};

		$container['multilingualpress.post_request_data_manipulator'] = function () {

			return new FullRequestDataManipulator( RequestDataManipulator::METHOD_POST );
		};

		$container['multilingualpress.relationship_control_search'] = function () {

			return new RequestAwareSearch();
		};

		$container['multilingualpress.relationship_control_search_controller'] = function ( Container $container ) {

			return new SearchController(
				$container['multilingualpress.relationship_control_search_results_view']
			);
		};

		$container['multilingualpress.relationship_control_search_results_view'] = function ( Container $container ) {

			return new StatusAwareSearchResultsView(
				$container['multilingualpress.relationship_control_search']
			);
		};

		$container['multilingualpress.relationship_control_view'] = function ( Container $container ) {

			return new RelationshipControlView(
				$container['multilingualpress.relationship_control_search_results_view'],
				$container['multilingualpress.asset_manager']
			);
		};

		$container['multilingualpress.relationship_controller'] = function ( Container $container ) {

			return new RelationshipController(
				$container['multilingualpress.content_relations']
			);
		};

		$container->share( 'multilingualpress.request', function () {

			return new ConditionalAwareRequest();
		} );

		$container['multilingualpress.update_plugin_settings_nonce'] = function () {

			return new WPNonce( 'update_plugin_settings' );
		};
	}

	/**
	 * Bootstraps the registered services.
	 *
	 * @since 3.0.0
	 *
	 * @param Container $container Container object.
	 *
	 * @return void
	 */
	public function bootstrap( Container $container ) {

		$properties = $container['multilingualpress.properties'];

		$plugin_dir_path = rtrim( $properties->plugin_dir_path(), '/' );

		$plugin_dir_url = rtrim( $properties->plugin_dir_url(), '/' );

		$container['multilingualpress.internal_locations']
			->add(
				'plugin',
				$plugin_dir_path,
				$plugin_dir_url
			)
			->add(
				'css',
				"$plugin_dir_path/assets/css",
				"$plugin_dir_url/assets/css"
			)
			->add(
				'js',
				"$plugin_dir_path/assets/js",
				"$plugin_dir_url/assets/js"
			)
			->add(
				'images',
				"$plugin_dir_path/assets/images",
				"$plugin_dir_url/assets/images"
			)
			->add(
				'flags',
				"$plugin_dir_path/assets/images/flags",
				"$plugin_dir_url/assets/images/flags"
			);

		$setting_page = $container['multilingualpress.plugin_settings_page'];

		add_action( 'plugins_loaded', [ $setting_page, 'register' ], 8 );

		add_action(
			'admin_post_' . PluginSettingsPage\PluginSettingsUpdater::ACTION,
			[ $container['multilingualpress.plugin_settings_updater'], 'update_settings' ]
		);

		add_action( 'network_admin_notices', function () use ( $setting_page ) {

			if (
				isset( $_GET['message'] )
				&& isset( $GLOBALS['hook_suffix'] )
				&& $setting_page->hookname() === $GLOBALS['hook_suffix']
			) {
				( new AdminNotice( '<p>' . __( 'Settings saved.', 'multilingual-press' ) . '</p>' ) )->render();
			}
		} );

		( new ActionLink(
			'settings',
			'<a href="' . esc_url( $setting_page->url() ) . '">' . __( 'Settings', 'multilingual-press' ) . '</a>'
		) )->register( 'network_admin_plugin_action_links_' . $properties->plugin_base_name() );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			$post_request_data_manipulator = $container['multilingualpress.post_request_data_manipulator'];

			add_action( 'mlp_before_post_synchronization', [ $post_request_data_manipulator, 'clear_data' ] );
			add_action( 'mlp_after_post_synchronization', [ $post_request_data_manipulator, 'restore_data' ] );

			add_action( 'mlp_before_term_synchronization', [ $post_request_data_manipulator, 'clear_data' ] );
			add_action( 'mlp_after_term_synchronization', [ $post_request_data_manipulator, 'restore_data' ] );
		}

		if ( is_admin() ) {
			$relationship_control_view = $container['multilingualpress.relationship_control_view'];

			add_action(
				'mlp_translation_meta_box_bottom',
				function ( WP_Post $post, $remote_site_id, WP_Post $remote_post ) use ( $relationship_control_view ) {

					global $pagenow;
					if ( 'post.php' === $pagenow ) {
						$relationship_control_view->render( new RelationshipContext( [
							RelationshipContext::KEY_REMOTE_POST_ID => $remote_post->ID,
							RelationshipContext::KEY_REMOTE_SITE_ID => $remote_site_id,
							RelationshipContext::KEY_SOURCE_POST_ID => $post->ID,
							RelationshipContext::KEY_SOURCE_SITE_ID => get_current_blog_id(),
						] ) );
					}
				},
				200,
				3
			);

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) ) {
				switch ( $_REQUEST['action'] ) {
					case SearchController::ACTION:
						$container['multilingualpress.relationship_control_search_controller']->initialize();
						break;

					case RelationshipController::ACTION_CONNECT_EXISTING:
					case RelationshipController::ACTION_CONNECT_NEW:
					case RelationshipController::ACTION_DISCONNECT:
						$container['multilingualpress.relationship_controller']->initialize();
						break;
				}
			}
		}
	}
}
