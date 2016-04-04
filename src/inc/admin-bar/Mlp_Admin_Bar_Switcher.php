<?php # -*- coding: utf-8 -*-

/**
 * Class Mlp_Admin_Bar_Switcher
 */
class Mlp_Admin_Bar_Switcher {

	/**
	 * Add hooks and setup callbacks
	 */
	public function setup() {

		add_filter( 'admin_bar_menu', array( $this, 'add_items' ), 11 );

	}

	/**
	 * Adds items to the WP_Admin_Bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function add_items( WP_Admin_Bar $wp_admin_bar ) {

		if ( is_network_admin() ) {
			return;
		}
		$original_blog_id = get_current_blog_id();
		$screen           = get_current_screen();
		$linked           = array();
		$type             = NULL;
		if ( in_array( $screen->base, array( 'post', 'edit-tags' ) ) ) {
			$type   = ( $screen->base === 'post' ) ? 'post' : 'term';
			$linked = mlp_get_linked_elements( $this->get_object_id( $type ), $type );
		}

		foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {
			if ( $original_blog_id === $blog->userblog_id ) {
				continue;
			}

			switch_to_blog( $blog->userblog_id );

			$menu_id = 'blog-' . $blog->userblog_id;

			if ( isset( $linked[ get_current_blog_id() ] ) ) {
				$href = $this->get_object_url( $linked[ get_current_blog_id() ], $type );
			} else {
				$href = $this->get_generic_remote_url();
			}
			$wp_admin_bar->add_menu( array(
				                         'parent' => $menu_id,
				                         'id'     => $menu_id . '-switch',
				                         'title'  => __( 'Switch admin page', 'multilingual-press' ),
				                         'href'   => $href,
			                         ) );

			restore_current_blog();
		}

	}

	/**
	 * Retrieves the object ID based on the content type
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public function get_object_id( $type ) {

		switch ( $type ) {
			case 'post':
				global $post;

				return $post->ID;
			case 'term':
				return $_GET[ 'tag_ID' ];
		}

		return NULL;
	}

	/**
	 * Returns the admin url to the linked content
	 *
	 * @param $id
	 * @param $type
	 *
	 * @return null|string
	 */
	public function get_object_url( $id, $type ) {

		switch ( $type ) {
			case 'post':
				return get_edit_post_link( $id );
			case 'term':
				return get_edit_term_link( $id, $_GET[ 'taxonomy' ] );
		}

		return NULL;
	}

	/**
	 * Recreates the current admin URL on the remote blog
	 *
	 * @return string
	 */
	public function get_generic_remote_url() {

		$admin = get_admin_url( get_current_blog_id() );
		$path  = preg_replace( '@^.*/wp-admin/@', '', add_query_arg() );

		return $admin . $path;
	}
}