<?php
/**
 * Claude API Handler
 *
 * Handles all communication with the Anthropic Claude API.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Claude_API class.
 */
class Claude_API implements AI_Provider {

	/**
	 * API endpoint.
	 */
	const API_ENDPOINT = 'https://api.anthropic.com/v1/messages';

	/**
	 * API version.
	 */
	const API_VERSION = '2023-06-01';

	/**
	 * Maximum retry attempts.
	 */
	const MAX_RETRIES = 5;

	/**
	 * Initial retry delay in seconds.
	 */
	const INITIAL_RETRY_DELAY = 2;

	/**
	 * Get the provider name.
	 *
	 * @return string Provider name.
	 */
	public function get_provider_name() {
		return 'Anthropic Claude';
	}

	/**
	 * Get the encrypted API key.
	 *
	 * @return string|false The decrypted API key or false if not set.
	 */
	public function get_api_key() {
		$encrypted_key = get_option( 'claude_post_processor_api_key', '' );
		
		if ( empty( $encrypted_key ) ) {
			return false;
		}

		// Decrypt the API key
		return $this->decrypt_api_key( $encrypted_key );
	}

	/**
	 * Set and encrypt the API key.
	 *
	 * @param string $api_key The API key to encrypt and store.
	 * @return bool True on success, false on failure.
	 */
	public function set_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return delete_option( 'claude_post_processor_api_key' );
		}

		$encrypted_key = $this->encrypt_api_key( $api_key );
		return update_option( 'claude_post_processor_api_key', $encrypted_key );
	}

	/**
	 * Encrypt the API key.
	 *
	 * @param string $api_key The API key to encrypt.
	 * @return string The encrypted API key.
	 */
	private function encrypt_api_key( $api_key ) {
		// Use WordPress salt as encryption key
		$key = wp_salt( 'auth' );
		
		// Use openssl for encryption if available
		if ( function_exists( 'openssl_encrypt' ) && function_exists( 'random_bytes' ) ) {
			$iv = random_bytes( 16 );
			$encrypted = openssl_encrypt( $api_key, 'AES-256-CBC', $key, 0, $iv );
			return base64_encode( $encrypted . '::' . $iv );
		}
		
		// Fallback to base64 encoding (not secure, but better than plaintext)
		return base64_encode( $api_key );
	}

	/**
	 * Decrypt the API key.
	 *
	 * @param string $encrypted_key The encrypted API key.
	 * @return string The decrypted API key.
	 */
	private function decrypt_api_key( $encrypted_key ) {
		// Use WordPress salt as encryption key
		$key = wp_salt( 'auth' );
		
		// Use openssl for decryption if available
		if ( function_exists( 'openssl_decrypt' ) && strpos( base64_decode( $encrypted_key ), '::' ) !== false ) {
			$decoded = base64_decode( $encrypted_key );
			list( $encrypted_data, $iv ) = explode( '::', $decoded, 2 );
			return openssl_decrypt( $encrypted_data, 'AES-256-CBC', $key, 0, $iv );
		}
		
		// Fallback to base64 decoding
		return base64_decode( $encrypted_key );
	}

	/**
	 * Send a message to Claude API.
	 *
	 * @param string $prompt The prompt to send.
	 * @param int    $max_tokens Maximum tokens in response (default 4096).
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	public function send_message( $prompt, $max_tokens = 4096 ) {
		$api_key = $this->get_api_key();
		
		if ( ! $api_key ) {
			$this->log_error( 'API key not configured' );
			return new WP_Error( 'no_api_key', __( 'Claude API key is not configured.', 'claude-post-processor' ) );
		}

		$model = get_option( 'claude_post_processor_model', 'claude-sonnet-4-20250514' );

		$body = array(
			'model'      => $model,
			'max_tokens' => $max_tokens,
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);

		$args = array(
			'headers' => array(
				'x-api-key'         => $api_key,
				'anthropic-version' => self::API_VERSION,
				'content-type'      => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		);

		// Attempt the request with exponential backoff
		$attempt = 0;
		$response = null;

		while ( $attempt < self::MAX_RETRIES ) {
			$response = wp_remote_post( self::API_ENDPOINT, $args );

			// Check for errors
			if ( is_wp_error( $response ) ) {
				$this->log_error( 'API request failed: ' . $response->get_error_message() );
				$attempt++;
				if ( $attempt < self::MAX_RETRIES ) {
					sleep( self::INITIAL_RETRY_DELAY * pow( 2, $attempt - 1 ) );
					continue;
				}
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );

			// Handle rate limiting (429) with exponential backoff
			if ( 429 === $status_code ) {
				$attempt++;
				if ( $attempt < self::MAX_RETRIES ) {
					$delay = self::INITIAL_RETRY_DELAY * pow( 2, $attempt - 1 );
					$this->log_error( "Rate limited. Retrying in {$delay} seconds (attempt {$attempt})" );
					sleep( $delay );
					continue;
				}
				return new WP_Error( 'rate_limited', __( 'API rate limit exceeded.', 'claude-post-processor' ) );
			}

			// Handle other non-200 responses
			if ( $status_code < 200 || $status_code >= 300 ) {
				$body_content = wp_remote_retrieve_body( $response );
				$this->log_error( "API request failed with status {$status_code}: {$body_content}" );
				return new WP_Error(
					'api_error',
					sprintf(
						/* translators: %d: HTTP status code */
						__( 'API request failed with status %d', 'claude-post-processor' ),
						$status_code
					)
				);
			}

			// Success - break out of retry loop
			break;
		}

		// Parse the response
		$body_content = wp_remote_retrieve_body( $response );
		$data = json_decode( $body_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log_error( 'Failed to parse API response: ' . json_last_error_msg() );
			return new WP_Error( 'parse_error', __( 'Failed to parse API response.', 'claude-post-processor' ) );
		}

		// Log successful API call
		$this->log_api_call( $prompt, $data );

		return $data;
	}

	/**
	 * Extract text from Claude API response.
	 *
	 * @param array $response The API response.
	 * @return string|false The extracted text or false on failure.
	 */
	public function extract_text( $response ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( ! isset( $response['content'] ) || ! is_array( $response['content'] ) ) {
			return false;
		}

		// Find the first text block
		foreach ( $response['content'] as $block ) {
			if ( isset( $block['type'] ) && 'text' === $block['type'] && isset( $block['text'] ) ) {
				return trim( $block['text'] );
			}
		}

		return false;
	}

	/**
	 * Log an API call.
	 *
	 * @param string $prompt The prompt sent.
	 * @param array  $response The response received.
	 */
	private function log_api_call( $prompt, $response ) {
		$log_dir = wp_upload_dir()['basedir'] . '/claude-processor-logs';
		
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
			// Add .htaccess to protect logs
			file_put_contents( $log_dir . '/.htaccess', 'Deny from all' );
		}

		$log_file = $log_dir . '/api-calls-' . gmdate( 'Y-m-d' ) . '.log';
		$log_entry = sprintf(
			"[%s] Prompt length: %d characters, Response: %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			strlen( $prompt ),
			wp_json_encode( $response )
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $log_entry, FILE_APPEND );
	}

	/**
	 * Log an error.
	 *
	 * @param string $message The error message.
	 */
	private function log_error( $message ) {
		$log_dir = wp_upload_dir()['basedir'] . '/claude-processor-logs';
		
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
			// Add .htaccess to protect logs
			file_put_contents( $log_dir . '/.htaccess', 'Deny from all' );
		}

		$log_file = $log_dir . '/errors-' . gmdate( 'Y-m-d' ) . '.log';
		$log_entry = sprintf(
			"[%s] ERROR: %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			$message
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $log_entry, FILE_APPEND );
	}

	/**
	 * Test the API connection.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function test_connection() {
		$response = $this->send_message( 'Hello, please respond with "OK"', 50 );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Get available models for Claude.
	 *
	 * @return array Array of model_id => model_name pairs.
	 */
	public function get_available_models() {
		return array(
			'claude-sonnet-4-20250514'   => 'Claude Sonnet 4 (Recommended)',
			'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
			'claude-3-opus-20240229'     => 'Claude 3 Opus',
		);
	}

	/**
	 * Get the selected model.
	 *
	 * @return string The selected model ID.
	 */
	public function get_model() {
		return get_option( 'claude_post_processor_model', 'claude-sonnet-4-20250514' );
	}

	/**
	 * Set the selected model.
	 *
	 * @param string $model The model ID to use.
	 * @return bool True on success, false on failure.
	 */
	public function set_model( $model ) {
		return update_option( 'claude_post_processor_model', $model );
	}
}
