<?php
/**
 * Admin Settings
 *
 * Handles the admin settings page and dashboard widget.
 *
 * @package Claude_Post_Processor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin_Settings class.
 */
class Admin_Settings {

	/**
	 * Post processor.
	 *
	 * @var Post_Processor
	 */
	private $processor;

	/**
	 * Constructor.
	 *
	 * @param Post_Processor $processor Post processor instance.
	 */
	public function __construct( $processor ) {
		$this->processor = $processor;

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'admin_post_claude_process_selected', array( $this, 'handle_manual_processing' ) );
	}

	/**
	 * Add settings page to admin menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Claude Post Processor', 'claude-post-processor' ),
			__( 'Claude Post Processor', 'claude-post-processor' ),
			'manage_options',
			'claude-post-processor',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// API Configuration
		register_setting(
			'claude_post_processor_api',
			'claude_post_processor_api_key',
			array(
				'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			)
		);
		register_setting( 'claude_post_processor_api', 'claude_post_processor_model' );

		// Processing Options
		register_setting( 'claude_post_processor_options', 'claude_post_processor_auto_process' );
		register_setting( 'claude_post_processor_options', 'claude_post_processor_email_notifications' );

		// API Configuration Section
		add_settings_section(
			'claude_api_section',
			__( 'API Configuration', 'claude-post-processor' ),
			array( $this, 'render_api_section' ),
			'claude_post_processor_api'
		);

		add_settings_field(
			'claude_api_key',
			__( 'Anthropic API Key', 'claude-post-processor' ),
			array( $this, 'render_api_key_field' ),
			'claude_post_processor_api',
			'claude_api_section'
		);

		add_settings_field(
			'claude_model',
			__( 'Claude Model', 'claude-post-processor' ),
			array( $this, 'render_model_field' ),
			'claude_post_processor_api',
			'claude_api_section'
		);

		// Processing Options Section
		add_settings_section(
			'claude_options_section',
			__( 'Processing Options', 'claude-post-processor' ),
			array( $this, 'render_options_section' ),
			'claude_post_processor_options'
		);

		add_settings_field(
			'claude_auto_process',
			__( 'Auto-process New Posts', 'claude-post-processor' ),
			array( $this, 'render_auto_process_field' ),
			'claude_post_processor_options',
			'claude_options_section'
		);

		add_settings_field(
			'claude_email_notifications',
			__( 'Email Notifications', 'claude-post-processor' ),
			array( $this, 'render_email_notifications_field' ),
			'claude_post_processor_options',
			'claude_options_section'
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'api';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=claude-post-processor&tab=api" class="nav-tab <?php echo 'api' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'API Configuration', 'claude-post-processor' ); ?>
				</a>
				<a href="?page=claude-post-processor&tab=options" class="nav-tab <?php echo 'options' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Processing Options', 'claude-post-processor' ); ?>
				</a>
				<a href="?page=claude-post-processor&tab=manual" class="nav-tab <?php echo 'manual' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Manual Processing', 'claude-post-processor' ); ?>
				</a>
				<a href="?page=claude-post-processor&tab=logs" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Logs', 'claude-post-processor' ); ?>
				</a>
			</h2>

			<div class="tab-content">
				<?php
				switch ( $active_tab ) {
					case 'api':
						$this->render_api_tab();
						break;
					case 'options':
						$this->render_options_tab();
						break;
					case 'manual':
						$this->render_manual_tab();
						break;
					case 'logs':
						$this->render_logs_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render API configuration tab.
	 */
	private function render_api_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'claude_post_processor_api' );
			do_settings_sections( 'claude_post_processor_api' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Render options tab.
	 */
	private function render_options_tab() {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'claude_post_processor_options' );
			do_settings_sections( 'claude_post_processor_options' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Render manual processing tab.
	 */
	private function render_manual_tab() {
		$unprocessed_posts = $this->processor->get_unprocessed_posts();
		?>
		<h2><?php esc_html_e( 'Unprocessed Posts', 'claude-post-processor' ); ?></h2>
		
		<?php if ( empty( $unprocessed_posts ) ) : ?>
			<p><?php esc_html_e( 'No unprocessed posts found.', 'claude-post-processor' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="claude_process_selected">
				<?php wp_nonce_field( 'claude_process_selected', 'claude_nonce' ); ?>
				
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="check-column"><input type="checkbox" id="select-all-posts"></td>
							<th><?php esc_html_e( 'Title', 'claude-post-processor' ); ?></th>
							<th><?php esc_html_e( 'Date', 'claude-post-processor' ); ?></th>
							<th><?php esc_html_e( 'Status', 'claude-post-processor' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $unprocessed_posts as $post ) : ?>
							<tr>
								<th class="check-column">
									<input type="checkbox" name="post_ids[]" value="<?php echo esc_attr( $post->ID ); ?>">
								</th>
								<td>
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( get_the_date( '', $post->ID ) ); ?></td>
								<td><?php echo esc_html( ucfirst( $post->post_status ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" name="process_type" value="selected" class="button button-primary">
						<?php esc_html_e( 'Process Selected', 'claude-post-processor' ); ?>
					</button>
					<button type="submit" name="process_type" value="all" class="button">
						<?php esc_html_e( 'Process All Unprocessed', 'claude-post-processor' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>

		<div id="processing-status" style="display:none; margin-top: 20px; padding: 10px; background: #fff; border-left: 4px solid #0073aa;">
			<h3><?php esc_html_e( 'Processing Status', 'claude-post-processor' ); ?></h3>
			<div id="processing-log"></div>
		</div>
		<?php
	}

	/**
	 * Render logs tab.
	 */
	private function render_logs_tab() {
		$log_dir = wp_upload_dir()['basedir'] . '/claude-processor-logs';
		?>
		<h2><?php esc_html_e( 'Processing Logs', 'claude-post-processor' ); ?></h2>

		<?php if ( ! file_exists( $log_dir ) ) : ?>
			<p><?php esc_html_e( 'No logs found.', 'claude-post-processor' ); ?></p>
		<?php else : ?>
			<?php
			$log_files = glob( $log_dir . '/*.log' );
			if ( empty( $log_files ) ) :
				?>
				<p><?php esc_html_e( 'No log files found.', 'claude-post-processor' ); ?></p>
			<?php else : ?>
				<ul>
					<?php foreach ( array_reverse( $log_files ) as $log_file ) : ?>
						<li>
							<strong><?php echo esc_html( basename( $log_file ) ); ?></strong>
							<br>
							<small><?php echo esc_html( human_time_diff( filemtime( $log_file ) ) ); ?> <?php esc_html_e( 'ago', 'claude-post-processor' ); ?></small>
							<br>
							<a href="<?php echo esc_url( wp_upload_dir()['baseurl'] . '/claude-processor-logs/' . basename( $log_file ) ); ?>" target="_blank">
								<?php esc_html_e( 'View Log', 'claude-post-processor' ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render API section description.
	 */
	public function render_api_section() {
		echo '<p>' . esc_html__( 'Configure your Anthropic Claude API credentials.', 'claude-post-processor' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field() {
		$api = new Claude_API();
		$has_key = (bool) $api->get_api_key();
		?>
		<input type="password" 
			   name="claude_post_processor_api_key" 
			   id="claude_post_processor_api_key" 
			   class="regular-text"
			   value="<?php echo $has_key ? '****************************************' : ''; ?>"
			   placeholder="<?php esc_attr_e( 'Enter your Anthropic API key', 'claude-post-processor' ); ?>">
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: URL to Anthropic Console */
					__( 'Get your API key from the <a href="%s" target="_blank">Anthropic Console</a>.', 'claude-post-processor' ),
					'https://console.anthropic.com/'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render model field.
	 */
	public function render_model_field() {
		$model = get_option( 'claude_post_processor_model', 'claude-sonnet-4-20250514' );
		$models = array(
			'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Recommended)',
			'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
			'claude-3-opus-20240229' => 'Claude 3 Opus',
		);
		?>
		<select name="claude_post_processor_model" id="claude_post_processor_model">
			<?php foreach ( $models as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the Claude model to use for processing.', 'claude-post-processor' ); ?>
		</p>
		<?php
	}

	/**
	 * Render options section description.
	 */
	public function render_options_section() {
		echo '<p>' . esc_html__( 'Configure how posts are processed.', 'claude-post-processor' ) . '</p>';
	}

	/**
	 * Render auto-process field.
	 */
	public function render_auto_process_field() {
		$auto_process = get_option( 'claude_post_processor_auto_process', false );
		?>
		<label>
			<input type="checkbox" 
				   name="claude_post_processor_auto_process" 
				   id="claude_post_processor_auto_process" 
				   value="1" 
				   <?php checked( $auto_process, true ); ?>>
			<?php esc_html_e( 'Automatically process new posts when they are created', 'claude-post-processor' ); ?>
		</label>
		<?php
	}

	/**
	 * Render email notifications field.
	 */
	public function render_email_notifications_field() {
		$email_notifications = get_option( 'claude_post_processor_email_notifications', false );
		?>
		<label>
			<input type="checkbox" 
				   name="claude_post_processor_email_notifications" 
				   id="claude_post_processor_email_notifications" 
				   value="1" 
				   <?php checked( $email_notifications, true ); ?>>
			<?php esc_html_e( 'Send email notifications when posts are processed', 'claude-post-processor' ); ?>
		</label>
		<?php
	}

	/**
	 * Handle manual processing form submission.
	 */
	public function handle_manual_processing() {
		// Verify nonce
		if ( ! isset( $_POST['claude_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['claude_nonce'] ) ), 'claude_process_selected' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'claude-post-processor' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to process posts.', 'claude-post-processor' ) );
		}

		$process_type = isset( $_POST['process_type'] ) ? sanitize_text_field( wp_unslash( $_POST['process_type'] ) ) : 'selected';
		$post_ids = array();

		if ( 'all' === $process_type ) {
			$unprocessed = $this->processor->get_unprocessed_posts();
			$post_ids = wp_list_pluck( $unprocessed, 'ID' );
		} elseif ( isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) {
			$post_ids = array_map( 'absint', wp_unslash( $_POST['post_ids'] ) );
		}

		if ( empty( $post_ids ) ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=claude-post-processor&tab=manual&error=no_posts' ) );
			exit;
		}

		$processed = 0;
		foreach ( $post_ids as $post_id ) {
			$result = $this->processor->process_post( $post_id );
			if ( ! is_wp_error( $result ) ) {
				$processed++;
			}
			// Add delay between posts
			sleep( 2 );
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=claude-post-processor&tab=manual&processed=' . $processed ) );
		exit;
	}

	/**
	 * Show admin notices.
	 */
	public function show_admin_notices() {
		if ( isset( $_GET['claude_processed'] ) ) {
			$count = absint( $_GET['claude_processed'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: Number of posts processed */
							_n( '%d post processed successfully.', '%d posts processed successfully.', $count, 'claude-post-processor' ),
							$count
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['processed'] ) ) {
			$count = absint( $_GET['processed'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: Number of posts processed */
							_n( '%d post processed successfully.', '%d posts processed successfully.', $count, 'claude-post-processor' ),
							$count
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( isset( $_GET['error'] ) && 'no_posts' === $_GET['error'] ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'No posts selected for processing.', 'claude-post-processor' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Add dashboard widget.
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'claude_post_processor_widget',
			__( 'Claude Post Processor', 'claude-post-processor' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 */
	public function render_dashboard_widget() {
		$unprocessed = $this->processor->get_unprocessed_posts();
		$unprocessed_count = count( $unprocessed );
		?>
		<div class="claude-dashboard-widget">
			<p>
				<strong><?php esc_html_e( 'Unprocessed Posts:', 'claude-post-processor' ); ?></strong>
				<?php echo esc_html( $unprocessed_count ); ?>
			</p>
			
			<?php if ( $unprocessed_count > 0 ) : ?>
				<p>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=claude-post-processor&tab=manual' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Process Now', 'claude-post-processor' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<hr>

			<p>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=claude-post-processor' ) ); ?>">
					<?php esc_html_e( 'Settings', 'claude-post-processor' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Sanitize and encrypt API key before saving.
	 *
	 * @param string $value The API key value.
	 * @return string The sanitized and encrypted value.
	 */
	public function sanitize_api_key( $value ) {
		// If the value is the placeholder, don't update it
		if ( '****************************************' === $value ) {
			return get_option( 'claude_post_processor_api_key', '' );
		}

		// Sanitize the input
		$value = sanitize_text_field( $value );

		// If empty, return empty
		if ( empty( $value ) ) {
			return '';
		}

		// Encrypt the API key and return it
		// Let WordPress's register_setting() handle the update_option() call
		return $this->encrypt_api_key_helper( $value );
	}

	/**
	 * Helper method to encrypt API key.
	 *
	 * @param string $api_key The API key to encrypt.
	 * @return string The encrypted API key.
	 */
	private function encrypt_api_key_helper( $api_key ) {
		// Use WordPress salt as encryption key
		$key = wp_salt( 'auth' );
		
		// Use openssl for encryption if available
		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv = openssl_random_pseudo_bytes( 16 );
			$encrypted = openssl_encrypt( $api_key, 'AES-256-CBC', $key, 0, $iv );
			return base64_encode( $encrypted . '::' . $iv );
		}
		
		// Fallback to base64 encoding (not secure, but better than plaintext)
		return base64_encode( $api_key );
	}
}
