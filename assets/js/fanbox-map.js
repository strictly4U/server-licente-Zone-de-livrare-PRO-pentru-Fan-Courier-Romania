/**
 * FANBox Map Integration
 * Integrates with FAN Courier's external map library for FANBox selection
 *
 * @package HgE_Pro_FAN_Courier
 * @since 2.0.0
 */

(function($) {
	'use strict';

	/**
	 * FANBox Map Handler
	 */
	var HgezlpfcrFanboxMap = {

		/**
		 * Configuration
		 */
		config: {
			methodId: 'fc_pro_fanbox',
			cookieNameFanbox: 'hgezlpfcr_pro_fanbox_name',
			cookieNameAddress: 'hgezlpfcr_pro_fanbox_address',
			cookieExpireDays: 30
		},

		/**
		 * Selected pickup point
		 */
		selectedPickupPoint: null,

		/**
		 * Initialize
		 */
		init: function() {
			var self = this;

			console.log('[FANBox] Initializing FANBox integration');

			// Bind events
			this.bindEvents();

			// Check selected shipping method on load
			this.checkShippingMethod();

			// Also check after short delays to catch dynamic loading
			setTimeout(function() { self.checkShippingMethod(); }, 1000);
			setTimeout(function() { self.checkShippingMethod(); }, 3000);

			// Monitor for external library loading
			this.monitorLibraryLoading();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			var self = this;

			// Listen for shipping method changes
			$(document).on('change', 'input[name^="shipping_method"]', function() {
				self.checkShippingMethod();
			});

			// Update on checkout update
			$(document.body).on('updated_checkout', function() {
				console.log('[FANBox] Checkout updated');
				self.checkShippingMethod();
			});

			// Cart updates
			$(document.body).on('updated_cart_totals updated_shipping_method', function() {
				console.log('[FANBox] Cart/shipping updated');
				self.checkShippingMethod();
			});

			// Listen for map selection events from external library
			window.addEventListener('map:select-point', function(event) {
				self.onFanboxSelected(event.detail.item);
			});

			// Validation on checkout submit
			$(document).on('checkout_place_order', function() {
				return self.validateFanboxSelection();
			});
		},

		/**
		 * Check if FANBox shipping method is selected
		 */
		checkShippingMethod: function() {
			var selectedMethod = this.getSelectedShippingMethod();
			var isFanboxMethod = selectedMethod && selectedMethod.indexOf(this.config.methodId) !== -1;

			console.log('[FANBox] Shipping method check:', {
				selected: selectedMethod,
				isFanbox: isFanboxMethod
			});

			if (isFanboxMethod) {
				this.showFanboxSelector();
				this.updateShippingDestination();
			} else {
				this.hideFanboxSelector();
			}
		},

		/**
		 * Get selected shipping method
		 */
		getSelectedShippingMethod: function() {
			var $selected = $('input[name^="shipping_method"]:checked');
			return $selected.length ? $selected.val() : null;
		},

		/**
		 * Show FANBox selector
		 */
		showFanboxSelector: function() {
			var self = this;

			// Remove existing selector
			$('.hgezlpfcr-pro-fanbox-row').remove();

			// Find insertion point
			var $insertionPoint = this.findInsertionPoint();

			if (!$insertionPoint || !$insertionPoint.length) {
				console.warn('[FANBox] Could not find insertion point');
				return;
			}

			// Create FANBox selector row
			var $fanboxRow = $('<tr class="hgezlpfcr-pro-fanbox-row hgezlpfcr-pro-pickup-selector">')
				.append($('<th>')
					.append($('<button type="button" class="button alt wp-element-button" id="hgezlpfcr-pro-fanbox-map-btn">')
						.text(hgezlpfcrProFanbox.i18n.mapButtonText)
					)
				)
				.append($('<td>')
					.append($('<strong>')
						.append($('<span id="hgezlpfcr-pro-fanbox-details">'))
					)
				);

			$insertionPoint.after($fanboxRow);

			console.log('[FANBox] Selector row added');

			// Bind map button click
			$('#hgezlpfcr-pro-fanbox-map-btn').on('click', function(e) {
				e.preventDefault();
				self.openMap();
			});

			// Show saved selection
			this.displaySavedSelection();

			// Update shipping destination
			this.updateShippingDestination();
		},

		/**
		 * Hide FANBox selector
		 */
		hideFanboxSelector: function() {
			$('.hgezlpfcr-pro-fanbox-row').remove();
			this.clearCookies();
		},

		/**
		 * Find insertion point for selector
		 */
		findInsertionPoint: function() {
			// Try multiple selectors for different themes and layouts
			var selectors = [
				'.woocommerce-shipping-totals tr:last-child',
				'.shop_table tfoot tr:last-child',
				'#shipping_method tr:last-child',
				'.cart-collaterals .shipping tr:last-child',
				'#order_review .shop_table tbody tr:last-child',
				'.cart_totals table tbody tr:last-child'
			];

			for (var i = 0; i < selectors.length; i++) {
				var $point = $(selectors[i]);
				if ($point.length) {
					return $point;
				}
			}

			return null;
		},

		/**
		 * Display saved FANBox selection
		 */
		displaySavedSelection: function() {
			var fanboxName = this.getCookie(this.config.cookieNameFanbox);

			if (fanboxName) {
				$('#hgezlpfcr-pro-fanbox-details').html(decodeURIComponent(fanboxName));
			} else {
				$('#hgezlpfcr-pro-fanbox-details').html('<span style="color: #e74c3c; font-style: italic;">' + hgezlpfcrProFanbox.i18n.noSelection + '</span>');
			}
		},

		/**
		 * Open FANBox map
		 */
		openMap: function() {
			var self = this;

			console.log('[FANBox] Opening map');

			// Check if external library is loaded
			if (typeof window.LoadMapFanBox === 'undefined') {
				console.error('[FANBox] Map library not loaded');
				alert(hgezlpfcrProFanbox.i18n.mapLoadError);
				return;
			}

			// Get map container
			var rootNode = document.getElementById('FANmapDiv');
			if (!rootNode) {
				console.error('[FANBox] FANmapDiv container not found');
				alert(hgezlpfcrProFanbox.i18n.mapLoadError);
				return;
			}

			// Get shipping address for filtering
			var county = this.getShippingCounty();
			var locality = this.getShippingLocality();

			console.log('[FANBox] Opening map with params:', {
				county: county,
				locality: locality,
				selectedPoint: this.selectedPickupPoint
			});

			// Open map with FAN Courier's library
			window.LoadMapFanBox({
				pickUpPoint: this.selectedPickupPoint,
				county: county,
				locality: locality,
				rootNode: rootNode
			});
		},

		/**
		 * Get shipping county
		 */
		getShippingCounty: function() {
			var county = $('#shipping_state').val() ||
				$('#billing_state').val() ||
				$('select[name="shipping_state"] option:selected').text() ||
				'';

			// Remove diacritics
			return String(county).normalize("NFD").replace(/[\u0300-\u036f]/g, "");
		},

		/**
		 * Get shipping locality
		 */
		getShippingLocality: function() {
			var locality = $('#shipping_city').val() ||
				$('#billing_city').val() ||
				$('input[name="shipping_city"]').val() ||
				'';

			// Remove diacritics
			return String(locality).normalize("NFD").replace(/[\u0300-\u036f]/g, "");
		},

		/**
		 * Handle FANBox selection from map
		 */
		onFanboxSelected: function(pickupPoint) {
			console.log('[FANBox] FANBox selected:', pickupPoint);

			this.selectedPickupPoint = pickupPoint;

			// Save to cookies
			this.setCookie(this.config.cookieNameFanbox, pickupPoint.name, this.config.cookieExpireDays);
			this.setCookie(this.config.cookieNameAddress, pickupPoint.countyName + '|' + pickupPoint.localityName, this.config.cookieExpireDays);

			// Update display
			$('#hgezlpfcr-pro-fanbox-details').html(pickupPoint.name);

			// Update shipping destination
			this.updateShippingDestination();

			// Trigger checkout update to save selection
			$('body').trigger('update_checkout');
		},

		/**
		 * Update shipping destination text
		 */
		updateShippingDestination: function() {
			var selectedMethod = this.getSelectedShippingMethod();
			if (!selectedMethod || selectedMethod.indexOf(this.config.methodId) === -1) {
				return;
			}

			var $destination = $('.woocommerce-shipping-destination');
			if (!$destination.length) {
				return;
			}

			var fanboxName = this.getCookie(this.config.cookieNameFanbox);
			var fanboxAddress = this.getCookie(this.config.cookieNameAddress);

			if (fanboxName) {
				// FANBox selected - show FANBox info
				var displayText = hgezlpfcrProFanbox.i18n.deliveryTo + ' <strong>FANBox ' + decodeURIComponent(fanboxName) + '</strong>';

				if (fanboxAddress) {
					var addressParts = decodeURIComponent(fanboxAddress).split('|');
					if (addressParts.length === 2) {
						displayText += '<br><small style="color: #666;">' + addressParts[1] + ', ' + addressParts[0] + '</small>';
					}
				}

				$destination.html(displayText);
			} else {
				// No FANBox selected - show prompt
				var popupHtml = '<a href="#" id="hgezlpfcr-pro-fanbox-popup-link" class="fanbox-popup-trigger">' +
					hgezlpfcrProFanbox.i18n.chooseFromMap + '</a>';
				$destination.html(popupHtml);

				// Bind click event
				$('#hgezlpfcr-pro-fanbox-popup-link').on('click', function(e) {
					e.preventDefault();
					var $mapBtn = $('#hgezlpfcr-pro-fanbox-map-btn');
					if ($mapBtn.length) {
						$mapBtn.trigger('click');
					}
				});
			}
		},

		/**
		 * Validate FANBox selection before order placement
		 */
		validateFanboxSelection: function() {
			var selectedMethod = this.getSelectedShippingMethod();

			if (selectedMethod && selectedMethod.indexOf(this.config.methodId) !== -1) {
				var fanboxName = this.getCookie(this.config.cookieNameFanbox);

				if (!fanboxName) {
					// Show error notice
					$('.woocommerce-NoticeGroup-checkout, .woocommerce-notices-wrapper').first().html(
						'<div class="woocommerce-error" role="alert">' +
						hgezlpfcrProFanbox.i18n.validationError +
						'</div>'
					);

					// Scroll to error
					$('html, body').animate({
						scrollTop: $('.woocommerce-error').offset().top - 100
					}, 500);

					return false;
				}
			}

			return true;
		},

		/**
		 * Set cookie
		 */
		setCookie: function(key, value, days) {
			var d = new Date();
			d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
			var expires = "expires=" + d.toUTCString();
			document.cookie = key + "=" + value + ";" + expires + ";path=/";
		},

		/**
		 * Get cookie
		 */
		getCookie: function(key) {
			var cookie = '';
			document.cookie.split(';').forEach(function(value) {
				if (value.split('=')[0].trim() === key) {
					cookie = value.split('=')[1];
				}
			});
			return cookie || '';
		},

		/**
		 * Clear cookies
		 */
		clearCookies: function() {
			this.setCookie(this.config.cookieNameFanbox, '', -1);
			this.setCookie(this.config.cookieNameAddress, '', -1);
		},

		/**
		 * Monitor for external library loading
		 */
		monitorLibraryLoading: function() {
			var attempts = 0;
			var maxAttempts = 20; // 10 seconds max

			var checkLibrary = function() {
				attempts++;

				if (typeof window.LoadMapFanBox !== 'undefined') {
					console.log('[FANBox] Map library loaded successfully after', attempts, 'attempts');
					return;
				}

				if (attempts < maxAttempts) {
					setTimeout(checkLibrary, 500);
				} else {
					console.error('[FANBox] Map library failed to load after', attempts, 'attempts');
				}
			};

			setTimeout(checkLibrary, 500);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		// Check if config is available
		if (typeof hgezlpfcrProFanbox !== 'undefined') {
			HgezlpfcrFanboxMap.init();
		} else {
			console.error('[FANBox] Configuration not found');
		}
	});

	// Expose globally for debugging
	window.HgezlpfcrFanboxMap = HgezlpfcrFanboxMap;

})(jQuery);
