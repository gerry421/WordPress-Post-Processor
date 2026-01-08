<?php
/**
 * OpenAI Provider
 *
 * Handles all communication with the OpenAI API.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * OpenAI_Provider class.
 */
class OpenAI_Provider implements AI_Provider {

	/**
	 * API endpoint.
	 */
	const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

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
		return 'OpenAI';
	}

	/**
	 * Get the API key.
	 *
	 * @return string|false The decrypted API key or false if not set.
	 */
	public function get_api_key() {
		$encrypted_key = get_option( 'claude_post_processor_openai_api_key', '' );
		
		if ( empty( $encrypted_key ) ) {
			return false;
		}

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
			return delete_option( 'claude_post_processor_openai_api_key' );
		}

		$encrypted_key = $this->encrypt_api_key( $api_key );
		return update_option( 'claude_post_processor_openai_api_key', $encrypted_key );
	}

	/**
	 * Encrypt the API key.
	 *
	 * @param string $api_key The API key to encrypt.
	 * @return string The encrypted API key.
	 */
	private function encrypt_api_key( $api_key ) {
		$key = wp_salt( 'auth' );
		
		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv = random_bytes( 16 );
			$encrypted = openssl_encrypt( $api_key, 'AES-256-CBC', $key, 0, $iv );
			return base64_encode( $encrypted . '::' . $iv );
		}
		
		return base64_encode( $api_key );
	}

	/**
	 * Decrypt the API key.
	 *
	 * @param string $encrypted_key The encrypted API key.
	 * @return string The decrypted API key.
	 */
	private function decrypt_api_key( $encrypted_key ) {
		$key = wp_salt( 'auth' );
		
		if ( function_exists( 'openssl_decrypt' ) && strpos( base64_decode( $encrypted_key ), '::' ) !== false ) {
			$decoded = base64_decode( $encrypted_key );
			list( $encrypted_data, $iv ) = explode( '::', $decoded, 2 );
			return openssl_decrypt( $encrypted_data, 'AES-256-CBC', $key, 0, $iv );
		}
		
		return base64_decode( $encrypted_key );
	}

	/**
	 * Send a message to OpenAI API.
	 *
	 * @param string $prompt The prompt to send.
	 * @param int    $max_tokens Maximum tokens in response (default 4096).
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	public function send_message( $prompt, $max_tokens = 4096 ) {
		$api_key = $this->get_api_key();
		
		if ( ! $api_key ) {
			$this->log_error( 'OpenAI API key not configured' );
			return new WP_Error( 'no_api_key', __( 'OpenAI API key is not configured.', 'claude-post-processor' ) );
		}

		$model = $this->get_model();

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
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		);

		$attempt = 0;
		$response = null;

		while ( $attempt < self::MAX_RETRIES ) {
			$response = wp_remote_post( self::API_ENDPOINT, $args );

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

			break;
		}

		$body_content = wp_remote_retrieve_body( $response );
		$data = json_decode( $body_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log_error( 'Failed to parse API response: ' . json_last_error_msg() );
			return new WP_Error( 'parse_error', __( 'Failed to parse API response.', 'claude-post-processor' ) );
		}

		$this->log_api_call( $prompt, $data );

		return $data;
	}

	/**
	 * Extract text from OpenAI API response.
	 *
	 * @param array $response The API response.
	 * @return string|false The extracted text or false on failure.
	 */
	public function extract_text( $response ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( ! isset( $response['choices'] ) || ! is_array( $response['choices'] ) || empty( $response['choices'] ) ) {
			return false;
		}

		$first_choice = $response['choices'][0];
		if ( isset( $first_choice['message']['content'] ) ) {
			return trim( $first_choice['message']['content'] );
		}

		return false;
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
	 * Get available models for OpenAI.
	 *
	 * @return array Array of model_id => model_name pairs.
	 */
	public function get_available_models() {
		return array(
			'gpt-4o'           => 'GPT-4o (Recommended)',
			'gpt-4o-mini'      => 'GPT-4o Mini',
			'gpt-4-turbo'      => 'GPT-4 Turbo',
			'gpt-4'            => 'GPT-4',
			'gpt-3.5-turbo'    => 'GPT-3.5 Turbo',
		);
	}

	/**
	 * Get the selected model.
	 *
	 * @return string The selected model ID.
	 */
	public function get_model() {
		return get_option( 'claude_post_processor_openai_model', 'gpt-4o' );
	}

	/**
	 * Set the selected model.
	 *
	 * @param string $model The model ID to use.
	 * @return bool True on success, false on failure.
	 */
	public function set_model( $model ) {
		return update_option( 'claude_post_processor_openai_model', $model );
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
			file_put_contents( $log_dir . '/.htaccess', 'Deny from all' );
		}

		$log_file = $log_dir . '/api-calls-' . gmdate( 'Y-m-d' ) . '.log';
		$log_entry = sprintf(
			"[%s] [OpenAI] Prompt length: %d characters, Response: %s\n",
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
			file_put_contents( $log_dir . '/.htaccess', 'Deny from all' );
		}

		$log_file = $log_dir . '/errors-' . gmdate( 'Y-m-d' ) . '.log';
		$log_entry = sprintf(
			"[%s] [OpenAI] ERROR: %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			$message
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $log_file, $log_entry, FILE_APPEND );
	}
}
