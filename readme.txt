=== Smart One Click Setup ===
Contributors: Chiranjit Hazarika
Tags: import, export, theme options, elementor, one click demo import
Requires at least: 5.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.6
License: GPLv3 or later

One-click import/export for demo content, widgets, customizer, plugin settings, and Elementor data. Perfect for theme authors and site migration.

== Description ==

Smart One Click Setup allows theme authors to define import files in their themes, so users can simply click the "Import Demo Data" button to get started.

**🎯 Key Features:**

**✨ One-Click ZIP Import/Export**
* Export everything to a single ZIP file - content, widgets, customizer, plugins, and Elementor data
* Import from a single ZIP file - automatically detects and imports all available data
* Easy site migration and backup - transfer your entire site configuration in one file

**🎨 Elementor Compatible**
* Full Elementor support - export and import Elementor templates, page data, and kit settings
* Preserve all Elementor designs and configurations across sites
* Seamless integration with Elementor Pro features

**🔌 Any Plugin Settings Import/Export**
* Export settings from ANY plugin - not just predefined ones
* Custom plugin support - developers can add their own export hooks
* Selective export - choose which plugins to include in your export
* Automatic detection of active plugins with customizable export list
* **Custom Plugin Options** - Add custom option names or values for any plugin (JSON Array or Object format)
* **Single-Option Plugin Detection** - Automatically handles plugins with nested settings structures

**🌐 Remote File Support**
* Import from remote URLs - no need to download files manually
* Support for presigned URLs (Amazon S3, etc.) via filters
* Local file support - use files from your theme directory
* Flexible file sources - mix and match remote and local files

**🚀 Smart Import Interface**
* Predefined demo imports - theme authors can pre-configure imports
* Manual ZIP upload - import your own exported files
* Tabbed interface - easy switching between predefined and manual imports
* Visual preview - see demo imports before importing

**⚙️ Developer-Friendly**
* Comprehensive hooks and filters for customization
* Before/after import actions for custom setup
* WP-CLI commands for automated imports
* Template function for theme integration
* Full WordPress coding standards compliance

> **Are you a theme author?**
>
> Setup Smart One Click Setup for your theme and your users will thank you for it!
>
> [Follow this easy guide on how to setup this plugin for your themes!](https://socs.buildbycj.com/#developer-guide)

> **Are you a theme user?**
>
> Contact the author of your theme and [let them know about this plugin](https://socs.buildbycj.com/#features). Theme authors can make any theme compatible with this plugin in 15 minutes and make it much more user-friendly.

Please take a look at our [plugin documentation](https://socs.buildbycj.com/#user-guide) for more information on how to import your demo content.

**Important Note:** There is no setting to "connect" authors from the demo import file to the existing users in your WP site (like there is in the original WP Importer plugin). All demo content will be imported under the current user.

**Do you want to contribute?** Please refer to our official [GitHub repository](https://github.com/buildbycj/smart-one-click-setup).

== Installation ==

**From your WordPress dashboard:**

1. Visit 'Plugins > Add New'
2. Search for 'Smart One Click Setup' and install the plugin
3. Activate 'Smart One Click Setup' from your Plugins page

**Manual Installation:**

1. Download 'Smart One Click Setup'
2. Upload the 'smart-one-click-setup' directory to your '/wp-content/plugins/' directory
3. Activate 'Smart One Click Setup' from your Plugins page

**Once the plugin is activated you will find:**
* Import page: *Appearance -> Import Demo Data*
* Export page: *Appearance -> Smart Export*

== Frequently Asked Questions ==

= I have activated the plugin. Where is the "Import Demo Data" page? =

You will find the import page in *wp-admin -> Appearance -> Import Demo Data*.

= How do I export my site's data? =

**✨ Export everything to a single ZIP file!**

1. Go to *Appearance -> Smart Export*
2. Select the items you want to export:
   * Content (posts, pages, media)
   * Widgets
   * Customizer settings
   * Elementor data (templates, pages, kit settings)
   * **Any plugin settings** - choose from all active plugins
3. Click the "Export" button
4. Download the generated ZIP file

**Key Advantage:** Everything is packaged in one ZIP file, making it super easy to transfer your entire site configuration to another WordPress installation.

= Can I import from a ZIP file? =

**✨ Yes! One ZIP file contains everything!**

1. Go to *Appearance -> Import Demo Data*
2. Click on the "Upload ZIP File" tab (if predefined imports are available) or use the manual import section
3. Upload your ZIP file
4. The plugin will automatically:
   * Extract the ZIP file
   * Detect available import files (content.xml, widgets.json, customizer.dat, plugin-settings.json, elementor.json)
   * Import all detected data in the correct order

**Key Advantage:** No need to upload multiple files separately - just one ZIP file and you're done!

= Is this plugin compatible with Elementor? =

**🎨 Yes! Full Elementor compatibility!**

* **Export Elementor Data:**
  * All Elementor templates and page data
  * Elementor kit settings (colors, typography, etc.)
  * Elementor CSS and edit mode settings
  * Everything packaged in one ZIP file

* **Import Elementor Data:**
  * Automatic detection of Elementor data in ZIP files
  * Import Elementor Site Kit settings and automatically set as active kit
  * Import Elementor page and template data with proper post ID mapping
  * Preserves all Elementor designs and configurations
  * Works seamlessly with Elementor Pro features

= Can I export settings from any plugin? =

**🔌 Yes! Export settings from ANY plugin!**

* **Automatic Detection:** Automatically detects all active plugins and shows them in a checklist
* **Selective Export:** Choose which plugins to include in your export
* **Custom Plugin Options (NEW in 1.2.2):** Add custom option names or values for any plugin
* **JSON Array Format:** `["option_name_1", "option_name_2"]` - fetches current values from database
* **JSON Object Format:** `{"option_name_1": "value1", "option_name_2": "value2"}` - uses provided values directly
* All plugin settings packaged in one ZIP file

= What JSON formats are supported for custom plugin options? =

Starting from version 1.2.2, you can add custom plugin options using two JSON formats:

**JSON Array Format** - Use when you want to fetch current values from the database:
```
["option_name_1", "option_name_2", "another_option"]
```
* All items must be non-empty strings (option names)
* Values are automatically fetched from the database during export
* Example: `["woocommerce_currency", "woocommerce_weight_unit"]`

**JSON Object Format** - Use when you want to provide specific custom values:
```
{
  "option_name_1": "value1",
  "option_name_2": {
    "nested": "data",
    "array": [1, 2, 3]
  },
  "option_name_3": 123,
  "option_name_4": true,
  "option_name_5": null
}
```
* Keys must be non-empty strings (option names)
* Values can be any valid JSON type: strings, numbers, booleans, arrays, objects, null
* Values are used directly during export (not fetched from database)
* Example: `{"my_custom_setting": "custom_value", "another_setting": {"key": "value"}}`

**How to Use:**
1. Go to Appearance → Smart Export
2. Select the plugins you want to export
3. Click the settings button (⚙️) next to any plugin
4. Enter your custom options in the modal using either format
5. The plugin will validate your JSON and save it
6. Export as usual - custom options will be included automatically

= Where are the demo import files and the log files saved? =

The files used in the demo import will be saved to the default WordPress uploads directory (e.g., `../wp-content/uploads/2023/03/`).

The log file will also be registered in the *wp-admin -> Media* section, so you can access it easily.

= How to predefine demo imports? =

This question is for theme authors. You can predefine demo imports using three methods:

**Method 1: Using ImportHelper (Recommended - Easiest)**

```php
use SOCS\ImportHelper;

// Simple: Add multiple imports from URLs (auto-generates names)
ImportHelper::add_multiple( array(
    'https://example.com/demos/demo1.zip',
    'https://example.com/demos/demo2.zip',
) );

// Advanced: Add with full details
ImportHelper::add(
    'Demo Import 1',
    'https://example.com/demos/demo1.zip',
    '',  // zip_path (optional)
    'First demo import description',
    'https://example.com/preview1.jpg',
    'https://example.com/demo1'
);
```

**Method 2: Using the Filter**

```php
add_filter( 'socs/predefined_import_files', function( $predefined_imports ) {
    return array(
        array(
            'name'          => 'Demo Import 1',
            'description'  => 'Demo import description',
            'preview_image' => 'http://example.com/preview.jpg',
            'preview_url'  => 'http://example.com/demo',
            'zip_url'      => 'http://example.com/demo.zip',  // Remote URL
            // or
            'zip_path'     => get_template_directory() . '/demos/demo.zip',  // Local path
        ),
    );
} );
```

**Method 3: Using Remote API (Automatic Demo Discovery)**

Set a base URL and the plugin automatically fetches demos based on your theme name:

```php
// In your theme's functions.php
add_filter( 'socs/demo_api_base_url', function() {
    return 'https://yourplugin.com';
} );
```

**How it works:**
1. Plugin detects your theme name (e.g., "zenix")
2. Constructs API URL: `https://yourplugin.com/zenix/demos.json`
3. Fetches and parses the JSON file
4. Automatically registers all demos found

**API JSON Format:** Your `demos.json` file should return one of these formats:

* Direct array: `[{"name": "Demo", "zip_url": "..."}]`
* Wrapped in 'demos' key: `{"demos": [...]}`
* Wrapped in 'data' key: `{"data": [...]}`

For more details, please refer to the plugin documentation.

= How to automatically assign "Front page", "Posts page" and menu locations after the importer is done? =

Use the `socs/after_import` action hook:

```php
function socs_after_import_setup() {
	// Assign menus to their locations
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
	if ( $main_menu ) {
		set_theme_mod( 'nav_menu_locations', array(
			'main-menu' => $main_menu->term_id, // Replace 'main-menu' with your menu location identifier
		) );
	}

	// Assign front page and posts page (blog page)
	$front_page = get_page_by_title( 'Home' );
	$blog_page  = get_page_by_title( 'Blog' );

	if ( $front_page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page->ID );
	}
	
	if ( $blog_page ) {
		update_option( 'page_for_posts', $blog_page->ID );
	}
}
add_action( 'socs/after_import', 'socs_after_import_setup' );
```

= How to handle different "after import setups" depending on which predefined import was selected? =

You can check the selected import name in your `socs/after_import` callback:

```php
function socs_after_import( $selected_import ) {
	$demo_name = isset( $selected_import['import_file_name'] ) 
		? $selected_import['import_file_name'] 
		: '';

	if ( 'Demo Import 1' === $demo_name ) {
		// Setup for Demo Import 1
		set_theme_mod( 'logo_img', get_template_directory_uri() . '/assets/images/logo1.png' );
	}
	elseif ( 'Demo Import 2' === $demo_name ) {
		// Setup for Demo Import 2
		set_theme_mod( 'logo_img', get_template_directory_uri() . '/assets/images/logo2.png' );
	}
}
add_action( 'socs/after_import', 'socs_after_import' );
```

= Can I add some code before the widgets get imported? =

Yes, use the `socs/widget_importer_before_widgets_import` action:

```php
function socs_before_widgets_import() {
	// Your code here that will be executed before widgets get imported
}
add_action( 'socs/widget_importer_before_widgets_import', 'socs_before_widgets_import' );
```

= How can I import via the WP-CLI? =

The plugin includes two WP-CLI commands:

* `wp socs list` - Lists all predefined demo imports for the current theme
* `wp socs import` - Imports content/widgets/customizer/predefined demos

**Usage:**

```bash
# Import specific files
wp socs import --content=content.xml --widgets=widgets.json --customizer=customizer.dat

# Import predefined demo (use index from 'wp socs list')
wp socs import --predefined=0
```

The content, widgets and customizer options can be mixed and used at the same time. If the `predefined` option is set, it will ignore all other options and import the predefined demo data.

= I'm a theme author and I want to change the plugin intro text, how can I do that? =

Use the `socs/plugin_intro_text` filter:

```php
function socs_plugin_intro_text( $default_text ) {
	$default_text .= '<div class="socs__intro-text">This is a custom text added to this plugin intro text.</div>';
	return $default_text;
}
add_filter( 'socs/plugin_intro_text', 'socs_plugin_intro_text' );
```

To add text in a separate "box", wrap your text in a div with class 'socs__intro-text', like in the example above.

= How can I hide or show the intro text section? =

Use the `socs/show_intro_text` filter:

```php
add_filter( 'socs/show_intro_text', '__return_false' );
```

Or when using the `socs_display_smart_import()` template function:

```php
socs_display_smart_import( array(
	'show_intro_text' => false,
) );
```

= How can I customize the intro description text? =

Use the `socs/intro_description_text` filter:

```php
add_filter( 'socs/intro_description_text', function( $description ) {
	return 'Your custom description text here.';
} );
```

The filter accepts HTML content which will be properly sanitized for security.

= How to disable generation of smaller images (thumbnails) during the content import? =

This will greatly improve import time, but only the original sized images will be imported. Add this to your theme's functions.php:

```php
add_filter( 'socs/regenerate_thumbnails_in_content_import', '__return_false' );
```

= How to change the location, title and other parameters of the plugin page? =

Use the `socs/plugin_page_setup` filter:

```php
function socs_plugin_page_setup( $default_settings ) {
	$default_settings['parent_slug'] = 'themes.php';
	$default_settings['page_title']   = esc_html__( 'Smart One Click Setup', 'smart-one-click-setup' );
	$default_settings['menu_title']   = esc_html__( 'Import Demo Data', 'smart-one-click-setup' );
	$default_settings['capability']   = 'import';
	$default_settings['menu_slug']    = 'smart-one-click-setup';
	return $default_settings;
}
add_filter( 'socs/plugin_page_setup', 'socs_plugin_page_setup' );
```

= How to do something before the content import executes? =

Use the `socs/before_content_import` action hook:

```php
function socs_before_content_import( $selected_import ) {
	if ( 'Demo Import 1' === $selected_import['import_file_name'] ) {
		// Code for "Demo Import 1" before content import starts
	}
}
add_action( 'socs/before_content_import', 'socs_before_content_import' );
```

= How can I enable the `customize_save*` wp action hooks in the customizer import? =

Add this to your theme:

```php
add_filter( 'socs/enable_wp_customize_save_hooks', '__return_true' );
```

This will enable the following WP hooks when importing customizer data: `customize_save`, `customize_save_*`, `customize_save_after`.

= How can I pass Amazon S3 presigned URL's (temporary links) as external files? =

Use the `socs/pre_download_import_files` filter:

```php
add_filter( 'socs/pre_download_import_files', function( $import_file_info ) {
	// Get presigned URLs from your API
	$request = get_my_custom_urls( $import_file_info );

	if ( ! is_wp_error( $request ) && isset( $request['data'] ) && is_array( $request['data'] ) ) {
		if ( isset( $request['data']['import_file_url'] ) ) {
			$import_file_info['import_file_url'] = $request['data']['import_file_url'];
		}
		if ( isset( $request['data']['import_widget_file_url'] ) ) {
			$import_file_info['import_widget_file_url'] = $request['data']['import_widget_file_url'];
		}
		if ( isset( $request['data']['import_customizer_file_url'] ) ) {
			$import_file_info['import_customizer_file_url'] = $request['data']['import_customizer_file_url'];
		}
	}

	return $import_file_info;
} );
```

= What about using local import files (from theme folder)? =

You can use local import files by using the `socs/import_files` filter with `local_*` array keys. The values must be absolute paths (not URLs) to your import files. Make sure your import files are readable!

Please refer to the plugin documentation for more details.

= I can't activate the plugin, because of a fatal error, what can I do? =

If you see the error "Plugin could not be activated because it triggered a fatal error", this usually means your hosting server is using a very old version of PHP.

This plugin requires PHP version **7.4** or higher (we recommend **8.0** or higher). Please contact your hosting company and ask them to update the PHP version for your site.

= Issues with the import, that we can't fix in the plugin =

Please visit this [docs page](https://github.com/buildbycj/smart-one-click-setup/blob/master/docs/import-problems.md) for more answers to issues with importing data.

== Screenshots ==

1. Example of multiple predefined demo imports, that a user can choose from.
2. How the import page looks like, when only one demo import is predefined.
3. Example of how the import page looks like, when no demo imports are predefined a.k.a manual import.
4. How the Recommended & Required theme plugins step looks like, just before the import step.

== Changelog ==

= 1.2.5 =

*Release Date - 25 Dec 2024*

* **Elementor Cache Fix**
  * Added automatic Elementor cache clearing after all imports complete
  * Fixes issue where list widgets on blog/WooCommerce pages don't display correctly after import
  * Ensures Elementor CSS is regenerated with correct imported data
  * Cache is now cleared at the end of import process, not just during Elementor import

= 1.2.4 =

*Release Date - 22 Nov 2025*

* **Code Quality Improvements**
  * Fixed WordPress.DB.SlowDBQuery.slow_db_query_tax_query PHPCS warnings in ElementorImporter class
  * Added proper PHPCS ignore comments for necessary tax_query usage

= 1.2.3 =

*Release Date - 22 Nov 2025*

* **Improved Customizer Import**
  * Fixed page ID remapping for home page settings (page_on_front, page_for_posts)
  * Improved mapping retrieval from importer instance and transient data
  * Better handling of show_on_front, page_on_front, and page_for_posts options order
  * Enhanced validation and error logging for page ID remapping
  * Ensures show_on_front is set before page_on_front for proper home page configuration
* **Enhanced Import Process**
  * Added automatic rewrite rules flush after import completion to ensure permalinks work correctly
  * Improved final import response with better error handling
  * Always show log link after import completion (for both success and error cases)
  * Better distinction between error log and regular log messages
* **Code Quality Improvements**
  * Fixed PHPCS warnings for prepared SQL queries in Exporter class
  * Improved code documentation and comments

= 1.2.2 =

*Release Date - 20 Nov 2025*

* **NEW: Custom Plugin Options Feature**
  * Add custom plugin options for any selected plugin during export
  * Support for JSON Array format: `["option_name_1", "option_name_2"]` - fetches values from database
  * Support for JSON Object format: `{"option_name_1": "value1", "option_name_2": "value2"}` - uses provided values directly
  * Beautiful modal interface with JSON validation
  * Visual indicators for plugins with custom options
  * Seamless integration with existing plugin settings export
* **NEW: Single-Option Plugin Detection**
  * Automatic detection of plugins that store all settings in a single option (nested structure)
  * Smart option name detection (checks database for existing options)
  * Generic detection works for any plugin with nested structures
* **Enhanced Plugin Settings Import/Export**
  * Improved JSON encoding with proper Unicode and formatting support
  * Better error handling for JSON encoding failures
  * Enhanced logging to show imported option counts and names
  * Tracks all imported options even if values didn't change
  * Proper WordPress hooks triggering for all imported options
  * Improved unserialize handling for custom JSON values
* **Widget Export Format Fix**
  * Fixed widget export format to match importer expectations
  * Widgets now export with `widget_id` as key for proper import
* **UI Improvements**
  * Added settings button next to each plugin in export list
  * Modal interface for adding custom plugin options
  * Better examples and descriptions in the UI
  * Improved error messages and validation

= 1.2.0 =

*Release Date - 20 Nov 2025*

* **NEW: Full Elementor Site Kit Import Support**
  * Import Elementor Site Kit settings (colors, typography, global styles) from exported `elementor.json` files
  * Automatically sets the imported kit as the active Elementor Site Kit
  * Import Elementor page and template data with proper post ID mapping
  * Preserves all Elementor designs, CSS, and edit mode settings
  * Seamless integration with Elementor Pro features
  * Complete Elementor data import workflow - from export to import in one click
* Enhanced Elementor compatibility - now supports both export and import of complete Elementor configurations
* Improved import process - Elementor data is imported after content import to ensure proper post ID mapping
* Better error handling and logging for Elementor import operations

= 1.1.1 =

*Release Date - 19 Nov 2025*

* Added `show_intro_text` parameter to `socs_display_smart_import()` function for controlling intro text section visibility
* Added `socs/show_intro_text` filter for programmatic control of intro text section visibility
* Added `socs/intro_description_text` filter for customizing the intro description text
* Enhanced customization options for theme developers

= 1.1.0 =

*Release Date - 19 Nov 2025*

* Added `show_smart_import_tabs` parameter to `socs_display_smart_import()` function for controlling tab visibility
* Added `show_file_upload_header` parameter to `socs_display_smart_import()` function for controlling header visibility
* Added `socs/show_smart_import_tabs` filter for programmatic control of smart import tabs visibility
* Added `socs/show_file_upload_header` filter for programmatic control of file upload header visibility
* Enhanced template function with more customization options for theme developers
* Improved flexibility for custom page integrations

= 1.0.0 =

*Release Date - 17 Nov 2025*

* Initial release!
* Full import functionality: content, widgets, customizer, Redux, WPForms
* Smart Import interface with predefined demo imports and ZIP file upload
* Full export functionality: content, widgets, customizer, plugin settings, Elementor data
* Export to ZIP file for easy transfer between sites
* Before/after import hooks for custom setup
* WP-CLI commands for automated imports
* Template function `socs_display_smart_import()` for theme integration
* Full WordPress coding standards compliance
* Comprehensive developer hooks and filters
* Support for Elementor templates and kit settings export/import
