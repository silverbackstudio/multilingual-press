<?php # -*- coding: utf-8 -*-

/**
 * Create new blogs based on an existing one.
 */
class Mlp_Duplicate_Blogs {

	/**
	 * @var Mlp_Content_Relations_Interface
	 */
	private $content_relations;

	/**
	 * @var Mlp_Table_Duplicator_Interface
	 */
	private $duplicator;

	/**
	 * @var Mlp_Db_Table_List_Interface
	 */
	private $table_names;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor
	 *
	 * @param                                 $deprecated
	 * @param wpdb                            $wpdb
	 * @param Mlp_Table_Duplicator_Interface  $duplicator
	 * @param Mlp_Db_Table_List_Interface     $table_names
	 * @param Mlp_Content_Relations_Interface $content_relations
	 */
	public function __construct(
		$deprecated,
		wpdb $wpdb,
		Mlp_Table_Duplicator_Interface $duplicator,
		Mlp_Db_Table_List_Interface $table_names,
		Mlp_Content_Relations_Interface $content_relations
	) {

		$this->wpdb = $wpdb;

		$this->duplicator  = $duplicator;

		$this->table_names = $table_names;

		$this->content_relations = $content_relations;
	}

	/**
	 * Register callbacks.
	 *
	 * @return void
	 */
	public function setup() {

		add_filter( 'wpmu_new_blog', array ( $this, 'wpmu_new_blog' ), 10, 2 );
		add_filter( 'mlp_after_new_blog_fields', array ( $this, 'display_fields' ) );
	}

	/**
	 * Duplicates the old blog to the new blog
	 *
	 * @global    wpdb $wpdb WordPress Database Wrapper
	 * @param	int $blog_id the new blog id
	 * @return	void
	 */
	public function wpmu_new_blog( $blog_id ) {

		// Return if we don't have a blog
		if ( ! isset ( $_POST[ 'blog' ][ 'basedon' ] ) || 1 > $_POST[ 'blog' ][ 'basedon' ] )
			return;

		$source_blog_id = (int) $_POST[ 'blog' ][ 'basedon' ];

		// Hook information
		$context = array (
			'source_blog_id' => $source_blog_id,
			'new_blog_id'    => $blog_id,
		);

		// Switch to the base blog
		switch_to_blog( $source_blog_id );

		$old_prefix = $this->wpdb->prefix;
		$domain     = $this->get_mapped_domain();
		$tables     = $this->get_table_names( $context );

		// Switch to our new blog
		restore_current_blog();
		switch_to_blog( $blog_id );

		// Set the stuff
		$current_admin_email = get_option( 'admin_email' );
		$url                 = get_option( 'siteurl' );

		// truncate all tables
		foreach ( $tables as $table_name ) {
			$new_name = preg_replace(
				'~^' . $old_prefix . '~',
				$this->wpdb->prefix,
				$table_name
			);
			$this->duplicator->replace_content(
				$new_name,
				$table_name,
				TRUE
			);
		}

		if ( isset( $_POST['blog']['activate_plugins'] ) ) {
			$this->activate_plugins();
		} else {
			$this->deactivate_plugins();
		}

		$this->update_admin_email( $current_admin_email );

		// if an url was used in the old blog, we set it to this url to change all content elements
		// change siteurl -> will start url rename plugin
		if ( '' != $domain )
			update_option( 'siteurl', $domain );

		update_option( 'blogname', stripslashes( $_POST [ 'blog' ][ 'title' ] ) );
		update_option( 'home', $url );

		// change siteurl -> will start url rename plugin
		update_option( 'siteurl', $url );

		$this->wpdb->update(
			$this->wpdb->options,
			array( 'option_name' => $this->wpdb->prefix . 'user_roles' ),
			array( 'option_name' => $old_prefix . 'user_roles' )
		);

		$this->insert_post_relations( $source_blog_id, $blog_id );

		$this->insert_term_relations( $source_blog_id, $blog_id );

		$this->copy_attachments( $source_blog_id, $blog_id, $blog_id );

		// Set the search engine visibility
		if ( isset( $_POST[ 'blog' ][ 'visibility' ] ) ) {
			update_option( 'blog_public', (bool) $_POST[ 'blog' ][ 'visibility' ] );
		}

		$theme = wp_get_theme();
		/** This action is documented in wp-includes/theme.php */
		do_action( 'switch_theme', $theme->get( 'Name' ), $theme );

		restore_current_blog();

		/**
		 * Runs after successful blog duplication.
		 *
		 * @param int[] $context Duplication context. {
		 *                       'source_blog_id' => int
		 *                       'new_blog_id'    => int
		 *                       }
		 */
		do_action( 'mlp_duplicated_blog', $context );
	}

	/**
	 * Update the admin email option.
	 *
	 * We cannot use update_option(), because that would trigger a
	 * confirmation email to the new address.
	 *
	 * @param  string $admin_email
	 * @return void
	 */
	private function update_admin_email( $admin_email ) {

		$this->wpdb->update(
				   $this->wpdb->options,
				   array( 'option_value' => $admin_email ),
				   array( 'option_name'  => 'admin_email' )
		);
	}

	/**
	 * Get the primary domain if domain mapping is active
	 *
	 * @return string
	 */
	private function get_mapped_domain() {

		if ( empty ( $this->wpdb->dmtable ) )
			return '';

		$sql    = 'SELECT domain FROM ' . $this->wpdb->dmtable . ' WHERE active = 1 AND blog_id = %s LIMIT 1';
		$sql    = $this->wpdb->prepare( $sql, get_current_blog_id() );
		$domain = $this->wpdb->get_var( $sql );

		if ( '' === $domain )
			return '';

		return ( is_ssl() ? 'https://' : 'http://' ) . $domain;
	}

	/**
	 * Tables to copy.
	 *
	 * @param array $context
	 * @return array
	 */
	private function get_table_names( Array $context ) {

		$tables = $this->table_names->get_site_core_tables(
			$context[ 'source_blog_id' ]
		);

		/**
		 * Filter the to-be-duplicated tables.
		 *
		 * @param string[] $tables  Table names.
		 * @param int[]    $context Duplication context. {
		 *                          'source_blog_id' => int
		 *                          'new_blog_id'    => int
		 *                          }
		 *
		 * @return string[]
		 */
		$tables = apply_filters( 'mlp_tables_to_duplicate', $tables, $context );

		return $tables;
	}

	/**
	 * Insert relations between the corresponding posts in the sites with the given IDs.
	 *
	 * @param int $source_site_id Source site ID.
	 * @param int $target_site_id Target site ID.
	 *
	 * @return void
	 */
	private function insert_post_relations( $source_site_id, $target_site_id ) {

		$query = "
SELECT ID
FROM {$this->wpdb->posts}
WHERE post_status IN('publish','future','draft','pending','private')";

		$post_ids = $this->wpdb->get_col( $query );

		$this->insert_content_relations( $post_ids, $source_site_id, $target_site_id, 'post' );
	}

	/**
	 * Insert relations between the corresponding terms in the sites with the given IDs.
	 *
	 * @param int $source_site_id Source site ID.
	 * @param int $target_site_id Target site ID.
	 *
	 * @return void
	 */
	private function insert_term_relations( $source_site_id, $target_site_id ) {

		$query = "
SELECT term_taxonomy_id
FROM {$this->wpdb->term_taxonomy}";

		$term_taxonomy_ids = $this->wpdb->get_col( $query );

		$this->insert_content_relations( $term_taxonomy_ids, $source_site_id, $target_site_id, 'term' );
	}

	/**
	 * Insert relations between the content elements in the sites with the given IDs.
	 *
	 * @param int[]  $content_ids    Array of content element IDs.
	 * @param int    $source_site_id Source site ID.
	 * @param int    $target_site_id Target site ID.
	 * @param string $type           Content type.
	 *
	 * @return void
	 */
	private function insert_content_relations( $content_ids, $source_site_id, $target_site_id, $type ) {

		foreach ( $content_ids as $content_id ) {
			$relationship_id = $this->content_relations->get_relationship_id(
				array( $source_site_id => (int) $content_id ),
				$type,
				true
			);

			$this->content_relations->set_relation( $relationship_id, $source_site_id, $content_id );
			$this->content_relations->set_relation( $relationship_id, $target_site_id, $content_id );
		}
	}

	/**
	 * Copy all attachments from source blog to new blog.
	 *
	 * @param int $from_id
	 * @param int $to_id
	 * @param int $final_id
	 * @return void
	 */
	private function copy_attachments( $from_id, $to_id, $final_id ) {

		$copy_files = new Mlp_Copy_Attachments( $from_id, $to_id, $final_id );

		if ( $copy_files->copy_attachments() )
			$this->update_file_urls( $copy_files );
	}

	/**
	 * Fires the plugin activation hooks for all active plugins on the duplicated site.
	 *
	 * @return void
	 */
	private function activate_plugins() {

		$active_plugins = get_option( 'active_plugins' );
		foreach ( $active_plugins as $plugin ) {
			/** This action is documented in wp-admin/includes/plugin.php */
			do_action( 'activate_plugin', $plugin, false );

			/** This action is documented in wp-admin/includes/plugin.php */
			do_action( 'activate_' . $plugin, false );

			/** This action is documented in wp-admin/includes/plugin.php */
			do_action( 'activated_plugin', $plugin, false );
		}
	}

	/**
	 * Deactivates all plugins on the duplicated site.
	 *
	 * @retuvn void
	 */
	private function deactivate_plugins() {

		update_option( 'active_plugins', array() );
	}

	/**
	 * Replace file URLs in new blog.
	 *
	 * @param Mlp_Copy_Attachments $copy_files
	 * @return int|false Number of rows affected/selected or false on error
	 */
	private function update_file_urls( $copy_files ) {

		$tables = array (
			$this->wpdb->posts         => array (
				'guid',
				'post_content',
				'post_excerpt',
				'post_content_filtered',
			),
			$this->wpdb->term_taxonomy => array (
				'description'
			),
			$this->wpdb->comments      => array (
				'comment_content'
			)
		);

		$db_replace    = new Mlp_Db_Replace( $this->wpdb );
		$replaced_rows = 0;

		foreach ( $tables as $table => $columns ) {
			$replaced_rows += (int) $db_replace->replace_string(
				$table,
				$columns,
				$copy_files->source_url,
				$copy_files->dest_url
			);
		}

		return $replaced_rows;
	}

	/**
	 * Add copy field at "Add new site" screen
	 *
	 * @return	void
	 */
	public function display_fields() {

		$blogs   = (array) $this->get_all_sites();
		$options = '<option value="0">' . __( 'Choose site', 'multilingual-press' ) . '</option>';

		foreach ( $blogs as $blog ) {

			if ( '/' === $blog[ 'path' ] )
				$blog[ 'path' ] = '';

			$options .= '<option value="' . $blog[ 'blog_id' ] . '">'
				. $blog[ 'domain' ] . $blog[ 'path' ]
				. '</option>';
		}

		?>
		<tr class="form-field">
			<td>
				<label for="mlp-base-site-id">
					<?php
					esc_html_e( 'Based on site', 'multilingual-press' );
					?>
				</label>
			</td>
			<td>
				<select id="mlp-base-site-id" name="blog[basedon]" autocomplete="off">
					<?php echo $options; ?>
				</select>
			</td>
		</tr>

		<tr class="form-field hide-if-js">
			<td>
				<?php esc_html_e( 'Plugins', 'multilingual-press' ); ?>
			</td>
			<td>
				<label for="mlp-activate-plugins">
					<input type="checkbox" value="1" id="mlp-activate-plugins" name="blog[activate_plugins]"
						checked="checked">
					<?php
					esc_html_e( 'Activate all plugins that are active on the source site', 'multilingual-press' );
					?>
				</label>
			</td>
		</tr>
		<?php

		/**
		 * Filter the default value for the search engine visibility when adding a new site.
		 *
		 * @param bool $visible Should the new site be visible by default?
		 *
		 * @return bool
		 */
		$visible = (bool) apply_filters( 'mlp_default_search_engine_visibility', FALSE );

		?>
		<tr class="form-field">
			<td>
				<?php esc_html_e( 'Search Engine Visibility', 'multilingual-press' ); ?>
			</td>
			<td>
				<label for="inpsyde_multilingual_visibility">
					<input type="checkbox" value="0" id="inpsyde_multilingual_visibility" name="blog[visibility]"
						<?php checked( $visible, FALSE ); ?>>
					<?php
					esc_html_e( 'Discourage search engines from indexing this site', 'multilingual-press' );
					?>
				</label>

				<p class="description">
					<?php esc_html_e( 'It is up to search engines to honor this request.', 'multilingual-press' ); ?>
				</p>
			</td>
		</tr>
	<?php
	}

	/**
	 * Get all existing blogs.
	 *
	 * @return array
	 */
	private function get_all_sites() {

		$sql = "SELECT `blog_id`, `domain`, `path`
			FROM {$this->wpdb->blogs}
			WHERE deleted = 0 AND site_id = '{$this->wpdb->siteid}' ";

		return $this->wpdb->get_results( $sql, ARRAY_A );
	}
}
