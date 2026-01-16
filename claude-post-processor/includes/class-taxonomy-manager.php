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
	 * Cached categories list for performance optimization.
	 *
	 * @var array|null
	 */
	private $cached_categories = null;

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

		// Cache all existing tags once to avoid multiple queries
		$all_tags = get_tags( array( 'hide_empty' => false ) );

		// Match existing tags case-insensitively
		$matched_tags = array();
		foreach ( $tags as $tag_name ) {
			$matched_tag = $this->find_existing_tag_in_list( $tag_name, $all_tags );
			if ( $matched_tag ) {
				$matched_tags[] = $matched_tag;
			} else {
				$matched_tags[] = $tag_name;
			}
		}

		// Set tags on the post
		wp_set_post_tags( $post_id, $matched_tags, false );

		// Store which tags were auto-generated
		update_post_meta( $post_id, '_claude_generated_tags', $matched_tags );

		return $matched_tags;
	}

	/**
	 * Find an existing tag by name in a list (case-insensitive).
	 *
	 * @param string $tag_name The tag name to search for.
	 * @param array  $tag_list Array of tag objects to search in.
	 * @return string|false The matched tag name or false if not found.
	 */
	private function find_existing_tag_in_list( $tag_name, $tag_list ) {
		foreach ( $tag_list as $tag ) {
			if ( strcasecmp( $tag->name, $tag_name ) === 0 ) {
				return $tag->name;
			}
		}
		
		return false;
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

		// Cache all existing categories once to avoid multiple queries
		$this->cached_categories = get_categories( array( 'hide_empty' => false ) );

		$category_ids = array();
		$generated_categories = array();

		foreach ( $categories as $category_path ) {
			$cat_id = $this->create_or_get_category( $category_path );
			if ( $cat_id ) {
				$category_ids[] = $cat_id;
				$generated_categories[] = $category_path;
			}
		}

		// Clear cache after processing
		$this->cached_categories = null;

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

		// Simple category - check if it exists (case-insensitive)
		$term = $this->find_existing_category( $category_path );
		
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
	 * Find an existing category by name (case-insensitive).
	 *
	 * @param string $category_name The category name to search for.
	 * @param int    $parent_id Optional parent ID to match.
	 * @return WP_Term|false The matched category term or false if not found.
	 */
	private function find_existing_category( $category_name, $parent_id = 0 ) {
		// Use cached categories if available, otherwise fetch
		$all_categories = $this->cached_categories ?? get_categories( array( 'hide_empty' => false ) );
		
		foreach ( $all_categories as $category ) {
			if ( strcasecmp( $category->name, $category_name ) === 0 ) {
				// If parent_id is specified, check if it matches
				if ( $parent_id > 0 && $category->parent !== $parent_id ) {
					continue;
				}
				return $category;
			}
		}
		
		return false;
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
			// Try to find existing category with this name and parent (case-insensitive)
			$term = $this->find_existing_category( $level, $parent_id );
			
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
					$existing = $this->find_existing_category( $level );
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

	/**
	 * Get the full hierarchical path of a category.
	 *
	 * @param WP_Term $category The category term object.
	 * @return string The hierarchical path (e.g., "Travel > Europe > France").
	 */
	public function get_category_path( $category ) {
		$path_parts = array( $category->name );
		$parent_id = $category->parent;
		
		// Walk up the hierarchy
		while ( $parent_id > 0 ) {
			$parent = get_category( $parent_id );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			array_unshift( $path_parts, $parent->name );
			$parent_id = $parent->parent;
		}
		
		return implode( ' > ', $path_parts );
	}
}
