<?php
/**
 * Class for the import actions used in the Smart One Click Setup plugin.
 * Register default WP actions for SMARTOCS plugin.
 *
 * @package smartocs
 */

namespace SMARTOCS;

class ImportActions {
	/**
	 * Track if hooks have been registered to prevent duplicate registrations.
	 *
	 * @var bool
	 */
	private static $hooks_registered = false;

	/**
	 * Static instance to ensure consistent callback references.
	 *
	 * @var ImportActions
	 */
	private static $instance = null;

	/**
	 * Get static instance to ensure hooks are registered to the same instance.
	 *
	 * @return ImportActions
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all action hooks for this class.
	 */
	public function register_hooks() {
		// Prevent duplicate hook registrations.
		if ( self::$hooks_registered ) {
			return;
		}

		// Use static instance to ensure callbacks reference the same object.
		$instance = self::get_instance();

		// Before content import.
		add_action( 'smartocs/before_content_import_execution', array( $instance, 'before_content_import_action' ), 10, 3 );

		// After content import.
		add_action( 'smartocs/after_content_import_execution', array( $instance, 'before_widget_import_action' ), 10, 3 );
		add_action( 'smartocs/after_content_import_execution', array( $instance, 'widgets_import' ), 20, 3 );
		add_action( 'smartocs/after_content_import_execution', array( $instance, 'redux_import' ), 30, 3 );
		add_action( 'smartocs/after_content_import_execution', array( $instance, 'wpforms_import' ), 40, 3 );
		add_action( 'smartocs/after_content_import_execution', array( $instance, 'elementor_import' ), 50, 3 );
		add_action( 'smartocs/after_content_import_execution', array( $instance, 'plugin_settings_import' ), 60, 3 );

		// Customizer import.
		add_action( 'smartocs/customizer_import_execution', array( $instance, 'customizer_import' ), 10, 1 );

		// After full import action.
		add_action( 'smartocs/after_all_import_execution', array( $instance, 'after_import_action' ), 10, 3 );
		add_action( 'smartocs/after_all_import_execution', array( $instance, 'clear_elementor_cache' ), 20, 3 );

		// Special widget import cases.
		if ( Helpers::apply_filters( 'smartocs/enable_custom_menu_widget_ids_fix', true ) ) {
			add_action( 'smartocs/widget_settings_array', array( $instance, 'fix_custom_menu_widget_ids' ) );
		}

		// Mark hooks as registered.
		self::$hooks_registered = true;
	}


	/**
	 * Change the menu IDs in the custom menu widgets in the widget import data.
	 * This solves the issue with custom menu widgets not having the correct (new) menu ID, because they
	 * have the old menu ID from the export site.
	 *
	 * @since 3.4.0 Made sure `$widget` is an array.
	 *
	 * @param array $widget The widget settings array.
	 */
	public function fix_custom_menu_widget_ids( $widget ) {

		// Make sure the passed variable is an array to prevent fatal error.
		if ( ! is_array( $widget ) ) {
			$widget = [];
		}

		// Skip (no changes needed), if this is not a custom menu widget.
		if ( ! array_key_exists( 'nav_menu', $widget ) || empty( $widget['nav_menu'] ) || ! is_int( $widget['nav_menu'] ) ) {
			return $widget;
		}

		// Get import data, with new menu IDs.
		$smartocs                = SmartOneClickSetup::get_instance();
		$content_import_data = $smartocs->importer->get_importer_data();
		$term_ids            = $content_import_data['mapping']['term_id'];

		// Set the new menu ID for the widget.
		$widget['nav_menu'] = $term_ids[ $widget['nav_menu'] ];

		return $widget;
	}


	/**
	 * Execute the widgets import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function widgets_import( $selected_import_files, $import_files, $selected_index ) {
		$smartocs = SmartOneClickSetup::get_instance();
		
		// Skip if already executed to prevent duplicates during content import continuations.
		if ( $smartocs->is_import_executed( 'widgets' ) ) {
			return;
		}

		if ( ! empty( $selected_import_files['widgets'] ) ) {
			WidgetImporter::import( $selected_import_files['widgets'] );
			$smartocs->mark_import_executed( 'widgets' );
			
			// Save execution state to transient.
			$importer_data = $smartocs->get_current_importer_data();
			Helpers::set_smartocs_import_data_transient( $importer_data );
		}
	}


	/**
	 * Execute the Redux import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function redux_import( $selected_import_files, $import_files, $selected_index ) {
		$smartocs = SmartOneClickSetup::get_instance();
		
		// Skip if already executed to prevent duplicates during content import continuations.
		if ( $smartocs->is_import_executed( 'redux' ) ) {
			return;
		}

		if ( ! empty( $selected_import_files['redux'] ) ) {
			ReduxImporter::import( $selected_import_files['redux'] );
			$smartocs->mark_import_executed( 'redux' );
			
			// Save execution state to transient.
			$importer_data = $smartocs->get_current_importer_data();
			Helpers::set_smartocs_import_data_transient( $importer_data );
		}
	}

	/**
	 * Execute the WPForms import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function wpforms_import( $selected_import_files, $import_files, $selected_index ) {
		$smartocs = SmartOneClickSetup::get_instance();
		
		// Skip if already executed to prevent duplicates during content import continuations.
		if ( $smartocs->is_import_executed( 'wpforms' ) ) {
			return;
		}

		if ( ! empty( $selected_import_files['wpforms'] ) ) {
			( new WPFormsImporter( $selected_import_files['wpforms'] ) )->import();
			$smartocs->mark_import_executed( 'wpforms' );
			
			// Save execution state to transient.
			$importer_data = $smartocs->get_current_importer_data();
			Helpers::set_smartocs_import_data_transient( $importer_data );
		}
	}

	/**
	 * Execute the Elementor import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, elementor).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function elementor_import( $selected_import_files, $import_files, $selected_index ) {
		$smartocs = SmartOneClickSetup::get_instance();
		
		// Skip if already executed to prevent duplicates during content import continuations.
		if ( $smartocs->is_import_executed( 'elementor' ) ) {
			return;
		}

		if ( ! empty( $selected_import_files['elementor'] ) ) {
			ElementorImporter::import( $selected_import_files['elementor'] );
			$smartocs->mark_import_executed( 'elementor' );
			
			// Save execution state to transient.
			$importer_data = $smartocs->get_current_importer_data();
			Helpers::set_smartocs_import_data_transient( $importer_data );
		}
	}

	/**
	 * Execute the plugin settings import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, plugins).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function plugin_settings_import( $selected_import_files, $import_files, $selected_index ) {
		$smartocs = SmartOneClickSetup::get_instance();
		
		// Skip if already executed to prevent duplicates during content import continuations.
		if ( $smartocs->is_import_executed( 'plugin_settings' ) ) {
			return;
		}

		if ( ! empty( $selected_import_files['plugins'] ) ) {
			PluginSettingsImporter::import( $selected_import_files['plugins'] );
			$smartocs->mark_import_executed( 'plugin_settings' );
			
			// Save execution state to transient.
			$importer_data = $smartocs->get_current_importer_data();
			Helpers::set_smartocs_import_data_transient( $importer_data );
		}
	}

	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function customizer_import( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			CustomizerImporter::import( $selected_import_files['customizer'] );
		}
	}


	/**
	 * Execute the action: 'smartocs/before_content_import'.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function before_content_import_action( $selected_import_files, $import_files, $selected_index ) {
		$this->do_import_action( 'smartocs/before_content_import', $import_files[ $selected_index ] );
	}


	/**
	 * Execute the action: 'smartocs/before_widgets_import'.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function before_widget_import_action( $selected_import_files, $import_files, $selected_index ) {
		$this->do_import_action( 'smartocs/before_widgets_import', $import_files[ $selected_index ] );
	}


	/**
	 * Execute the action: 'smartocs/after_import'.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function after_import_action( $selected_import_files, $import_files, $selected_index ) {
		$this->do_import_action( 'smartocs/after_import', $import_files[ $selected_index ] );
	}

	/**
	 * Clear Elementor cache after all imports complete.
	 * This ensures Elementor widgets (especially list widgets on blog/WooCommerce pages) display correctly.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer, redux).
	 * @param array $import_files          The filtered import files defined in `smartocs/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function clear_elementor_cache( $selected_import_files, $import_files, $selected_index ) {
		// Check if Elementor is active.
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$elementor = \Elementor\Plugin::$instance;

		// Clear Elementor files cache.
		if ( method_exists( $elementor->files_manager, 'clear_cache' ) ) {
			$elementor->files_manager->clear_cache();
		}

		// Clear Elementor posts CSS cache (important for widgets to display correctly).
		if ( method_exists( $elementor->posts_css_manager, 'clear_cache' ) ) {
			$elementor->posts_css_manager->clear_cache();
		}

		// Clear Elementor kits cache.
		if ( method_exists( $elementor->kits_manager, 'clear_cache' ) ) {
			$elementor->kits_manager->clear_cache();
		}

		// Also try to clear Elementor's general cache if available.
		if ( method_exists( $elementor, 'files_manager' ) && method_exists( $elementor->files_manager, 'regenerate_css_files' ) ) {
			$elementor->files_manager->regenerate_css_files();
		}
	}


	/**
	 * Register the do_action hook, so users can hook to these during import.
	 *
	 * @param string $action          The action name to be executed.
	 * @param array  $selected_import The data of selected import from `smartocs/import_files` filter.
	 */
	private function do_import_action( $action, $selected_import ) {
		if ( false !== Helpers::has_action( $action ) ) {
			$smartocs          = SmartOneClickSetup::get_instance();
			$log_file_path = $smartocs->get_log_file_path();

			try {
				// Execute the action. Actions should not output directly.
				// If they do, it's not our responsibility to capture it.
				Helpers::do_action( $action, $selected_import );
			} catch ( \Exception $e ) {
				// Log the exception if needed.
				Helpers::append_to_file(
					// translators: %1$s: action name, %2$s: error message
					sprintf( esc_html__( 'Error executing action %1$s: %2$s', 'smart-one-click-setup' ), $action, $e->getMessage() ),
					$log_file_path,
					$action
				);
			}
		}
	}
}
