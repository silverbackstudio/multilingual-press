<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\API\Translations;
use Inpsyde\MultilingualPress\Common\AcceptHeader\AcceptHeaderParser;
use Inpsyde\MultilingualPress\Common\Type\Language;
use Inpsyde\MultilingualPress\Common\Type\Translation;
use Inpsyde\MultilingualPress\Module\Redirect\LanguageNegotiation\AcceptLanguageParser;

/**
 * Find best alternative for given content
 *
 * @version    2014.09.26
 * @author     Inpsyde GmbH, toscho
 * @license    GPL
 * @package    MultilingualPress
 * @subpackage Redirect
 */
class Mlp_Language_Negotiation implements Mlp_Language_Negotiation_Interface {

	/**
	 * @type Translations
	 */
	private $translations;

	/**
	 * @var float
	 */
	private $language_only_priority_factor;

	/**
	 * @var AcceptHeaderParser
	 */
	private $parser;

	/**
	 * @param Translations $translations Translations API object.
	 * @param AcceptHeaderParser         $parser       Optional. Accept-Language parser object. Defaults to null.
	 */
	public function __construct( Translations $translations, AcceptHeaderParser $parser = null ) {

		$this->translations = $translations;

		$this->parser = $parser ?: new AcceptLanguageParser();

		/**
		 * Filters the factor used to compute the priority of language-only matches. This has to be between 0 and 1.
		 *
		 * @see   get_user_priority()
		 * @since 2.4.8
		 *
		 * @param float $factor The factor used to compute the priority of language-only matches.
		 */
		$factor = (float) apply_filters( 'multilingualpress.language_only_priority_factor', .7 );
		$factor = min( 1, $factor );
		$factor = max( 0, $factor );

		$this->language_only_priority_factor = $factor;
	}

	/**
	 * @return array
	 */
	public function get_redirect_match() {

		$translations = $this->translations->get_translations( [
			'include_base' => true,
		] );

		if ( empty ( $translations ) )
			return $this->get_fallback_match();

		$possible = $this->get_possible_matches( $translations );

		if ( empty ( $possible ) )
			return $this->get_fallback_match();

		uasort( $possible, [ $this, 'sort_priorities' ] );

		return array_pop( $possible );
	}

	/**
	 * @param array $translations
	 * @return array
	 */
	private function get_possible_matches( array $translations ) {

		$possible = [];

		$user = $this->parse_accept_header( $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ] );

		if ( empty ( $user ) )
			return $possible;

		/** @type Translation $translation */
		foreach ( $translations as $site_id => $translation )
			$this->collect_matches( $possible, $site_id, $translation, $user );

		return $possible;
	}

	/**
	 * @return array
	 */
	private function get_fallback_match() {

		return [
			'priority' => 0,
			'url'      => '',
			'language' => '',
			'site_id'  => 0
		 ];
	}

	/**
	 * @param  array       $possible
	 * @param  int         $site_id
	 * @param  Translation $translation
	 * @param  array       $user
	 * @return void
	 */
	private function collect_matches(
		array &$possible,
		$site_id,
		Translation $translation,
		array $user
	) {

		$language      = $translation->language();
		$user_priority = $this->get_user_priority( $language, $user );

		if ( 0 === $user_priority )
			return;

		$url = $translation->remote_url();

		if ( empty ( $url ) )
			return;

		$combined_value   = $language->priority() * $user_priority;
		$possible[]       = [
			'priority' => $combined_value,
			'url'      => $url,
			'language' => $language->name( 'http' ),
			'site_id'  => $site_id,
		];
	}

	/**
	 * Helper to sort URLs by priority.
	 *
	 * @param  array $a
	 * @param  array $b
	 * @return int
	 */
	private function sort_priorities( $a, $b ) {

		if ( $a[ 'priority' ] === $b[ 'priority' ] )
			return 0;

		return ( $a[ 'priority' ] < $b[ 'priority' ] ) ? -1 : 1;
	}

	/**
	 * @param Language $language
	 * @param array    $user
	 * @return float The user priority
	 */
	private function get_user_priority( Language $language, array $user ) {

		$lang_http = strtolower( $language->name( 'http_name' ) );

		if ( isset( $user[ $lang_http ] ) ) {
			return $user[ $lang_http ];
		}

		$lang_short = strtolower( $language->name( 'language_short' ) );

		if ( isset( $user[ $lang_short ] ) ) {
			return $this->language_only_priority_factor * $user[ $lang_short ];
		}

		return 0;
	}

	/**
	 * Inspect HTTP_ACCEPT_LANGUAGE and parse priority parameters.
	 *
	 * @param string $accept_header Accept header string
	 * @return array
	 */
	private function parse_accept_header( $accept_header ) {

		$fields = $this->parser->parse( $accept_header );

		if ( empty ( $fields ) )
			return $fields;

		$out = [];

		foreach ( $fields as $name => $priority ) {
			$name = strtolower( $name );

			$out[ $name ] = $priority;

			$short = $this->get_short_form( $name );

			if ( $short && ( $short !== $name ) && ! isset ( $out[ $short ] ) )
				$out[ $short ] = $priority;
		}

		return $out;
	}

	/**
	 * Get the first characters of a language code until an '-'.
	 *
	 * @param  string $long
	 * @return string
	 */
	private function get_short_form( $long ) {

		if ( ! strpos( $long, '-' ) )
			return '';

		return strtok( $long, '-' );
	}
}
