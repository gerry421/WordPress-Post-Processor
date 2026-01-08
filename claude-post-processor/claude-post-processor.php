<?php
/**
 * Plugin Name: Claude Post Processor
 * Plugin URI: https://github.com/gerry421/WordPress-Post-Processor
 * Description: Automatically or manually processes posts using the Anthropic Claude API to enhance posts with AI-generated improvements, proper media handling, and historical enrichment.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/gerry421
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: claude-post-processor
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'CLAUDE_POST_PROCESSOR_VERSION', '1.0.0' );
define( 'CLAUDE_POST_PROCESSOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLAUDE_POST_PROCESSOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Composer autoload.
 */
if ( file_exists( CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Require the core classes.
 */
require_once CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'includes/class-claude-api.php';
require_once CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'includes/class-post-processor.php';
require_once CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'includes/class-media-handler.php';
require_once CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'includes/class-taxonomy-manager.php';
require_once CLAUDE_POST_PROCESSOR_PLUGIN_DIR . 'includes/class-admin-settings.php';

/**
 * Main Plugin Class - Singleton Pattern
 */
class Claude_Post_Processor {

	/**
	 * The single instance of the class.
	 *
	 * @var Claude_Post_Processor
	 */
	private static $instance = null;

	/**
	 * Claude API handler.
	 *
	 * @var Claude_API
	 */
	public $api;

	/**
	 * Post processor.
	 *
	 * @var Post_Processor
	 */
	public $processor;

	/**
	 * Media handler.
	 *
	 * @var Media_Handler
	 */
	public $media;

	/**
	 * Taxonomy manager.
	 *
	 * @var Taxonomy_Manager
	 */
	public $taxonomy;

	/**
	 * Admin settings.
	 *
	 * @var Admin_Settings
	 */
	public $admin;

	/**
	 * Main Claude_Post_Processor Instance.
	 *
	 * Ensures only one instance of Claude_Post_Processor is loaded or can be loaded.
	 *
	 * @return Claude_Post_Processor - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		$this->api       = new Claude_API();
		$this->media     = new Media_Handler();
		$this->taxonomy  = new Taxonomy_Manager();
		$this->processor = new Post_Processor( $this->api, $this->media, $this->taxonomy );
		$this->admin     = new Admin_Settings( $this->processor );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'claude-post-processor',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Initialize plugin.
	 */
	public function init() {
		// Nothing to do here yet
	}

	/**
	 * Enqueue admin CSS and JavaScript.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page and post edit screens
		if ( 'settings_page_claude-post-processor' !== $hook && 'post.php' !== $hook && 'edit.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'claude-post-processor-admin',
			CLAUDE_POST_PROCESSOR_PLUGIN_URL . 'admin/css/admin-styles.css',
			array(),
			CLAUDE_POST_PROCESSOR_VERSION
		);

		wp_enqueue_script(
			'claude-post-processor-admin',
			CLAUDE_POST_PROCESSOR_PLUGIN_URL . 'admin/js/admin-scripts.js',
			array( 'jquery' ),
			CLAUDE_POST_PROCESSOR_VERSION,
			true
		);

		wp_localize_script(
			'claude-post-processor-admin',
			'claudePostProcessor',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'claude_post_processor_nonce' ),
			)
		);
	}
}

/**
 * Returns the main instance of Claude_Post_Processor.
 *
 * @return Claude_Post_Processor
 */
function claude_post_processor() {
	return Claude_Post_Processor::instance();
}

// Initialize the plugin
claude_post_processor();
