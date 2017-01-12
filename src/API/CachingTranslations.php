<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\API;

use Inpsyde\MultilingualPress\Common\Type\Translation;
use Inpsyde\MultilingualPress\Translation\Translator;
use Inpsyde\MultilingualPress\Translation\Translator\NullTranslator;
use Inpsyde\MultilingualPress\Common\Request;
use Inpsyde\MultilingualPress\Factory\TypeFactory;

/**
 * Caching translations API implementation.
 *
 * @package Inpsyde\MultilingualPress\API
 * @since   3.0.0
 */
final class CachingTranslations implements Translations {

	/**
	 * @var ContentRelations
	 */
	private $content_relations;

	/**
	 * @var Languages
	 */
	private $languages;

	/**
	 * @var NullTranslator
	 */
	private $null_translator;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var SiteRelations
	 */
	private $site_relations;

	/**
	 * @var Translator[]
	 */
	private $translators = [];

	/**
	 * @var TypeFactory
	 */
	private $type_factory;

	/**
	 * @var string[]
	 */
	private $unfiltered_translations;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param SiteRelations    $site_relations    Site relations API object.
	 * @param ContentRelations $content_relations Content relations API object.
	 * @param Languages        $languages         Languages API object.
	 * @param Request          $request           Request object.
	 * @param TypeFactory      $type_factory      Type factory object.
	 */
	public function __construct(
		SiteRelations $site_relations,
		ContentRelations $content_relations,
		Languages $languages,
		Request $request,
		TypeFactory $type_factory
	) {

		$this->site_relations = $site_relations;

		$this->content_relations = $content_relations;

		$this->languages = $languages;

		$this->request = $request;

		$this->type_factory = $type_factory;
	}

	/**
	 * Returns all translation according to the given arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Optional. Arguments required to fetch the translations. Defaults to empty array.
	 *
	 * @return Translation[] An array with site IDs as keys and Translation objects as values.
	 */
	public function get_translations( array $args = [] ) {

		$args = $this->normalize_arguments( $args );

		$key = md5( serialize( $args ) );

		$translations = wp_cache_get( $key, 'mlp' );
		if ( is_array( $translations ) ) {
			return $translations;
		}

		$translations = [];

		$sites = $this->site_relations->get_related_site_ids( $args['site_id'], $args['include_base'] );
		if ( $sites ) {
			$content_relations = 0 < $args['content_id']
				? $this->content_relations->get_relations( $args['site_id'], $args['content_id'], $args['type'] )
				: [];

			if ( $content_relations || ! $args['strict'] ) {
				$languages = $this->languages->get_all_site_languages();

				$sites = array_intersect( $sites, array_keys( $languages ) );
				if ( $sites ) {
					$post_type = $args['post_type'];

					$source_site_id = $args['site_id'];

					$suppress_filters = $args['suppress_filters'];

					$type = $args['type'];

					foreach ( $sites as $site_id ) {
						$translation = [
							'remote_title'      => '',
							'remote_url'        => '',
							'source_site_id'    => $source_site_id,
							'suppress_filters'  => $suppress_filters,
							'target_site_id'    => $site_id,
							'target_content_id' => 0,
							'type'              => $type,
						];

						if ( empty( $content_relations[ $site_id ] ) ) {
							$translator = $this->translator( $type );

							$data = [];

							switch ( $type ) {
								case Request::TYPE_POST_TYPE_ARCHIVE:
									$data = $translator->get_translation( $site_id, [
										'post_type' => $post_type,
									] );
									break;

								case Request::TYPE_SEARCH:
									$data = $translator->get_translation( $site_id, [
										'query' => (string) $args['search_term'],
									] );
									break;
							}

							if ( $data ) {
								$translation = array_merge( $translation, $data );
							}

							if (
								( ! $translation['remote_url'] && ! $args['strict'] )
								|| Request::TYPE_FRONT_PAGE === $type
							) {
								$translation = array_merge(
									$translation,
									$this->translator( Request::TYPE_FRONT_PAGE )->get_translation( $site_id )
								);
							}

							if ( ! $translation['remote_url'] ) {
								continue;
							}
						} else {
							$content_id = $content_relations[ $site_id ];

							$translation['target_content_id'] = $content_id;

							if ( in_array( $type, [ Request::TYPE_SINGULAR, Request::TYPE_TERM_ARCHIVE ], true ) ) {
								$translator = $this->translator( $type );

								$data = [];

								switch ( $type ) {
									case Request::TYPE_SINGULAR:
										$data = $translator->get_translation( $site_id, [
											'content_id' => $content_id,
											'strict'     => $args['strict'],
										] );
										break;

									case Request::TYPE_TERM_ARCHIVE:
										$data = $translator->get_translation( $site_id, [
											'content_id' => $content_id,
										] );
										break;
								}

								if ( ! $data ) {
									continue;
								}

								$translation = array_merge( $translation, $data );
							}
						}

						$language = $languages[ $site_id ];
						if ( empty( $language['http_name'] ) ) {
							$language['http_name'] = empty( $language['lang'] ) ? '' : $language['lang'];
						}

						$translation['icon_url'] = $language['http_name']
							? \Inpsyde\MultilingualPress\get_flag_url_for_site( $site_id )
							: $this->type_factory->create_url( [
								'',
							] );

						$translations[ $site_id ] = $this->type_factory->create_translation( [
							$translation,
							$this->type_factory->create_language( [
								$language,
							] ),
						] );
					}
				}
			}
		}

		/**
		 * Filter the translations before they are used.
		 *
		 * @param Translation[] $translations Translations.
		 * @param array         $args         Translation args.
		 */
		$translations = (array) apply_filters( 'mlp_translations', $translations, $args );

		wp_cache_set( $key, $translations, 'mlp' );

		return $translations;
	}

	/**
	 * Returns the unfiltered translations.
	 *
	 * @since 3.0.0
	 *
	 * @return string[] Array with HTTP language codes as keys and URLs as values.
	 */
	public function get_unfiltered_translations() {

		if ( isset( $this->unfiltered_translations ) ) {
			return $this->unfiltered_translations;
		}

		$this->unfiltered_translations = [];

		$translations = $this->get_translations( [
			'include_base'     => true,
			'suppress_filters' => true,
		] );
		if ( ! $translations ) {
			return $this->unfiltered_translations;
		}

		array_walk( $translations, function ( Translation $translation ) {

			$url = $translation->remote_url();
			if ( $url ) {
				$this->unfiltered_translations[ $translation->language()->name( 'http' ) ] = $url;
			}
		} );

		return $this->unfiltered_translations;
	}

	/**
	 * Registers the given translator for the given type.
	 *
	 * @since 3.0.0
	 *
	 * @param Translator $translator Translator object.
	 * @param string     $type       Request or content type.
	 *
	 * @return bool Whether or not the translator was registered successfully.
	 */
	public function register_translator( Translator $translator, $type ) {

		if ( isset( $this->translators[ $type ] ) ) {
			return false;
		}

		$this->translators[ $type ] = $translator;

		return true;
	}

	/**
	 * Returns a normalized arguments array according to the one passed, but with all missing defaults.
	 *
	 * @param array $args Arguments required to fetch the translations.
	 *
	 * @return array Arguments required to fetch the translations.
	 */
	private function normalize_arguments( array $args ) {

		$args = wp_parse_args( $args, [
			'content_id'       => $this->request->queried_object_id(),
			'include_base'     => false,
			'post_type'        => $this->request->post_type(),
			'search_term'      => get_search_query(),
			'site_id'          => get_current_blog_id(),
			'strict'           => true,
			'suppress_filters' => false,
			'type'             => $this->request->type(),
		] );

		/**
		 * Filters the arguments required to fetch the translations.
		 *
		 * @since 3.0.0
		 *
		 * @param array $args Arguments required to fetch the translations.
		 */
		$args = (array) apply_filters( 'mlp_get_translations_args', $args );

		return $args;
	}

	/**
	 * Returns the null translator instance.
	 *
	 * @return NullTranslator Translator object.
	 */
	private function null_translator() {

		if ( ! $this->null_translator ) {
			$this->null_translator = new NullTranslator();
		}

		return $this->null_translator;
	}

	/**
	 * Returns the translator instance for the given type.
	 *
	 * @param string $type Request or content type.
	 *
	 * @return Translator Translator object.
	 */
	private function translator( $type ) {

		return isset( $this->translators[ $type ] )
			? $this->translators[ $type ]
			: $this->null_translator();
	}
}
