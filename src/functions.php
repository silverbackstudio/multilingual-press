<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress {

	use Inpsyde\MultilingualPress\Common\Locations;
	use Inpsyde\MultilingualPress\Common\Nonce\Nonce;
	use Inpsyde\MultilingualPress\Common\Type\URL;
	use wpdb;

/**
 * Returns the according HTML string representation for the given array of attributes.
 *
 * @since 3.0.0
 *
 * @param string[] $attributes An array of HTML attribute names as keys and the according values.
 *
 * @return string The according HTML string representation for the given array of attributes.
 */
function attributes_array_to_string( array $attributes ) {

	if ( ! $attributes ) {
		return '';
	}

	$strings = [];

	array_walk( $attributes, function ( $value, $name ) use ( &$strings ) {

		$strings[] = $name . '="' . esc_attr( true === $value ? $name : $value ) . '"';
	} );

	return implode( ' ', $strings );
}

/**
 * Wrapper for the exit language construct.
 *
 * Introduced to allow for easy unit testing.
 *
 * @since 3.0.0
 *
 * @param int|string $status Exit status.
 *
 * @return void
 */
function call_exit( $status = '' ) {

	exit( $status );
}

/**
 * Checks if the given nonce is valid, and if not, terminates WordPress execution unless this is an admin request.
 *
 * This function is the MultilingualPress equivalent of the WordPress function with the same name.
 *
 * @since 3.0.0
 *
 * @param Nonce $nonce Nonce object.
 *
 * @return bool Whether or not the nonce is valid.
 */
function check_admin_referer( Nonce $nonce ) {

	if ( $nonce->is_valid() ) {
		return true;
	}

	if ( 0 !== strpos( strtolower( wp_get_referer() ), strtolower( admin_url() ) ) ) {
		wp_nonce_ays( null );
		call_exit();
	}

	return false;
}

/**
 * Checks if the given nonce is valid, and if not, terminates WordPress execution according to passed flag.
 *
 * This function is the MultilingualPress equivalent of the WordPress function with the same name.
 *
 * @since 3.0.0
 *
 * @param Nonce $nonce     Nonce object.
 * @param bool  $terminate Optional. Terminate WordPress execution in case the nonce is invalid? Defaults to true.
 *
 * @return bool Whether or not the nonce is valid.
 */
function check_ajax_referer( Nonce $nonce, $terminate = true ) {

	$is_nonce_valid = $nonce->is_valid();

	if ( $terminate && ! $is_nonce_valid ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			wp_die( '-1' );
		} else {
			call_exit( '-1' );
		}
	}

	return $is_nonce_valid;
}

/**
 * Writes debug data to the error log.
 *
 * To enable this function, add the following line to your wp-config.php file:
 *
 *     define( 'MULTILINGUALPRESS_DEBUG', true );
 *
 * @since 3.0.0
 *
 * @param string $message The message to be logged.
 *
 * @return void
 */
function debug( $message ) {

	if ( defined( 'MULTILINGUALPRESS_DEBUG' ) && MULTILINGUALPRESS_DEBUG ) {
		error_log( sprintf(
			'MultilingualPress: %s %s',
			date( 'H:m:s' ),
			$message
		) );
	}
}

/**
 * Returns the names of all available languages according to the given arguments.
 *
 * @since 3.0.0
 *
 * @param bool $related              Optional. Include related sites of the current site only? Defaults to true.
 * @param bool $include_current_site Optional. Include the current site? Defaults to true.
 *
 * @return string[] The names of all available languages.
 */
function get_available_language_names( $related = true, $include_current_site = true ) {

	$current_site_id = get_current_blog_id();

	$related_sites = [];

	if ( $related ) {
		$related_sites = MultilingualPress::resolve( 'multilingualpress.site_relations' )->get_related_site_ids(
			$current_site_id,
			$include_current_site
		);
		if ( ! $related_sites ) {
			return [];
		}
	}

	$language_settings = get_site_option( 'inpsyde_multilingual', [] );
	if ( ! $language_settings || ! is_array( $language_settings ) ) {
		return [];
	}

	if ( ! $include_current_site ) {
		unset( $language_settings[ $current_site_id ] );
	}

	$languages = [];

	foreach ( $language_settings as $site_id => $language_data ) {
		if ( $related_sites && ! in_array( $site_id, $related_sites ) ) {
			continue;
		}

		$value = '';

		if ( isset( $language_data['text'] ) ) {
			$value = $language_data['text'];
		}

		if ( ! $value && isset( $language_data['lang'] ) ) {
			$value = get_language_by_http_name( str_replace( '_', '-', $language_data['lang'] ) );
		}

		if ( $value ) {
			$languages[ $site_id ] = (string) $value;
		}
	}

	return $languages;
}

/**
 * Returns the individual MultilingualPress language code of all (related) sites.
 *
 * @since 3.0.0
 *
 * @param bool $related_sites_only Optional. Restrict to related sites only? Defaults to true.
 *
 * @return string[] An array with site IDs as keys and the individual MultilingualPress language code as values.
 */
function get_available_languages( $related_sites_only = true ) {

	// TODO: Do not hard-code the option name, and maybe even get the languages some other way.
	$languages = (array) get_network_option( null, 'inpsyde_multilingual', [] );
	if ( ! $languages ) {
		return [];
	}

	if ( $related_sites_only ) {
		$related_site_ids = MultilingualPress::resolve( 'multilingualpress.site_relations' )->get_related_site_ids();
		if ( ! $related_site_ids ) {
			return [];
		}

		// Restrict ro related sites.
		$languages = array_diff_key( $languages, array_flip( $related_site_ids ) );
	}

	$available_languages = [];

	// TODO: In the old option, there might also be sites with a "-1" as lang value. Update the option, and set to "".
	array_walk( $languages, function ( $language_data, $site_id ) use ( &$available_languages ) {

		if ( isset( $language_data['lang'] ) ) {
			$available_languages[ (int) $site_id ] = (string) $language_data['lang'];
		}
	} );

	return $available_languages;
}

/**
 * Returns the MultilingualPress language for the current site.
 *
 * @since 3.0.0
 *
 * @param bool $language_only Optional. Whether or not to return the language part only. Defaults to false.
 *
 * @return string The MultilingualPress language for the current site.
 */
function get_current_site_language( $language_only = false ) {

	return get_site_language( get_current_blog_id(), $language_only );
}

/**
 * Returns the given content ID, if valid, and the ID of the queried object otherwise.
 *
 * @since 3.0.0
 *
 * @param int $content_id Content ID.
 *
 * @return int The given content ID, if valid, and the ID of the queried object otherwise.
 */
function get_default_content_id( $content_id ) {

	return (int) $content_id ?: get_queried_object_id();
}

/**
 * Returns the URL of the flag image for the given (or current) site ID.
 *
 * @since 3.0.0
 *
 * @param int $site_id Optional. Site ID. Defaults to 0.
 *
 * @return URL Flag URL object.
 */
function get_flag_url_for_site( $site_id = 0 ) {

	$site_id = (int) $site_id ?: get_current_blog_id();

	$type_factory = MultilingualPress::resolve( 'multilingualpress.type_factory' );

	$custom_flag = get_blog_option( $site_id, 'inpsyde_multilingual_flag_url' );
	if ( $custom_flag ) {
		return $type_factory->create_url( [
			$custom_flag,
		] );
	}

	$internal_locations = MultilingualPress::resolve( 'multilingualpress.internal_locations' );

	$file_name = get_site_language( $site_id, true ) . '.gif';

	if ( is_readable( $internal_locations->get( 'flags', Locations::TYPE_PATH ) . "/$file_name" ) ) {
		return $type_factory->create_url( [
			$internal_locations->get( 'flags', Locations::TYPE_URL ) . $file_name,
		] );
	}

	return $type_factory->create_url( [
		'',
	] );
}

/**
 * Returns the desired field value of the language with the given HTTP code.
 *
 * @since 3.0.0
 *
 * @param string          $http_code Language HTTP code.
 * @param string          $field     Optional. The field which should be queried. Defaults to 'native_name'.
 * @param string|string[] $fallbacks Optional. Falback language fields. Defaults to native and English name.
 *
 * @return string|string[] The desired field value, an empty string on failure, or an array for field 'all'.
 */
function get_language_by_http_name(
	$http_code,
	$field = 'native_name',
	$fallbacks = [
		'native_name',
		'english_name',
	]
) {

	return MultilingualPress::resolve( 'multilingualpress.languages' )->get_language_by_http_code(
		$http_code,
		$field,
		$fallbacks
	);
}

/**
 * Returns the MultilingualPress language for the site with the given ID.
 *
 * @since 3.0.0
 *
 * @param int  $site_id       Optional. Site ID. Defaults to 0.
 * @param bool $language_only Optional. Whether or not to return the language part only. Defaults to false.
 *
 * @return string The MultilingualPress language for the site with the given ID.
 */
function get_site_language( $site_id = 0, $language_only = false ) {

	$site_id = $site_id ?: get_current_blog_id();

	// TODO: Don't hardcode the option name.
	$languages = get_network_option( null, 'inpsyde_multilingual', [] );

	// TODO: Maybe also don't hardcode the 'lang' key...?
	if ( ! isset( $languages[ $site_id ]['lang'] ) ) {
		return '';
	}

	return $language_only
		? strtok( $languages[ $site_id ]['lang'], '_' )
		: (string) $languages[ $site_id ]['lang'];
}

/**
 * Returns the content IDs of all translations for the given content element data.
 *
 * @since 3.0.0
 *
 * @param int    $content_id Optional. Content ID. Defaults to 0.
 * @param string $type       Optional. Content type. Defaults to 'post'.
 * @param int    $site_id    Optional. Site ID. Defaults to 0.
 *
 * @return int[] An array with site IDs as keys and content IDs as values.
 */
function get_translation_ids( $content_id = 0, $type = 'post', $site_id = 0 ) {

	$content_id = get_default_content_id( $content_id );
	if ( ! $content_id ) {
		return [];
	}

	return MultilingualPress::resolve( 'multilingualpress.content_relations' )->get_relations(
		$site_id ?: get_current_blog_id(),
		$content_id,
		(string) $type
	);
}

/**
 * Checks if MultilingualPress debug mode is on.
 *
 * @since 3.0.0
 *
 * @return bool Whether or not MultilingualPress debug mode is on.
 */
function is_debug_mode() {

	return defined( 'MULTILINGUALPRESS_DEBUG' ) && MULTILINGUALPRESS_DEBUG;
}

/**
 * Checks if the site with the given ID has HTTP redirection enabled.
 *
 * If no ID is passed, the current site is checked.
 *
 * @since 3.0.0
 *
 * @param int $site_id Optional. Site ID. Defaults to 0.
 *
 * @return bool Whether or not the site with the given ID has HTTP redirection enabled.
 */
function is_redirect_enabled( $site_id = 0 ) {

	// TODO: Don't hard-code the option name.
	return (bool) get_blog_option( $site_id ?: get_current_blog_id(), 'inpsyde_multilingual_redirect' );
}

/**
 * Checks if either MultilingualPress or WordPress script debug mode is on.
 *
 * @since 3.0.0
 *
 * @return bool Whether or not MultilingualPress or WordPress script debug mode is on.
 */
function is_script_debug_mode() {

	return is_debug_mode() || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
}

/**
 * Checks if either MultilingualPress or WordPress debug mode is on.
 *
 * @since 3.0.0
 *
 * @return bool Whether or not MultilingualPress or WordPress debug mode is on.
 */
function is_wp_debug_mode() {

	return is_debug_mode() || ( defined( 'WP_DEBUG' ) && WP_DEBUG );
}

/**
 * Returns the HTML string for the hidden nonce field according to the given nonce object.
 *
 * @since 3.0.0
 *
 * @param Nonce $nonce        Nonce object.
 * @param bool  $with_referer Optional. Render a referer field as well? Defaults to true.
 *
 * @return string The HTML string for the hidden nonce field according to the given nonce object.
 */
function nonce_field( Nonce $nonce, $with_referer = true ) {

	return sprintf(
		'<input type="hidden" name="%s" value="%s">%s',
		esc_attr( $nonce->action() ),
		esc_attr( (string) $nonce ),
		$with_referer ? wp_referer_field( false ) : ''
	);
}

/**
 * Checks if the site with the given ID exists (within the current or given network) and is not marked as deleted.
 *
 * @since 3.0.0
 *
 * @param int $site_id    Site ID.
 * @param int $network_id Optional. Network ID. Defaults to 0.
 *
 * @return bool Wheter or not the site with the given ID exists and is not marked as deleted.
 */
function site_exists( $site_id, $network_id = 0 ) {

	static $cache = [];

	// We don't test large sites.
	if ( wp_is_large_network() ) {
		return true;
	}

	// TODO: With WordPress 4.6 + 2, use get_current_network_id() instead of "get_current_site()->id".
	$network_id = (int) ( $network_id ? $network_id : get_current_site()->id );

	if ( ! isset( $cache[ $network_id ] ) ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$query = $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = %d AND deleted = 0", $network_id );

		$cache[ $network_id ] = array_map( 'intval', $wpdb->get_col( $query ) );
	}

	return in_array( (int) $site_id, $cache[ $network_id ], true );
}

}



// TODO: Move all functions to Inpsyde\MultilingualPress namespace (see below) and adapt names (no prefix etc.).
namespace {

	/**
	 * Wrapper for Mlp_Helpers::show_linked_elements().
	 *
	 * @param array|string $args_or_deprecated_text Arguments array, or value for the 'link_text' argument.
	 * @param bool         $deprecated_echo         Optional. Display the output? Defaults to TRUE.
	 * @param string       $deprecated_sort         Optional. Sort elements. Defaults to 'blogid'.
	 *
	 * @return string
	 */
	function mlp_show_linked_elements( $args_or_deprecated_text = 'text', $deprecated_echo = true, $deprecated_sort = 'blogid' ) {

		$args     = is_array( $args_or_deprecated_text )
			? $args_or_deprecated_text
			: [
				'link_text' => $args_or_deprecated_text,
				'sort'      => $deprecated_sort,
			];
		$defaults = [
			'link_text'         => 'text',
			'sort'              => 'priority',
			'show_current_blog' => false,
			'display_flag'      => false,
			'strict'            => false, // get exact translations only
		];
		$params   = wp_parse_args( $args, $defaults );
		$output   = Mlp_Helpers::show_linked_elements( $params );

		$echo = isset( $params['echo'] ) ? $params['echo'] : $deprecated_echo;
		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * get the linked elements with a lot of more information
	 *
	 * @since    0.7
	 *
	 * @param    int $element_id current post / page / whatever
	 *
	 * @return    array
	 */
	function mlp_get_interlinked_permalinks( $element_id = 0 ) {

		return Mlp_Helpers::get_interlinked_permalinks( $element_id );
	}
}
