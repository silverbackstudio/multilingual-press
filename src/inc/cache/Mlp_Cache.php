<?php # -*- coding: utf-8 -*-

/**
 * Bridge to core WordPress's caching functions.
 */
class Mlp_Cache {

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
	 * @param string $key   The cache key.
	 * @param string $group Optional. The cache group. Defaults to 'mlp'.
	 */
	public function __construct( $key, $group = 'mlp' ) {

		$this->key = (string) $key;

		$this->group = (string) $group;
	}

	/**
	 * Adds the given data to the cache unless it is set already, using the key generated from the key base and the
	 * given key fragments.
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function add( $data, array $key_fragments = array(), $expire = 0 ) {

		return wp_cache_add( $this->get_key( $key_fragments ), $data, $this->group, (int) $expire );
	}

	/**
	 * Removes the data from the cache, using the key generated from the key base and the given key fragments.
	 *
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 *
	 * @return bool
	 */
	public function delete( array $key_fragments = array() ) {

		return wp_cache_delete( $this->get_key( $key_fragments ), $this->group );
	}

	/**
	 * Removes the data from the cache, using the given key.
	 *
	 * @param string $key The cache key.
	 *
	 * @return bool
	 */
	public function delete_for_key( $key ) {

		return wp_cache_delete( $key, $this->group );
	}

	/**
	 * Returns the data from the cache, using the key generated from the key base and the given key fragments.
	 *
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 * @param bool  $force         Optional. Update the local cache from the persistent cache? Defaults to false.
	 *
	 * @return mixed|bool
	 */
	public function get( array $key_fragments = array(), $force = false ) {

		return wp_cache_get( $this->get_key( $key_fragments ), $this->group, (bool) $force );
	}

	/**
	 * Returns the cache key for the given key fragments.
	 *
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 *
	 * @return string
	 */
	public function get_key( array $key_fragments = array() ) {

		if ( ! $key_fragments ) {
			return $this->key;
		}

		// TODO: With MultilingualPress 3.0.0, turn maybe_hashify() into a closure.
		$key_fragments = array_map( array( $this, 'maybe_hashify' ), $key_fragments );

		return $this->key . '|' . implode( '|', $key_fragments );
	}

	/**
	 * Returns a hash string for arrays and objects, and the string representation of the passed data otherwise.
	 *
	 * @param mixed $data Data.
	 *
	 * @return string
	 */
	public function maybe_hashify( $data ) {

		if ( is_array( $data ) || is_object( $data ) ) {
			$data = md5( serialize( $data ) );
		}

		return (string) $data;
	}

	/**
	 * Registers the deletion of the cached data for the given action hook(s), using the key generated from the key base
	 * and the given key fragments.
	 *
	 * @param string|string[] $actions       One or more action hooks.
	 * @param array           $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 *
	 * @return void
	 */
	public function register_deletion_action( $actions, array $key_fragments = array() ) {

		$deletor = Mlp_Cache_Deletor_Factory::create( $this, $this->get_key( $key_fragments ) );

		foreach ( (array) $actions as $action ) {
			add_action( $action, array( $deletor, 'delete' ) );
		}
	}

	/**
	 * Registers the execution of the given callback for the given action hook(s).
	 *
	 * @param callable        $callback The callback.
	 * @param string|string[] $actions  One or more action hooks.
	 *
	 * @return void
	 */
	public function register_callback_for_action( callable $callback, $actions ) {

		$actor = Mlp_Cache_Actor_Factory::create( $this, $callback );

		foreach ( (array) $actions as $action ) {
			add_action( $action, array( $actor, 'act' ) );
		}
	}

	/**
	 * Replaces the original data in the cache with the given data, using the key generated from the key base and the
	 * given key fragments.
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function replace( $data, array $key_fragments = array(), $expire = 0 ) {

		return wp_cache_replace( $this->get_key( $key_fragments ), $data, $this->group, (int) $expire );
	}

	/**
	 * Saves the given data to the cache, using the key generated from the key base and the given key fragments.
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param array $key_fragments Optional. Fragments to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function set( $data, array $key_fragments = array(), $expire = 0 ) {

		return wp_cache_set( $this->get_key( $key_fragments ), $data, $this->group, (int) $expire );
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
