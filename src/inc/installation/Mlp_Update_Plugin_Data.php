<?php # -*- coding: utf-8 -*-

/**
 * MultilingualPress Installation
 */
class Mlp_Update_Plugin_Data {

	/**
	 * @var Inpsyde_Property_List_Interface
	 */
	private $plugin_data;

	/**
	 * @var Mlp_Version_Number_Interface
	 */
	private $last_version;

	/**
	 * @var Mlp_Version_Number_Interface
	 */
	private $current_version;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var array
	 */
	private $all_sites;

	/**
	 * Constructor. Set up the properties.
	 *
	 * @param Inpsyde_Property_List_Interface $plugin_data
	 * @param wpdb                            $wpdb
	 * @param Mlp_Version_Number_Interface    $current_version
	 * @param Mlp_Version_Number_Interface    $last_version
	 *
	 * @return  Mlp_Update_Plugin_Data
	 */
	public function __construct(
		Inpsyde_Property_List_Interface $plugin_data,
		wpdb $wpdb,
		Mlp_Version_Number_Interface $current_version,
		Mlp_Version_Number_Interface $last_version
	) {

		$this->plugin_data = $plugin_data;

		$this->wpdb = $wpdb;

		$this->current_version = $current_version;

		$this->last_version = $last_version;

		$this->all_sites = wp_get_sites();
	}

	/**
	 * Handle the update routines.
	 *
	 * @param Mlp_Network_Plugin_Deactivation_Interface $deactivator
	 *
	 * @return void
	 */
	public function update( Mlp_Network_Plugin_Deactivation_Interface $deactivator ) {

		$deactivator->deactivate(
			array(
				'disable-acf.php',
				'mlp-wp-seo-compat.php',
			)
		);

		// add hook to import active languages when reset is done
		add_action( 'mlp_reset_table_done', array( $this, 'import_active_languages' ) );

		// The site option with the version number exists since 2.0.
		// If the last version is a fallback, it is a version below 2.0.
		if ( Mlp_Version_Number_Interface::FALLBACK_VERSION === $this->last_version ) {
			$this->update_plugin_data( 1 );
		} else {
			$this->update_plugin_data( $this->last_version );
		}
	}

	/**
	 * Handle updates.
	 *
	 * @param string|int $last_version Last plugin version.
	 *
	 * @return bool
	 */
	private function update_plugin_data( $last_version ) {

		if ( $last_version === 1 ) {
			$this->import_active_languages( new Mlp_Db_Languages_Schema( $this->wpdb ) );
		}

		// TODO: Define correct version
		if ( version_compare( $last_version, '2.3.0', '<' ) ) {

			// TODO: Delete all entries in the multilingual_linked (!) table for posts/terms that don't exist anymore

			// TODO: Trigger data migration from multilingual_linked to both content_relations and relationships

			$installer = new Mlp_Db_Installer( $this->plugin_data->get( 'site_relations_schema' ) );

			if ( version_compare( $last_version, '2.0.4', '<' ) ) {
				$installer->install();

				$this->import_site_relations();

				$this->update_type_column();
			}

			$installer->install( $this->plugin_data->get( 'relationships_schema' ) );

			$installer->install( $this->plugin_data->get( 'content_relations_schema' ) );
		}

		// remove unneeded plugin data
		delete_option( 'inpsyde_companyname' );

		return update_site_option( 'mlp_version', $this->plugin_data->get( 'version' ) );
	}

	/**
	 * Move site relationships from separate options to network table.
	 *
	 * @return void
	 */
	private function import_site_relations() {

		$option_name = 'inpsyde_multilingual_blog_relationship';

		$inserted = 0;

		/** @var Mlp_Site_Relations_Interface $db */
		$relations = $this->plugin_data->get( 'site_relations' );

		foreach ( $this->all_sites as $site ) {

			$linked = get_blog_option( $site[ 'blog_id' ], $option_name, array() );

			if ( ! empty( $linked ) ) {
				$inserted += $relations->set_relation( $site[ 'blog_id' ], $linked );
			}

			delete_blog_option( $site[ 'blog_id' ], $option_name );
		}
	}

	/**
	 * Update mlp_multilingual_linked table and set type to "post" if empty.
	 *
	 * @return void
	 */
	private function update_type_column() {

		$sql = "
UPDATE {$this->wpdb->base_prefix}multilingual_linked
SET ml_type = 'post'
WHERE ml_type != 'term'";

		$this->wpdb->query( $sql );
	}

	/**
	 * Load the localization.
	 *
	 * @param Mlp_Db_Schema_Interface $languages
	 *
	 * @return void
	 */
	private function import_active_languages( Mlp_Db_Schema_Interface $languages ) {

		// get active languages
		$mlp_settings = get_site_option( 'inpsyde_multilingual' );
		if ( empty( $mlp_settings ) ) {
			return;
		}

		$table = $languages->get_table_name();

		$sql = "
SELECT ID
FROM $table
WHERE wp_locale = %s
	OR iso_639_1 = %s";

		foreach ( $mlp_settings as $mlp_site ) {

			$query = $this->wpdb->prepare( $sql, $mlp_site[ 'lang' ], $mlp_site[ 'lang' ] );

			$lang_id = $this->wpdb->get_var( $query );
			if ( empty( $lang_id ) ) {
				$text = $mlp_site[ 'text' ] !== ''
					? $mlp_site[ 'text' ]
					: $mlp_site[ 'lang' ];

				// language not found -> insert
				// @todo add custom name
				$this->wpdb->insert(
					$table,
					array(
						'english_name' => $text,
						'wp_locale'    => $mlp_site[ 'lang' ],
						'http_name'    => str_replace( '_', '-', $mlp_site[ 'lang' ] ),
					)
				);
			} else {
				// language found -> change priority
				$this->wpdb->update(
					$table,
					array( 'priority' => 10 ),
					array( 'ID' => $lang_id )
				);
			}
		}
	}

	/**
	 * Install the plugin tables.
	 *
	 * @return bool
	 */
	public function install_plugin() {

		$installer = new Mlp_Db_Installer( new Mlp_Db_Languages_Schema( $this->wpdb ) );
		$installer->install();
		$installer->install( $this->plugin_data->get( 'site_relations_schema' ) );
		$installer->install( $this->plugin_data->get( 'relationships_schema' ) );
		$installer->install( $this->plugin_data->get( 'content_relations_schema' ) );

		return update_site_option( 'mlp_version', $this->plugin_data->get( 'version' ) );
	}

}
