<?php
/**
 * Media Handler
 *
 * Handles photo galleries, PDFs, and video embeds.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Media_Handler class.
 */
class Media_Handler {

	/**
	 * Process media in post content.
	 *
	 * @param string $content The post content.
	 * @param int    $post_id The post ID.
	 * @return string The processed content with media properly formatted.
	 */
	public function process_media( $content, $post_id ) {
		// Process in order: videos, PDFs, then images
		$content = $this->process_youtube_videos( $content );
		$content = $this->process_direct_videos( $content );
		$content = $this->process_pdfs( $content );
		$content = $this->process_images( $content, $post_id );

		return $content;
	}

	/**
	 * Process images in post content.
	 *
	 * @param string $content The post content.
	 * @param int    $post_id The post ID.
	 * @return string The processed content.
	 */
	public function process_images( $content, $post_id ) {
		// Check if content already has WordPress block gallery or images
		if ( $this->has_wordpress_blocks( $content ) ) {
			// Content already has block editor formatting, preserve it
			return $content;
		}

		// Get all attached images
		$attachments = get_attached_media( 'image', $post_id );
		
		// Also extract any images referenced in simple img tags
		$img_ids_from_content = $this->extract_image_ids_from_tags( $content );
		
		if ( empty( $attachments ) && empty( $img_ids_from_content ) ) {
			return $content;
		}

		// Combine attached images with images found in content
		$all_image_ids = array_keys( $attachments );
		$all_image_ids = array_merge( $all_image_ids, $img_ids_from_content );
		$all_image_ids = array_unique( $all_image_ids );
		
		// Get image IDs that are not already in WordPress blocks
		$used_ids = $this->get_images_in_content( $content );
		$new_attachment_ids = array_diff( $all_image_ids, $used_ids );

		if ( empty( $new_attachment_ids ) ) {
			// All images are already in proper block format
			return $content;
		}

		$image_count = count( $new_attachment_ids );

		// Remove standalone image tags (not in blocks) from content
		$content = preg_replace( '/<img[^>]+>/i', '', $content );
		$content = trim( $content );

		if ( 1 === $image_count ) {
			// Single image - display as WordPress block image
			$image_id = array_values( $new_attachment_ids )[0];
			$image_block = $this->create_image_block( $image_id );
			$content .= "\n\n" . $image_block;
		} else {
			// Multiple images - create WordPress block gallery
			$gallery_block = $this->create_gallery_block( $new_attachment_ids );
			$content .= "\n\n" . $gallery_block;
		}

		return $content;
	}

	/**
	 * Check if content has WordPress block editor formatting.
	 *
	 * @param string $content The post content.
	 * @return bool True if content has WordPress blocks.
	 */
	private function has_wordpress_blocks( $content ) {
		return preg_match( '/<!-- wp:(gallery|image|media-text|columns|group)/', $content ) > 0;
	}

	/**
	 * Extract image attachment IDs from img tags in content.
	 *
	 * @param string $content The post content.
	 * @return array Array of attachment IDs found in img tags.
	 */
	private function extract_image_ids_from_tags( $content ) {
		$ids = array();
		
		// Match img tags with wp-image-ID class
		if ( preg_match_all( '/wp-image-(\d+)/', $content, $matches ) ) {
			$ids = array_merge( $ids, $matches[1] );
		}
		
		// Match img tags with attachment_ID in src
		if ( preg_match_all( '/wp-content\/uploads\/[^"]+\.(?:jpg|jpeg|png|gif|webp)/i', $content, $matches ) ) {
			foreach ( $matches[0] as $url ) {
				// Try to get attachment ID from URL
				$attachment_id = attachment_url_to_postid( $url );
				if ( $attachment_id ) {
					$ids[] = $attachment_id;
				}
			}
		}
		
		return array_unique( array_map( 'intval', array_filter( $ids ) ) );
	}

	/**
	 * Get image IDs already used in content.
	 *
	 * @param string $content The post content.
	 * @return array Array of attachment IDs found in content.
	 */
	private function get_images_in_content( $content ) {
		$ids = array();
		
		// Match WordPress block gallery/image IDs
		if ( preg_match_all( '/"id":(\d+)/', $content, $matches ) ) {
			$ids = array_merge( $ids, $matches[1] );
		}
		
		// Match gallery shortcode IDs
		if ( preg_match( '/\[gallery[^\]]*ids="([^"]+)"/', $content, $matches ) ) {
			$shortcode_ids = explode( ',', $matches[1] );
			$ids = array_merge( $ids, $shortcode_ids );
		}
		
		return array_unique( array_map( 'intval', $ids ) );
	}

	/**
	 * Create a WordPress block image.
	 *
	 * @param int $image_id The attachment ID.
	 * @return string The image block HTML.
	 */
	private function create_image_block( $image_id ) {
		$image = wp_get_attachment_image_src( $image_id, 'large' );
		$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		$caption = wp_get_attachment_caption( $image_id );
		
		if ( ! $image ) {
			return '';
		}

		$block = '<!-- wp:image {"id":' . $image_id . ',"sizeSlug":"large","linkDestination":"none"} -->' . "\n";
		$block .= '<figure class="wp-block-image size-large">';
		$block .= '<img src="' . esc_url( $image[0] ) . '" alt="' . esc_attr( $alt ) . '" class="wp-image-' . $image_id . '"/>';
		
		if ( $caption ) {
			$block .= '<figcaption class="wp-element-caption">' . esc_html( $caption ) . '</figcaption>';
		}
		
		$block .= '</figure>' . "\n";
		$block .= '<!-- /wp:image -->';
		
		return $block;
	}

	/**
	 * Create a WordPress block gallery.
	 *
	 * @param array $image_ids Array of attachment IDs.
	 * @return string The gallery block HTML.
	 */
	private function create_gallery_block( $image_ids ) {
		$images_json = array();
		$images_html = '';
		
		foreach ( $image_ids as $image_id ) {
			$image = wp_get_attachment_image_src( $image_id, 'large' );
			if ( ! $image ) {
				continue;
			}
			
			$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$caption = wp_get_attachment_caption( $image_id );
			
			$images_json[] = array(
				'id'  => $image_id,
				'url' => $image[0],
				'alt' => $alt,
			);
			
			$images_html .= '<figure class="wp-block-image size-large">';
			$images_html .= '<img src="' . esc_url( $image[0] ) . '" alt="' . esc_attr( $alt ) . '" data-id="' . $image_id . '" class="wp-image-' . $image_id . '"/>';
			
			if ( $caption ) {
				$images_html .= '<figcaption class="wp-element-caption">' . esc_html( $caption ) . '</figcaption>';
			}
			
			$images_html .= '</figure>';
		}
		
		$block = '<!-- wp:gallery {"linkTo":"none","sizeSlug":"large"} -->' . "\n";
		$block .= '<figure class="wp-block-gallery has-nested-images columns-default is-cropped">';
		$block .= $images_html;
		$block .= '</figure>' . "\n";
		$block .= '<!-- /wp:gallery -->';
		
		return $block;
	}

	/**
	 * Process PDF files in post content.
	 *
	 * @param string $content The post content.
	 * @return string The processed content.
	 */
	public function process_pdfs( $content ) {
		// Find PDF links
		$pattern = '/<a[^>]+href=["\']([^"\']+\.pdf)["\'][^>]*>.*?<\/a>/i';
		
		$content = preg_replace_callback(
			$pattern,
			function ( $matches ) {
				$pdf_url = $matches[1];
				return $this->create_pdf_embed( $pdf_url );
			},
			$content
		);

		return $content;
	}

	/**
	 * Create PDF embed HTML.
	 *
	 * @param string $pdf_url The URL to the PDF file.
	 * @return string The PDF embed HTML.
	 */
	private function create_pdf_embed( $pdf_url ) {
		return sprintf(
			'<div class="pdf-embed-container">
				<object data="%s" type="application/pdf" width="100%%" height="600px">
					<p>%s <a href="%s">%s</a></p>
				</object>
			</div>',
			esc_url( $pdf_url ),
			esc_html__( 'Unable to display PDF.', 'claude-post-processor' ),
			esc_url( $pdf_url ),
			esc_html__( 'Download instead', 'claude-post-processor' )
		);
	}

	/**
	 * Process YouTube videos in post content.
	 *
	 * @param string $content The post content.
	 * @return string The processed content.
	 */
	public function process_youtube_videos( $content ) {
		// Pattern to match various YouTube URL formats
		$patterns = array(
			'#https?://(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]+)#i',
			'#https?://(?:www\.)?youtu\.be/([a-zA-Z0-9_-]+)#i',
			'#https?://(?:www\.)?youtube\.com/embed/([a-zA-Z0-9_-]+)#i',
		);

		foreach ( $patterns as $pattern ) {
			$content = preg_replace_callback(
				$pattern,
				function ( $matches ) {
					return $this->create_youtube_embed( $matches[1] );
				},
				$content
			);
		}

		return $content;
	}

	/**
	 * Create YouTube embed HTML.
	 *
	 * @param string $video_id The YouTube video ID.
	 * @return string The YouTube embed HTML.
	 */
	private function create_youtube_embed( $video_id ) {
		return sprintf(
			'<div class="video-embed-container">
				<iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen loading="lazy"></iframe>
			</div>',
			esc_attr( $video_id )
		);
	}

	/**
	 * Process direct video files in post content.
	 *
	 * @param string $content The post content.
	 * @return string The processed content.
	 */
	public function process_direct_videos( $content ) {
		// Pattern to match video file links
		$pattern = '/<a[^>]+href=["\']([^"\']+\.(mp4|webm|mov))["\'][^>]*>.*?<\/a>/i';
		
		$content = preg_replace_callback(
			$pattern,
			function ( $matches ) {
				return $this->create_video_embed( $matches[1], $matches[2] );
			},
			$content
		);

		return $content;
	}

	/**
	 * Create video embed HTML.
	 *
	 * @param string $video_url The URL to the video file.
	 * @param string $extension The file extension.
	 * @return string The video embed HTML.
	 */
	private function create_video_embed( $video_url, $extension ) {
		$mime_types = array(
			'mp4'  => 'video/mp4',
			'webm' => 'video/webm',
			'mov'  => 'video/mp4',
		);

		$mime_type = isset( $mime_types[ $extension ] ) ? $mime_types[ $extension ] : 'video/mp4';

		return sprintf(
			'<div class="video-embed-container">
				<video controls width="100%%">
					<source src="%s" type="%s">
					%s
				</video>
			</div>',
			esc_url( $video_url ),
			esc_attr( $mime_type ),
			esc_html__( 'Your browser does not support the video tag.', 'claude-post-processor' )
		);
	}

	/**
	 * Get media assembly location.
	 * Determines where to insert media (after first paragraph or at end).
	 *
	 * @param string $content The post content.
	 * @return int The position to insert media.
	 */
	public function get_media_insert_position( $content ) {
		// Try to find the end of the first paragraph
		$pos = strpos( $content, '</p>' );
		
		if ( false !== $pos ) {
			return $pos + 4; // After the closing </p> tag
		}

		// Fallback to end of content
		return strlen( $content );
	}
}
