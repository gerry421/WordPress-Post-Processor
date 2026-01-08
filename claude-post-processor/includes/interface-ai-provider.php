<?php
/**
 * AI Provider Interface
 *
 * Interface for AI service providers (Claude, OpenAI, Google AI, etc.).
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AI_Provider interface.
 */
interface AI_Provider {

	/**
	 * Get the provider name.
	 *
	 * @return string Provider name.
	 */
	public function get_provider_name();

	/**
	 * Get the API key.
	 *
	 * @return string|false The API key or false if not set.
	 */
	public function get_api_key();

	/**
	 * Set the API key.
	 *
	 * @param string $api_key The API key to store.
	 * @return bool True on success, false on failure.
	 */
	public function set_api_key( $api_key );

	/**
	 * Send a message to the AI service.
	 *
	 * @param string $prompt The prompt to send.
	 * @param int    $max_tokens Maximum tokens in response.
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	public function send_message( $prompt, $max_tokens = 4096 );

	/**
	 * Extract text from AI service response.
	 *
	 * @param array $response The API response.
	 * @return string|false The extracted text or false on failure.
	 */
	public function extract_text( $response );

	/**
	 * Test the API connection.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function test_connection();

	/**
	 * Get available models for this provider.
	 *
	 * @return array Array of model_id => model_name pairs.
	 */
	public function get_available_models();

	/**
	 * Get the selected model.
	 *
	 * @return string The selected model ID.
	 */
	public function get_model();

	/**
	 * Set the selected model.
	 *
	 * @param string $model The model ID to use.
	 * @return bool True on success, false on failure.
	 */
	public function set_model( $model );
}
