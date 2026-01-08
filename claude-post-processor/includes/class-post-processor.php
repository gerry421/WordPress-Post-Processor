<?php
/**
 * Post Processor
 *
 * Main processing logic for posts.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post_Processor class.
 */
class Post_Processor {

	/**
	 * Minimum content length for historical enrichment validation.
	 *
	 * @var int
	 */
	const MIN_ENRICHMENT_LENGTH = 20;

	/**
	 * Claude API handler.
	 *
	 * @var AI_Provider
	 */
	private $api;

	/**
	 * Media handler.
	 *
	 * @var Media_Handler
	 */
	private $media;

	/**
	 * Taxonomy manager.
	 *
	 * @var Taxonomy_Manager
	 */
	private $taxonomy;

	/**
	 * Constructor.
	 *
	 * @param AI_Provider      $api AI provider handler.
	 * @param Media_Handler    $media Media handler.
	 * @param Taxonomy_Manager $taxonomy Taxonomy manager.
	 */
	public function __construct( $api, $media, $taxonomy ) {
		$this->api      = $api;
		$this->media    = $media;
		$this->taxonomy = $taxonomy;

		// Hook into save_post for auto-processing
		add_action( 'save_post', array( $this, 'maybe_auto_process' ), 10, 3 );

		// Background processing hook
		add_action( 'claude_process_post_background', array( $this, 'process_post' ) );

		// Add bulk action
		add_filter( 'bulk_actions-edit-post', array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-edit-post', array( $this, 'handle_bulk_action' ), 10, 3 );

		// Add row action
		add_filter( 'post_row_actions', array( $this, 'add_row_action' ), 10, 2 );
		add_action( 'admin_action_claude_process_post', array( $this, 'handle_row_action' ) );
		add_action( 'admin_action_claude_reprocess_post', array( $this, 'handle_reprocess_row_action' ) );

		// Add custom column
		add_filter( 'manage_posts_columns', array( $this, 'add_custom_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_custom_column' ), 10, 2 );
	}

	/**
	 * Maybe auto-process a post when it's saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an update.
	 */
	public function maybe_auto_process( $post_id, $post, $update ) {
		// Skip if auto-processing is disabled
		if ( ! get_option( 'claude_post_processor_auto_process', false ) ) {
			return;
		}

		// Skip if this is an update
		if ( $update ) {
			return;
		}

		// Skip if not a post
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Skip if already processed
		if ( get_post_meta( $post_id, '_claude_processed', true ) ) {
			return;
		}

		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Schedule background processing
		wp_schedule_single_event( time() + 10, 'claude_process_post_background', array( $post_id ) );
	}

	/**
	 * Process a single post.
	 *
	 * @param int $post_id The post ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function process_post( $post_id ) {
		$post = get_post( $post_id );
		
		if ( ! $post || 'post' !== $post->post_type ) {
			return new WP_Error( 'invalid_post', __( 'Invalid post.', 'claude-post-processor' ) );
		}

		// Backup original content
		update_post_meta( $post_id, '_claude_original_content', $post->post_content );
		update_post_meta( $post_id, '_claude_original_title', $post->post_title );

		$processing_log = array();
		$content = $post->post_content;
		$title = $post->post_title;

		// Step 1: Grammar and spelling correction
		$processing_log[] = 'Starting grammar and spelling correction';
		$corrected_content = $this->correct_grammar( $content );
		if ( is_wp_error( $corrected_content ) ) {
			$processing_log[] = 'Grammar correction failed: ' . $corrected_content->get_error_message();
			update_post_meta( $post_id, '_claude_processing_log', $processing_log );
			return $corrected_content;
		}
		$content = $corrected_content;
		$processing_log[] = 'Grammar and spelling correction completed';

		// Add delay between API calls
		sleep( 2 );

		// Step 2: Title generation
		$processing_log[] = 'Starting title generation';
		$generated_title = $this->generate_title( $content );
		if ( ! is_wp_error( $generated_title ) && ! empty( $generated_title ) ) {
			$title = $generated_title;
			$processing_log[] = 'Title generation completed';
		} else {
			$processing_log[] = 'Title generation failed or returned empty';
		}

		// Add delay between API calls
		sleep( 2 );

		// Step 3: Tag generation
		$processing_log[] = 'Starting tag generation';
		$tags = $this->generate_tags( $content );
		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
			$this->taxonomy->process_tags( $post_id, $tags );
			$processing_log[] = 'Tags generated and assigned';
		} else {
			$processing_log[] = 'Tag generation failed or returned empty';
		}

		// Add delay between API calls
		sleep( 2 );

		// Step 4: Category generation
		$processing_log[] = 'Starting category generation';
		$categories = $this->generate_categories( $content );
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$this->taxonomy->process_categories( $post_id, $categories );
			$processing_log[] = 'Categories generated and assigned';
		} else {
			$processing_log[] = 'Category generation failed or returned empty';
		}

		// Add delay between API calls
		sleep( 2 );

		// Step 5: Historical enrichment (if enabled)
		$enrichment = '';
		if ( get_option( 'claude_post_processor_include_historical', true ) ) {
			$processing_log[] = 'Starting historical enrichment';
			$enrichment = $this->generate_historical_enrichment( $content );
			if ( ! is_wp_error( $enrichment ) && ! empty( $enrichment ) ) {
				$processing_log[] = 'Historical enrichment completed';
			} else {
				$enrichment = '';
				$processing_log[] = 'Historical enrichment failed or returned empty';
			}
		} else {
			$processing_log[] = 'Historical enrichment skipped (disabled in settings)';
		}

		// Step 6: Assemble final content
		$processing_log[] = 'Assembling final content';
		$final_content = $this->assemble_content( $content, $enrichment, $post_id );

		// Step 7: Process media
		$processing_log[] = 'Processing media';
		$final_content = $this->media->process_media( $final_content, $post_id );

		// Update the post
		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $title,
				'post_content' => $final_content,
				'post_status'  => 'draft',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			$processing_log[] = 'Failed to update post: ' . $updated->get_error_message();
			update_post_meta( $post_id, '_claude_processing_log', $processing_log );
			return $updated;
		}

		// Mark as processed
		update_post_meta( $post_id, '_claude_processed', true );
		update_post_meta( $post_id, '_claude_processed_date', current_time( 'timestamp' ) );
		update_post_meta( $post_id, '_claude_processing_log', $processing_log );

		$processing_log[] = 'Post processing completed successfully';

		// Send email notification if enabled
		if ( get_option( 'claude_post_processor_email_notifications', false ) ) {
			$this->send_notification_email( $post_id );
		}

		return true;
	}

	/**
	 * Correct grammar and spelling in content.
	 *
	 * @param string $content The content to correct.
	 * @return string|WP_Error The corrected content or WP_Error on failure.
	 */
	private function correct_grammar( $content ) {
		$prompt = "You are an expert editor. Review and correct the following text for:\n";
		$prompt .= "- Grammar errors\n";
		$prompt .= "- Spelling mistakes\n";
		$prompt .= "- Punctuation issues\n";
		$prompt .= "- Sentence structure improvements\n\n";
		$prompt .= "Preserve the original voice and style. Return only the corrected text without explanations.\n\n";
		$prompt .= "Text to review:\n" . $content;

		$response = $this->api->send_message( $prompt );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$corrected = $this->api->extract_text( $response );
		
		if ( false === $corrected ) {
			return new WP_Error( 'extraction_failed', __( 'Failed to extract corrected text.', 'claude-post-processor' ) );
		}

		return $corrected;
	}

	/**
	 * Generate a title for the content.
	 *
	 * @param string $content The content.
	 * @return string|WP_Error The generated title or WP_Error on failure.
	 */
	private function generate_title( $content ) {
		$prompt = "Based on the following narrative, generate a compelling, SEO-friendly title that captures the essence of the content. The title should be:\n";
		$prompt .= "- Between 40-60 characters\n";
		$prompt .= "- Engaging and descriptive\n";
		$prompt .= "- Not clickbait\n\n";
		$prompt .= "Return only the title, no quotes or additional text.\n\n";
		$prompt .= "Narrative:\n" . $content;

		$response = $this->api->send_message( $prompt, 100 );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$title = $this->api->extract_text( $response );
		
		if ( false === $title ) {
			return new WP_Error( 'extraction_failed', __( 'Failed to extract title.', 'claude-post-processor' ) );
		}

		return $title;
	}

	/**
	 * Generate tags for the content.
	 *
	 * @param string $content The content.
	 * @return string|WP_Error Comma-separated tags or WP_Error on failure.
	 */
	private function generate_tags( $content ) {
		$prompt = "Analyze the following narrative and generate 5-10 relevant tags.\n";
		$prompt .= "Tags should be:\n";
		$prompt .= "- Single words or short phrases (2-3 words max)\n";
		$prompt .= "- Relevant to the main topics, themes, people, and places mentioned\n";
		$prompt .= "- A mix of specific and general terms for SEO\n\n";
		$prompt .= "Return as a comma-separated list only.\n\n";
		$prompt .= "Narrative:\n" . $content;

		$response = $this->api->send_message( $prompt, 200 );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tags = $this->api->extract_text( $response );
		
		if ( false === $tags ) {
			return new WP_Error( 'extraction_failed', __( 'Failed to extract tags.', 'claude-post-processor' ) );
		}

		return $tags;
	}

	/**
	 * Generate categories for the content.
	 *
	 * @param string $content The content.
	 * @return string|WP_Error Comma-separated categories or WP_Error on failure.
	 */
	private function generate_categories( $content ) {
		$prompt = "Analyze the following narrative and suggest 1-3 appropriate categories.\n";
		$prompt .= "Categories should be:\n";
		$prompt .= "- Broad topic areas that could apply to multiple posts\n";
		$prompt .= "- Hierarchical if appropriate (e.g., \"Travel > Europe > France\")\n\n";
		$prompt .= "Return as a comma-separated list. Use \" > \" for hierarchy.\n\n";
		$prompt .= "Narrative:\n" . $content;

		$response = $this->api->send_message( $prompt, 200 );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$categories = $this->api->extract_text( $response );
		
		if ( false === $categories ) {
			return new WP_Error( 'extraction_failed', __( 'Failed to extract categories.', 'claude-post-processor' ) );
		}

		return $categories;
	}

	/**
	 * Generate historical enrichment for the content.
	 *
	 * @param string $content The content.
	 * @return string|WP_Error Historical enrichment HTML or WP_Error on failure.
	 */
	private function generate_historical_enrichment( $content ) {
		$prompt = "Analyze the following narrative and identify SPECIFIC places, landmarks, or historical sites mentioned.\n\n";
		$prompt .= "IMPORTANT QUALITY GUIDELINES:\n";
		$prompt .= "- Only include SPECIFIC, named locations (e.g., 'Eiffel Tower', 'Golden Gate Bridge', 'Notre-Dame Cathedral')\n";
		$prompt .= "- DO NOT include generic references (e.g., 'the park', 'home', 'a restaurant', 'the beach')\n";
		$prompt .= "- Only include locations that are CENTRAL to the narrative\n";
		$prompt .= "- If NO specific places are found, return exactly: NO_PLACES_FOUND\n\n";
		$prompt .= "For each SPECIFIC location found, provide:\n";
		$prompt .= "- Historical context (2-3 sentences) with SPECIFIC dates and events\n";
		$prompt .= "- One interesting, LESSER-KNOWN fact (avoid obvious information)\n";
		$prompt .= "- Focus on substantive historical information, not trivial details\n\n";
		$prompt .= "Format the response as a WordPress quote block with the following EXACT structure:\n\n";
		$prompt .= "<!-- wp:quote -->\n";
		$prompt .= "<blockquote class=\"wp-block-quote\">\n";
		$prompt .= "  <p><strong>📍 Historical Context</strong></p>\n";
		$prompt .= "  <p><strong>[Location Name]</strong><br>\n";
		$prompt .= "  [Historical context in 2-3 sentences. Include specific dates and events.]</p>\n";
		$prompt .= "  <p><em>Did you know?</em> [Interesting lesser-known fact]</p>\n";
		$prompt .= "  <!-- Repeat for additional locations -->\n";
		$prompt .= "</blockquote>\n";
		$prompt .= "<!-- /wp:quote -->\n\n";
		$prompt .= "Narrative:\n" . $content;

		$response = $this->api->send_message( $prompt );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$enrichment = $this->api->extract_text( $response );
		
		if ( false === $enrichment ) {
			return new WP_Error( 'extraction_failed', __( 'Failed to extract historical enrichment.', 'claude-post-processor' ) );
		}

		// Check if no places were found
		if ( false !== stripos( $enrichment, 'NO_PLACES_FOUND' ) ) {
			return '';
		}

		// Validate that proper quote block format exists
		if ( false === strpos( $enrichment, 'wp:quote' ) || false === strpos( $enrichment, 'wp-block-quote' ) ) {
			return '';
		}

		// Validate content has meaningful length
		$content_check = strip_tags( $enrichment );
		if ( strlen( trim( $content_check ) ) < self::MIN_ENRICHMENT_LENGTH ) {
			return '';
		}

		return $enrichment;
	}

	/**
	 * Assemble final content in correct order.
	 *
	 * @param string $narrative The main narrative.
	 * @param string $enrichment The historical enrichment.
	 * @param int    $post_id The post ID.
	 * @return string The assembled content.
	 */
	private function assemble_content( $narrative, $enrichment, $post_id ) {
		// Structure:
		// 1. Main narrative
		// 2. Historical enrichment (if any)
		// 3. Media will be added by media handler

		$content = $narrative;

		// Add historical enrichment if available (only if not empty after trimming)
		if ( ! empty( trim( $enrichment ) ) ) {
			$content .= "\n\n" . $enrichment;
		}

		return $content;
	}

	/**
	 * Send notification email.
	 *
	 * @param int $post_id The post ID.
	 */
	private function send_notification_email( $post_id ) {
		$post = get_post( $post_id );
		$admin_email = get_option( 'admin_email' );
		
		$subject = sprintf(
			/* translators: %s: Post title */
			__( 'Post processed: %s', 'claude-post-processor' ),
			$post->post_title
		);

		$message = sprintf(
			/* translators: 1: Post title, 2: Edit post URL */
			__( 'The post "%1$s" has been processed with Claude AI and is now in draft status.\n\nView and edit: %2$s', 'claude-post-processor' ),
			$post->post_title,
			get_edit_post_link( $post_id, 'raw' )
		);

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Add bulk action.
	 *
	 * @param array $actions Existing actions.
	 * @return array Modified actions.
	 */
	public function add_bulk_action( $actions ) {
		$actions['claude_process'] = __( 'Process with Claude', 'claude-post-processor' );
		$actions['claude_reprocess'] = __( 'Reprocess with Claude', 'claude-post-processor' );
		return $actions;
	}

	/**
	 * Handle bulk action.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action Action name.
	 * @param array  $post_ids Post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
		if ( 'claude_process' !== $action && 'claude_reprocess' !== $action ) {
			return $redirect_to;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $redirect_to;
		}

		$processed = 0;
		foreach ( $post_ids as $post_id ) {
			// Clear metadata if reprocessing
			if ( 'claude_reprocess' === $action ) {
				$this->clear_processing_meta( $post_id );
			}

			$result = $this->process_post( $post_id );
			if ( ! is_wp_error( $result ) ) {
				$processed++;
			}
			// Add delay between posts
			sleep( 2 );
		}

		if ( 'claude_reprocess' === $action ) {
			$redirect_to = add_query_arg( 'reprocessed', $processed, $redirect_to );
		} else {
			$redirect_to = add_query_arg( 'claude_processed', $processed, $redirect_to );
		}
		return $redirect_to;
	}

	/**
	 * Add row action.
	 *
	 * @param array   $actions Existing actions.
	 * @param WP_Post $post Post object.
	 * @return array Modified actions.
	 */
	public function add_row_action( $actions, $post ) {
		if ( 'post' === $post->post_type && current_user_can( 'edit_post', $post->ID ) ) {
			$is_processed = get_post_meta( $post->ID, '_claude_processed', true );
			
			if ( $is_processed ) {
				// Show "Reprocess" for already processed posts
				$url = wp_nonce_url(
					admin_url( 'admin.php?action=claude_reprocess_post&post=' . $post->ID ),
					'claude_reprocess_post_' . $post->ID
				);
				$actions['claude_reprocess'] = '<a href="' . esc_url( $url ) . '">' . __( 'Reprocess with Claude', 'claude-post-processor' ) . '</a>';
			} else {
				// Show "Process" for unprocessed posts
				$url = wp_nonce_url(
					admin_url( 'admin.php?action=claude_process_post&post=' . $post->ID ),
					'claude_process_post_' . $post->ID
				);
				$actions['claude_process'] = '<a href="' . esc_url( $url ) . '">' . __( 'Process with Claude', 'claude-post-processor' ) . '</a>';
			}
		}
		return $actions;
	}

	/**
	 * Handle row action.
	 */
	public function handle_row_action() {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( esc_html__( 'No post specified.', 'claude-post-processor' ) );
		}

		$post_id = absint( $_GET['post'] );

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'claude_process_post_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'claude-post-processor' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to process this post.', 'claude-post-processor' ) );
		}

		// Process the post
		$result = $this->process_post( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		// Redirect back to post list
		wp_safe_redirect( admin_url( 'edit.php?claude_processed=1' ) );
		exit;
	}

	/**
	 * Handle reprocess row action.
	 */
	public function handle_reprocess_row_action() {
		if ( ! isset( $_GET['post'] ) ) {
			wp_die( esc_html__( 'No post specified.', 'claude-post-processor' ) );
		}

		$post_id = absint( $_GET['post'] );

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'claude_reprocess_post_' . $post_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'claude-post-processor' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to reprocess this post.', 'claude-post-processor' ) );
		}

		// Clear processing metadata
		$this->clear_processing_meta( $post_id );

		// Process the post
		$result = $this->process_post( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		// Redirect back to post list
		wp_safe_redirect( admin_url( 'edit.php?reprocessed=1' ) );
		exit;
	}

	/**
	 * Add custom column to posts list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_custom_column( $columns ) {
		$columns['claude_status'] = __( 'Claude Status', 'claude-post-processor' );
		return $columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_custom_column( $column, $post_id ) {
		if ( 'claude_status' !== $column ) {
			return;
		}

		$processed = get_post_meta( $post_id, '_claude_processed', true );
		
		if ( $processed ) {
			$date = get_post_meta( $post_id, '_claude_processed_date', true );
			echo '<span style="color: green;">✓ ' . esc_html__( 'Processed', 'claude-post-processor' ) . '</span>';
			if ( $date ) {
				echo '<br><small>' . esc_html( date_i18n( get_option( 'date_format' ), $date ) ) . '</small>';
			}
		} else {
			echo '<span style="color: #999;">—</span>';
		}
	}

	/**
	 * Get unprocessed posts.
	 *
	 * @return array Array of post objects.
	 */
	public function get_unprocessed_posts() {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_claude_processed',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_claude_processed',
					'value'   => '',
					'compare' => '=',
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Get processed posts.
	 *
	 * @return array Array of post objects.
	 */
	public function get_processed_posts() {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_claude_processed',
					'value'   => '1',
					'compare' => '=',
				),
			),
		);
		return get_posts( $args );
	}

	/**
	 * Clear processing metadata to allow reprocessing.
	 *
	 * @param int $post_id The post ID.
	 */
	public function clear_processing_meta( $post_id ) {
		delete_post_meta( $post_id, '_claude_processed' );
		delete_post_meta( $post_id, '_claude_processed_date' );
		// Keep _claude_original_content and _claude_processing_log for audit trail
	}
}
