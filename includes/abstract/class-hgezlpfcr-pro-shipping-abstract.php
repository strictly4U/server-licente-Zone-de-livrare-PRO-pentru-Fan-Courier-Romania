<?php
/**
 * Abstract Base Class for all PRO Shipping Methods
 *
 * Provides common functionality for all PRO shipping services:
 * - Dynamic pricing via Standard's API Client
 * - Free shipping logic
 * - COD detection and Service ID switching
 * - Weight/dimension calculations
 * - Common form fields
 *
 * All PRO shipping methods MUST extend this class and implement:
 * - get_service_name() - Return FAN Courier service name
 * - get_service_key() - Return service registry key
 *
 * @package HgE_Pro_FAN_Courier
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * HGEZLPFCR_Pro_Shipping_Abstract class
 *
 * @abstract
 */
abstract class HGEZLPFCR_Pro_Shipping_Abstract extends WC_Shipping_Method {

    /**
     * Service key from registry
     * Used to fetch metadata and Service IDs
     *
     * @var string
     */
    protected $service_key = '';

    /**
     * FAN Courier Service ID (standard payment)
     *
     * @var int
     */
    protected $service_id = 0;

    /**
     * FAN Courier Service ID for COD (Cont Colector)
     *
     * @var int
     */
    protected $service_id_cod = 0;

    /**
     * Whether this service supports pickup points
     *
     * @var bool
     */
    protected $has_pickup_point = false;

    /**
     * Constructor
     *
     * @param int $instance_id Shipping zone instance ID
     */
    public function __construct($instance_id = 0) {
        $this->instance_id = absint($instance_id);
        $this->supports    = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];
        $this->enabled = 'yes';

        // Load Service IDs from registry
        $this->load_service_ids();

        // Initialize form fields
        $this->init_form_fields_common();

        // Allow child classes to add custom form fields
        $this->init_custom_form_fields();

        // Initialize instance settings
        $this->init_instance_settings();

        // Get title from settings
        $this->title = $this->get_instance_option('title', $this->method_title);

        // Save settings hook
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Load Service IDs from registry
     */
    protected function load_service_ids() {
        if (empty($this->service_key)) {
            return;
        }

        $service_meta = HGEZLPFCR_Pro_Service_Registry::get_service($this->service_key);
        if (!$service_meta) {
            return;
        }

        // Load Service IDs
        $service_ids = $service_meta['service_ids'];
        $this->service_id = isset($service_ids[0]) ? $service_ids[0] : 0;
        $this->service_id_cod = isset($service_ids[1]) ? $service_ids[1] : 0;

        // Check if this service has pickup point
        $features = $service_meta['features'] ?? [];
        $this->has_pickup_point = in_array('pickup_point', $features, true);
    }

    /**
     * Initialize common form fields for all PRO services
     *
     * Child classes can override init_custom_form_fields() to add more fields
     */
    protected function init_form_fields_common() {
        $this->instance_form_fields = [
            'title' => [
                'title'       => __('Title', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'default'     => $this->method_title,
                'desc_tip'    => true,
            ],
            'enable_dynamic_pricing' => [
                'title'       => __('Dynamic pricing', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type'        => 'checkbox',
                'label'       => __('Enable real-time cost calculation via FAN Courier API', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'default'     => 'yes',
                'description' => __('When enabled, shipping cost will be calculated dynamically based on destination, weight, and dimensions. If disabled or API fails, fixed cost will be used.', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'desc_tip'    => true,
            ],
            'cost_fixed' => [
                'title'       => __('Fixed cost', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type'        => 'price',
                'default'     => '0',
                'description' => __('Fixed shipping cost. Used when dynamic pricing is disabled or API call fails.', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'desc_tip'    => true,
            ],
            'free_shipping_min' => [
                'title'       => __('Free shipping threshold', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type'        => 'price',
                'default'     => '0',
                'description' => __('Minimum cart subtotal for free shipping. Set to 0 to disable free shipping.', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Initialize custom form fields for child classes
     *
     * Override this method in child classes to add service-specific settings
     */
    protected function init_custom_form_fields() {
        // To be implemented by child classes
    }

    /**
     * Calculate shipping cost
     *
     * This is the main method called by WooCommerce to get shipping rate
     *
     * @param array $package Cart package data
     */
    public function calculate_shipping($package = []) {
        // Check if service is available for this package
        if (!$this->is_service_available($package)) {
            return;
        }

        $enable_dynamic = $this->get_instance_option('enable_dynamic_pricing', 'yes') === 'yes';

        // Check for free shipping first
        $free_shipping_min = (float) $this->get_instance_option('free_shipping_min', 0);
        $cart_total = WC()->cart ? WC()->cart->get_cart_contents_total() : 0;

        if ($free_shipping_min > 0 && $cart_total >= $free_shipping_min) {
            $cost = 0;
            $cost_type = 'free';
        } elseif ($enable_dynamic) {
            // Try dynamic pricing
            $cost = $this->get_dynamic_cost($package);

            if ($cost > 0) {
                $cost_type = 'dynamic';
            } else {
                // Fallback to fixed cost if dynamic fails
                $cost = $this->get_fixed_cost();
                $cost_type = 'fixed_fallback';
            }
        } else {
            // Use fixed cost
            $cost = $this->get_fixed_cost();
            $cost_type = 'fixed';
        }

        // Log calculation
        HGEZLPFCR_Logger::log('PRO Shipping calculation', [
            'service'            => $this->id,
            'service_key'        => $this->service_key,
            'enable_dynamic'     => $enable_dynamic,
            'free_shipping_min'  => $free_shipping_min,
            'cart_total'         => $cart_total,
            'calculated_cost'    => $cost,
            'cost_type'          => $cost_type,
            'destination'        => $package['destination'] ?? [],
        ]);

        // Add shipping rate
        $this->add_rate([
            'id'        => $this->get_rate_id(),
            'label'     => $this->title,
            'cost'      => max(0, $cost),
            'meta_data' => [
                'cost_type'      => $cost_type,
                'service_key'    => $this->service_key,
                'dynamic_pricing' => $enable_dynamic ? 'yes' : 'no',
            ],
        ]);
    }

    /**
     * Get dynamic cost via FAN Courier API
     *
     * REUSES Standard's API Client - NO duplication!
     *
     * @param array $package Cart package data
     * @return float Calculated cost or 0 if fails
     */
    protected function get_dynamic_cost($package) {
        try {
            // Use Standard's API Client
            if (!class_exists('HGEZLPFCR_API_Client')) {
                HGEZLPFCR_Logger::error('PRO: API Client not available', [
                    'service' => $this->id
                ]);
                return 0;
            }

            $api = new HGEZLPFCR_API_Client();

            // Get destination data
            $destination = $package['destination'] ?? [];
            if (empty($destination['city'])) {
                HGEZLPFCR_Logger::log('PRO: Insufficient destination data for dynamic pricing', [
                    'service' => $this->id,
                    'destination' => $destination
                ]);
                return 0;
            }

            // First check if service is available for destination
            $service_check = [
                'service'  => $this->get_service_name(),
                'county'   => $destination['state'] ?? '',
                'locality' => $destination['city'] ?? '',
                'weight'   => $this->calculate_package_weight($package),
                'length'   => HGEZLPFCR_PACKAGE_DEFAULT_LENGTH,
                'width'    => HGEZLPFCR_PACKAGE_DEFAULT_WIDTH,
                'height'   => HGEZLPFCR_PACKAGE_DEFAULT_HEIGHT,
            ];

            $availability = $api->check_service($service_check);
            if (is_wp_error($availability) || empty($availability['available'])) {
                HGEZLPFCR_Logger::log('PRO: Service not available for destination', [
                    'service' => $this->id,
                    'destination' => $destination
                ]);
                return 0;
            }

            // Determine Service ID based on payment method (COD detection)
            $service_id = $this->get_service_id_for_order();

            // Build tariff request
            $params = [
                'service'        => $this->get_service_name(),
                'service_id'     => $service_id,
                'county'         => $destination['state'] ?? '',
                'locality'       => $destination['city'] ?? '',
                'weight'         => $this->calculate_package_weight($package),
                'length'         => HGEZLPFCR_PACKAGE_DEFAULT_LENGTH,
                'width'          => HGEZLPFCR_PACKAGE_DEFAULT_WIDTH,
                'height'         => HGEZLPFCR_PACKAGE_DEFAULT_HEIGHT,
                'declared_value' => WC()->cart ? WC()->cart->get_total('edit') : 0,
            ];

            // Allow child classes to modify tariff params
            $params = $this->modify_tariff_params($params, $package);

            $response = $api->get_tariff($params);

            if (is_wp_error($response)) {
                HGEZLPFCR_Logger::error('PRO: API tariff calculation failed', [
                    'service' => $this->id,
                    'error'   => $response->get_error_message()
                ]);
                return 0;
            }

            return isset($response['price']) ? (float) $response['price'] : 0;

        } catch (Exception $e) {
            HGEZLPFCR_Logger::error('PRO: Dynamic pricing exception', [
                'service'   => $this->id,
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Determine Service ID based on payment method (COD detection)
     *
     * Automatically switches to Cont Colector Service ID for COD orders
     *
     * @return int Service ID
     */
    protected function get_service_id_for_order() {
        // Get chosen payment method from session
        $payment_method = WC()->session ? WC()->session->get('chosen_payment_method') : '';

        // If COD and COD Service ID exists, use it
        if ($payment_method === 'cod' && $this->service_id_cod > 0) {
            HGEZLPFCR_Logger::log('PRO: Using COD Service ID', [
                'service'         => $this->id,
                'service_id_std'  => $this->service_id,
                'service_id_cod'  => $this->service_id_cod,
                'payment_method'  => $payment_method
            ]);
            return $this->service_id_cod;
        }

        return $this->service_id;
    }

    /**
     * Calculate package weight from cart items
     *
     * @param array $package Cart package data
     * @return float Total weight in kg
     */
    protected function calculate_package_weight($package) {
        $weight = 0;

        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $item_weight = $product->get_weight();

            if ($item_weight) {
                $weight += (float) $item_weight * $item['quantity'];
            }
        }

        // Use default weight if cart has no weight set
        return max($weight, HGEZLPFCR_DEFAULT_WEIGHT_KG);
    }

    /**
     * Calculate package dimensions from cart items
     *
     * @param array $package Cart package data
     * @return array ['length', 'width', 'height'] in cm
     */
    protected function calculate_package_dimensions($package) {
        // For now, use default dimensions
        // TODO: Implement smart dimension calculation based on products
        return [
            'length' => HGEZLPFCR_PACKAGE_DEFAULT_LENGTH,
            'width'  => HGEZLPFCR_PACKAGE_DEFAULT_WIDTH,
            'height' => HGEZLPFCR_PACKAGE_DEFAULT_HEIGHT,
        ];
    }

    /**
     * Get fixed cost fallback
     *
     * @return float Fixed cost
     */
    protected function get_fixed_cost() {
        return (float) $this->get_instance_option('cost_fixed', 0);
    }

    /**
     * Check if service is available for package
     *
     * Override this in child classes for service-specific availability checks
     * (e.g., weight restrictions for RedCode, zone restrictions for Express Loco)
     *
     * @param array $package Cart package data
     * @return bool True if available, false otherwise
     */
    protected function is_service_available($package) {
        // Default: service is available
        // Child classes can override for specific restrictions
        return true;
    }

    /**
     * Modify tariff params before API call
     *
     * Override this in child classes to add service-specific parameters
     *
     * @param array $params Tariff parameters
     * @param array $package Cart package data
     * @return array Modified parameters
     */
    protected function modify_tariff_params($params, $package) {
        // Default: no modification
        // Child classes can override
        return $params;
    }

    /**
     * Check if method is available in general
     *
     * @param array $package Cart package data
     * @return bool True if available, false otherwise
     */
    public function is_available($package) {
        // Basic availability check
        if (!parent::is_available($package)) {
            return false;
        }

        // Check if service is enabled in PRO settings
        if (!HGEZLPFCR_Pro_Service_Registry::is_service_enabled($this->service_key)) {
            return false;
        }

        // Service-specific availability check
        if (!$this->is_service_available($package)) {
            return false;
        }

        return true;
    }

    /**
     * Get instance option value
     *
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed Option value
     */
    public function get_instance_option($key, $default = '') {
        if (isset($this->instance_settings[$key])) {
            return $this->instance_settings[$key];
        }
        return $default;
    }

    /**
     * Abstract method: Get FAN Courier service name
     *
     * Must be implemented by child classes
     * Examples: "Standard", "FANbox", "Express Loco 2H", etc.
     *
     * @return string Service name for FAN Courier API
     */
    abstract protected function get_service_name();

    /**
     * Get service registry key
     *
     * Should be overridden by child classes or set in constructor
     *
     * @return string Service key
     */
    protected function get_service_key() {
        return $this->service_key;
    }
}
