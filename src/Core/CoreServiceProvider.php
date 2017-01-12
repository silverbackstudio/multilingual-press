<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Core;

use Inpsyde\MultilingualPress\Common\Admin\ActionLink;
use Inpsyde\MultilingualPress\Common\Admin\AdminNotice;
use Inpsyde\MultilingualPress\Common\Admin\SettingsPage;
use Inpsyde\MultilingualPress\Common\Admin\SitesListTableColumn;
use Inpsyde\MultilingualPress\Common\Nonce\WPNonce;
use Inpsyde\MultilingualPress\Common\ConditionalAwareRequest;
use Inpsyde\MultilingualPress\Core\Admin\PluginSettingsPageView;
use Inpsyde\MultilingualPress\Core\Admin\PluginSettingsUpdater;
use Inpsyde\MultilingualPress\Module;
use Inpsyde\MultilingualPress\Service\Container;
use Inpsyde\MultilingualPress\Service\BootstrappableServiceProvider;

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

			return new PluginSettingsPageView(
				$container['multilingualpress.module_manager'],
				$container['multilingualpress.update_plugin_settings_nonce'],
				$container['multilingualpress.asset_manager']
			);
		};

		$container['multilingualpress.plugin_settings_updater'] = function ( Container $container ) {

			return new PluginSettingsUpdater(
				$container['multilingualpress.module_manager'],
				$container['multilingualpress.update_plugin_settings_nonce'],
				$container['multilingualpress.plugin_settings_page']
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

		load_plugin_textdomain( 'multilingual-press' );

		$setting_page = $container['multilingualpress.plugin_settings_page'];

		add_action( 'plugins_loaded', [ $setting_page, 'register' ], 8 );

		add_action(
			'admin_post_' . PluginSettingsUpdater::ACTION,
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

		// TODO: Bundle the following block in some PluginDataDeletor or so...
		$content_relations = $container['multilingualpress.content_relations'];
		$site_relations = $container['multilingualpress.site_relations'];
		add_action( 'delete_blog', function ( $site_id ) use ( $content_relations, $site_relations ) {

			$content_relations->delete_relations_for_site( $site_id );

			$site_relations->delete_relation( $site_id );

			// TODO: Refactor to use a repository as soon as available (see Mlp_Network_Site_Settings_Controller).
			$languages = get_network_option( null, 'inpsyde_multilingual' );
			if ( isset( $languages[ $site_id ] ) ) {
				unset( $languages[ $site_id ] );

				update_site_option( 'inpsyde_multilingual', $languages );
			}
		} );

		if ( is_admin() ) {
			global $pagenow;

			if ( 'sites.php' === $pagenow ) {
				( new SitesListTableColumn(
					'multilingualpress.relationships',
					__( 'Relationships', 'multilingual-press' ),
					function ( $id, $site_id ) {

						switch_to_blog( $site_id );
						$sites = \Inpsyde\MultilingualPress\get_available_language_names();
						restore_current_blog();
						unset( $sites[ $site_id ] );

						return $sites
							? sprintf(
								'<div class="mlp-site-relations">%s</div>',
								join( '<br>', array_map( 'esc_html', $sites ) )
							)
							: __( 'none', 'multilingual-press' );
					}
				) )->register();

				( new SitesListTableColumn(
					'multilingualpress.site_language',
					__( 'Site Language', 'multilingual-press' ),
					function ( $id, $site_id ) {

						$language = \Inpsyde\MultilingualPress\get_site_language( $site_id );

						return '' === $language
							? __( 'none', 'multilingual-press' )
							: sprintf(
								'<div class="mlp-site-language">%s</div>',
								esc_html( \Inpsyde\MultilingualPress\get_language_by_http_name(
									str_replace( '_', '-', $language )
								) )
							);
					}
				) )->register();
			}
		} else {
			$translations = $container['multilingualpress.translations'];

			add_action( 'template_redirect', function () use ( $translations ) {

				if ( ! is_paged() ) {
					( new FrontEnd\AlternateLanguageHTTPHeaders( $translations ) )->send();
				}
			} );
			add_action( 'wp_head', function () use ( $translations ) {

				if ( ! is_paged() ) {
					( new FrontEnd\AlternateLanguageHTMLLinkTags( $translations ) )->render();
				}
			} );

			add_filter( 'language_attributes', __NAMESPACE__ . '\\replace_language_in_language_attributes' );
		}
	}
}
