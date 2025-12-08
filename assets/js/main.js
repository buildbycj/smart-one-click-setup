jQuery( function ( $ ) {
	'use strict';

	/**
	 * ---------------------------------------
	 * ------------- DOM Ready ---------------
	 * ---------------------------------------
	 */

	// Move the admin notices inside the appropriate div.
	$( '.js-smartocs-notice-wrapper' ).appendTo( '.js-smartocs-admin-notices-container' );

	/**
	 * ---------------------------------------
	 * -------- Error Display Functions ------
	 * ---------------------------------------
	 */

	/**
	 * Display error message using plugin's error handling system.
	 *
	 * @param {string} message The error message to display.
	 * @param {Object} options Optional settings for error display.
	 * @param {string} options.target Selector for specific error container (e.g., '.js-smartocs-plugin-item-error').
	 * @param {string} options.type Type of error display: 'notice' (admin notice) or 'inline' (inline error).
	 */
	function displayError( message, options ) {
		options = options || {};
		var $target = options.target ? $( options.target ) : null;
		var errorType = options.type || 'notice';

		// Sanitize message - ensure it's wrapped in paragraph tags if needed.
		var errorHtml = message;
		if ( -1 === errorHtml.indexOf( '<p>' ) ) {
			errorHtml = '<p>' + errorHtml + '</p>';
		}

		if ( errorType === 'inline' && $target && $target.length ) {
			// Display inline error in specific container.
			$target.append( errorHtml );
		} else {
			// Display as admin notice.
			var $noticeContainer = $( '.js-smartocs-admin-notices-container' );
			if ( $noticeContainer.length ) {
				var noticeHtml = '<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>';
				$noticeContainer.append( noticeHtml );

				// Make notice dismissible.
				$( document ).trigger( 'wp-updates-notice-added' );
			} else {
				// Fallback: try to find any error container.
				var $errorContainer = $( '.js-smartocs-ajax-response, .js-smartocs-export-response' ).first();
				if ( $errorContainer.length ) {
					$errorContainer.append( '<img class="smartocs-imported-content-imported smartocs-imported-content-imported--error" src="' + smartocs.plugin_url + 'assets/images/error.svg" alt="Error">' + errorHtml );
				}
			}
		}
	}

	// Auto start the manual import if on the import page and the 'js-smartocs-auto-start-manual-import' element is present.
	if ( $( '.js-smartocs-auto-start-manual-import' ).length ) {
		startImport( false );
	}

	// Auto start the import if on the import page and the 'js-smartocs-auto-start-import' element is present.
	if ( $( '.js-smartocs-auto-start-import' ).length ) {
		var selected = getUrlParameter( 'import' );
		startImport( selected );
	}

	/**
	 * ---------------------------------------
	 * ------------- Events ------------------
	 * ---------------------------------------
	 */

	/**
	 * No predefined demo import button click (manual import).
	 */
	$( '.js-smartocs-start-manual-import' ).on( 'click', function ( event ) {
		event.preventDefault();

		var $button = $( this );

		if ( $button.hasClass( 'smartocs-button-disabled' ) ) {
			return false;
		}

		// Prepare data for the AJAX call
		var data = new FormData();
		data.append( 'action', 'smartocs_upload_manual_import_files' );
		data.append( 'security', smartocs.ajax_nonce );

		if ( $('#smartocs__content-file-upload').length && $('#smartocs__content-file-upload').get(0).files.length ) {
			var contentFile = $('#smartocs__content-file-upload')[0].files[0];
			var contentFileExt = contentFile.name.split('.').pop();

			if ( -1 === [ 'xml' ].indexOf( contentFileExt.toLowerCase() ) ) {
				displayError( smartocs.texts.content_filetype_warn );

				return false;
			}

			data.append( 'content_file', contentFile );
		}
		if ( $('#smartocs__widget-file-upload').length && $('#smartocs__widget-file-upload').get(0).files.length ) {
			var widgetsFile = $('#smartocs__widget-file-upload')[0].files[0];
			var widgetsFileExt = widgetsFile.name.split('.').pop();

			if ( -1 === [ 'json', 'wie' ].indexOf( widgetsFileExt.toLowerCase() ) ) {
				displayError( smartocs.texts.widgets_filetype_warn );

				return false;
			}

			data.append( 'widget_file', widgetsFile );
		}
		if ( $('#smartocs__customizer-file-upload').length && $('#smartocs__customizer-file-upload').get(0).files.length ) {
			var customizerFile = $('#smartocs__customizer-file-upload')[0].files[0];
			var customizerFileExt = customizerFile.name.split('.').pop();

			if ( -1 === [ 'dat' ].indexOf( customizerFileExt.toLowerCase() ) ) {
				displayError( smartocs.texts.customizer_filetype_warn );

				return false;
			}

			data.append( 'customizer_file', customizerFile );
		}
		if ( $('#smartocs__redux-file-upload').length && $('#smartocs__redux-file-upload').get(0).files.length ) {
			var reduxFile = $('#smartocs__redux-file-upload')[0].files[0];
			var reduxFileExt = reduxFile.name.split('.').pop();

			if ( -1 === [ 'json' ].indexOf( reduxFileExt.toLowerCase() ) ) {
				displayError( smartocs.texts.redux_filetype_warn );

				return false;
			}

			data.append( 'redux_file', reduxFile );
			data.append( 'redux_option_name', $('#smartocs__redux-option-name').val() );
		}

		$button.addClass( 'smartocs-button-disabled' );

		// AJAX call to upload all selected import files (content, widgets, customizer and redux).
		$.ajax({
			method: 'POST',
			url: smartocs.ajax_url,
			data: data,
			contentType: false,
			processData: false,
		})
			.done( function( response ) {
				if ( response.success ) {
					window.location.href = smartocs.import_url;
				} else {
					displayError( response.data );
					$button.removeClass( 'smartocs-button-disabled' );
				}
			})
			.fail( function( error ) {
				displayError( error.statusText + ' (' + error.status + ')' );
				$button.removeClass( 'smartocs-button-disabled' );
			})
	} );

	/**
	 * Remove the files from the manual import upload controls (when clicked on the "cancel" button).
	 */
	$( '.js-smartocs-cancel-manual-import').on( 'click', function() {
		$( '.smartocs__file-upload-container-items input[type=file]' ).each( function() {
			$( this ).val( '' ).trigger( 'change' );
		} );
	} );

	/**
	 * Show and hide the file upload label and input on file input change event.
	 */
	$( document ).on( 'change', '.smartocs__file-upload-container-items input[type=file]', function() {
		var $input = $( this ),
			$label = $input.siblings( 'label' ),
			fileIsSet = false;

		if( this.files && this.files.length > 0 ) {
			$input.removeClass( 'smartocs-hide-input' ).blur();
			$label.hide();
		} else {
			$input.addClass( 'smartocs-hide-input' );
			$label.show();
		}

		// Enable or disable the main manual import/cancel buttons.
		$( '.smartocs__file-upload-container-items input[type=file]' ).each( function() {
			if ( this.files && this.files.length > 0 ) {
				fileIsSet = true;
			}
		} );

		$( '.js-smartocs-start-manual-import' ).prop( 'disabled', ! fileIsSet );
		$( '.js-smartocs-cancel-manual-import' ).prop( 'disabled', ! fileIsSet );

	} );

	/**
	 * Grid Layout categories navigation.
	 */
	(function () {
		// Cache selector to all items
		var $items = $( '.js-smartocs-gl-item-container' ).find( '.js-smartocs-gl-item' ),
			fadeoutClass = 'smartocs-is-fadeout',
			fadeinClass = 'smartocs-is-fadein',
			animationDuration = 200;

		// Hide all items.
		var fadeOut = function () {
			var dfd = jQuery.Deferred();

			$items
				.addClass( fadeoutClass );

			setTimeout( function() {
				$items
					.removeClass( fadeoutClass )
					.hide();

				dfd.resolve();
			}, animationDuration );

			return dfd.promise();
		};

		var fadeIn = function ( category, dfd ) {
			var filter = category ? '[data-categories*="' + category + '"]' : 'div';

			if ( 'all' === category ) {
				filter = 'div';
			}

			$items
				.filter( filter )
				.show()
				.addClass( 'smartocs-is-fadein' );

			setTimeout( function() {
				$items
					.removeClass( fadeinClass );

				dfd.resolve();
			}, animationDuration );
		};

		var animate = function ( category ) {
			var dfd = jQuery.Deferred();

			var promise = fadeOut();

			promise.done( function () {
				fadeIn( category, dfd );
			} );

			return dfd;
		};

		$( '.js-smartocs-nav-link' ).on( 'click', function( event ) {
			event.preventDefault();

			// Remove 'active' class from the previous nav list items.
			$( this ).parent().siblings().removeClass( 'active' );

			// Add the 'active' class to this nav list item.
			$( this ).parent().addClass( 'active' );

			var category = this.hash.slice(1);

			// show/hide the right items, based on category selected
			var $container = $( '.js-smartocs-gl-item-container' );
			$container.css( 'min-width', $container.outerHeight() );

			var promise = animate( category );

			promise.done( function () {
				$container.removeAttr( 'style' );
			} );
		} );
	}());


	/**
	 * Grid Layout search functionality.
	 */
	$( '.js-smartocs-gl-search' ).on( 'keyup', function( event ) {
		if ( 0 < $(this).val().length ) {
			// Hide all items.
			$( '.js-smartocs-gl-item-container' ).find( '.js-smartocs-gl-item' ).hide();

			// Show just the ones that have a match on the import name.
			$( '.js-smartocs-gl-item-container' ).find( '.js-smartocs-gl-item[data-name*="' + $(this).val().toLowerCase() + '"]' ).show();
		}
		else {
			$( '.js-smartocs-gl-item-container' ).find( '.js-smartocs-gl-item' ).show();
		}
	} );

	/**
	 * ---------------------------------------
	 * --------Helper functions --------------
	 * ---------------------------------------
	 */

	/**
	 * The main AJAX call, which executes the import process.
	 *
	 * @param FormData data The data to be passed to the AJAX call.
	 */
	function ajaxCall( data ) {
		$.ajax({
			method:      'POST',
			url:         smartocs.ajax_url,
			data:        data,
			contentType: false,
			processData: false,
			beforeSend:  function() {
				$( '.js-smartocs-importing' ).show();
			}
		})
		.done( function( response ) {
			if ( 'undefined' !== typeof response.status && 'newAJAX' === response.status ) {
				ajaxCall( data );
			}
			else if ( 'undefined' !== typeof response.status && 'customizerAJAX' === response.status ) {
				// Fix for data.set and data.delete, which they are not supported in some browsers.
				var newData = new FormData();
				newData.append( 'action', 'smartocs_import_customizer_data' );
				newData.append( 'security', smartocs.ajax_nonce );

				// Set the wp_customize=on only if the plugin filter is set to true.
				if ( true === smartocs.wp_customize_on ) {
					newData.append( 'wp_customize', 'on' );
				}

				ajaxCall( newData );
			}
			else if ( 'undefined' !== typeof response.status && 'afterAllImportAJAX' === response.status ) {
				// Fix for data.set and data.delete, which they are not supported in some browsers.
				var newData = new FormData();
				newData.append( 'action', 'smartocs_after_import_data' );
				newData.append( 'security', smartocs.ajax_nonce );
				ajaxCall( newData );
			}
			else if ( 'undefined' !== typeof response.message ) {
				$( '.js-smartocs-ajax-response' ).append( response.message );

				if ( 'undefined' !== typeof response.title ) {
					$( '.js-smartocs-ajax-response-title' ).html( response.title );
				}

				if ( 'undefined' !== typeof response.subtitle ) {
					$( '.js-smartocs-ajax-response-subtitle' ).html( response.subtitle );
				}

				$( '.js-smartocs-importing' ).hide();
				$( '.js-smartocs-imported' ).show();

				// Trigger custom event, when SMARTOCS import is complete.
				$( document ).trigger( 'smartocsImportComplete' );
			}
			else {
				$( '.js-smartocs-ajax-response' ).append( '<img class="smartocs-imported-content-imported smartocs-imported-content-imported--error" src="' + smartocs.plugin_url + 'assets/images/error.svg" alt="' + smartocs.texts.import_failed + '"><p>' + response + '</p>' );
				$( '.js-smartocs-ajax-response-title' ).html( smartocs.texts.import_failed );
				$( '.js-smartocs-ajax-response-subtitle' ).html( '<p>' + smartocs.texts.import_failed_subtitle + '</p>' );
				$( '.js-smartocs-importing' ).hide();
				$( '.js-smartocs-imported' ).show();
			}
		})
		.fail( function( error ) {
			$( '.js-smartocs-ajax-response' ).append( '<img class="smartocs-imported-content-imported smartocs-imported-content-imported--error" src="' + smartocs.plugin_url + 'assets/images/error.svg" alt="' + smartocs.texts.import_failed + '"><p>Error: ' + error.statusText + ' (' + error.status + ')' + '</p>' );
			$( '.js-smartocs-ajax-response-title' ).html( smartocs.texts.import_failed );
			$( '.js-smartocs-ajax-response-subtitle' ).html( '<p>' + smartocs.texts.import_failed_subtitle + '</p>' );
			$( '.js-smartocs-importing' ).hide();
			$( '.js-smartocs-imported' ).show();
		});
	}

	/**
	 * Unique array helper function.
	 *
	 * @param value
	 * @param index
	 * @param self
	 *
	 * @returns {boolean}
	 */
	function onlyUnique( value, index, self ) {
		return self.indexOf( value ) === index;
	}

	/**
	 * Get the parameter value from the URL.
	 *
	 * @param param
	 * @returns {boolean|string}
	 */
	function getUrlParameter( param ) {
		var sPageURL = window.location.search.substring( 1 ),
			sURLVariables = sPageURL.split( '&' ),
			sParameterName,
			i;

		for ( i = 0; i < sURLVariables.length; i++ ) {
			sParameterName = sURLVariables[ i ].split( '=' );

			if ( sParameterName[0] === param ) {
				return typeof sParameterName[1] === undefined ? true : decodeURIComponent( sParameterName[1] );
			}
		}

		return false;
	}

	/**
	 * Run the main import with a selected predefined demo or with manual files (selected = false).
	 *
	 * Files for the manual import have already been uploaded in the '.js-smartocs-start-manual-import' event above.
	 */
	function startImport( selected ) {
		// Prepare data for the AJAX call
		var data = new FormData();
		data.append( 'action', 'smartocs_import_demo_data' );
		data.append( 'security', smartocs.ajax_nonce );

		if ( selected ) {
			data.append( 'selected', selected );
		}

		// AJAX call to import everything (content, widgets, before/after setup)
		ajaxCall( data );
	}

	/**
	 * ---------------------------------------
	 * ------------- Smart Export -------------
	 * ---------------------------------------
	 */

	// Initialize checkbox button states on page load.
	// Exclude plugin list checkboxes - they should remain as a simple checklist.
	function initializeExportCheckboxes() {
		$( '#smartocs-export-form input[type="checkbox"]' ).not( '.smartocs-export-plugins-list input[type="checkbox"]' ).each( function() {
			var $checkbox = $( this );
			var $label = $checkbox.siblings( '.js-smartocs-export-checkbox-label' );
			var $button = $checkbox.closest( 'label' );
			
			if ( $checkbox.is( ':checked' ) ) {
				$label.text( smartocs.texts.selected || 'Selected' );
				$button.removeClass( 'button-secondary' ).addClass( 'button-primary' );
			} else {
				$label.text( smartocs.texts.select || 'Select' );
				$button.removeClass( 'button-primary' ).addClass( 'button-secondary' );
			}
		});
	}

	// Initialize on DOM ready if export form exists.
	if ( $( '#smartocs-export-form' ).length ) {
		initializeExportCheckboxes();
	}

	// Toggle checkbox labels on export page.
	// Exclude plugin list checkboxes - they should remain as a simple checklist.
	$( document ).on( 'change', '#smartocs-export-form input[type="checkbox"]', function() {
		var $checkbox = $( this );
		
		// Skip plugin list checkboxes - they don't have button styling.
		if ( $checkbox.closest( '.smartocs-export-plugins-list' ).length ) {
			return;
		}
		
		var $label = $checkbox.siblings( '.js-smartocs-export-checkbox-label' );
		var $button = $checkbox.closest( 'label' );
		
		if ( $checkbox.is( ':checked' ) ) {
			$label.text( smartocs.texts.selected || 'Selected' );
			$button.removeClass( 'button-secondary' ).addClass( 'button-primary' );
		} else {
			$label.text( smartocs.texts.select || 'Select' );
			$button.removeClass( 'button-primary' ).addClass( 'button-secondary' );
		}
	});

	// Export form submission.
	$( '#smartocs-export-form' ).on( 'submit', function( event ) {
		event.preventDefault();

		var $form = $( this );
		var $button = $form.find( '.js-smartocs-start-export' );
		var $exportContent = $( '.js-smartocs-export-content' );
		var $exporting = $( '.js-smartocs-exporting' );
		var $exported = $( '.js-smartocs-exported' );

		if ( $button.hasClass( 'smartocs-button-disabled' ) ) {
			return false;
		}

		$button.addClass( 'smartocs-button-disabled' );
		$exportContent.hide();
		$exporting.show();

		// Prepare form data.
		var formData = new FormData( $form[0] );
		formData.append( 'action', 'smartocs_export_data' );
		formData.append( 'security', smartocs.ajax_nonce );

		// Collect custom plugin options.
		var customPluginOptions = {};
		$( '.smartocs-export-plugin-checkbox:checked' ).each( function() {
			var pluginSlug = $( this ).data( 'plugin-slug' );
			var customOptions = $( this ).data( 'custom-options' );
			var isObjectFormat = $( this ).data( 'custom-options-is-object' ) || false;
			
			if ( customOptions !== null && customOptions !== undefined ) {
				var hasOptions = false;
				if ( Array.isArray( customOptions ) && customOptions.length > 0 ) {
					hasOptions = true;
				} else if ( typeof customOptions === 'object' && customOptions !== null && Object.keys( customOptions ).length > 0 ) {
					hasOptions = true;
				}
				
				if ( hasOptions ) {
					// Store both the data and format flag.
					customPluginOptions[ pluginSlug ] = {
						options: customOptions,
						is_object: isObjectFormat
					};
				}
			}
		});

		// Add custom options to form data if any exist.
		if ( Object.keys( customPluginOptions ).length > 0 ) {
			formData.append( 'custom_plugin_options', JSON.stringify( customPluginOptions ) );
		}

		// AJAX call to export data.
		$.ajax({
			method: 'POST',
			url: smartocs.ajax_url,
			data: formData,
			contentType: false,
			processData: false,
		})
		.done( function( response ) {
			if ( response.success && response.data ) {
				$exporting.hide();
				$exported.show();
				$( '.js-smartocs-export-response-title' ).text( 'Export Complete!' );
				$( '.js-smartocs-export-response-subtitle p' ).text( 'Your export package has been generated successfully.' );
				$( '.js-smartocs-export-response' ).html( '<img class="smartocs-imported-content-imported smartocs-imported-content-imported--success" src="' + smartocs.plugin_url + 'assets/images/success.svg" alt="Successful Export">' );
				$( '.js-smartocs-download-export' ).attr( 'href', response.data.file_url );
			} else {
				var errorMsg = response.data || 'Export failed. Please try again.';
				displayError( errorMsg );
				$exporting.hide();
				$exportContent.show();
				$button.removeClass( 'smartocs-button-disabled' );
			}
		})
		.fail( function( jqXHR, textStatus, errorThrown ) {
			var errorMsg = 'Export failed. Please try again.';
			if ( jqXHR.responseJSON && jqXHR.responseJSON.data ) {
				errorMsg = jqXHR.responseJSON.data;
			} else if ( jqXHR.responseText ) {
				try {
					var errorResponse = JSON.parse( jqXHR.responseText );
					if ( errorResponse.data ) {
						errorMsg = errorResponse.data;
					}
				} catch ( e ) {
					// Not JSON, use default message
				}
			}
			displayError( errorMsg );
			$exporting.hide();
			$exportContent.show();
			$button.removeClass( 'smartocs-button-disabled' );
		});
	});

	/**
	 * ---------------------------------------
	 * ------------- Smart Import -------------
	 * ---------------------------------------
	 */

	// Tab switching.
	$( document ).on( 'click', '.smartocs-smart-import-tab', function( e ) {
		e.preventDefault();
		var tab = $( this ).data( 'tab' );
		if ( ! tab ) {
			return;
		}
		$( '.smartocs-smart-import-tab' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		$( '.smartocs-smart-import-tab-content' ).removeClass( 'active' );
		$( '.smartocs-smart-import-tab-content[data-tab-content="' + tab + '"]' ).addClass( 'active' );
	});

	// File upload handler - trigger file input when upload area is clicked.
	$( '.smartocs-smart-import-upload-area' ).on( 'click', function( e ) {
		// Don't trigger if clicking on the label button.
		if ( ! $( e.target ).closest( 'label' ).length ) {
			$( '#smartocs__zip-file-upload' ).trigger( 'click' );
		}
	});

	// File upload handler.
	$( '#smartocs__zip-file-upload' ).on( 'change', function() {
		var fileInput = $( this )[0];
		var fileName = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : '';
		
		if ( fileName ) {
			$( '.js-smartocs-smart-import-file-name' ).html( '<strong>' + fileName + '</strong>' ).css( 'color', '#2271b1' );
			$( '.js-smartocs-start-smart-import' ).prop( 'disabled', false );
		} else {
			$( '.js-smartocs-smart-import-file-name' ).text( '' );
			$( '.js-smartocs-start-smart-import' ).prop( 'disabled', true );
		}
	});

	// Smart import form submission.
	$( '#smartocs-smart-import-form' ).on( 'submit', function( event ) {
		event.preventDefault();

		var $form = $( this );
		var $button = $form.find( '.js-smartocs-start-smart-import' );
		var $importContent = $( '.js-smartocs-smart-import-content' );
		var $importing = $( '.js-smartocs-importing' );

		if ( $button.hasClass( 'smartocs-button-disabled' ) || $button.prop( 'disabled' ) ) {
			return false;
		}

		var zipFile = $( '#smartocs__zip-file-upload' )[0].files[0];
		if ( ! zipFile ) {
			displayError( 'Please select a ZIP file to import.' );
			return false;
		}

		$button.addClass( 'smartocs-button-disabled' ).prop( 'disabled', true );
		$importContent.hide();
		$importing.show();

		// Prepare form data.
		var formData = new FormData();
		formData.append( 'action', 'smartocs_import_zip_file' );
		formData.append( 'security', smartocs.ajax_nonce );
		formData.append( 'zip_file', zipFile );
		
		var beforeHook = $form.find( 'textarea[name="before_import_hook"]' ).val();
		var afterHook = $form.find( 'textarea[name="after_import_hook"]' ).val();
		
		if ( beforeHook ) {
			formData.append( 'before_import_hook', beforeHook );
		}
		if ( afterHook ) {
			formData.append( 'after_import_hook', afterHook );
		}

		// AJAX call to process ZIP file and start import.
		$.ajax({
			method: 'POST',
			url: smartocs.ajax_url,
			data: formData,
			contentType: false,
			processData: false,
		})
		.done( function( response ) {
			if ( response.success ) {
				// Start the import process.
				startImport( false );
			} else {
				var errorMsg = response.data || 'Import failed. Please try again.';
				displayError( errorMsg );
				$importing.hide();
				$importContent.show();
				$button.removeClass( 'smartocs-button-disabled' ).prop( 'disabled', false );
			}
		})
		.fail( function( jqXHR, textStatus, errorThrown ) {
			var errorMsg = 'Import failed. Please try again.';
			if ( jqXHR.responseJSON && jqXHR.responseJSON.data ) {
				errorMsg = jqXHR.responseJSON.data;
			} else if ( jqXHR.responseText ) {
				try {
					var errorResponse = JSON.parse( jqXHR.responseText );
					if ( errorResponse.data ) {
						errorMsg = errorResponse.data;
					}
				} catch ( e ) {
					// Not JSON, use default message
				}
			}
			displayError( errorMsg );
			$importing.hide();
			$importContent.show();
			$button.removeClass( 'smartocs-button-disabled' ).prop( 'disabled', false );
		});
	});

	// Success buttons handler - ensure they work correctly and don't get caught by other handlers.
	$( document ).on( 'click', '.smartocs-success-button', function( event ) {
		// Allow default link behavior for success buttons.
		// Stop propagation to prevent other handlers from interfering.
		event.stopPropagation();
		// Don't prevent default - let the link work normally.
		return true;
	});

	// Predefined import handler.
	$( '.js-smartocs-use-predefined-import' ).on( 'click', function( event ) {
		var $button = $( this );
		
		// Exclude success buttons from import handler.
		if ( $button.hasClass( 'smartocs-success-button' ) ) {
			return true;
		}
		
		event.preventDefault();

		var importIndex = $button.data( 'import-index' );
		var $importContent = $( '.js-smartocs-smart-import-content' );
		var $importing = $( '.js-smartocs-importing' );

		if ( typeof importIndex === 'undefined' ) {
			displayError( 'Invalid import configuration.' );
			return false;
		}

		$button.prop( 'disabled', true );
		$importContent.hide();
		$importing.show();

		// Prepare form data.
		var formData = new FormData();
		formData.append( 'action', 'smartocs_import_predefined_zip' );
		formData.append( 'security', smartocs.ajax_nonce );
		formData.append( 'import_index', importIndex );

		// AJAX call to process predefined ZIP file and start import.
		$.ajax({
			method: 'POST',
			url: smartocs.ajax_url,
			data: formData,
			contentType: false,
			processData: false,
		})
		.done( function( response ) {
			if ( response.success ) {
				// Start the import process.
				startImport( false );
			} else {
				var errorMsg = response.data || 'Import failed. Please try again.';
				displayError( errorMsg );
				$importing.hide();
				$importContent.show();
				$button.prop( 'disabled', false );
			}
		})
		.fail( function( jqXHR, textStatus, errorThrown ) {
			var errorMsg = 'Import failed. Please try again.';
			if ( jqXHR.responseJSON && jqXHR.responseJSON.data ) {
				errorMsg = jqXHR.responseJSON.data;
			} else if ( jqXHR.responseText ) {
				try {
					var errorResponse = JSON.parse( jqXHR.responseText );
					if ( errorResponse.data ) {
						errorMsg = errorResponse.data;
					}
				} catch ( e ) {
					// Not JSON, use default message
				}
			}
			displayError( errorMsg );
			$importing.hide();
			$importContent.show();
			$button.prop( 'disabled', false );
		});
	});

	/**
	 * ---------------------------------------
	 * -------- Custom Plugin Options --------
	 * ---------------------------------------
	 */

	var $modal = $( '#smartocs-custom-options-modal' );
	var $modalTextarea = $( '#smartocs-custom-options-textarea' );
	var $modalError = $( '.smartocs-modal-error' );
	var currentPluginSlug = null;
	var currentPluginName = null;

	// Open modal when custom options button is clicked.
	$( document ).on( 'click', '.smartocs-export-plugin-custom-options-btn', function( e ) {
		e.preventDefault();
		e.stopPropagation();

		var $btn = $( this );
		currentPluginSlug = $btn.data( 'plugin-slug' );
		currentPluginName = $btn.data( 'plugin-name' );

		// Get existing custom options if any.
		var $checkbox = $( '.smartocs-export-plugin-checkbox[data-plugin-slug="' + currentPluginSlug + '"]' );
		var existingOptions = $checkbox.data( 'custom-options' ) || null;
		var existingOptionsJson = $checkbox.data( 'custom-options-json' ) || null;

		// Set modal content.
		$( '.smartocs-modal-plugin-name' ).text( currentPluginName );
		// Use the raw JSON if available, otherwise stringify the options object.
		if ( existingOptionsJson ) {
			$modalTextarea.val( existingOptionsJson );
		} else if ( existingOptions ) {
			$modalTextarea.val( JSON.stringify( existingOptions, null, 2 ) );
		} else {
			$modalTextarea.val( '' );
		}
		$modalError.hide().text( '' );

		// Show modal.
		$modal.fadeIn( 200 );
		$modalTextarea.focus();
	});

	// Close modal.
	function closeModal() {
		$modal.fadeOut( 200 );
		$modalTextarea.val( '' );
		$modalError.hide().text( '' );
		currentPluginSlug = null;
		currentPluginName = null;
	}

	$( document ).on( 'click', '.smartocs-modal-close, .smartocs-modal-cancel, .smartocs-modal-overlay', function( e ) {
		if ( $( e.target ).hasClass( 'smartocs-modal-overlay' ) || $( e.target ).closest( '.smartocs-modal-close, .smartocs-modal-cancel' ).length ) {
			closeModal();
		}
	});

	// Save custom options.
	$( document ).on( 'click', '.smartocs-modal-save', function() {
		if ( ! currentPluginSlug ) {
			return;
		}

		var textareaValue = $modalTextarea.val().trim();
		var customOptions = null;
		var isObjectFormat = false;

		// Validate JSON if not empty.
		if ( textareaValue ) {
			try {
				customOptions = JSON.parse( textareaValue );
				
				// Check if it's an array (option names only) or object (option names with values).
				if ( Array.isArray( customOptions ) ) {
					// Validate each option name is a string.
					for ( var i = 0; i < customOptions.length; i++ ) {
						if ( typeof customOptions[ i ] !== 'string' || customOptions[ i ].trim() === '' ) {
							throw new Error( 'All option names must be non-empty strings.' );
						}
					}
					isObjectFormat = false;
				} else if ( typeof customOptions === 'object' && customOptions !== null ) {
					// Validate object keys are strings.
					for ( var key in customOptions ) {
						if ( ! customOptions.hasOwnProperty( key ) ) {
							continue;
						}
						if ( typeof key !== 'string' || key.trim() === '' ) {
							throw new Error( 'All option names (keys) must be non-empty strings.' );
						}
					}
					isObjectFormat = true;
				} else {
					throw new Error( 'Custom options must be either an array of option names or an object with option names and values.' );
				}
			} catch ( e ) {
				$modalError.text( 'Invalid JSON format: ' + e.message ).show();
				return;
			}
		}

		// Save to checkbox data attribute.
		var $checkbox = $( '.smartocs-export-plugin-checkbox[data-plugin-slug="' + currentPluginSlug + '"]' );
		$checkbox.data( 'custom-options', customOptions );
		$checkbox.data( 'custom-options-json', textareaValue ); // Store raw JSON for editing
		$checkbox.data( 'custom-options-is-object', isObjectFormat ); // Flag to indicate format

		// Update button appearance.
		var $btn = $( '.smartocs-export-plugin-custom-options-btn[data-plugin-slug="' + currentPluginSlug + '"]' );
		var hasOptions = false;
		if ( Array.isArray( customOptions ) && customOptions.length > 0 ) {
			hasOptions = true;
		} else if ( typeof customOptions === 'object' && customOptions !== null && Object.keys( customOptions ).length > 0 ) {
			hasOptions = true;
		}
		
		if ( hasOptions ) {
			$btn.addClass( 'has-custom-options' );
		} else {
			$btn.removeClass( 'has-custom-options' );
		}

		closeModal();
	});

	// Close modal on Escape key.
	$( document ).on( 'keydown', function( e ) {
		if ( e.key === 'Escape' && $modal.is( ':visible' ) ) {
			closeModal();
		}
	});
} );
