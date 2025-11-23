<?php

/*
Plugin Name: Smart One Click Setup
Plugin URI: https://socs.buildbycj.com
Description: Smart one-click demo import and export plugin. Export your content, widgets, customizer settings, plugin settings, and Elementor data. Import from ZIP files or predefined configurations with before/after import hooks. Full Elementor Site Kit import support - automatically imports and activates Elementor kits. Custom plugin options support with JSON Array/Object formats. Automatic single-option plugin detection for nested settings structures.
Version: 1.2.8
Requires at least: 5.5
Requires PHP: 7.4
Author: Chiranjit Hazarika
Author URI: https://buildbycj.com
License: GPL3
License URI: http://www.gnu.org/licenses/gpl.html
Text Domain: smart-one-click-setup
Domain Path: /languages
*/

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Main plugin class with initialization tasks.
 */
class SOCS_Plugin {
	/**
	 * Constructor for this class.
	 */
	public function __construct() {
		/**
		 * Display admin error message if PHP version is older than 7.4.
		 * Otherwise execute the main plugin class.
		 */
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'old_php_admin_error_notice' ) );
		}
		else {
			// Set plugin constants.
			$this->set_plugin_constants();

			// Load WordPress core importer class if not already loaded.
			if ( ! class_exists( '\WP_Importer' ) ) {
				require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
			}

			// Load buildbycj/wp-content-importer-v2 classes manually.
			if ( ! class_exists( '\BuildByCj\WPContentImporter2\WXRImporter' ) ) {
				require_once SOCS_PATH . 'vendor/buildbycj/wp-content-importer-v2/src/WXRImporter.php';
			}
			if ( ! class_exists( '\BuildByCj\WPContentImporter2\WPImporterLoggerCLI' ) ) {
				require_once SOCS_PATH . 'vendor/buildbycj/wp-content-importer-v2/src/WPImporterLogger.php';
				require_once SOCS_PATH . 'vendor/buildbycj/wp-content-importer-v2/src/WPImporterLoggerCLI.php';
			}

			// Composer autoloader.
			require_once SOCS_PATH . 'vendor/autoload.php';

			// Instantiate the main plugin class *Singleton*.
			$one_click_demo_import = SOCS\SmartOneClickSetup::get_instance();

			// Include template functions for theme developers.
			require_once SOCS_PATH . 'inc/TemplateFunctions.php';

			// Register WP CLI commands
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::add_command( 'socs list', array( 'SOCS\WPCLICommands', 'list_predefined' ) );
				WP_CLI::add_command( 'socs import', array( 'SOCS\WPCLICommands', 'import' ) );
			}
		}
	}


	/**
	 * Display an admin error notice when PHP is older the version 7.4.
	 * Hook it to the 'admin_notices' action.
	 */
	public function old_php_admin_error_notice() { /* translators: %1$s - the PHP version, %2$s and %3$s - strong HTML tags, %4$s - br HTMl tag. */
		$message = sprintf( esc_html__( 'The %2$sSmart One Click Setup%3$s plugin requires %2$sPHP 7.4+%3$s to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 7.4%4$s Your current version of PHP: %2$s%1$s%3$s', 'smart-one-click-setup' ), phpversion(), '<strong>', '</strong>', '<br>' );

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}


	/**
	 * Set plugin constants.
	 *
	 * Path/URL to root of this plugin, with trailing slash and plugin version.
	 */
	private function set_plugin_constants() {
		// Path/URL to root of this plugin, with trailing slash.
		if ( ! defined( 'SOCS_PATH' ) ) {
			define( 'SOCS_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'SOCS_URL' ) ) {
			define( 'SOCS_URL', plugin_dir_url( __FILE__ ) );
		}

		// Used for backward compatibility.
		if ( ! defined( 'CJ_SOCS_PATH' ) ) {
			define( 'CJ_SOCS_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'CJ_SOCS_URL' ) ) {
			define( 'CJ_SOCS_URL', plugin_dir_url( __FILE__ ) );
		}

		// Action hook to set the plugin version constant.
		add_action( 'admin_init', array( $this, 'set_plugin_version_constant' ) );
	}


	/**
	 * Set plugin version constant -> SOCS_VERSION.
	 */
	public function set_plugin_version_constant() {
		$plugin_data = get_plugin_data( __FILE__ );

		if ( ! defined( 'SOCS_VERSION' ) ) {
			define( 'SOCS_VERSION', $plugin_data['Version'] );
		}

		// Used for backward compatibility.
		if ( ! defined( 'CJ_SOCS_VERSION' ) ) {
			define( 'CJ_SOCS_VERSION', $plugin_data['Version'] );
		}
	}
}

// Instantiate the plugin class.
$socs_plugin = new SOCS_Plugin();

/**
 * Template function to display the Smart Import box in themes.
 *
 * This is a global wrapper function that theme developers can use
 * to easily display the Smart Import interface in their themes.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Optional. Array of arguments.
 *
 *     @type bool   $echo                    Whether to echo the output or return it. Default true.
 *     @type string $wrapper_class           Additional CSS class for the wrapper. Default empty.
 *     @type bool   $show_header             Whether to show the plugin header. Default true.
 *     @type bool   $show_sidebar            Whether to show the theme card sidebar. Default true.
 *     @type bool   $load_plugin_css         Whether to load plugin CSS. Set to false to use theme styles. Default true.
 *     @type bool   $show_smart_import_tabs  Whether to show the smart import tabs. Default null (uses filter default).
 *     @type bool   $show_file_upload_header Whether to show the file upload container header. Default true.
 *     @type bool   $show_intro_text         Whether to show the intro text section. Default true.
 * }
 * @return string|void HTML output if $echo is false, void otherwise.
 *
 * @example
 * // Basic usage - display in theme template
 * socs_display_smart_import();
 *
 * // Return HTML instead of echoing
 * $html = socs_display_smart_import( array( 'echo' => false ) );
 *
 * // Custom wrapper class
 * socs_display_smart_import( array( 'wrapper_class' => 'my-custom-class' ) );
 *
 * // Hide header and sidebar
 * socs_display_smart_import( array(
 *     'show_header' => false,
 *     'show_sidebar' => false,
 * ) );
 *
 * // Use theme styles instead of plugin CSS
 * socs_display_smart_import( array(
 *     'load_plugin_css' => false,
 * ) );
 *
 * // Hide tabs, header, and intro text for custom integration
 * socs_display_smart_import( array(
 *     'show_smart_import_tabs'  => false,
 *     'show_file_upload_header' => false,
 *     'show_intro_text'         => false,
 * ) );
 */
if ( ! function_exists( 'socs_display_smart_import' ) ) {
	function socs_display_smart_import( $args = array() ) {
		return SOCS\socs_display_smart_import( $args );
	}
}

