<img src="assets/images/logo.svg" alt="Smart One Click Setup Logo" width="80"> 

# Smart One Click Setup

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

One-click import/export for demo content, widgets, customizer, plugin settings, and Elementor data. Perfect for theme authors and site migration.

## 📖 About

Smart One Click Setup is a powerful WordPress plugin that simplifies importing and exporting demo content, theme settings, and plugin configurations. Whether you're a theme author providing a seamless demo import experience or a developer migrating site configurations, this plugin has you covered.

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

### Manual Installation

1. Download the latest release
2. Upload the 'smart-one-click-setup' directory to your '/wp-content/plugins/' directory
3. Activate 'Smart One Click Setup' from your Plugins page

### Once Activated

- **Import page:** `Appearance -> Import Demo Data`
- **Export page:** `Appearance -> Smart Export`

## 📖 Documentation

For detailed documentation, please visit: [https://socs.buildbycj.com](https://socs.buildbycj.com)

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

[Follow this easy guide on how to setup this plugin for your themes!](https://socs.buildbycj.com/#developer-guide)

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

## 👤 Author

**Chiranjit Hazarika**

- GitHub: [@buildbycj](https://github.com/buildbycj)
- Website: [https://buildbycj.com](https://buildbycj.com)
- Plugin Site: [https://socs.buildbycj.com](https://socs.buildbycj.com)

## 🙏 Credits

- Built with WordPress coding standards

## 📞 Support

For support, feature requests, or bug reports, please [open an issue](https://github.com/buildbycj/smart-one-click-setup/issues) on GitHub.
