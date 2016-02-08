<?php # -*- coding: utf-8 -*-

/**
 * Interface for the relationships between content elements.
 */
interface Mlp_Content_Relations_Interface {

	/**
	 * Set a relation according to the given parameters.
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param int $site_id         Site ID.
	 * @param int $content_id      Content ID.
	 *
	 * @return bool
	 */
	public function set_relation( $relationship_id, $site_id, $content_id );

	/**
	 * Return the content ID for the given arguments.
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param int $site_id         Site ID.
	 *
	 * @return int
	 */
	public function get_content_id( $relationship_id, $site_id );

	/**
	 * Delete all relations for the given site ID.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return int
	 */
	public function delete_all_relations_for_site( $site_id );

	/**
	 * Delete the relation for the given arguments.
	 *
	 * @param int[]  $content_ids Array with site IDs as keys and content IDs as value.
	 * @param string $type        Content type.
	 *
	 * @return int
	 */
	public function delete_relation( array $content_ids, $type );

	/**
	 * Return the relationship ID for the given arguments.
	 *
	 * @param int[]  $content_ids Array with site IDs as keys and content IDs as values.
	 * @param string $type        Content type.
	 * @param bool   $create      Optional. Create a new relationship if not exists? Defaults to FALSE.
	 *
	 * @return int
	 */
	public function get_relationship_id( array $content_ids, $type, $create = false );

	/**
	 * Return the content ID in the given target site for the given source content element.
	 *
	 * @param int    $site_id        Source site ID.
	 * @param int    $content_id     Source post ID or term taxonomy ID.
	 * @param string $type           Content type.
	 * @param int    $target_site_id Target site ID.
	 *
	 * @return int
	 */
	public function get_content_id_for_site(
		$site_id,
		$content_id,
		$type,
		$target_site_id
	);

	/**
	 * Return an array with site IDs as keys and content IDs as values.
	 *
	 * @param int    $site_id    Source site ID.
	 * @param int    $content_id Source post ID or term taxonomy ID.
	 * @param string $type       Content type.
	 *
	 * @return array
	 */
	public function get_relations( $site_id, $content_id, $type );
}
