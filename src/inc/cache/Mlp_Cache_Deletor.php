<?php # -*- coding: utf-8 -*-

/**
 * Handles deletion of a specific cache entry.
 */
class Mlp_Cache_Deletor {

	/**
	 * @var Mlp_Cache
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $key;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param Mlp_Cache $cache Cache object.
	 * @param string    $key   The cache key.
	 */
	public function __construct( Mlp_Cache $cache, $key ) {

		$this->cache = $cache;

		$this->key = (string) $key;
	}

	/**
	 * Removes the data from the injected cache, using the given key.
	 *
	 * @return bool
	 */
	public function delete() {

		return $this->cache->delete_for_key( $this->key );
	}
}
