<?php
/**
 * Main Smart One Click Setup plugin class/file.
 *
 * @package socs
 */

namespace SOCS;

use WP_Error;

/**
 * Smart One Click Setup class, so we don't have to worry about namespaces.
 */
class SmartOneClickSetup {
	/**
	 * The instance *Singleton* of this class
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * The instance of the SOCS\Importer class.
	 *
	 * @var object
	 */
	public $importer;

	/**
	 * The resulting page's hook_suffix, or false if the user does not have the capability required.
	 *
	 * @var boolean or string
	 */
	private $plugin_page;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files;

	/**
	 * The path of the log file.
	 *
	 * @var string
	 */
	public $log_file_path;

	/**
	 * The index of the `import_files` array (which import files was selected).
	 *
	 * @var int
	 */
	private $selected_index;

	/**
	 * The paths of the actual import files to be used in the import.
	 *
	 * @var array
	 */
	private $selected_import_files;

	/**
	 * Holds any error messages, that should be printed out at the end of the import.
	 *
	 * @var string
	 */
	public $frontend_error_messages = array();

	/**
	 * Was the before content import already triggered?
	 *
	 * @var boolean
	 */
	private $before_import_executed = false;

	/**
	 * Make plugin page options available to other methods.
	 *
	 * @var array
	 */
	private $plugin_page_setup = array();

	/**
	 * Imported terms.
	 *
	 * @var array
	 */
	private $imported_terms = array();

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return SmartOneClickSetup the *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * Class construct function, to initiate the plugin.
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		// Actions.
		add_action( 'admin_menu', array( $this, 'create_plugin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_socs_upload_manual_import_files', array( $this, 'upload_manual_import_files_callback' ) );
		add_action( 'wp_ajax_socs_import_demo_data', array( $this, 'import_demo_data_ajax_callback' ) );
		add_action( 'wp_ajax_socs_import_customizer_data', array( $this, 'import_customizer_data_ajax_callback' ) );
		add_action( 'wp_ajax_socs_after_import_data', array( $this, 'after_all_import_data_ajax_callback' ) );
		add_action( 'wp_ajax_socs_export_data', array( $this, 'export_data_ajax_callback' ) );
		add_action( 'wp_ajax_socs_import_zip_file', array( $this, 'import_zip_file_ajax_callback' ) );
		add_action( 'wp_ajax_socs_import_predefined_zip', array( $this, 'import_predefined_zip_ajax_callback' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_plugin_with_filter_data' ) );
		add_action( 'user_admin_notices', array( $this, 'start_notice_output_capturing' ), 0 );
		add_action( 'admin_notices', array( $this, 'start_notice_output_capturing' ), 0 );
		add_action( 'all_admin_notices', array( $this, 'finish_notice_output_capturing' ), PHP_INT_MAX );
		add_action( 'admin_init', array( $this, 'redirect_from_old_default_admin_page' ) );
		add_action( 'set_object_terms', array( $this, 'add_imported_terms' ), 10, 6 );
		add_filter( 'wxr_importer.pre_process.post', [ $this, 'skip_failed_attachment_import' ] );
		add_action( 'wxr_importer.process_failed.post', [ $this, 'handle_failed_attachment_import' ], 10, 5 );
		add_action( 'wp_import_insert_post', [ $this, 'save_wp_navigation_import_mapping' ], 10, 4 );
		add_action( 'socs/after_import', [ $this, 'fix_imported_wp_navigation' ] );

		// Merge ImportHelper imports with filter-based imports.
		add_filter( 'socs/predefined_import_files', array( $this, 'merge_helper_imports' ), 999 );
	}

	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Empty unserialize method to prevent unserializing of the *Singleton* instance.
	 *
	 * @return void
	 */
	public function __wakeup() {}

	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_plugin_page() {
		$this->plugin_page_setup = Helpers::get_plugin_page_setup_data();

		// Check if admin menu should be disabled.
		$disable_menu = Helpers::apply_filters( 'socs/disable_admin_menu', false );

		// Determine parent slug - use empty string to hide from menu if disabled.
		$parent_slug = $disable_menu ? '' : $this->plugin_page_setup['parent_slug'];

		// Main plugin page (Smart Import/Export).
		// Always register the page so it's accessible via direct URL, even if menu is hidden.
		$this->plugin_page = add_submenu_page(
			$parent_slug,
			$this->plugin_page_setup['page_title'],
			$this->plugin_page_setup['menu_title'],
			$this->plugin_page_setup['capability'],
			$this->plugin_page_setup['menu_slug'],
			Helpers::apply_filters( 'socs/plugin_page_display_callback_function', array( $this, 'display_plugin_page' ) )
		);

		// Smart Export page (Export functionality).
		// Always register the page so it's accessible via direct URL, even if menu is hidden.
		add_submenu_page(
			$parent_slug,
			esc_html__( 'Smart Export', 'smart-one-click-setup' ),
			esc_html__( 'Smart Export', 'smart-one-click-setup' ),
			$this->plugin_page_setup['capability'],
			'socs-smart-export',
			Helpers::apply_filters( 'socs/export_page_display_callback_function', array( $this, 'display_export_page' ) )
		);

		

		// Register the old default settings page, so we can redirect to the new one and not break any existing links.
		add_submenu_page(
			'',
			$this->plugin_page_setup['page_title'],
			$this->plugin_page_setup['menu_title'],
			$this->plugin_page_setup['capability'],
			'pt-smart-one-click-setup'
		);

		// Only register importer if menu is not disabled.
		if ( ! $disable_menu ) {
			register_importer( $this->plugin_page_setup['menu_slug'], $this->plugin_page_setup['page_title'], $this->plugin_page_setup['menu_title'], Helpers::apply_filters( 'socs/plugin_page_display_callback_function', array( $this, 'display_plugin_page' ) ) );
		}
	}

	/**
	 * Plugin page display.
	 * Output (HTML) is in another file.
	 */
	public function display_plugin_page() {

		

		require_once SOCS_PATH . 'views/smart-import.php';
	}

	/**
	 * Export page display.
	 * Output (HTML) is in another file.
	 */
	public function display_export_page() {

		// Future: Add step-based routing here if needed
		// if ( isset( $_GET['step'] ) && 'export' === $_GET['step'] ) {
		// 	require_once SOCS_PATH . 'views/export.php';
		// 	return;
		// }

		require_once SOCS_PATH . 'views/export.php';
	}

	

	/**
	 * Enqueue admin scripts (JS and CSS)
	 *
	 * @param string $hook holds info on which admin page you are currently loading.
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Enqueue the scripts on plugin pages.
		$plugin_pages = array(
			$this->plugin_page,
			'appearance_page_socs-smart-export',
			'appearance_page_socs-smart-import',
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $hook, $plugin_pages, true ) || ( 'admin.php' === $hook && isset( $_GET['import'] ) && $this->plugin_page_setup['menu_slug'] === sanitize_text_field( wp_unslash( $_GET['import'] ) ) ) ) {
			$this->enqueue_template_scripts();
		}
	}

	/**
	 * Enqueue scripts and styles for template usage.
	 * This method can be called when displaying the Smart Import box in themes.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $load_css Whether to load plugin CSS. Default true.
	 */
	public function enqueue_template_scripts( $load_css = true ) {
		wp_enqueue_script( 'socs-main-js', SOCS_URL . 'assets/js/main.js' , array( 'jquery' ), SOCS_VERSION, false );

		// Get theme data.
		$theme = wp_get_theme();

		wp_localize_script( 'socs-main-js', 'socs',
			array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'       => wp_create_nonce( 'socs-ajax-verification' ),
				'import_files'     => $this->import_files,
				'wp_customize_on'  => Helpers::apply_filters( 'socs/enable_wp_customize_save_hooks', false ),
				'theme_screenshot' => $theme->get_screenshot(),
				'missing_plugins'  => array(),
				'plugin_url'       => SOCS_URL,
				'import_url'       => $this->get_plugin_settings_url( [ 'step' => 'import' ] ),
				'texts'            => array(
					'missing_preview_image'    => esc_html__( 'No preview image defined for this import.', 'smart-one-click-setup' ),
					'dialog_title'             => esc_html__( 'Are you sure?', 'smart-one-click-setup' ),
					'dialog_no'                => esc_html__( 'Cancel', 'smart-one-click-setup' ),
					'dialog_yes'               => esc_html__( 'Yes, import!', 'smart-one-click-setup' ),
					'selected_import_title'    => esc_html__( 'Selected demo import:', 'smart-one-click-setup' ),
					'importing'                => esc_html__( 'Importing...', 'smart-one-click-setup' ),
					'successful_import'        => esc_html__( 'Successfully Imported!', 'smart-one-click-setup' ),
					'import_failed'            => esc_html__( 'Import Failed', 'smart-one-click-setup' ),
					'import_failed_subtitle'   => esc_html__( 'Whoops, there was a problem importing your content.', 'smart-one-click-setup' ),
					'content_filetype_warn'    => esc_html__( 'Invalid file type detected! Please select an XML file for the Content Import.', 'smart-one-click-setup' ),
					'widgets_filetype_warn'    => esc_html__( 'Invalid file type detected! Please select a JSON or WIE file for the Widgets Import.', 'smart-one-click-setup' ),
					'customizer_filetype_warn' => esc_html__( 'Invalid file type detected! Please select a DAT file for the Customizer Import.', 'smart-one-click-setup' ),
					'redux_filetype_warn'      => esc_html__( 'Invalid file type detected! Please select a JSON file for the Redux Import.', 'smart-one-click-setup' ),
					'selected'                  => esc_html__( 'Selected', 'smart-one-click-setup' ),
					'select'                    => esc_html__( 'Select', 'smart-one-click-setup' ),
				),
			)
		);

		// Only load CSS if requested (allows themes to use their own styles).
		if ( $load_css ) {
			wp_enqueue_style( 'socs-main-css', SOCS_URL . 'assets/css/main.css', array() , SOCS_VERSION );
		}
	}


	/**
	 * AJAX callback method for uploading the manual import files.
	 */
	public function upload_manual_import_files_callback() {
		Helpers::verify_ajax_call();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES ) ) {
			wp_send_json_error( esc_html__( 'Manual import files are missing! Please select the import files and try again.', 'smart-one-click-setup' ) );
		}

		// Create a date and time string to use for demo and log file names.
		Helpers::set_demo_import_start_time();

		// Define log file path.
		$this->log_file_path = Helpers::get_log_path();

		$this->selected_index = 0;

		// Get paths for the uploaded files.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->selected_import_files = Helpers::process_uploaded_files( $_FILES, $this->log_file_path );

		// Set the name of the import files, because we used the uploaded files.
		$this->import_files[ $this->selected_index ]['import_file_name'] = esc_html__( 'Manually uploaded files', 'smart-one-click-setup' );

		// Save the initial import data as a transient, so the next import call (in new AJAX call) can use that data.
		Helpers::set_socs_import_data_transient( $this->get_current_importer_data() );

		wp_send_json_success();
	}


	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set, Squiz.PHP.DiscouragedFunctions.Discouraged
		@ini_set( 'memory_limit', Helpers::apply_filters( 'socs/import_memory_limit', '350M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			// Define log file path.
			$this->log_file_path = Helpers::get_log_path();

			// Get selected file index or set it to 0.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->selected_index = empty( $_POST['selected'] ) ? 0 : absint( $_POST['selected'] );

			/**
			 * 1). Prepare import files.
			 * Manually uploaded import files or predefined import files via filter: socs/import_files
			 */
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_FILES ) ) { // Using manual file uploads?
				// Get paths for the uploaded files.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$this->selected_import_files = Helpers::process_uploaded_files( $_FILES, $this->log_file_path );

				// Set the name of the import files, because we used the uploaded files.
				$this->import_files[ $this->selected_index ]['import_file_name'] = esc_html__( 'Manually uploaded files', 'smart-one-click-setup' );
			}
			elseif ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: socs/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'smart-one-click-setup' )
					);
				}

				// Add this message to log file.
				$log_added = Helpers::append_to_file(
					sprintf( /* translators: %s - the name of the selected import. */
						__( 'The import files for: %s were successfully downloaded!', 'smart-one-click-setup' ),
						$this->import_files[ $this->selected_index ]['import_file_name']
					) . Helpers::import_file_info( $this->selected_import_files ),
					$this->log_file_path,
					esc_html__( 'Downloaded files' , 'smart-one-click-setup' )
				);
			}
			else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'smart-one-click-setup' ) );
			}
		}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_socs_import_data_transient( $this->get_current_importer_data() );

		if ( ! $this->before_import_executed ) {
			$this->before_import_executed = true;

			/**
			 * 2). Execute the actions hooked to the 'socs/before_content_import_execution' action:
			 *
			 * Default actions:
			 * 1 - Before content import WP action (with priority 10).
			 */
			Helpers::do_action( 'socs/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		}

		/**
		 * 4). Execute the actions hooked to the 'socs/after_content_import_execution' action:
		 *
		 * Default actions:
		 * 1 - Before widgets import setup (with priority 10).
		 * 2 - Import widgets (with priority 20).
		 * 3 - Import Redux data (with priority 30).
		 * 4 - Import WPForms data (with priority 40).
		 */
		Helpers::do_action( 'socs/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );

		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_socs_import_data_transient( $this->get_current_importer_data() );

		// Request the customizer import AJAX call.
		if ( ! empty( $this->selected_import_files['customizer'] ) ) {
			wp_send_json( array( 'status' => 'customizerAJAX' ) );
		}

		// Request the after all import AJAX call.
		if ( false !== Helpers::has_action( 'socs/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Update terms count.
		$this->update_terms_count();

		// Send a JSON response with final report.
		$this->final_response();
	}

	/**
	 * AJAX callback for importing the customizer data.
	 * This request has the wp_customize set to 'on', so that the customizer hooks can be called
	 * (they can only be called with the $wp_customize instance). But if the $wp_customize is defined,
	 * then the widgets do not import correctly, that's why the customizer import has its own AJAX call.
	 */
	public function import_customizer_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			/**
			 * Execute the customizer import actions.
			 *
			 * Default actions:
			 * 1 - Customizer import (with priority 10).
			 */
			Helpers::do_action( 'socs/customizer_import_execution', $this->selected_import_files );
		}

		// Request the after all import AJAX call.
		if ( false !== Helpers::has_action( 'socs/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * AJAX callback for the after all import action.
	 */
	public function after_all_import_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			/**
			 * Execute the after all import actions.
			 *
			 * Default actions:
			 * 1 - after_import action (with priority 10).
			 */
			Helpers::do_action( 'socs/after_all_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );
		}

		// Update terms count.
		$this->update_terms_count();

		// Send a JSON response with final report.
		$this->final_response();
	}


	/**
	 * Send a JSON response with final report.
	 */
	private function final_response() {
		// Delete importer data transient for current import.
		delete_transient( 'socs_importer_data' );
		delete_transient( 'socs_importer_data_failed_attachment_imports' );
		delete_transient( 'socs_import_menu_mapping' );
		delete_transient( 'socs_import_posts_with_nav_block' );

		// Display final messages (success or warning messages).
		$response['title'] = esc_html__( 'Import Complete!', 'smart-one-click-setup' );
		$response['subtitle'] = '<p>' . esc_html__( 'Congrats, your demo was imported successfully. You can now begin editing your site.', 'smart-one-click-setup' ) . '</p>';
		$response['message'] = '<img class="socs-imported-content-imported socs-imported-content-imported--success" src="' . esc_url( SOCS_URL . 'assets/images/success.svg' ) . '" alt="' . esc_attr__( 'Successful Import', 'smart-one-click-setup' ) . '">';

		if ( ! empty( $this->frontend_error_messages ) ) {
			$response['subtitle'] = '<p>' . esc_html__( 'Your import completed, but some things may not have imported properly.', 'smart-one-click-setup' ) . '</p>';
			/* translators: %s: Link to the log file. */
			$socs_log_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( Helpers::get_log_url( $this->log_file_path ) ),
				esc_html__( 'View error log', 'smart-one-click-setup' )
			);
			$response['subtitle'] .= '<p>' . sprintf(
				/* translators: %s: Link to the log file. */
				esc_html__( '%s for more information.', 'smart-one-click-setup' ),
				wp_kses( $socs_log_link, array(
					'a' => [
						'href'   => [],
						'target' => [],
					],
				) )
			) . '</p>';

			$response['message'] = '<div class="notice notice-warning"><p>' . $this->frontend_error_messages_display() . '</p></div>';
		}

		wp_send_json( $response );
	}


	/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data() {
		if ( $data = get_transient( 'socs_importer_data' ) ) {
			$this->frontend_error_messages = empty( $data['frontend_error_messages'] ) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty( $data['log_file_path'] ) ? '' : $data['log_file_path'];
			$this->selected_index          = empty( $data['selected_index'] ) ? 0 : $data['selected_index'];
			$this->selected_import_files   = empty( $data['selected_import_files'] ) ? array() : $data['selected_import_files'];
			$this->import_files            = empty( $data['import_files'] ) ? array() : $data['import_files'];
			$this->before_import_executed  = empty( $data['before_import_executed'] ) ? false : $data['before_import_executed'];
			$this->imported_terms          = empty( $data['imported_terms'] ) ? [] : $data['imported_terms'];
			$this->importer->set_importer_data( $data );

			return true;
		}
		return false;
	}


	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data() {
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'log_file_path'           => $this->log_file_path,
			'selected_index'          => $this->selected_index,
			'selected_import_files'   => $this->selected_import_files,
			'import_files'            => $this->import_files,
			'before_import_executed'  => $this->before_import_executed,
			'imported_terms'          => $this->imported_terms,
		);
	}


	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}


	/**
	 * Setter function to append additional value to the private frontend_error_messages value.
	 *
	 * @param string $additional_value The additional value that will be appended to the existing frontend_error_messages.
	 */
	public function append_to_frontend_error_messages( $text ) {
		$lines = array();

		if ( ! empty( $text ) ) {
			$text = str_replace( '<br>', PHP_EOL, $text );
			$lines = explode( PHP_EOL, $text );
		}

		foreach ( $lines as $line ) {
			if ( ! empty( $line ) && ! in_array( $line , $this->frontend_error_messages ) ) {
				$this->frontend_error_messages[] = $line;
			}
		}
	}


	/**
	 * Display the frontend error messages.
	 *
	 * @return string Text with HTML markup.
	 */
	public function frontend_error_messages_display() {
		$output = '';

		if ( ! empty( $this->frontend_error_messages ) ) {
			foreach ( $this->frontend_error_messages as $line ) {
				$output .= esc_html( $line );
				$output .= '<br>';
			}
		}

		return $output;
	}


	/**
	 * Get data from filters, after the theme has loaded and instantiate the importer.
	 */
	public function setup_plugin_with_filter_data() {
		if ( ! ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) ) {
			return;
		}

		// Get info of import data files and filter it.
		$this->import_files = Helpers::validate_import_file_info( Helpers::apply_filters( 'socs/import_files', array() ) );

		/**
		 * Register all default actions (before content import, widget, customizer import and other actions)
		 * to the 'before_content_import_execution' and the 'socs/after_content_import_execution' action hook.
		 */
		$import_actions = new ImportActions();
		$import_actions->register_hooks();

		// Importer options array.
		$importer_options = Helpers::apply_filters( 'socs/importer_options', array(
			'fetch_attachments' => true,
		) );

		// Logger options for the logger used in the importer.
		$logger_options = Helpers::apply_filters( 'socs/logger_options', array(
			'logger_min_level' => 'warning',
		) );

		// Configure logger instance and set it to the importer.
		$logger            = new Logger();
		$logger->min_level = $logger_options['logger_min_level'];

		// Create importer instance with proper parameters.
		$this->importer = new Importer( $importer_options, $logger );
	}

	/**
	 * Getter for $plugin_page_setup.
	 *
	 * @return array
	 */
	public function get_plugin_page_setup() {
		return $this->plugin_page_setup;
	}

	/**
	 * Merge ImportHelper imports with filter-based imports.
	 *
	 * This method automatically includes any imports added via the ImportHelper class
	 * along with imports added via the filter hook. It also fetches demos from API
	 * if the socs/demo_api_base_url filter is set.
	 *
	 * @param array $predefined_imports Array of predefined imports from filter.
	 * @return array Merged array of imports.
	 */
	public function merge_helper_imports( $predefined_imports ) {
		// Fetch demos from API if base URL is set.
		$api_base_url = Helpers::apply_filters( 'socs/demo_api_base_url', '' );
		if ( ! empty( $api_base_url ) ) {
			$api_result = ImportHelper::fetch_from_api();
			// Note: Errors are silently ignored to prevent breaking the import page.
			// Developers can check for errors if needed.
		}

		// Get imports from ImportHelper (including API-fetched ones).
		$helper_imports = ImportHelper::get_imports();

		if ( ! empty( $helper_imports ) ) {
			$predefined_imports = array_merge( $predefined_imports, $helper_imports );
		}

		return $predefined_imports;
	}

	/**
	 * Output the begining of the container div for all notices, but only on SOCS pages.
	 */
	public function start_notice_output_capturing() {
		$screen = get_current_screen();

		// Check for main plugin page or export page.
		if ( false === strpos( $screen->base, $this->plugin_page_setup['menu_slug'] ) && false === strpos( $screen->base, 'socs-smart-export' ) ) {
			return;
		}

		echo '<div class="socs-notices-wrapper js-socs-notice-wrapper">';
	}

	/**
	 * Output the ending of the container div for all notices, but only on SOCS pages.
	 */
	public function finish_notice_output_capturing() {
		if ( is_network_admin() ) {
			return;
		}

		$screen = get_current_screen();

		// Check for main plugin page or export page.
		if ( false === strpos( $screen->base, $this->plugin_page_setup['menu_slug'] ) && false === strpos( $screen->base, 'socs-smart-export' ) ) {
			return;
		}

		echo '</div><!-- /.socs-notices-wrapper -->';
	}

	/**
	 * Get the URL of the plugin settings page.
	 *
	 * @return string
	 */
	public function get_plugin_settings_url( $query_parameters = [] ) {
		if ( empty( $this->plugin_page_setup ) ) {
			$this->plugin_page_setup = Helpers::get_plugin_page_setup_data();
		}

		$parameters = array_merge(
			array( 'page' => $this->plugin_page_setup['menu_slug'] ),
			$query_parameters
		);

		$url = menu_page_url( $this->plugin_page_setup['parent_slug'], false );

		if ( empty( $url ) ) {
			$url = self_admin_url( $this->plugin_page_setup['parent_slug'] );
		}

		return add_query_arg( $parameters, $url );
	}

	/**
	 * Redirect from the old default SOCS settings page URL to the new one.
	 */
	public function redirect_from_old_default_admin_page() {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $pagenow == 'themes.php' && isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) == 'pt-smart-one-click-setup' ) {
			wp_safe_redirect( $this->get_plugin_settings_url() );
			exit;
		}
	}

	/**
	 * Add imported terms.
	 *
	 * Mainly it's needed for saving all imported terms and trigger terms count updates.
	 * WP core term defer counting is not working, since import split to chunks and we are losing `$_deffered` array
	 * items between ajax calls.
	 */
	public function add_imported_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ){

		if ( ! isset( $this->imported_terms[ $taxonomy ] ) ) {
			$this->imported_terms[ $taxonomy ] = array();
		}

		$this->imported_terms[ $taxonomy ] = array_unique( array_merge( $this->imported_terms[ $taxonomy ], $tt_ids ) );
	}

	/**
	 * Returns an empty array if current attachment to be imported is in the failed imports list.
	 *
	 * This will skip the current attachment import.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data Post data to be imported.
	 *
	 * @return array
	 */
	public function skip_failed_attachment_import( $data ) {
		// Check if failed import.
		if (
			! empty( $data ) &&
			! empty( $data['post_type'] ) &&
			$data['post_type'] === 'attachment' &&
			! empty( $data['attachment_url'] )
		) {
			// Get the previously failed imports.
			$failed_media_imports = Helpers::get_failed_attachment_imports();

			if ( ! empty( $failed_media_imports ) && in_array( $data['attachment_url'], $failed_media_imports, true ) ) {
				// If the current attachment URL is in the failed imports, then skip it.
				return [];
			}
		}

		return $data;
	}

	/**
	 * Save the failed attachment import.
	 *
	 * @since 3.2.0
	 *
	 * @param WP_Error $post_id Error object.
	 * @param array    $data Raw data imported for the post.
	 * @param array    $meta Raw meta data, already processed.
	 * @param array    $comments Raw comment data, already processed.
	 * @param array    $terms Raw term data, already processed.
	 */
	public function handle_failed_attachment_import( $post_id, $data, $meta, $comments, $terms ) {

		if ( empty( $data ) || empty( $data['post_type'] ) || $data['post_type'] !== 'attachment' ) {
			return;
		}

		Helpers::set_failed_attachment_import( $data['attachment_url'] );
	}

	/**
	 * Save the information needed to process the navigation block.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $post_id     The new post ID.
	 * @param int   $original_id The original post ID.
	 * @param array $postdata    The post data used to insert the post.
	 * @param array $data        Post data from the WXR file.
	 */
	public function save_wp_navigation_import_mapping( $post_id, $original_id, $postdata, $data ) {

		if ( empty( $postdata['post_content'] ) ) {
			return;
		}

		if ( $postdata['post_type'] !== 'wp_navigation' ) {

			/*
			 * Save the post ID that has navigation block in transient.
			 */
			if ( strpos( $postdata['post_content'], '<!-- wp:navigation' ) !== false ) {
				// Keep track of POST ID that has navigation block.
				$socs_post_nav_block = get_transient( 'socs_import_posts_with_nav_block' );

				if ( empty( $socs_post_nav_block ) ) {
					$socs_post_nav_block = [];
				}

				$socs_post_nav_block[] = $post_id;

				set_transient( 'socs_import_posts_with_nav_block', $socs_post_nav_block, HOUR_IN_SECONDS );
			}
		} else {

			/*
			 * Save the `wp_navigation` post type mapping of the original menu ID and the new menu ID
			 * in transient.
			 */
			$socs_menu_mapping = get_transient( 'socs_import_menu_mapping' );

			if ( empty( $socs_menu_mapping ) ) {
				$socs_menu_mapping = [];
			}

			// Let's save the mapping of the original menu ID and the new menu ID.
			$socs_menu_mapping[] = [
				'original_menu_id' => $original_id,
				'new_menu_id'      => $post_id,
			];

			set_transient( 'socs_import_menu_mapping', $socs_menu_mapping, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Fix issue with WP Navigation block.
	 *
	 * We did this by looping through all the imported posts with the WP Navigation block
	 * and replacing the original menu ID with the new menu ID.
	 *
	 * @since 3.2.0
	 */
	public function fix_imported_wp_navigation() {

		// Get the `wp_navigation` import mapping.
		$nav_import_mapping = get_transient( 'socs_import_menu_mapping' );

		// Get the post IDs that needs to be updated.
		$posts_nav_block = get_transient( 'socs_import_posts_with_nav_block' );

		if ( empty( $nav_import_mapping ) || empty( $posts_nav_block ) ) {
			return;
		}

		$replace_pairs = [];

		foreach ( $nav_import_mapping as $mapping ) {
			$replace_pairs[ '<!-- wp:navigation {"ref":' . $mapping['original_menu_id'] . '} /-->' ] = '<!-- wp:navigation {"ref":' . $mapping['new_menu_id'] . '} /-->';
		}

		// Loop through each the posts that needs to be updated.
		foreach ( $posts_nav_block as $post_id ) {
			$post_nav_block = get_post( $post_id );

			if ( empty( $post_nav_block ) || empty( $post_nav_block->post_content ) ) {
				return;
			}

			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => strtr( $post_nav_block->post_content, $replace_pairs ),
				]
			);
		}
	}

	/**
	 * Update imported terms count.
	 */
	private function update_terms_count() {

		foreach ( $this->imported_terms as $tax => $terms ) {
			wp_update_term_count_now( $terms, $tax );
		}
	}

	/**
	 * AJAX callback for exporting data.
	 */
	public function export_data_ajax_callback() {
		// Verify AJAX call first.
		Helpers::verify_ajax_call();

		// Check if at least one export option is selected.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$has_content = ! empty( $_POST['export_content'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$has_widgets = ! empty( $_POST['export_widgets'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$has_customizer = ! empty( $_POST['export_customizer'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$has_elementor = ! empty( $_POST['export_elementor'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$has_plugins = ! empty( $_POST['export_plugins'] );

		if ( ! $has_content && ! $has_widgets && ! $has_customizer && ! $has_elementor && ! $has_plugins ) {
			wp_send_json_error( esc_html__( 'Please select at least one export option.', 'smart-one-click-setup' ) );
		}

		$export_options = array(
			'content'    => $has_content,
			'widgets'    => $has_widgets,
			'customizer' => $has_customizer,
			'elementor'  => $has_elementor,
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'plugins'    => $has_plugins ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['export_plugins'] ) ) : array(),
		);

		// Allow filtering of export options.
		$export_options = Helpers::apply_filters( 'socs/export_options', $export_options );

		// Increase memory limit and execution time for large exports.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set, Squiz.PHP.DiscouragedFunctions.Discouraged
		@ini_set( 'memory_limit', '512M' );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_time_limit, Squiz.PHP.DiscouragedFunctions.Discouraged
		@set_time_limit( 300 );

		try {
			$exporter = new \SOCS\Exporter( $export_options );
			$result = $exporter->generate_export();

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( $result->get_error_message() );
			}

			if ( empty( $result ) || ! is_array( $result ) ) {
				wp_send_json_error( esc_html__( 'Export completed but no file was generated.', 'smart-one-click-setup' ) );
			}

			wp_send_json_success( $result );
		} catch ( \Exception $e ) {
			/* translators: %s: The error message. */
			wp_send_json_error( sprintf( esc_html__( 'Export failed: %s', 'smart-one-click-setup' ), $e->getMessage() ) );
		} catch ( \Error $e ) {
			/* translators: %s: The error message. */
			wp_send_json_error( sprintf( esc_html__( 'Export failed: %s', 'smart-one-click-setup' ), $e->getMessage() ) );
		}
	}

	/**
	 * AJAX callback for importing ZIP file.
	 */
	public function import_zip_file_ajax_callback() {
		Helpers::verify_ajax_call();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['zip_file'] ) ) {
			wp_send_json_error( esc_html__( 'No ZIP file provided.', 'smart-one-click-setup' ) );
		}

		// Create a date and time string to use for demo and log file names.
		Helpers::set_demo_import_start_time();

		// Define log file path.
		$this->log_file_path = Helpers::get_log_path();

		// Process uploaded ZIP file.
		// Nonce verification is done via Helpers::verify_ajax_call() at the start of this function.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$zip_file = $this->process_zip_upload( $_FILES['zip_file'] );
		if ( is_wp_error( $zip_file ) ) {
			wp_send_json_error( $zip_file->get_error_message() );
		}

		// Extract ZIP file.
		$extracted_files = $this->extract_zip_file( $zip_file );
		if ( is_wp_error( $extracted_files ) ) {
			wp_send_json_error( $extracted_files->get_error_message() );
		}

		// Process before import hooks if provided.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['before_import_hook'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$before_hook = sanitize_textarea_field( wp_unslash( $_POST['before_import_hook'] ) );
			// Allow developers to execute custom code before import.
			Helpers::do_action( 'socs/before_content_import', $extracted_files );
			// Note: Direct code execution from user input is a security risk.
			// In production, this should be handled via filters only.
		}

		// Set selected import files.
		$this->selected_import_files = $extracted_files;
		$this->selected_index = 0;
		$this->import_files[ $this->selected_index ] = array(
			'import_file_name' => esc_html__( 'ZIP Import', 'smart-one-click-setup' ),
		);

		// Save the initial import data as a transient.
		Helpers::set_socs_import_data_transient( $this->get_current_importer_data() );

		// Process after import hooks if provided.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['after_import_hook'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$after_hook = sanitize_textarea_field( wp_unslash( $_POST['after_import_hook'] ) );
			// Allow developers to execute custom code after import.
			// Note: Direct code execution from user input is a security risk.
			// In production, this should be handled via filters only.
		}

		wp_send_json_success( array(
			'message' => esc_html__( 'ZIP file processed successfully. Starting import...', 'smart-one-click-setup' ),
		) );
	}

	/**
	 * Process uploaded ZIP file.
	 *
	 * @param array $file Uploaded file data.
	 * @return string|WP_Error File path or WP_Error.
	 */
	private function process_zip_upload( $file ) {
		$upload_overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'zip' => 'application/zip',
			),
		);

		$uploaded = wp_handle_upload( $file, $upload_overrides );

		if ( isset( $uploaded['error'] ) ) {
			return new \WP_Error( 'upload_failed', $uploaded['error'] );
		}

		return $uploaded['file'];
	}

	/**
	 * AJAX callback for importing predefined ZIP file.
	 */
	public function import_predefined_zip_ajax_callback() {
		Helpers::verify_ajax_call();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$import_index = isset( $_POST['import_index'] ) ? absint( $_POST['import_index'] ) : -1;

		if ( $import_index < 0 ) {
			wp_send_json_error( esc_html__( 'Invalid import index.', 'smart-one-click-setup' ) );
		}

		// Get predefined imports.
		$predefined_imports = Helpers::apply_filters( 'socs/predefined_import_files', array() );

		if ( empty( $predefined_imports[ $import_index ] ) ) {
			wp_send_json_error( esc_html__( 'Predefined import not found.', 'smart-one-click-setup' ) );
		}

		$import_config = $predefined_imports[ $import_index ];

		// Create a date and time string to use for demo and log file names.
		Helpers::set_demo_import_start_time();

		// Define log file path.
		$this->log_file_path = Helpers::get_log_path();

		// Get ZIP file path.
		$zip_file_path = '';

		// Check for local file path first.
		if ( ! empty( $import_config['zip_path'] ) && file_exists( $import_config['zip_path'] ) ) {
			$zip_file_path = $import_config['zip_path'];
		}
		// Check for URL.
		elseif ( ! empty( $import_config['zip_url'] ) ) {
			$downloader = new Downloader();
			$upload_dir = wp_upload_dir();
			$zip_filename = 'predefined-import-' . $import_index . '-' . Helpers::$demo_import_start_time . '.zip';
			$zip_file_path = $downloader->download_file( $import_config['zip_url'], $zip_filename );

			if ( is_wp_error( $zip_file_path ) ) {
				/* translators: %s: The error message. */
				wp_send_json_error( sprintf( esc_html__( 'Failed to download ZIP file: %s', 'smart-one-click-setup' ), $zip_file_path->get_error_message() ) );
			}
		}
		else {
			wp_send_json_error( esc_html__( 'No ZIP file URL or path provided in predefined import.', 'smart-one-click-setup' ) );
		}

		// Extract ZIP file.
		$extracted_files = $this->extract_zip_file( $zip_file_path );
		if ( is_wp_error( $extracted_files ) ) {
			wp_send_json_error( $extracted_files->get_error_message() );
		}

		// Process before import hooks if provided in config.
		if ( ! empty( $import_config['before_import'] ) && is_callable( $import_config['before_import'] ) ) {
			call_user_func( $import_config['before_import'], $extracted_files, $import_config );
		}

		// Also trigger the standard before import action.
		Helpers::do_action( 'socs/before_content_import', $extracted_files );

		// Set selected import files.
		$this->selected_import_files = $extracted_files;
		$this->selected_index = $import_index;
		$this->import_files[ $this->selected_index ] = array(
			'import_file_name' => ! empty( $import_config['name'] ) ? $import_config['name'] : esc_html__( 'Predefined Import', 'smart-one-click-setup' ),
		);

		// Save the initial import data as a transient.
		Helpers::set_socs_import_data_transient( $this->get_current_importer_data() );

		// Process after import hooks if provided in config.
		if ( ! empty( $import_config['after_import'] ) && is_callable( $import_config['after_import'] ) ) {
			// Will be called after import completes via the standard hook.
			$current_index = $import_index;
			add_action( 'socs/after_import', function( $selected_import_files, $import_files, $selected_index ) use ( $import_config, $current_index ) {
				if ( $selected_index === $current_index ) {
					call_user_func( $import_config['after_import'], $selected_import_files, $import_config );
				}
			}, 20, 3 );
		}

		wp_send_json_success( array(
			'message' => esc_html__( 'ZIP file processed successfully. Starting import...', 'smart-one-click-setup' ),
		) );
	}

	/**
	 * Extract ZIP file and return file paths.
	 *
	 * @param string $zip_file_path Path to ZIP file.
	 * @return array|WP_Error Array of file paths or WP_Error.
	 */
	private function extract_zip_file( $zip_file_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'zip_not_supported', esc_html__( 'ZIP extraction is not supported on this server.', 'smart-one-click-setup' ) );
		}

		$upload_dir = wp_upload_dir();
		$extract_dir = trailingslashit( $upload_dir['basedir'] ) . 'socs-extracted/' . basename( $zip_file_path, '.zip' ) . '/';

		// Create extract directory.
		if ( ! file_exists( $extract_dir ) ) {
			wp_mkdir_p( $extract_dir );
		}

		$zip = new \ZipArchive();
		if ( $zip->open( $zip_file_path ) !== true ) {
			return new \WP_Error( 'zip_open_failed', esc_html__( 'Failed to open ZIP file.', 'smart-one-click-setup' ) );
		}

		$zip->extractTo( $extract_dir );
		$zip->close();

		// Find extracted files.
		$extracted_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
			'elementor'  => '',
			'plugins'    => '',
		);

		// Look for common file patterns.
		$files = glob( $extract_dir . '*' );
		foreach ( $files as $file ) {
			$filename = basename( $file );
			if ( preg_match( '/content.*\.xml$/i', $filename ) ) {
				$extracted_files['content'] = $file;
			} elseif ( preg_match( '/widget.*\.(json|wie)$/i', $filename ) ) {
				$extracted_files['widgets'] = $file;
			} elseif ( preg_match( '/customizer.*\.dat$/i', $filename ) ) {
				$extracted_files['customizer'] = $file;
			} elseif ( preg_match( '/elementor.*\.json$/i', $filename ) ) {
				$extracted_files['elementor'] = $file;
			} elseif ( preg_match( '/plugin.*\.json$/i', $filename ) ) {
				$extracted_files['plugins'] = $file;
			}
		}

		// Clean up ZIP file.
		if ( file_exists( $zip_file_path ) ) {
			wp_delete_file( $zip_file_path );
		}

		return $extracted_files;
	}

	/**
	 * Get the import buttons HTML for the successful import page.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_import_successful_buttons_html() {

		/**
		 * Filter the buttons that are displayed on the successful import page.
		 *
		 * @since 3.2.0
		 *
		 * @param array $buttons {
		 *     Array of buttons.
		 *
		 *     @type string $label  Button label.
		 *     @type string $class  Button class.
		 *     @type string $href   Button URL.
		 *     @type string $target Button target. Can be `_blank`, `_parent`, `_top`. Default is `_self`.
		 * }
		 */
		$buttons = Helpers::apply_filters(
			'socs/import_successful_buttons',
			[
				[
					'label'  => __( 'Theme Settings' , 'smart-one-click-setup' ),
					'class'  => 'button button-primary button-hero',
					'href'   => admin_url( 'customize.php' ),
					'target' => '_blank',
				],
				[
					'label'  => __( 'Visit Site' , 'smart-one-click-setup' ),
					'class'  => 'button button-primary button-hero',
					'href'   => get_home_url(),
					'target' => '_blank',
				],
			]
		);

		if ( empty( $buttons ) || ! is_array( $buttons ) ) {
			return '';
		}

		ob_start();

		foreach ( $buttons as $button ) {

			if ( empty( $button['href'] ) || empty( $button['label'] ) ) {
				continue;
			}

			$target = '_self';
			if (
				! empty( $button['target'] ) &&
				in_array( strtolower( $button['target'] ), [ '_blank', '_parent', '_top' ], true )
			) {
				$target = $button['target'];
			}

			$class = 'button button-primary button-hero';
			if ( ! empty( $button['class'] ) ) {
				$class = $button['class'];
			}

			printf(
				'<a href="%1$s" class="%2$s" target="%3$s">%4$s</a>',
				esc_url( $button['href'] ),
				esc_attr( $class ),
				esc_attr( $target ),
				esc_html( $button['label'] )
			);
		}

		$buttons_html = ob_get_clean();

		return empty( $buttons_html ) ? '' : $buttons_html;
	}
}
