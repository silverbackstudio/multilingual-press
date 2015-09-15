<?php # -*- coding: utf-8 -*-

/**
 * Term translation selector.
 */
class Mlp_Term_Translation_Selector {

	/**
	 * @var Mlp_Term_Translation_Presenter
	 */
	private $presenter;

	/**
	 * @var string[]
	 */
	private $related_sites = array();

	/**
	 * @var int
	 */
	private $relationship_id;

	/**
	 * @param Mlp_Term_Translation_Presenter $presenter
	 */
	public function __construct( Mlp_Term_Translation_Presenter $presenter ) {

		$this->presenter = $presenter;

		$this->related_sites = $presenter->get_site_languages();

		$this->relationship_id = $this->get_current_relationship_id();
	}

	/**
	 * Return the relationship ID for the current term taxonomy ID.
	 *
	 * @return int
	 */
	private function get_current_relationship_id() {

		if ( empty( $_GET[ 'tag_ID' ] ) ) {
			return 0;
		}

		$term = get_term_by( 'id', (int) $_GET[ 'tag_ID' ], $this->presenter->get_taxonomy_name() );
		if ( ! isset( $term->term_taxonomy_id ) ) {
			return 0;
		}

		$site_id = get_current_blog_id();

		return $this->presenter->get_relationship_id( $site_id, $term->term_taxonomy_id );
	}

	/**
	 * @return bool
	 */
	public function print_title() {

		if ( empty( $this->related_sites ) ) {
			return FALSE;
		}

		echo $this->presenter->get_group_title();

		return TRUE;
	}

	/**
	 * @return bool
	 */
	public function print_table() {

		if ( empty( $this->related_sites ) ) {
			return FALSE;
		}

		echo $this->presenter->get_nonce_field();

		$this->print_style();
		?>
		<table class="mlp_term_selections">
			<?php foreach ( $this->related_sites as $site_id => $language ) : ?>
				<?php
				$key = $this->presenter->get_key_base( $site_id );
				$label_id = $this->get_label_id( $key );
				$terms = $this->presenter->get_terms_for_site( $site_id );
				$current_term_taxonomy_id = $this->get_current_term_taxonomy_id( $site_id );
				?>
				<tr>
					<th>
						<label for="<?php echo $label_id; ?>"><?php echo $language; ?></label>
					</th>
					<td>
						<?php if ( empty( $terms ) ) : ?>
							<?php echo $this->get_no_terms_found_message( $site_id ); ?>
						<?php else : ?>
							<select name="<?php echo $key; ?>" id="<?php echo $label_id; ?>" autocomplete="off">
								<option value="0" class="mlp_empty_option">
									<?php esc_html_e( 'No translation', 'multilingualpress' ); ?>
								</option>
								<?php $this->print_term_options( $terms, $current_term_taxonomy_id, $site_id ); ?>
							</select>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
		return TRUE;
	}

	/**
	 * Print inline stylesheet.
	 *
	 * @return void
	 */
	private function print_style() {

		$id = $this->get_fieldset_id();

		echo <<<STYLE
<style>
	#$id {
		margin: 1em 0;
	}
	#$id legend {
		font-weight: bold;
	}
	.mlp_term_selections th {
		text-align: right;
	}
	.mlp_term_selections select {
		width: 20em;
	}
	.mlp_empty_option {
		font-style: italic;
	}
	.mlp_term_selections th, .mlp_term_selections td {
		padding: 0 5px;
		vertical-align: middle;
		font-weight: normal;
		width: auto;
	}
</style>
STYLE;
	}

	/**
	 * @return string
	 */
	public function get_fieldset_id() {

		return 'mlp_term_translation';
	}

	/**
	 * Make sure we have a HTML-4 compatible id attribute.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private function get_label_id( $key ) {

		return str_replace( array( '[', ']' ), '', $key );
	}

	/**
	 * Return the term taxonomy ID for the currently saved term.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return int
	 */
	private function get_current_term_taxonomy_id( $site_id ) {

		if ( empty( $_GET[ 'tag_ID' ] ) ) {
			return 0;
		}

		return $this->presenter->get_current_term_taxonomy_id( $site_id, (int) $_GET[ 'tag_ID' ] );
	}

	/**
	 * Create the message to display when there are no terms on the other site.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return string
	 */
	private function get_no_terms_found_message( $site_id ) {

		$taxonomy_name = $this->presenter->get_taxonomy_name();

		$url = get_admin_url( $site_id, 'edit-tags.php' );
		$url = add_query_arg(
			'taxonomy',
			$taxonomy_name,
			$url
		);
		$url = esc_url( $url );

		$taxonomy_object = get_taxonomy( $taxonomy_name );

		$text = isset( $taxonomy_object->labels->not_found )
			? esc_html( $taxonomy_object->labels->not_found )
			: esc_html__( 'No terms found.', 'multilingualpress' );

		return sprintf( '<p><a href="%1$s">%2$s</a></p>', $url, $text );
	}

	/**
	 * Render the option tags for the given terms.
	 *
	 * @param string[] $terms                    Term names.
	 * @param int      $current_term_taxonomy_id Currently saved term taxonomy ID.
	 * @param int      $site_id                  Site ID.
	 *
	 * @return void
	 */
	private function print_term_options( array $terms, $current_term_taxonomy_id, $site_id ) {

		foreach ( $terms as $term_taxonomy_id => $term_name ) {
			echo $this->get_option_element(
				$term_taxonomy_id,
				$term_name,
				$current_term_taxonomy_id,
				$this->presenter->get_relationship_id( $site_id, $term_taxonomy_id )
			);
		}
	}

	/**
	 * Return the option tag for the given term.
	 *
	 * @param int    $term_taxonomy_id         Term taxonomy ID.
	 * @param string $term_name                Term name.
	 * @param int    $current_term_taxonomy_id Currently saved term taxonomy ID.
	 * @param int    $relationship_id          Relationship ID.
	 *
	 * @return string
	 */
	private function get_option_element(
		$term_taxonomy_id,
		$term_name,
		$current_term_taxonomy_id,
		$relationship_id
	) {

		$site_id = get_current_blog_id();

		$state = '';
		if (
			$relationship_id
			&& $relationship_id !== $this->relationship_id
			&& (
				$this->relationship_id
				|| $this->presenter->relation_exists( $relationship_id, $site_id )
			)
		) {
			$state = ' disabled="disabled"';
		} elseif ( $current_term_taxonomy_id === $term_taxonomy_id ) {
			$state = ' selected="selected"';
		}

		return sprintf(
			'<option value="%1$d" data-relationship="%4$d"%2$s>%3$s</option>',
			$term_taxonomy_id,
			$state,
			$term_name,
			$relationship_id
		);
	}

}
