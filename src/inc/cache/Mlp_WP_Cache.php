<?php # -*- coding: utf-8 -*-

/**
 * Bridge to core WordPress's caching functions.
 */
class Mlp_WP_Cache implements Mlp_Cache {

	/**
	 * @var string
	 */
	private $group;

	/**
	 * @var string
	 */
	private $key;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param string $key   The cache key (base).
	 * @param string $group Optional. The cache group. Defaults to 'mlp'.
	 */
	public function __construct( $key, $group = 'mlp' ) {

		$this->key = (string) $key;

		$this->group = (string) $group;
	}

	/**
	 * Adds the given data to the cache unless it is set already, using the key generated from the key base and the
	 * given key fragment(s).
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function add( $data, $key_fragments = array(), $expire = 0 ) {

		return wp_cache_add( $this->get_key( (array) $key_fragments ), $data, $this->group, (int) $expire );
	}

	/**
	 * Removes the data from the cache, using the key generated from the key base and the given key fragment(s).
	 *
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 *
	 * @return bool
	 */
	public function delete( $key_fragments = array() ) {

		return wp_cache_delete( $this->get_key( (array) $key_fragments ), $this->group );
	}

	/**
	 * Removes the data from the cache, using the given key.
	 *
	 * @param string $key The cache key.
	 *
	 * @return bool
	 */
	public function delete_for_key( $key ) {

		return wp_cache_delete( (string) $key, $this->group );
	}

	/**
	 * Removes all data from the cache.
	 *
	 * @return bool
	 */
	public function flush(){

		return wp_cache_flush();
	}

	/**
	 * Returns the data from the cache, using the key generated from the key base and the given key fragment(s).
	 *
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param bool  $force         Optional. Update the local cache from the persistent cache? Defaults to false.
	 *
	 * @return mixed|bool
	 */
	public function get( $key_fragments = array(), $force = false ) {

		return wp_cache_get( $this->get_key( (array) $key_fragments ), $this->group, (bool) $force );
	}

	/**
	 * Returns the cache key for the given key fragment(s).
	 *
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 *
	 * @return string
	 */
	public function get_key( array $key_fragments = array() ) {

		if ( ! $key_fragments ) {
			return $this->key;
		}

		// TODO: With MultilingualPress 3.0.0, turn stringify() into a closure.
		$key_fragments = array_map( array( $this, 'stringify' ), $key_fragments );

		return $this->key . '|' . implode( '|', $key_fragments );
	}

	/**
	 * Returns the (hash) string representation for the passed data.
	 *
	 * @param mixed $data Data.
	 *
	 * @return string
	 */
	public function stringify( $data ) {

		if ( null === $data ) {
			return 'NULL';
		}

		if ( is_scalar( $data ) ) {
			return (string) $data;
		}

		if ( is_array( $data ) || is_object( $data ) ) {
			if ( $data instanceof Closure ) {
				$data = create_function( '$c', 'return $c();' );
			}

			return md5( serialize( $data ) );
		}

		return '';
	}

	/**
	 * Registers the execution of the given callback for the given action hook(s).
	 *
	 * @todo With MultilingualPress 3.0.0, add callable type hint.
	 *
	 * @param callable        $callback The callback.
	 * @param string|string[] $actions  One or more action hooks.
	 *
	 * @return void
	 */
	public function register_callback_for_action( $callback, $actions ) {

		$actor = Mlp_Cache_Actor_Factory::create( $this, $callback );

		foreach ( (array) $actions as $action ) {
			add_action( (string) $action, array( $actor, 'act' ) );
		}
	}

	/**
	 * Registers the deletion of the cached data for the given action hook(s), using the key generated from the key base
	 * and the given key fragment(s).
	 *
	 * @param string|string[] $actions       One or more action hooks.
	 * @param mixed           $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 *
	 * @return void
	 */
	public function register_deletion_action( $actions, $key_fragments = array() ) {

		$deletor = Mlp_Cache_Deletor_Factory::create( $this, $this->get_key( (array) $key_fragments ) );

		foreach ( (array) $actions as $action ) {
			add_action( (string) $action, array( $deletor, 'delete' ) );
		}
	}

	/**
	 * Replaces the original data in the cache with the given data, using the key generated from the key base and the
	 * given key fragment(s).
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function replace( $data, $key_fragments = array(), $expire = 0 ) {

		return wp_cache_replace( $this->get_key( (array) $key_fragments ), $data, $this->group, (int) $expire );
	}

	/**
	 * Saves the given data to the cache, using the key generated from the key base and the given key fragment(s).
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function set( $data, $key_fragments = array(), $expire = 0 ) {

		return wp_cache_set( $this->get_key( (array) $key_fragments ), $data, $this->group, (int) $expire );
	}

	/**
	 * Switches to the specific cache for the site with the given ID.
	 *
	 * @param int $site_id The new site ID.
	 *
	 * @return void
	 */
	public function switch_to_site( $site_id ) {

		wp_cache_switch_to_blog( (int) $site_id );
	}
}
