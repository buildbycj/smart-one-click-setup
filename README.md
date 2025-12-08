<img src="assets/images/logo.svg" alt="Smart One Click Setup Logo" width="80"> 

# Smart One Click Setup

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![Version](https://img.shields.io/badge/Version-1.3.9-green.svg)](https://github.com/buildbycj/smart-one-click-setup)
[![WordPress.org](https://img.shields.io/badge/WordPress.org-Plugin%20Directory-blue.svg)](https://wordpress.org/plugins/smart-one-click-setup/)

One-click import/export for demo content, widgets, customizer, plugin settings, and Elementor data. Perfect for theme authors and site migration.

## 📖 About

Smart One Click Setup is a powerful WordPress plugin that simplifies importing and exporting demo content, theme settings, and plugin configurations. Whether you're a theme author providing a seamless demo import experience or a developer migrating site configurations, this plugin has you covered.

**📹 Watch our explainer video:**

<iframe width="560" height="315" src="https://www.youtube.com/embed/m85CcVUcclU" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

**What makes it special?**

- **All-in-One Solution**: Export and import everything in a single ZIP file - no more managing multiple files
- **Theme Author Friendly**: Easy integration with any WordPress theme through simple filters and helper classes
- **Developer Focused**: Comprehensive hooks and filters for complete customization
- **Elementor Ready**: Full support for Elementor templates, pages, and kit settings
- **Universal Plugin Support**: Export settings from any WordPress plugin, not just predefined ones
- **Remote & Local Support**: Import from remote URLs or local theme directories

## 🎯 Key Features

### ✨ One-Click ZIP Import/Export
- Export everything to a single ZIP file - content, widgets, customizer, plugins, and Elementor data
- Import from a single ZIP file - automatically detects and imports all available data
- Easy site migration and backup - transfer your entire site configuration in one file

### 🎨 Elementor Compatible
- Full Elementor support - export and import Elementor templates, page data, and kit settings
- Preserve all Elementor designs and configurations across sites
- Seamless integration with Elementor Pro features

### 🔌 Any Plugin Settings Import/Export
- Export settings from ANY plugin - not just predefined ones
- Custom plugin support - developers can add their own export hooks
- Selective export - choose which plugins to include in your export
- Automatic detection of active plugins with customizable export list
- **Custom Plugin Options** - Add custom option names or values for any plugin (JSON Array or Object format)
- **Single-Option Plugin Detection** - Automatically handles plugins with nested settings structures

### 🌐 Remote File Support
- Import from remote URLs - no need to download files manually
- Support for presigned URLs (Amazon S3, etc.) via filters
- Local file support - use files from your theme directory
- Flexible file sources - mix and match remote and local files

### 🚀 Smart Import Interface
- Predefined demo imports - theme authors can pre-configure imports
- Manual ZIP upload - import your own exported files
- Tabbed interface - easy switching between predefined and manual imports
- Visual preview - see demo imports before importing

### ⚙️ Developer-Friendly
- Comprehensive hooks and filters for customization
- Before/after import actions for custom setup
- WP-CLI commands for automated imports
- Template function for theme integration
- Full WordPress coding standards compliance

## 📋 Requirements

- WordPress 5.5 or higher
- PHP 7.4 or higher (PHP 8.0+ recommended)

## 🚀 Installation

### From WordPress Dashboard

1. Visit 'Plugins > Add New'
2. Search for 'Smart One Click Setup' and install the plugin
3. Activate 'Smart One Click Setup' from your Plugins page

**Or install directly from [WordPress.org Plugin Directory](https://wordpress.org/plugins/smart-one-click-setup/)**

### Manual Installation

1. Download the latest release
2. Upload the 'smart-one-click-setup' directory to your '/wp-content/plugins/' directory
3. Activate 'Smart One Click Setup' from your Plugins page

### Once Activated

- **Import page:** `Appearance -> Import Demo Data`
- **Export page:** `Appearance -> Smart Export`

## 📖 Documentation

For detailed documentation, please visit: [https://smartocs.buildbycj.com](https://smartocs.buildbycj.com)

## 🔧 Custom Plugin Options

Starting from version 1.2.2, you can add custom plugin options for any selected plugin during export. This feature supports two JSON formats:

### JSON Array Format

Use this format when you want to fetch current values from the database:

```json
["option_name_1", "option_name_2", "another_option"]
```

**Characteristics:**
- All items must be non-empty strings (option names)
- Values are automatically fetched from the database during export
- Perfect when you want current database values
- Example: `["woocommerce_currency", "woocommerce_weight_unit"]`

### JSON Object Format

Use this format when you want to provide specific custom values:

```json
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

**Characteristics:**
- Keys must be non-empty strings (option names)
- Values can be any valid JSON type: strings, numbers, booleans, arrays, objects, null
- Values are used directly during export (not fetched from database)
- Perfect when you want to set specific custom values
- Example: `{"my_custom_setting": "custom_value", "another_setting": {"key": "value"}}`

### How to Use

1. Go to **Appearance → Smart Export**
2. Select the plugins you want to export
3. Click the settings button (⚙️) next to any plugin
4. Enter your custom options in the modal:
   - Use JSON Array format for option names only
   - Use JSON Object format for option names with values
5. The plugin will validate your JSON and save it
6. Export as usual - custom options will be included automatically

### Plugin Settings Export File Format

The exported `plugin-settings.json` file uses a nested JSON Object structure:

```json
{
  "plugin_slug_1": {
    "option_name_1": "value1",
    "option_name_2": "value2"
  },
  "plugin_slug_2": {
    "section_1": {
      "name": "Section Name",
      "fields": {
        "field_1": "value1"
      }
    }
  }
}
```

This format supports:
- Simple key-value pairs
- Nested structures for single-option plugins
- Any valid JSON data types
- Unicode characters

## 🛠️ For Theme Authors

Setup Smart One Click Setup for your theme and your users will thank you for it!

[Follow this easy guide on how to setup this plugin for your themes!](https://smartocs.buildbycj.com/#developer-guide)

### 🚀 Remote Demo Management

**SmartOCS Cloud** - A premium PHP script that allows theme developers to manage their theme demo files remotely for Smart One Click Setup. With SmartOCS Cloud, you can:

* Manage demo imports remotely without updating your theme
* Update demo files and configurations from a central location
* Provide a seamless experience for your theme users
* Control demo availability and versions dynamically

[Get SmartOCS Cloud →](https://links.buildbycj.com/SmartOCSCloud) *(Premium PHP Script)*

### Quick Start

```php
use SOCS\ImportHelper;

// Method 1: Add multiple imports from URLs (simplest)
ImportHelper::add_multiple( array(
    'https://example.com/demos/demo1.zip',
    'https://example.com/demos/demo2.zip',
) );

// Method 2: Add with full details
ImportHelper::add(
    'Demo Import 1',
    'https://example.com/demos/demo1.zip',
    '',  // zip_path (optional)
    'First demo import description',
    'https://example.com/preview1.jpg',
    'https://example.com/demo1'
);

// Method 3: Use the filter method
add_filter( 'socs/predefined_import_files', function( $predefined_imports ) {
	return array(
		array(
			'name'          => 'Business Demo',
			'description'   => 'Perfect for business websites',
			'preview_image' => 'https://example.com/previews/business.jpg',
			'preview_url'   => 'https://example.com/demo/business',
			'zip_url'       => 'https://example.com/demos/business.zip',
		),
	);
} );

// Display Smart Import with custom options
if ( function_exists( 'socs_display_smart_import' ) ) {
	socs_display_smart_import( array(
		'wrapper_class'          => 'my-custom-class',
		'show_header'            => false,
		'show_sidebar'           => false,
		'load_plugin_css'        => false,
		'show_smart_import_tabs' => false,
		'show_file_upload_header' => false,
		'show_intro_text'        => false,
	) );
}
```

### Post-Import Setup

Automatically set menus, home page, and other settings after import:

```php
add_action( 'socs/after_import', function( $selected_import ) {
	// Set menu locations
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
	if ( $main_menu ) {
		set_theme_mod( 'nav_menu_locations', array(
			'primary' => $main_menu->term_id,
		) );
	}
	
	// Set front page
	$front_page = get_page_by_title( 'Home' );
	if ( $front_page ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page->ID );
	}
	
	// Set blog page
	$blog_page = get_page_by_title( 'Blog' );
	if ( $blog_page ) {
		update_option( 'page_for_posts', $blog_page->ID );
	}
} );
```

## 📝 Changelog

### 1.3.9 (December 7, 2025)

* **Documentation Improvements**
  * Enhanced external services documentation with clearer privacy and data handling information
  * Added detailed service provider information for theme demo content delivery
  * Improved transparency about data sent to external servers (IP address and demo content requests)
  * Better guidance for users regarding Privacy Policy and Terms of Service for external services

### 1.3.8 (December 7, 2025)

* **WordPress Coding Standards Compliance**
  * Fixed missing translators comments for translatable strings with placeholders
  * Fixed unordered placeholders in translatable strings - now using ordered placeholders (%1$s, %2$s) for better translation support
  * Improved internationalization compliance in WPCLICommands and ImportActions classes
  * Enhanced code quality and WordPress.org standards adherence

### 1.3.7 (December 6, 2025)

* **WordPress Core File Loading Improvements**
  * No longer calling WordPress core files directly - now using proper WordPress hooks and actions
  * Improved compatibility with WordPress core updates and plugin isolation
  * Better adherence to WordPress coding standards for core file access
  * Enhanced plugin stability and maintainability
* Version bump and maintenance update
* Tested with latest WordPress version

### 1.3.6 (December 5, 2025)

* **WordPress.org Standards Compliance Verification**
  * Verified all output buffers are properly closed - all 14 instances of `ob_start()` are correctly paired with `ob_get_clean()`
  * Confirmed proper file/directory location determination - uses WordPress path functions (`plugin_dir_path()`, `plugin_dir_url()`, `wp_upload_dir()`) with no hardcoded paths
  * Validated core file loading practices - only loads acceptable admin includes files with proper `require_once` and immediate usage patterns
  * All code follows WordPress coding standards and best practices
* **Code Quality**
  * Enhanced code documentation and comments
  * Improved error handling and validation throughout

### 1.3.5 (December 4, 2025)

* Version bump and maintenance update
* Tested with latest WordPress version

### 1.3.4 (December 3, 2025)

* Version bump and minor internal improvements for WordPress.org review

### 1.3.3 (December 2, 2025)

* **WordPress.org Standards Compliance**
  * Fixed file/directory location determination - replaced hardcoded ABSPATH usage with proper WordPress path functions (`trailingslashit()` and proper validation)
  * Improved core file loading - added `is_readable()` checks and better error handling for WordPress core includes
  * Enhanced security documentation - added comprehensive comments explaining json_decode() sanitization flow
  * All fixes follow WordPress coding standards and best practices for plugin directory

### 1.3.0 (December 2, 2025)

* Version bump and general improvements

### 1.2.7 (November 23, 2025)

* **API Demo Fetch Improvements**
  * Added cache validation for API-fetched demos - automatically detects and clears invalid cache entries
  * Enhanced error logging and debugging for API demo fetching process
  * Improved demo registration with detailed debug information
  * Better handling of malformed cached data
  * Added `clear_api_cache()` method to ImportHelper class for manual cache clearing
* **Enhanced Debugging**
  * Added comprehensive debug logging for API response handling
  * Better visibility into demo registration process
  * Improved error messages for troubleshooting API issues

### 1.2.6 (November 23, 2025)

* Initial release of API demo fetching feature

### 1.2.5 (December 25, 2024)

* **Elementor Cache Fix**
  * Added automatic Elementor cache clearing after all imports complete
  * Fixes issue where list widgets on blog/WooCommerce pages don't display correctly after import
  * Ensures Elementor CSS is regenerated with correct imported data
  * Cache is now cleared at the end of import process, not just during Elementor import

### 1.2.4 (November 22, 2025)

* **Code Quality Improvements**
  * Fixed WordPress.DB.SlowDBQuery.slow_db_query_tax_query PHPCS warnings in ElementorImporter class
  * Added proper PHPCS ignore comments for necessary tax_query usage

### 1.2.3 (November 22, 2025)

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

### 1.2.2 (November 20, 2025)

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

### 1.2.0 (November 20, 2025)

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

### 1.1.1 (November 19, 2025)

* Added `show_intro_text` parameter to `socs_display_smart_import()` function for controlling intro text section visibility
* Added `socs/show_intro_text` filter for programmatic control of intro text section visibility
* Added `socs/intro_description_text` filter for customizing the intro description text
* Enhanced customization options for theme developers

### 1.1.0 (November 19, 2025)

* Added `show_smart_import_tabs` parameter to `socs_display_smart_import()` function for controlling tab visibility
* Added `show_file_upload_header` parameter to `socs_display_smart_import()` function for controlling header visibility
* Added `socs/show_smart_import_tabs` filter for programmatic control of smart import tabs visibility
* Added `socs/show_file_upload_header` filter for programmatic control of file upload header visibility
* Enhanced template function with more customization options for theme developers
* Improved flexibility for custom page integrations

### 1.0.0 (November 17, 2025)

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

See [readme.txt](readme.txt) for the complete changelog.

## 🔒 External Services & Privacy

This plugin may connect to external services to fetch demo import configurations and download import files. This functionality is only active when configured by theme authors or developers.

### Demo API Service

This plugin can connect to a remote API service to automatically fetch and register demo import configurations. This feature is used to provide users with a list of available demo imports for their theme.

**What data is sent and when:**

* The plugin sends a GET request to a remote API endpoint to retrieve demo import configurations
* The API URL is constructed from a base URL (configured via the `smartocs/demo_api_base_url` filter) and the current theme name
* The theme name (TextDomain or stylesheet directory name) is included in the API URL path
* This request is made when the plugin initializes and checks for available demo imports
* The API response is cached for 1 hour (configurable via `smartocs/demo_api_cache_duration` filter) to reduce external requests
* No personal data, user information, or site-specific data is transmitted to the API service

**Example API URL format:**
```
{base_url}/{theme_name}/demos.json
```

### About External Domains

This plugin does not include, define, or ship with any default API domain, endpoint, or external server.  
The plugin code contains no hard-coded URLs.

All external domains used by the plugin come entirely from the theme author or developer via the `smartocs/demo_api_base_url` filter.

If a theme developer does not provide an API base URL, the plugin will not make any external requests.  
In this case, it operates only with locally bundled or manually provided demo files.

This means the plugin never contacts any external service by itself, and only does so when explicitly configured by the theme developer.

This plugin does not provide a service of its own. It only acts as a bridge to fetch files from URLs defined by the theme author.

**When this service is used:**

* Only when a theme author or developer has configured the API base URL using the `smartocs/demo_api_base_url` filter
* The service is optional and not required for plugin functionality
* If no API base URL is configured, the plugin works normally with locally defined or manually uploaded import files

**Service provider information:**

The API service provider is determined by the theme author or developer who configures the `smartocs/demo_api_base_url` filter. Theme authors are responsible for:

* Providing the API base URL to their users
* Ensuring their API service has appropriate terms of service and privacy policy
* Disclosing any data collection or usage by their API service

**Remote file downloads:**

The plugin may also download import files (ZIP archives, XML files, JSON files, etc.) from remote URLs when:

* Theme authors have configured remote URLs for demo imports
* Users manually specify remote URLs for import files
* The plugin needs to fetch import files from external sources

These downloads are standard HTTP GET requests to retrieve file content. No additional data beyond standard HTTP headers is sent during these requests.

**Privacy and data protection:**

* The plugin does not collect or transmit personal user data to external services
* Only theme identification information (theme name) is included in API requests
* All external requests use WordPress's built-in `wp_remote_get()` function with proper SSL verification
* Theme authors should ensure their API services comply with applicable privacy laws and regulations

**Disabling external services:**

If you wish to disable external API calls, simply do not configure the `smartocs/demo_api_base_url` filter. The plugin will function normally using only locally defined or manually uploaded import files.

## ❓ Frequently Asked Questions

### Where is the "Import Demo Data" page?

You will find the import page in `wp-admin -> Appearance -> Import Demo Data`.

### How do I export my site's data?

**✨ Export everything to a single ZIP file!**

1. Go to `Appearance -> Smart Export`
2. Select the items you want to export:
   * Content (posts, pages, media)
   * Widgets
   * Customizer settings
   * Elementor data (templates, pages, kit settings)
   * **Any plugin settings** - choose from all active plugins
3. Click the "Export" button
4. Download the generated ZIP file

**Key Advantage:** Everything is packaged in one ZIP file, making it super easy to transfer your entire site configuration to another WordPress installation.

### Can I import from a ZIP file?

**✨ Yes! One ZIP file contains everything!**

1. Go to `Appearance -> Import Demo Data`
2. Click on the "Upload ZIP File" tab (if predefined imports are available) or use the manual import section
3. Upload your ZIP file
4. The plugin will automatically:
   * Extract the ZIP file
   * Detect available import files (content.xml, widgets.json, customizer.dat, plugin-settings.json, elementor.json)
   * Import all detected data in the correct order

**Key Advantage:** No need to upload multiple files separately - just one ZIP file and you're done!

### Is this plugin compatible with Elementor?

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

### Can I export settings from any plugin?

**🔌 Yes! Export settings from ANY plugin!**

* **Automatic Detection:** Automatically detects all active plugins and shows them in a checklist
* **Selective Export:** Choose which plugins to include in your export
* **Custom Plugin Options:** Add custom option names or values for any plugin
* **JSON Array Format:** `["option_name_1", "option_name_2"]` - fetches current values from database
* **JSON Object Format:** `{"option_name_1": "value1", "option_name_2": "value2"}` - uses provided values directly
* All plugin settings packaged in one ZIP file

### Where are the demo import files and log files saved?

The files used in the demo import will be saved to the default WordPress uploads directory (e.g., `../wp-content/uploads/2023/03/`).

The log file will also be registered in the `wp-admin -> Media` section, so you can access it easily.

### Important Note

There is no setting to "connect" authors from the demo import file to the existing users in your WP site (like there is in the original WP Importer plugin). All demo content will be imported under the current user.

### Issues with the import

Please visit this [docs page](https://github.com/buildbycj/smart-one-click-setup/blob/master/docs/import-problems.md) for more answers to issues with importing data.

### I can't activate the plugin because of a fatal error. What can I do?

If you see the error "Plugin could not be activated because it triggered a fatal error", this usually means your hosting server is using a very old version of PHP.

This plugin requires PHP version **7.4** or higher (we recommend **8.0** or higher). Please contact your hosting company and ask them to update the PHP version for your site.

## 🤝 Contributing

Contributions are welcome! Please refer to our [contributing guidelines](https://github.com/buildbycj/smart-one-click-setup/blob/main/CONTRIBUTING.md) for more information.

## 📄 License

This plugin is licensed under the GPL v3 or later.

```
Copyright (C) 2025 Chiranjit Hazarika

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👤 Contributors

- **buildbycj**
- **Chiranjit Hazarika**

## 👤 Author

**Chiranjit Hazarika**

- GitHub: [@buildbycj](https://github.com/buildbycj)
- Website: [https://buildbycj.com](https://buildbycj.com)
- Plugin Site: [https://smartocs.buildbycj.com](https://smartocs.buildbycj.com)

## 🙏 Credits

- Built with WordPress coding standards

## 🔗 Resources

- **[WordPress.org Plugin Directory](https://wordpress.org/plugins/smart-one-click-setup/)** - Download, rate, and review the plugin
- **[Plugin Documentation](https://smartocs.buildbycj.com)** - Complete documentation and developer guide
- **[GitHub Repository](https://github.com/buildbycj/smart-one-click-setup)** - Source code, issues, and contributions
- **[SmartOCS Cloud](https://links.buildbycj.com/SmartOCSCloud)** - Premium PHP script for remote demo management

## 📞 Support

For support, feature requests, or bug reports:
- [Open an issue on GitHub](https://github.com/buildbycj/smart-one-click-setup/issues)
- [Visit the WordPress.org support forum](https://wordpress.org/support/plugin/smart-one-click-setup/)
