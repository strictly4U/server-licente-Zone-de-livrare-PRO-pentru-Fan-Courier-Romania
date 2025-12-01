<?php
/**
 * Service Registry - Central management for all PRO shipping services
 *
 * This class provides centralized registration and management of all PRO shipping services.
 * It handles service metadata, dependency checking, and WooCommerce integration.
 *
 * @package HgE_Pro_FAN_Courier
 * @since 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * HGEZLPFCR_Pro_Service_Registry class
 *
 * Manages all PRO shipping services with metadata-driven architecture
 */
class HGEZLPFCR_Pro_Service_Registry {

    /**
     * Available PRO services with metadata
     *
     * Each service contains:
     * - class: PHP class name
     * - file: Path to class file (relative to includes/)
     * - id: WooCommerce shipping method ID
     * - name: Display name
     * - description: Short description
     * - service_ids: Array of FAN Courier Service IDs [standard, cod]
     * - requires: Required classes from Standard plugin
     * - selector: Optional selector class for pickup points
     * - selector_file: Path to selector class file
     * - priority: Display priority (higher = shown first)
     * - features: Array of special features
     *
     * @var array
     */
    private static $services = [
        'fanbox' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_Fanbox',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-fanbox.php',
            'id'            => 'fc_pro_fanbox',
            'name'          => 'FAN Courier FANBox',
            'description'   => 'Livrare în lockere FANBox amplasate în diverse locații (magazine, benzinării, etc.)',
            'service_ids'   => [27, 28], // Standard: 27, COD: 28
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'selector'      => 'HGEZLPFCR_Pro_Fanbox_Selector',
            'selector_file' => 'selectors/class-hgezlpfcr-pro-fanbox-selector.php',
            'priority'      => 10,
            'features'      => ['pickup_point', 'map_selector', 'cod_support'],
        ],
        'express_loco' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_Express_Loco',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-express-loco.php',
            'id'            => 'fc_pro_express_loco',
            'name'          => 'FAN Courier Express Loco 2H',
            'description'   => 'Livrare ultra-rapidă în 2 ore (disponibil în București și zone selectate)',
            'service_ids'   => [0, 0], // TBD from API
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'priority'      => 9,
            'features'      => ['same_day', 'time_restricted', 'zone_restricted'],
        ],
        'redcode' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_Redcode',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-redcode.php',
            'id'            => 'fc_pro_redcode',
            'name'          => 'FAN Courier RedCode',
            'description'   => 'Livrare în aceeași zi pentru colete mici (max 5kg)',
            'service_ids'   => [0, 0], // TBD from API
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'priority'      => 8,
            'features'      => ['same_day', 'weight_restricted', 'zone_restricted'],
        ],
        'paypoint' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_CollectPoint_PayPoint',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-collectpoint-paypoint.php',
            'id'            => 'fc_pro_paypoint',
            'name'          => 'FAN Courier CollectPoint PayPoint',
            'description'   => 'Ridicare colete din rețeaua de puncte PayPoint',
            'service_ids'   => [0, 0], // TBD from API
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'selector'      => 'HGEZLPFCR_Pro_PayPoint_Selector',
            'selector_file' => 'selectors/class-hgezlpfcr-pro-paypoint-selector.php',
            'priority'      => 7,
            'features'      => ['pickup_point', 'cod_support'],
        ],
        'omv' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_CollectPoint_OMV',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-collectpoint-omv.php',
            'id'            => 'fc_pro_omv',
            'name'          => 'FAN Courier CollectPoint OMV/Petrom',
            'description'   => 'Ridicare colete din benzinării OMV și Petrom',
            'service_ids'   => [0, 0], // TBD from API
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'selector'      => 'HGEZLPFCR_Pro_OMV_Selector',
            'selector_file' => 'selectors/class-hgezlpfcr-pro-omv-selector.php',
            'priority'      => 6,
            'features'      => ['pickup_point', 'cod_support'],
        ],
        'produse_albe' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_Produse_Albe',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-produse-albe.php',
            'id'            => 'fc_pro_produse_albe',
            'name'          => 'FAN Courier Produse Albe',
            'description'   => 'Transport specializat pentru electronice și electrocasnice (asigurare obligatorie)',
            'service_ids'   => [0, 0], // TBD from API
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'priority'      => 5,
            'features'      => ['insurance_required', 'category_restricted'],
        ],
        'cargo' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_Cargo',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-cargo.php',
            'id'            => 'fc_pro_cargo',
            'name'          => 'FAN Courier Cargo',
            'description'   => 'Transport pachete mari și grele (palete, mobilier, greutate >30kg)',
            'service_ids'   => [0, 0], // TBD from API
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'priority'      => 4,
            'features'      => ['weight_restricted', 'special_handling'],
        ],
        'export' => [
            'class'         => 'HGEZLPFCR_Pro_Shipping_Export',
            'file'          => 'shipping/class-hgezlpfcr-pro-shipping-export.php',
            'id'            => 'fc_pro_export',
            'name'          => 'FAN Courier Export',
            'description'   => 'Livrări internaționale (Bulgaria, Moldova, Grecia, etc.)',
            'service_ids'   => [0], // Single ID for export
            'requires'      => ['HGEZLPFCR_API_Client', 'HGEZLPFCR_Logger'],
            'priority'      => 3,
            'features'      => ['international', 'customs_required'],
        ],
    ];

    /**
     * Initialize registry
     * Hooks into WooCommerce shipping system
     */
    public static function init() {
        // Register shipping methods with WooCommerce
        add_filter('woocommerce_shipping_methods', [__CLASS__, 'register_shipping_methods'], 20);

        // Load service classes when WooCommerce shipping initializes
        add_action('woocommerce_shipping_init', [__CLASS__, 'load_service_classes'], 20);

        // Add admin scripts for selectors
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_assets']);
    }

    /**
     * Load all enabled service classes
     * Called on woocommerce_shipping_init hook
     */
    public static function load_service_classes() {
        // Load Abstract Base Class FIRST (required by all service classes)
        $abstract_class_file = HGEZLPFCR_PRO_PLUGIN_DIR . 'includes/abstract/class-hgezlpfcr-pro-shipping-abstract.php';
        if (file_exists($abstract_class_file)) {
            require_once $abstract_class_file;
            HGEZLPFCR_Logger::log('PRO: Abstract Base Class loaded');
        } else {
            HGEZLPFCR_Logger::error('PRO: Abstract Base Class not found', [
                'file' => $abstract_class_file
            ]);
            return; // Cannot continue without base class
        }

        foreach (self::$services as $key => $service) {
            // Check if service is enabled
            if (!self::is_service_enabled($key)) {
                continue;
            }

            // Check dependencies from Standard plugin
            if (!self::check_dependencies($service['requires'])) {
                HGEZLPFCR_Logger::error("PRO Service {$key} dependencies not met", [
                    'service' => $key,
                    'requires' => $service['requires']
                ]);
                continue;
            }

            // Load main service class file
            $file_path = HGEZLPFCR_PRO_PLUGIN_DIR . 'includes/' . $service['file'];
            if (file_exists($file_path)) {
                require_once $file_path;

                HGEZLPFCR_Logger::log("PRO Service loaded: {$key}", [
                    'class' => $service['class']
                ]);
            } else {
                HGEZLPFCR_Logger::error("PRO Service file not found: {$key}", [
                    'file' => $file_path
                ]);
            }

            // Load selector class if exists
            if (!empty($service['selector']) && !empty($service['selector_file'])) {
                $selector_path = HGEZLPFCR_PRO_PLUGIN_DIR . 'includes/' . $service['selector_file'];
                if (file_exists($selector_path)) {
                    require_once $selector_path;

                    // Initialize selector
                    if (class_exists($service['selector'])) {
                        $selector_class = $service['selector'];
                        $selector_class::init();
                    }
                }
            }
        }
    }

    /**
     * Register shipping methods with WooCommerce
     *
     * @param array $methods Existing shipping methods
     * @return array Modified shipping methods array
     */
    public static function register_shipping_methods($methods) {
        foreach (self::$services as $key => $service) {
            // Only register if service is enabled AND class exists
            if (self::is_service_enabled($key) && class_exists($service['class'])) {
                $methods[$service['id']] = $service['class'];

                HGEZLPFCR_Logger::log("PRO Service registered: {$key}", [
                    'id' => $service['id'],
                    'class' => $service['class']
                ]);
            }
        }

        return $methods;
    }

    /**
     * Enqueue frontend assets for selectors
     */
    public static function enqueue_frontend_assets() {
        if (!is_checkout()) {
            return;
        }

        // Check if any pickup point service is enabled
        $has_pickup_service = false;
        foreach (self::$services as $key => $service) {
            if (self::is_service_enabled($key) && in_array('pickup_point', $service['features'] ?? [], true)) {
                $has_pickup_service = true;
                break;
            }
        }

        if (!$has_pickup_service) {
            return;
        }

        // Enqueue common checkout CSS
        $css_file = HGEZLPFCR_PRO_PLUGIN_DIR . 'assets/css/pro-checkout.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'hgezlpfcr-pro-checkout',
                HGEZLPFCR_PRO_PLUGIN_URL . 'assets/css/pro-checkout.css',
                [],
                HGEZLPFCR_PRO_VERSION
            );
        }

        // Enqueue common checkout JS
        $js_file = HGEZLPFCR_PRO_PLUGIN_DIR . 'assets/js/pro-checkout.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'hgezlpfcr-pro-checkout',
                HGEZLPFCR_PRO_PLUGIN_URL . 'assets/js/pro-checkout.js',
                ['jquery'],
                HGEZLPFCR_PRO_VERSION,
                true
            );

            // Localize script
            wp_localize_script('hgezlpfcr-pro-checkout', 'hgezlpfcrPro', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('hgezlpfcr_pro_checkout'),
                'i18n'    => [
                    'selectPoint' => __('Please select a pickup point', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                    'loading'     => __('Loading...', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                ],
            ]);
        }
    }

    /**
     * Check if service is enabled
     *
     * @param string $service_key Service key from registry
     * @return bool True if enabled, false otherwise
     */
    public static function is_service_enabled($service_key) {
        return get_option("hgezlpfcr_pro_service_{$service_key}_enabled", 'no') === 'yes';
    }

    /**
     * Check if all dependencies are met
     *
     * @param array $requires Array of required class names
     * @return bool True if all dependencies exist, false otherwise
     */
    private static function check_dependencies($requires) {
        foreach ($requires as $dependency) {
            if (!class_exists($dependency)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get service metadata
     *
     * @param string $key Service key
     * @return array|null Service metadata or null if not found
     */
    public static function get_service($key) {
        return isset(self::$services[$key]) ? self::$services[$key] : null;
    }

    /**
     * Get all services metadata
     *
     * @return array All services
     */
    public static function get_all_services() {
        return self::$services;
    }

    /**
     * Get enabled services
     *
     * @return array Enabled services only
     */
    public static function get_enabled_services() {
        $enabled = [];
        foreach (self::$services as $key => $service) {
            if (self::is_service_enabled($key)) {
                $enabled[$key] = $service;
            }
        }
        return $enabled;
    }

    /**
     * Get Service ID for FAN Courier API
     *
     * @param string $service_key Service key
     * @param bool $is_cod Whether this is a COD order
     * @return int Service ID or 0 if not found
     */
    public static function get_service_id($service_key, $is_cod = false) {
        $service = self::get_service($service_key);
        if (!$service) {
            return 0;
        }

        $service_ids = $service['service_ids'];

        // If COD and COD variant exists, use it
        if ($is_cod && isset($service_ids[1]) && $service_ids[1] > 0) {
            return $service_ids[1];
        }

        // Otherwise use standard variant
        return isset($service_ids[0]) ? $service_ids[0] : 0;
    }
}
