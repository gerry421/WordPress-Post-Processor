<?php
/**
 * Taxonomy Manager
 *
 * Handles tags and categories creation and assignment.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Taxonomy_Manager class.
 */
class Taxonomy_Manager {

	/**
	 * Process and assign tags to a post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $tags_csv Comma-separated list of tags.
	 * @return array Array of assigned tag IDs.
	 */
	public function process_tags( $post_id, $tags_csv ) {
		if ( empty( $tags_csv ) ) {
			return array();
		}

		// Split tags by comma and trim whitespace
		$tags = array_map( 'trim', explode( ',', $tags_csv ) );
		$tags = array_filter( $tags ); // Remove empty values

		// Set tags on the post
		wp_set_post_tags( $post_id, $tags, false );

		// Store which tags were auto-generated
		update_post_meta( $post_id, '_claude_generated_tags', $tags );

		return $tags;
	}

	/**
	 * Process and assign categories to a post.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $categories_csv Comma-separated list of categories.
	 * @return array Array of assigned category IDs.
	 */
	public function process_categories( $post_id, $categories_csv ) {
		if ( empty( $categories_csv ) ) {
			return array();
		}

		// Split categories by comma and trim whitespace
		$categories = array_map( 'trim', explode( ',', $categories_csv ) );
		$categories = array_filter( $categories ); // Remove empty values

		$category_ids = array();
		$generated_categories = array();

		foreach ( $categories as $category_path ) {
			$cat_id = $this->create_or_get_category( $category_path );
			if ( $cat_id ) {
				$category_ids[] = $cat_id;
				$generated_categories[] = $category_path;
			}
		}

		// Assign categories to post
		if ( ! empty( $category_ids ) ) {
			wp_set_post_categories( $post_id, $category_ids, false );
		}

		// Store which categories were auto-generated
		update_post_meta( $post_id, '_claude_generated_categories', $generated_categories );

		return $category_ids;
	}

	/**
	 * Create or get a category, handling hierarchy.
	 *
	 * @param string $category_path Category path (e.g., "Travel > Europe > France").
	 * @return int|false Category ID or false on failure.
	 */
	private function create_or_get_category( $category_path ) {
		// Check if this is a hierarchical category
		if ( strpos( $category_path, '>' ) !== false ) {
			return $this->create_hierarchical_category( $category_path );
		}

		// Simple category - check if it exists
		$term = get_term_by( 'name', $category_path, 'category' );
		
		if ( $term ) {
			return $term->term_id;
		}

		// Create the category
		$result = wp_insert_term( $category_path, 'category' );
		
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return $result['term_id'];
	}

	/**
	 * Create hierarchical categories.
	 *
	 * @param string $category_path Hierarchical category path.
	 * @return int|false The leaf category ID or false on failure.
	 */
	private function create_hierarchical_category( $category_path ) {
		// Split by ' > ' to get hierarchy levels
		$levels = array_map( 'trim', explode( '>', $category_path ) );
		$parent_id = 0;

		foreach ( $levels as $level ) {
			$term = get_term_by( 'name', $level, 'category' );
			
			if ( $term && $term->parent === $parent_id ) {
				// Category exists with correct parent
				$parent_id = $term->term_id;
			} else {
				// Create the category with the current parent
				$result = wp_insert_term(
					$level,
					'category',
					array( 'parent' => $parent_id )
				);
				
				if ( is_wp_error( $result ) ) {
					// If category exists but with different parent, try to find it
					$existing = get_term_by( 'name', $level, 'category' );
					if ( $existing ) {
						$parent_id = $existing->term_id;
					} else {
						return false;
					}
				} else {
					$parent_id = $result['term_id'];
				}
			}
		}

		return $parent_id;
	}

	/**
	 * Get all categories as a hierarchical array.
	 *
	 * @return array Array of categories.
	 */
	public function get_all_categories() {
		return get_categories(
			array(
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
			)
		);
	}

	/**
	 * Get all tags.
	 *
	 * @return array Array of tags.
	 */
	public function get_all_tags() {
		return get_tags(
			array(
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
			)
		);
	}
}
