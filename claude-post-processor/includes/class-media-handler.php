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
		// Get all attached images
		$attachments = get_attached_media( 'image', $post_id );
		
		if ( empty( $attachments ) ) {
			return $content;
		}

		$attachment_ids = array_keys( $attachments );
		$image_count = count( $attachment_ids );

		// Remove existing image tags from content
		$content = preg_replace( '/<img[^>]+>/i', '', $content );

		if ( 1 === $image_count ) {
			// Single image - display as featured image
			$image_id = $attachment_ids[0];
			$image_html = wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'single-post-image' ) );
			$caption = wp_get_attachment_caption( $image_id );
			
			if ( $caption ) {
				$image_html = '<figure class="wp-block-image size-large">' . $image_html . '<figcaption>' . esc_html( $caption ) . '</figcaption></figure>';
			}
			
			$content .= "\n\n" . $image_html;
		} else {
			// Multiple images - create gallery
			$gallery_shortcode = '[gallery ids="' . implode( ',', $attachment_ids ) . '" columns="3" link="file" size="large"]';
			$content .= "\n\n" . $gallery_shortcode;
		}

		return $content;
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
