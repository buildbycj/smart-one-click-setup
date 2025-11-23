jQuery( function ( $ ) {
	'use strict';

	/**
	 * ---------------------------------------
	 * ------------- DOM Ready ---------------
	 * ---------------------------------------
	 */

	// Move the admin notices inside the appropriate div.
	$( '.js-socs-notice-wrapper' ).appendTo( '.js-socs-admin-notices-container' );

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
	 * @param {string} options.target Selector for specific error container (e.g., '.js-socs-plugin-item-error').
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
			var $noticeContainer = $( '.js-socs-admin-notices-container' );
			if ( $noticeContainer.length ) {
				var noticeHtml = '<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>';
				$noticeContainer.append( noticeHtml );

				// Make notice dismissible.
				$( document ).trigger( 'wp-updates-notice-added' );
			} else {
				// Fallback: try to find any error container.
				var $errorContainer = $( '.js-socs-ajax-response, .js-socs-export-response' ).first();
				if ( $errorContainer.length ) {
					$errorContainer.append( '<img class="socs-imported-content-imported socs-imported-content-imported--error" src="' + socs.plugin_url + 'assets/images/error.svg" alt="Error">' + errorHtml );
				}
			}
		}
	}

	// Auto start the manual import if on the import page and the 'js-socs-auto-start-manual-import' element is present.
	if ( $( '.js-socs-auto-start-manual-import' ).length ) {
		startImport( false );
	}

	// Auto start the import if on the import page and the 'js-socs-auto-start-import' element is present.
	if ( $( '.js-socs-auto-start-import' ).length ) {
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
	$( '.js-socs-start-manual-import' ).on( 'click', function ( event ) {
		event.preventDefault();

		var $button = $( this );

		if ( $button.hasClass( 'socs-button-disabled' ) ) {
			return false;
		}

		// Prepare data for the AJAX call
		var data = new FormData();
		data.append( 'action', 'socs_upload_manual_import_files' );
		data.append( 'security', socs.ajax_nonce );

		if ( $('#socs__content-file-upload').length && $('#socs__content-file-upload').get(0).files.length ) {
			var contentFile = $('#socs__content-file-upload')[0].files[0];
			var contentFileExt = contentFile.name.split('.').pop();

			if ( -1 === [ 'xml' ].indexOf( contentFileExt.toLowerCase() ) ) {
				displayError( socs.texts.content_filetype_warn );

				return false;
			}

			data.append( 'content_file', contentFile );
		}
		if ( $('#socs__widget-file-upload').length && $('#socs__widget-file-upload').get(0).files.length ) {
			var widgetsFile = $('#socs__widget-file-upload')[0].files[0];
			var widgetsFileExt = widgetsFile.name.split('.').pop();

			if ( -1 === [ 'json', 'wie' ].indexOf( widgetsFileExt.toLowerCase() ) ) {
				displayError( socs.texts.widgets_filetype_warn );

				return false;
			}

			data.append( 'widget_file', widgetsFile );
		}
		if ( $('#socs__customizer-file-upload').length && $('#socs__customizer-file-upload').get(0).files.length ) {
			var customizerFile = $('#socs__customizer-file-upload')[0].files[0];
			var customizerFileExt = customizerFile.name.split('.').pop();

			if ( -1 === [ 'dat' ].indexOf( customizerFileExt.toLowerCase() ) ) {
				displayError( socs.texts.customizer_filetype_warn );

				return false;
			}

			data.append( 'customizer_file', customizerFile );
		}
		if ( $('#socs__redux-file-upload').length && $('#socs__redux-file-upload').get(0).files.length ) {
			var reduxFile = $('#socs__redux-file-upload')[0].files[0];
			var reduxFileExt = reduxFile.name.split('.').pop();

			if ( -1 === [ 'json' ].indexOf( reduxFileExt.toLowerCase() ) ) {
				displayError( socs.texts.redux_filetype_warn );

				return false;
			}

			data.append( 'redux_file', reduxFile );
			data.append( 'redux_option_name', $('#socs__redux-option-name').val() );
		}

		$button.addClass( 'socs-button-disabled' );

		// AJAX call to upload all selected import files (content, widgets, customizer and redux).
		$.ajax({
			method: 'POST',
			url: socs.ajax_url,
			data: data,
			contentType: false,
			processData: false,
		})
			.done( function( response ) {
				if ( response.success ) {
					window.location.href = socs.import_url;
				} else {
					displayError( response.data );
					$button.removeClass( 'socs-button-disabled' );
				}
			})
			.fail( function( error ) {
				displayError( error.statusText + ' (' + error.status + ')' );
				$button.removeClass( 'socs-button-disabled' );
			})
	} );

	/**
	 * Remove the files from the manual import upload controls (when clicked on the "cancel" button).
	 */
	$( '.js-socs-cancel-manual-import').on( 'click', function() {
		$( '.socs__file-upload-container-items input[type=file]' ).each( function() {
			$( this ).val( '' ).trigger( 'change' );
		} );
	} );

	/**
	 * Show and hide the file upload label and input on file input change event.
	 */
	$( document ).on( 'change', '.socs__file-upload-container-items input[type=file]', function() {
		var $input = $( this ),
			$label = $input.siblings( 'label' ),
			fileIsSet = false;

		if( this.files && this.files.length > 0 ) {
			$input.removeClass( 'socs-hide-input' ).blur();
			$label.hide();
		} else {
			$input.addClass( 'socs-hide-input' );
			$label.show();
		}

		// Enable or disable the main manual import/cancel buttons.
		$( '.socs__file-upload-container-items input[type=file]' ).each( function() {
			if ( this.files && this.files.length > 0 ) {
				fileIsSet = true;
			}
		} );

		$( '.js-socs-start-manual-import' ).prop( 'disabled', ! fileIsSet );
		$( '.js-socs-cancel-manual-import' ).prop( 'disabled', ! fileIsSet );

	} );

	/**
	 * Grid Layout categories navigation.
	 */
	(function () {
		// Cache selector to all items
		var $items = $( '.js-socs-gl-item-container' ).find( '.js-socs-gl-item' ),
			fadeoutClass = 'socs-is-fadeout',
			fadeinClass = 'socs-is-fadein',
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
				.addClass( 'socs-is-fadein' );

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

		$( '.js-socs-nav-link' ).on( 'click', function( event ) {
			event.preventDefault();

			// Remove 'active' class from the previous nav list items.
			$( this ).parent().siblings().removeClass( 'active' );

			// Add the 'active' class to this nav list item.
			$( this ).parent().addClass( 'active' );

			var category = this.hash.slice(1);

			// show/hide the right items, based on category selected
			var $container = $( '.js-socs-gl-item-container' );
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
	$( '.js-socs-gl-search' ).on( 'keyup', function( event ) {
		if ( 0 < $(this).val().length ) {
			// Hide all items.
			$( '.js-socs-gl-item-container' ).find( '.js-socs-gl-item' ).hide();

			// Show just the ones that have a match on the import name.
			$( '.js-socs-gl-item-container' ).find( '.js-socs-gl-item[data-name*="' + $(this).val().toLowerCase() + '"]' ).show();
		}
		else {
			$( '.js-socs-gl-item-container' ).find( '.js-socs-gl-item' ).show();
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
			url:         socs.ajax_url,
			data:        data,
			contentType: false,
			processData: false,
			beforeSend:  function() {
				$( '.js-socs-importing' ).show();
			}
		})
		.done( function( response ) {
			if ( 'undefined' !== typeof response.status && 'newAJAX' === response.status ) {
				ajaxCall( data );
			}
			else if ( 'undefined' !== typeof response.status && 'customizerAJAX' === response.status ) {
				// Fix for data.set and data.delete, which they are not supported in some browsers.
				var newData = new FormData();
				newData.append( 'action', 'socs_import_customizer_data' );
				newData.append( 'security', socs.ajax_nonce );

				// Set the wp_customize=on only if the plugin filter is set to true.
				if ( true === socs.wp_customize_on ) {
					newData.append( 'wp_customize', 'on' );
				}

				ajaxCall( newData );
			}
			else if ( 'undefined' !== typeof response.status && 'afterAllImportAJAX' === response.status ) {
				// Fix for data.set and data.delete, which they are not supported in some browsers.
				var newData = new FormData();
				newData.append( 'action', 'socs_after_import_data' );
				newData.append( 'security', socs.ajax_nonce );
				ajaxCall( newData );
			}
			else if ( 'undefined' !== typeof response.message ) {
				$( '.js-socs-ajax-response' ).append( response.message );

				if ( 'undefined' !== typeof response.title ) {
					$( '.js-socs-ajax-response-title' ).html( response.title );
				}

				if ( 'undefined' !== typeof response.subtitle ) {
					$( '.js-socs-ajax-response-subtitle' ).html( response.subtitle );
				}

				$( '.js-socs-importing' ).hide();
				$( '.js-socs-imported' ).show();

				// Trigger custom event, when SOCS import is complete.
				$( document ).trigger( 'socsImportComplete' );
			}
			else {
				$( '.js-socs-ajax-response' ).append( '<img class="socs-imported-content-imported socs-imported-content-imported--error" src="' + socs.plugin_url + 'assets/images/error.svg" alt="' + socs.texts.import_failed + '"><p>' + response + '</p>' );
				$( '.js-socs-ajax-response-title' ).html( socs.texts.import_failed );
				$( '.js-socs-ajax-response-subtitle' ).html( '<p>' + socs.texts.import_failed_subtitle + '</p>' );
				$( '.js-socs-importing' ).hide();
				$( '.js-socs-imported' ).show();
			}
		})
		.fail( function( error ) {
			$( '.js-socs-ajax-response' ).append( '<img class="socs-imported-content-imported socs-imported-content-imported--error" src="' + socs.plugin_url + 'assets/images/error.svg" alt="' + socs.texts.import_failed + '"><p>Error: ' + error.statusText + ' (' + error.status + ')' + '</p>' );
			$( '.js-socs-ajax-response-title' ).html( socs.texts.import_failed );
			$( '.js-socs-ajax-response-subtitle' ).html( '<p>' + socs.texts.import_failed_subtitle + '</p>' );
			$( '.js-socs-importing' ).hide();
			$( '.js-socs-imported' ).show();
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
	 * Files for the manual import have already been uploaded in the '.js-socs-start-manual-import' event above.
	 */
	function startImport( selected ) {
		// Prepare data for the AJAX call
		var data = new FormData();
		data.append( 'action', 'socs_import_demo_data' );
		data.append( 'security', socs.ajax_nonce );

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
		$( '#socs-export-form input[type="checkbox"]' ).not( '.socs-export-plugins-list input[type="checkbox"]' ).each( function() {
			var $checkbox = $( this );
			var $label = $checkbox.siblings( '.js-socs-export-checkbox-label' );
			var $button = $checkbox.closest( 'label' );
			
			if ( $checkbox.is( ':checked' ) ) {
				$label.text( socs.texts.selected || 'Selected' );
				$button.removeClass( 'button-secondary' ).addClass( 'button-primary' );
			} else {
				$label.text( socs.texts.select || 'Select' );
				$button.removeClass( 'button-primary' ).addClass( 'button-secondary' );
			}
		});
	}

	// Initialize on DOM ready if export form exists.
	if ( $( '#socs-export-form' ).length ) {
		initializeExportCheckboxes();
	}

	// Toggle checkbox labels on export page.
	// Exclude plugin list checkboxes - they should remain as a simple checklist.
	$( document ).on( 'change', '#socs-export-form input[type="checkbox"]', function() {
		var $checkbox = $( this );
		
		// Skip plugin list checkboxes - they don't have button styling.
		if ( $checkbox.closest( '.socs-export-plugins-list' ).length ) {
			return;
		}
		
		var $label = $checkbox.siblings( '.js-socs-export-checkbox-label' );
		var $button = $checkbox.closest( 'label' );
		
		if ( $checkbox.is( ':checked' ) ) {
			$label.text( socs.texts.selected || 'Selected' );
			$button.removeClass( 'button-secondary' ).addClass( 'button-primary' );
		} else {
			$label.text( socs.texts.select || 'Select' );
			$button.removeClass( 'button-primary' ).addClass( 'button-secondary' );
		}
	});

	// Export form submission.
	$( '#socs-export-form' ).on( 'submit', function( event ) {
		event.preventDefault();

		var $form = $( this );
		var $button = $form.find( '.js-socs-start-export' );
		var $exportContent = $( '.js-socs-export-content' );
		var $exporting = $( '.js-socs-exporting' );
		var $exported = $( '.js-socs-exported' );

		if ( $button.hasClass( 'socs-button-disabled' ) ) {
			return false;
		}

		$button.addClass( 'socs-button-disabled' );
		$exportContent.hide();
		$exporting.show();

		// Prepare form data.
		var formData = new FormData( $form[0] );
		formData.append( 'action', 'socs_export_data' );
		formData.append( 'security', socs.ajax_nonce );

		// Collect custom plugin options.
		var customPluginOptions = {};
		$( '.socs-export-plugin-checkbox:checked' ).each( function() {
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
			url: socs.ajax_url,
			data: formData,
			contentType: false,
			processData: false,
		})
		.done( function( response ) {
			if ( response.success && response.data ) {
				$exporting.hide();
				$exported.show();
				$( '.js-socs-export-response-title' ).text( 'Export Complete!' );
				$( '.js-socs-export-response-subtitle p' ).text( 'Your export package has been generated successfully.' );
				$( '.js-socs-export-response' ).html( '<img class="socs-imported-content-imported socs-imported-content-imported--success" src="' + socs.plugin_url + 'assets/images/success.svg" alt="Successful Export">' );
				$( '.js-socs-download-export' ).attr( 'href', response.data.file_url );
			} else {
				var errorMsg = response.data || 'Export failed. Please try again.';
				displayError( errorMsg );
				$exporting.hide();
				$exportContent.show();
				$button.removeClass( 'socs-button-disabled' );
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
			$button.removeClass( 'socs-button-disabled' );
		});
	});

	/**
	 * ---------------------------------------
	 * ------------- Smart Import -------------
	 * ---------------------------------------
	 */

	// Tab switching.
	$( document ).on( 'click', '.socs-smart-import-tab', function( e ) {
		e.preventDefault();
		var tab = $( this ).data( 'tab' );
		if ( ! tab ) {
			return;
		}
		$( '.socs-smart-import-tab' ).removeClass( 'active' );
		$( this ).addClass( 'active' );
		$( '.socs-smart-import-tab-content' ).removeClass( 'active' );
		$( '.socs-smart-import-tab-content[data-tab-content="' + tab + '"]' ).addClass( 'active' );
	});

	// File upload handler - trigger file input when upload area is clicked.
	$( '.socs-smart-import-upload-area' ).on( 'click', function( e ) {
		// Don't trigger if clicking on the label button.
		if ( ! $( e.target ).closest( 'label' ).length ) {
			$( '#socs__zip-file-upload' ).trigger( 'click' );
		}
	});

	// File upload handler.
	$( '#socs__zip-file-upload' ).on( 'change', function() {
		var fileInput = $( this )[0];
		var fileName = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : '';
		
		if ( fileName ) {
			$( '.js-socs-smart-import-file-name' ).html( '<strong>' + fileName + '</strong>' ).css( 'color', '#2271b1' );
			$( '.js-socs-start-smart-import' ).prop( 'disabled', false );
		} else {
			$( '.js-socs-smart-import-file-name' ).text( '' );
			$( '.js-socs-start-smart-import' ).prop( 'disabled', true );
		}
	});

	// Smart import form submission.
	$( '#socs-smart-import-form' ).on( 'submit', function( event ) {
		event.preventDefault();

		var $form = $( this );
		var $button = $form.find( '.js-socs-start-smart-import' );
		var $importContent = $( '.js-socs-smart-import-content' );
		var $importing = $( '.js-socs-importing' );

		if ( $button.hasClass( 'socs-button-disabled' ) || $button.prop( 'disabled' ) ) {
			return false;
		}

		var zipFile = $( '#socs__zip-file-upload' )[0].files[0];
		if ( ! zipFile ) {
			displayError( 'Please select a ZIP file to import.' );
			return false;
		}

		$button.addClass( 'socs-button-disabled' ).prop( 'disabled', true );
		$importContent.hide();
		$importing.show();

		// Prepare form data.
		var formData = new FormData();
		formData.append( 'action', 'socs_import_zip_file' );
		formData.append( 'security', socs.ajax_nonce );
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
			url: socs.ajax_url,
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
				$button.removeClass( 'socs-button-disabled' ).prop( 'disabled', false );
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
			$button.removeClass( 'socs-button-disabled' ).prop( 'disabled', false );
		});
	});

	// Success buttons handler - ensure they work correctly and don't get caught by other handlers.
	$( document ).on( 'click', '.socs-success-button', function( event ) {
		// Allow default link behavior for success buttons.
		// Stop propagation to prevent other handlers from interfering.
		event.stopPropagation();
		// Don't prevent default - let the link work normally.
		return true;
	});

	// Predefined import handler.
	$( '.js-socs-use-predefined-import' ).on( 'click', function( event ) {
		var $button = $( this );
		
		// Exclude success buttons from import handler.
		if ( $button.hasClass( 'socs-success-button' ) ) {
			return true;
		}
		
		event.preventDefault();

		var importIndex = $button.data( 'import-index' );
		var $importContent = $( '.js-socs-smart-import-content' );
		var $importing = $( '.js-socs-importing' );

		if ( typeof importIndex === 'undefined' ) {
			displayError( 'Invalid import configuration.' );
			return false;
		}

		$button.prop( 'disabled', true );
		$importContent.hide();
		$importing.show();

		// Prepare form data.
		var formData = new FormData();
		formData.append( 'action', 'socs_import_predefined_zip' );
		formData.append( 'security', socs.ajax_nonce );
		formData.append( 'import_index', importIndex );

		// AJAX call to process predefined ZIP file and start import.
		$.ajax({
			method: 'POST',
			url: socs.ajax_url,
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

	var $modal = $( '#socs-custom-options-modal' );
	var $modalTextarea = $( '#socs-custom-options-textarea' );
	var $modalError = $( '.socs-modal-error' );
	var currentPluginSlug = null;
	var currentPluginName = null;

	// Open modal when custom options button is clicked.
	$( document ).on( 'click', '.socs-export-plugin-custom-options-btn', function( e ) {
		e.preventDefault();
		e.stopPropagation();

		var $btn = $( this );
		currentPluginSlug = $btn.data( 'plugin-slug' );
		currentPluginName = $btn.data( 'plugin-name' );

		// Get existing custom options if any.
		var $checkbox = $( '.socs-export-plugin-checkbox[data-plugin-slug="' + currentPluginSlug + '"]' );
		var existingOptions = $checkbox.data( 'custom-options' ) || null;
		var existingOptionsJson = $checkbox.data( 'custom-options-json' ) || null;

		// Set modal content.
		$( '.socs-modal-plugin-name' ).text( currentPluginName );
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

	$( document ).on( 'click', '.socs-modal-close, .socs-modal-cancel, .socs-modal-overlay', function( e ) {
		if ( $( e.target ).hasClass( 'socs-modal-overlay' ) || $( e.target ).closest( '.socs-modal-close, .socs-modal-cancel' ).length ) {
			closeModal();
		}
	});

	// Save custom options.
	$( document ).on( 'click', '.socs-modal-save', function() {
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
		var $checkbox = $( '.socs-export-plugin-checkbox[data-plugin-slug="' + currentPluginSlug + '"]' );
		$checkbox.data( 'custom-options', customOptions );
		$checkbox.data( 'custom-options-json', textareaValue ); // Store raw JSON for editing
		$checkbox.data( 'custom-options-is-object', isObjectFormat ); // Flag to indicate format

		// Update button appearance.
		var $btn = $( '.socs-export-plugin-custom-options-btn[data-plugin-slug="' + currentPluginSlug + '"]' );
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
