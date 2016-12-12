<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Relations\Post;

use WP_Post;

/**
 * Relationship context data object.
 *
 * @package Inpsyde\MultilingualPress\Relations\Post
 * @since   3.0.0
 */
class RelationshipContext {

	/**
	 * Data key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const KEY_NEW_POST_ID = 'new_post_id';

	/**
	 * Data key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const KEY_NEW_POST_TITLE = 'new_post_title';

	/**
	 * Data key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const KEY_REMOTE_POST_ID = 'remote_post_id';

	/**
	 * Data key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const KEY_REMOTE_SITE_ID = 'remote_site_id';

	/**
	 * Data key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const KEY_SOURCE_POST_ID = 'source_post_id';

	/**
	 * Data key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const KEY_SOURCE_SITE_ID = 'source_site_id';

	/**
	 * @var array
	 */
	private static $default_data = [
		self::KEY_NEW_POST_ID    => 0,
		self::KEY_NEW_POST_TITLE => '',
		self::KEY_REMOTE_POST_ID => 0,
		self::KEY_REMOTE_SITE_ID => 0,
		self::KEY_SOURCE_POST_ID => 0,
		self::KEY_SOURCE_SITE_ID => 0,
	];

	/**
	 * @var array
	 */
	private $data;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data Optional. Initial context data. Defaults to empty array.
	 */
	public function __construct( array $data = [] ) {

		if ( ! isset( $this->data ) ) {
			$this->data = array_intersect_key( array_merge( static::$default_data, $data ), static::$default_data );
		}
	}

	/**
	 * Returns a new context object, instantiated according to the data in the given context object and the array.
	 *
	 * @since 3.0.0
	 *
	 * @param RelationshipContext $context Context object.
	 * @param array               $data    Context data.
	 *
	 * @return RelationshipContext Context object.
	 */
	public static function from_existing( RelationshipContext $context, array $data ) {

		$clone = clone $context;

		$data = array_intersect_key( $data, static::$default_data );

		array_walk( $data, function ( $value, $key, $clone ) {

			$clone->data[ $key ] = $value;
		}, $clone );

		return $clone;
	}

	/**
	 * Returns a new context object, instantiated according to the data in the current request.
	 *
	 * @since 3.0.0
	 *
	 * @return static Context object.
	 */
	public static function from_request() {

		return new static( $_REQUEST );
	}

	/**
	 * Returns the new post ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int New post ID.
	 */
	public function new_post_id() {

		return (int) $this->data[ self::KEY_NEW_POST_ID ];
	}

	/**
	 * Returns the new post title.
	 *
	 * @since 3.0.0
	 *
	 * @return string New post title.
	 */
	public function new_post_title() {

		return (string) $this->data[ self::KEY_NEW_POST_TITLE ];
	}

	/**
	 * Returns the remote post ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int Remote post ID.
	 */
	public function remote_post_id() {

		return (int) $this->data[ self::KEY_REMOTE_POST_ID ];
	}

	/**
	 * Returns the remote site ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int Remote site ID.
	 */
	public function remote_site_id() {

		return (int) $this->data[ self::KEY_REMOTE_SITE_ID ];
	}

	/**
	 * Returns the source post ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int Source post ID.
	 */
	public function source_post_id() {

		return (int) $this->data[ self::KEY_SOURCE_POST_ID ];
	}

	/**
	 * Returns the source post object.
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Post|null Source post object.
	 */
	public function source_post() {

		static $source_post = false;

		if ( false === $source_post ) {
			$source_site_id = $this->source_site_id();
			if ( ! $source_site_id ) {
				$source_post = null;

				return null;
			}

			$source_post_id = $this->source_post_id();
			if ( ! $source_post_id ) {
				$source_post = null;

				return null;
			}

			switch_to_blog( $source_site_id );
			$source_post = get_post( $source_post_id );
			restore_current_blog();
		}

		return $source_post;
	}

	/**
	 * Returns the source site ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int Source site ID.
	 */
	public function source_site_id() {

		return (int) $this->data[ self::KEY_SOURCE_SITE_ID ];
	}
}
