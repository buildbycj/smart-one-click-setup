<?php

/*
Plugin Name: Smart One Click Setup
Plugin URI: https://smartocs.buildbycj.com
Description: Smart one-click demo import and export plugin. Export your content, widgets, customizer settings, plugin settings, and Elementor data. Import from ZIP files or predefined configurations with before/after import hooks. Full Elementor Site Kit import support - automatically imports and activates Elementor kits. Custom plugin options support with JSON Array/Object formats. Automatic single-option plugin detection for nested settings structures.
Version: 1.3.9
Requires at least: 5.5
Requires PHP: 7.4
Author: Chiranjit Hazarika
Author URI: https://buildbycj.com
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: smart-one-click-setup
Domain Path: /languages
*/

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Main plugin class with initialization tasks.
 */
class SMARTOCS_Plugin {
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

		// Load custom WXR importer class (no longer requires WP_Importer).
		// The custom importer is self-contained and doesn't extend WP_Importer.
		// Only load in admin or WP-CLI context since it's only used for imports.
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			if ( ! class_exists( 'SMARTOCS\CustomWXRImporter' ) ) {
				require_once SMARTOCS_PATH . 'inc/CustomWXRImporter.php';
			}
			// Still load logger classes from vendor (they don't extend WP_Importer).
			if ( ! class_exists( '\BuildByCj\WPContentImporter2\WPImporterLoggerCLI' ) ) {
				require_once SMARTOCS_PATH . 'vendor/buildbycj/wp-content-importer-v2/src/WPImporterLogger.php';
				require_once SMARTOCS_PATH . 'vendor/buildbycj/wp-content-importer-v2/src/WPImporterLoggerCLI.php';
			}
		}

			// Composer autoloader.
			require_once SMARTOCS_PATH . 'vendor/autoload.php';

			// Load the main plugin class.
			require_once SMARTOCS_PATH . 'inc/SmartOneClickSetup.php';

			// Instantiate the main plugin class *Singleton*.
			$one_click_demo_import = SMARTOCS\SmartOneClickSetup::get_instance();

			// Include template functions for theme developers.
			require_once SMARTOCS_PATH . 'inc/TemplateFunctions.php';

			// Register WP CLI commands
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::add_command( 'smartocs list', array( 'SMARTOCS\WPCLICommands', 'list_predefined' ) );
				WP_CLI::add_command( 'smartocs import', array( 'SMARTOCS\WPCLICommands', 'import' ) );
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
	 * Load custom WXR importer class if needed.
	 * No longer requires WP_Importer - the custom importer is self-contained.
	 *
	 * @return bool True if class is available, false otherwise.
	 */
	private function load_wp_importer_if_needed() {
		// Custom importer doesn't require WP_Importer anymore.
		// This method is kept for backward compatibility but now loads our custom class.
		if ( ! class_exists( 'SMARTOCS\CustomWXRImporter' ) ) {
			require_once SMARTOCS_PATH . 'inc/CustomWXRImporter.php';
		}

		return class_exists( 'SMARTOCS\CustomWXRImporter' );
	}

	/**
	 * Set plugin constants.
	 *
	 * Path/URL to root of this plugin, with trailing slash and plugin version.
	 */
	private function set_plugin_constants() {
		// Path/URL to root of this plugin, with trailing slash.
		if ( ! defined( 'SMARTOCS_PATH' ) ) {
			define( 'SMARTOCS_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'SMARTOCS_URL' ) ) {
			define( 'SMARTOCS_URL', plugin_dir_url( __FILE__ ) );
		}

		// Action hook to set the plugin version constant.
		add_action( 'admin_init', array( $this, 'set_plugin_version_constant' ) );
	}


	/**
	 * Set plugin version constant -> SMARTOCS_VERSION.
	 */
	public function set_plugin_version_constant() {
		$smartocs_plugin_data = get_plugin_data( __FILE__ );

		if ( ! defined( 'SMARTOCS_VERSION' ) ) {
			define( 'SMARTOCS_VERSION', $smartocs_plugin_data['Version'] );
		}
	}
}

// Instantiate the plugin class.
$smartocs_plugin = new SMARTOCS_Plugin();

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
 * smartocs_display_smart_import();
 *
 * // Return HTML instead of echoing
 * $html = smartocs_display_smart_import( array( 'echo' => false ) );
 *
 * // Custom wrapper class
 * smartocs_display_smart_import( array( 'wrapper_class' => 'my-custom-class' ) );
 *
 * // Hide header and sidebar
 * smartocs_display_smart_import( array(
 *     'show_header' => false,
 *     'show_sidebar' => false,
 * ) );
 *
 * // Use theme styles instead of plugin CSS
 * smartocs_display_smart_import( array(
 *     'load_plugin_css' => false,
 * ) );
 *
 * // Hide tabs, header, and intro text for custom integration
 * smartocs_display_smart_import( array(
 *     'show_smart_import_tabs'  => false,
 *     'show_file_upload_header' => false,
 *     'show_intro_text'         => false,
 * ) );
 */
if ( ! function_exists( 'smartocs_display_smart_import' ) ) {
	function smartocs_display_smart_import( $args = array() ) {
		return SMARTOCS\smartocs_display_smart_import( $args );
	}
}

