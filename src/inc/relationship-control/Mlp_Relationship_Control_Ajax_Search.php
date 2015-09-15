<?php # -*- coding: utf-8 -*-

/**
 * Render results from AJAX search.
 */
class Mlp_Relationship_Control_Ajax_Search {

	/**
	 * @var Mlp_Content_Relations_Interface
	 */
	private $content_relations;

	/**
	 * @var string
	 */
	private $content_type = 'post';

	/**
	 * @var Mlp_Relationship_Control_Data
	 */
	private $data;

	/**
	 * @var int
	 */
	private $relationship_id;

	/**
	 * @param Mlp_Relationship_Control_Data   $data
	 * @param Mlp_Content_Relations_Interface $content_relations
	 */
	public function __construct(
		Mlp_Relationship_Control_Data $data,
		Mlp_Content_Relations_Interface $content_relations
	) {

		$this->data = $data;

		$this->content_relations = $content_relations;

		$this->site_id = get_current_blog_id();

		$this->relationship_id = $content_relations->get_relationship_id(
			array( $this->site_id => $data->get_source_post_id() ),
			$this->content_type
		);
	}

	public function render() {

		$results = $this->data->get_search_results();

		echo $this->format_results( $results );

		$this->die_on_ajax();
	}

	/**
	 * @param WP_Post[] $results
	 *
	 * @return string
	 */
	private function format_results( array $results ) {

		if ( empty( $results ) ) {
			return '<li>' . esc_html__( 'Nothing found.', 'multilingualpress' ) . '</li>';
		}

		$site_id = $this->data->get_remote_site_id();

		$out = '';

		foreach ( $this->prepare_titles( $results ) as $result ) {
			$out .= sprintf(
				'<li>'
				. '<label for="%1$s">'
				. '<input type="radio" name="%2$s" value="%3$d" id="%1$s"%6$s>%4$s (%5$s)'
				. '</label>'
				. '</li>',
				"id_{$site_id}_{$result->ID}",
				"mlp_add_post[$site_id]",
				$result->ID,
				$result->post_title,
				$this->get_translated_status( $result->post_status ),
				$this->is_disabled( $site_id, $result->ID ) ? ' disabled="disabled"' : ''
			);
		}

		return $out;
	}

	/**
	 * Mark duplicates titles with the post ID.
	 *
	 * @param WP_Post[] $posts
	 *
	 * @return WP_Post[]
	 */
	private function prepare_titles( array $posts ) {

		$out = $titles = $duplicates = array();

		foreach ( $posts as $post ) {
			$post->post_title = esc_html( $post->post_title );

			$existing = array_search( $post->post_title, $titles );
			if ( $existing ) {
				$duplicates[] = $post->ID;
				$duplicates[] = $existing;
			}

			$out[ $post->ID ] = $post;

			$titles[ $post->ID ] = $post->post_title;
		}

		if ( empty( $duplicates ) ) {
			return $out;
		}

		foreach ( array_unique( $duplicates ) as $id ) {
			$out[ $id ]->post_title .= " [#$id]";
		}

		return $out;
	}

	/**
	 * Get the translated post status if possible.
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	private function get_translated_status( $status ) {

		static $statuses = FALSE;

		if ( ! $statuses ) {
			$statuses = get_post_statuses();
		}

		if ( isset( $statuses[ $status ] ) ) {
			return $statuses[ $status ];
		}

		$status = ucfirst( $status );

		return esc_html( $status );
	}

	/**
	 * @param int $site_id
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function is_disabled( $site_id, $post_id ) {

		$relationship_id = $this->content_relations->get_relationship_id(
			array( $site_id => $post_id ),
			$this->content_type
		);

		return (
			$relationship_id
			&& $relationship_id !== $this->relationship_id
			&& (
				$this->relationship_id
				|| $this->content_relations->get_content_id( $relationship_id, $this->site_id )
			)
		);
	}

	private function die_on_ajax() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			die;
		}
	}

}
