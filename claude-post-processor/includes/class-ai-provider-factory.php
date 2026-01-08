<?php
/**
 * AI Provider Factory
 *
 * Factory class for creating AI provider instances.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AI_Provider_Factory class.
 */
class AI_Provider_Factory {

	/**
	 * Get available AI providers.
	 *
	 * @return array Array of provider_id => provider_name pairs.
	 */
	public static function get_available_providers() {
		return array(
			'claude'    => __( 'Anthropic Claude', 'claude-post-processor' ),
			'openai'    => __( 'OpenAI', 'claude-post-processor' ),
			'google_ai' => __( 'Google AI (Gemini)', 'claude-post-processor' ),
		);
	}

	/**
	 * Get the selected provider.
	 *
	 * @return string The selected provider ID.
	 */
	public static function get_selected_provider() {
		return get_option( 'claude_post_processor_ai_provider', 'claude' );
	}

	/**
	 * Set the selected provider.
	 *
	 * @param string $provider The provider ID to use.
	 * @return bool True on success, false on failure.
	 */
	public static function set_selected_provider( $provider ) {
		$available_providers = array_keys( self::get_available_providers() );
		if ( ! in_array( $provider, $available_providers, true ) ) {
			return false;
		}
		return update_option( 'claude_post_processor_ai_provider', $provider );
	}

	/**
	 * Create an AI provider instance.
	 *
	 * @param string|null $provider_id The provider ID, or null to use the selected provider.
	 * @return AI_Provider|WP_Error The provider instance or WP_Error on failure.
	 */
	public static function create_provider( $provider_id = null ) {
		if ( null === $provider_id ) {
			$provider_id = self::get_selected_provider();
		}

		switch ( $provider_id ) {
			case 'claude':
				return new Claude_API();

			case 'openai':
				return new OpenAI_Provider();

			case 'google_ai':
				return new Google_AI_Provider();

			default:
				return new WP_Error(
					'invalid_provider',
					sprintf(
						/* translators: %s: Provider ID */
						__( 'Invalid AI provider: %s', 'claude-post-processor' ),
						$provider_id
					)
				);
		}
	}

	/**
	 * Get the current provider instance.
	 *
	 * @return AI_Provider|WP_Error The current provider instance or WP_Error on failure.
	 */
	public static function get_current_provider() {
		return self::create_provider();
	}
}
