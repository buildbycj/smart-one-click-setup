<img src="assets/images/logo.svg" alt="Smart One Click Setup Logo" width="80"> 

# Smart One Click Setup

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

One-click import/export for demo content, widgets, customizer, plugin settings, and Elementor data. Perfect for theme authors and site migration.

## 📖 About

Smart One Click Setup is a powerful WordPress plugin designed to simplify the process of importing and exporting demo content, theme settings, and plugin configurations. Whether you're a theme author looking to provide a seamless demo import experience for your users, or a developer needing to migrate site configurations, this plugin has you covered.

**What makes it special?**

- **All-in-One Solution**: Export and import everything in a single ZIP file - no more managing multiple files
- **Theme Author Friendly**: Easy integration with any WordPress theme through simple filters and helper classes
- **Developer Focused**: Comprehensive hooks and filters for complete customization
- **Elementor Ready**: Full support for Elementor templates, pages, and kit settings
- **Universal Plugin Support**: Export settings from any WordPress plugin, not just predefined ones
- **Remote & Local Support**: Import from remote URLs or local theme directories

Perfect for theme authors who want to provide their users with a one-click demo import experience, and for developers who need to migrate complete site configurations between WordPress installations.

## 🎯 Key Features

- **✨ One-Click ZIP Import/Export** - Export everything to a single ZIP file and import from a single ZIP file
- **🎨 Elementor Compatible** - Full Elementor support for templates, page data, and kit settings
- **🔌 Any Plugin Settings Import/Export** - Export settings from ANY plugin, not just predefined ones
- **🌐 Remote File Support** - Import from remote URLs with support for presigned URLs (Amazon S3, etc.)
- **🚀 Smart Import Interface** - Predefined demo imports and manual ZIP upload with tabbed interface
- **⚙️ Developer-Friendly** - Comprehensive hooks and filters, WP-CLI commands, and template functions

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

## 🛠️ For Theme Authors

Setup Smart One Click Setup for your theme and your users will thank you for it!

[Follow this easy guide on how to setup this plugin for your themes!](https://socs.buildbycj.com/#developer-guide)

### Quick Start

```php
use SOCS\ImportHelper;

// Add multiple imports from URLs
ImportHelper::add_multiple( array(
    'https://example.com/demos/demo1.zip',
    'https://example.com/demos/demo2.zip',
) );

// Or add with full details
ImportHelper::add(
    'Demo Import 1',
    'https://example.com/demos/demo1.zip',
    '',  // zip_path (optional)
    'First demo import description',
    'https://example.com/preview1.jpg',
    'https://example.com/demo1'
);

// Or use the filter method
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
socs_display_smart_import( array(
    'wrapper_class'          => 'my-custom-class',
    'show_header'            => false,
    'show_sidebar'            => false,
    'load_plugin_css'         => false,
    'show_smart_import_tabs'  => false,
    'show_file_upload_header' => false,
    'show_intro_text'         => false,
) );
```

## 🤝 Contributing

Contributions are welcome! Please refer to our [contributing guidelines](https://github.com/buildbycj/smart-one-click-setup/blob/main/CONTRIBUTING.md) for more information.

## 📝 Changelog

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

