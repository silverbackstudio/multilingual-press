<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Translation\Translator;

use Inpsyde\MultilingualPress\Factory\TypeFactory;
use Inpsyde\MultilingualPress\Translation\Translator;

/**
 * Translator implementation for post types.
 *
 * @package Inpsyde\MultilingualPress\Translation\Translator
 * @since   3.0.0
 */
final class PostTypeTranslator implements Translator {

	/**
	 * @var TypeFactory
	 */
	private $type_factory;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param TypeFactory $type_factory Type factory object.
	 */
	public function __construct( TypeFactory $type_factory ) {

		$this->type_factory = $type_factory;
	}

	/**
	 * Returns the translation data for the given site, according to the given arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $site_id Site ID.
	 * @param array $args    Optional. Arguments required to fetch translation. Defaults to empty array.
	 *
	 * @return array Translation data.
	 */
	public function get_translation( $site_id, array $args = [] ) {

		if ( empty( $args['post_type'] ) ) {
			return [];
		}

		switch_to_blog( $site_id );

		$post_type = $args['post_type'];

		$data = [
			'remote_url' => $this->type_factory->create_url( [
				get_post_type_archive_link( $post_type ),
			] ),
		];

		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type_object ) {
			$data['remote_title'] = $post_type_object->labels->name;
		}

		restore_current_blog();

		return $data;
	}
}
