/**
 * PRO Checkout JavaScript
 * Common functionality for all PRO shipping services
 *
 * @package HgE_Pro_FAN_Courier
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * PRO Checkout Handler
     */
    var HgezlpfcrProCheckout = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.checkSelectedShippingMethod();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Listen for shipping method changes
            $('body').on('change', 'input[name^="shipping_method"]', function() {
                self.onShippingMethodChange();
            });

            // Update on checkout update
            $(document.body).on('updated_checkout', function() {
                self.checkSelectedShippingMethod();
            });
        },

        /**
         * Check selected shipping method
         */
        checkSelectedShippingMethod: function() {
            var selectedMethod = $('input[name^="shipping_method"]:checked').val();

            if (!selectedMethod) {
                return;
            }

            // Show/hide pickup point selectors based on selected method
            this.togglePickupPointSelectors(selectedMethod);
        },

        /**
         * Handle shipping method change
         */
        onShippingMethodChange: function() {
            this.checkSelectedShippingMethod();
        },

        /**
         * Toggle pickup point selectors
         */
        togglePickupPointSelectors: function(selectedMethod) {
            // Hide all pickup point selectors
            $('.hgezlpfcr-pro-pickup-selector').hide();

            // Show selector for selected method if it has pickup points
            if (selectedMethod) {
                var methodId = selectedMethod.split(':')[0]; // Extract method ID before instance

                // FANBox
                if (methodId === 'fc_pro_fanbox') {
                    $('#hgezlpfcr-fanbox-selector').show();
                }

                // PayPoint
                if (methodId === 'fc_pro_paypoint') {
                    $('#hgezlpfcr-paypoint-selector').show();
                }

                // OMV/Petrom
                if (methodId === 'fc_pro_omv') {
                    $('#hgezlpfcr-omv-selector').show();
                }
            }
        },

        /**
         * Validate pickup point selection before order placement
         */
        validatePickupPoint: function() {
            var selectedMethod = $('input[name^="shipping_method"]:checked').val();

            if (!selectedMethod) {
                return true;
            }

            var methodId = selectedMethod.split(':')[0];
            var requiresPickupPoint = ['fc_pro_fanbox', 'fc_pro_paypoint', 'fc_pro_omv'];

            if (requiresPickupPoint.indexOf(methodId) !== -1) {
                var selectedPoint = this.getSelectedPickupPoint(methodId);

                if (!selectedPoint) {
                    alert(hgezlpfcrPro.i18n.selectPoint || 'Please select a pickup point');
                    return false;
                }
            }

            return true;
        },

        /**
         * Get selected pickup point for method
         */
        getSelectedPickupPoint: function(methodId) {
            var inputName = '';

            switch(methodId) {
                case 'fc_pro_fanbox':
                    inputName = 'hgezlpfcr_fanbox_id';
                    break;
                case 'fc_pro_paypoint':
                    inputName = 'hgezlpfcr_paypoint_id';
                    break;
                case 'fc_pro_omv':
                    inputName = 'hgezlpfcr_omv_id';
                    break;
            }

            if (inputName) {
                return $('input[name="' + inputName + '"]').val();
            }

            return null;
        },

        /**
         * Show loading state
         */
        showLoading: function($element) {
            $element.addClass('hgezlpfcr-pro-loading');
        },

        /**
         * Hide loading state
         */
        hideLoading: function($element) {
            $element.removeClass('hgezlpfcr-pro-loading');
        },

        /**
         * Show error message
         */
        showError: function($container, message) {
            var $error = $('<div class="hgezlpfcr-pro-error"></div>').text(message);
            $container.find('.hgezlpfcr-pro-error').remove();
            $container.append($error);
        },

        /**
         * Hide error message
         */
        hideError: function($container) {
            $container.find('.hgezlpfcr-pro-error').remove();
        }
    };

    /**
     * Validate before checkout
     */
    $('form.checkout').on('checkout_place_order', function() {
        return HgezlpfcrProCheckout.validatePickupPoint();
    });

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        HgezlpfcrProCheckout.init();
    });

    // Expose globally for extensions
    window.HgezlpfcrProCheckout = HgezlpfcrProCheckout;

})(jQuery);
