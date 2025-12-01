<?php
if (!defined('ABSPATH')) exit;

class HGEZLPFCR_Pro_Settings {
    public static function init() {
        // Add PRO section to HGEZLPFCR tab
        add_filter('woocommerce_get_sections_hgezlpfcr', [__CLASS__, 'add_pro_section'], 10);

        // Add PRO settings for the PRO section
        add_filter('woocommerce_get_settings_hgezlpfcr', [__CLASS__, 'get_pro_settings'], 10, 2);
    }

    /**
     * Add PRO section to Fan Courier tab
     *
     * @param array $sections Existing sections
     * @return array Modified sections with PRO added
     */
    public static function add_pro_section($sections) {
        // Add PRO section
        $sections['pro'] = __('PRO Automations', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro');

        if (class_exists('HGEZLPFCR_Logger')) {
            HGEZLPFCR_Logger::log('[HGEZLPFCR PRO] Adding PRO section to FC tab', [
                'sections' => array_keys($sections)
            ]);
        }

        return $sections;
    }

    /**
     * Get PRO settings for the PRO section
     *
     * @param array $settings Existing settings
     * @param string $current_section Current section ID
     * @return array Settings for current section
     */
    public static function get_pro_settings($settings, $current_section) {
        // Only return PRO settings if we're in the PRO section
        if ($current_section !== 'pro') {
            return $settings;
        }

        // Debug logging
        if (class_exists('HGEZLPFCR_Logger')) {
            HGEZLPFCR_Logger::log('[HGEZLPFCR PRO] Loading PRO settings', [
                'section' => $current_section
            ]);
        }

        // Return ONLY PRO settings for this section
        $pro_settings = [
            [
                'title' => __('HgE PRO: Advanced Automations for FAN Courier Romania', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type' => 'title',
                'desc' => 'Configure advanced automations for FAN Courier orders. These features require the HgE: Shipping Zones for FAN Courier Romania plugin to be active.',
                'id' => 'hgezlpfcr_pro_section'
            ],

            // SECTION 1: Auto AWB Generation
            [
                'title' => __('Automatic AWB Generation', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type' => 'title',
                'desc' => 'Configure which order statuses should trigger automatic AWB generation for FAN Courier.',
                'id' => 'hgezlpfcr_pro_auto_awb_section'
            ],
            [
                'title' => 'Enable automatic AWB generation?',
                'id' => 'hgezlpfcr_pro_auto_awb_enabled',
                'type' => 'select',
                'desc' => 'If enabled, AWB will be generated automatically for the selected statuses below',
                'options' => ['no' => 'No', 'yes' => 'Yes'],
                'default' => 'no',
                'css' => 'min-width:300px'
            ],
            [
                'title' => 'Order statuses for automatic AWB generation',
                'desc' => 'Select the order statuses that should trigger automatic AWB generation. When an order reaches one of these statuses, the AWB will be generated automatically.',
                'id' => 'hgezlpfcr_pro_auto_awb_statuses',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'options' => self::get_order_statuses_options(),
                'default' => '',
                'css' => 'min-width:300px'
            ],
            ['type' => 'sectionend', 'id' => 'hgezlpfcr_pro_auto_awb_section'],

            // SECTION 2: Auto Close Orders
            [
                'title' => __('Automatic Order Completion', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type' => 'title',
                'desc' => 'Configure automatic order completion after AWB generation. This action executes AFTER the AWB has been generated.',
                'id' => 'hgezlpfcr_pro_auto_close_section'
            ],
            [
                'title' => 'Automatically complete order after AWB generation?',
                'id' => 'hgezlpfcr_pro_auto_close_order',
                'type' => 'select',
                'desc' => 'If enabled, the order will be automatically moved to the configured status below after the AWB has been successfully generated.',
                'options' => ['no' => 'No', 'yes' => 'Yes'],
                'default' => 'no',
                'css' => 'min-width:300px'
            ],
            [
                'title' => 'Apply only for these initial statuses',
                'desc' => 'Select the order statuses for which automatic completion should apply. Leave empty to apply for all statuses.',
                'id' => 'hgezlpfcr_pro_auto_close_from_statuses',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'options' => self::get_order_statuses_options(),
                'default' => '',
                'css' => 'min-width:300px'
            ],
            [
                'title' => 'New order status',
                'desc' => 'Select the status to which the order should be moved after AWB generation',
                'id' => 'hgezlpfcr_pro_auto_close_status',
                'type' => 'select',
                'options' => self::get_order_statuses_options(),
                'default' => 'completed',
                'css' => 'min-width:300px'
            ],
            [
                'title' => 'Only for orders with SmartBill invoice?',
                'id' => 'hgezlpfcr_pro_auto_close_only_with_invoice',
                'type' => 'checkbox',
                'desc' => 'Order will be completed only if a SmartBill invoice has been issued.',
                'default' => 'no'
            ],
            ['type' => 'sectionend', 'id' => 'hgezlpfcr_pro_auto_close_section'],

            // SECTION 3: Performance & Throttling
            [
                'title' => __('Performance and Server Protection', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type' => 'title',
                'desc' => 'Configure limitations to protect your server and database from overload.',
                'id' => 'hgezlpfcr_pro_performance_section'
            ],
            [
                'title' => 'Asynchronous processing (Action Scheduler)',
                'id' => 'hgezlpfcr_pro_use_async',
                'type' => 'checkbox',
                'desc' => 'Use Action Scheduler for background processing (HIGHLY recommended)',
                'default' => 'yes'
            ],
            [
                'title' => 'Delay between automatic completions (seconds)',
                'id' => 'hgezlpfcr_pro_throttle_delay',
                'type' => 'number',
                'desc' => 'Minimum delay between automatic completions to avoid database overload. 0 = no delay.',
                'default' => '2',
                'custom_attributes' => ['min' => 0, 'max' => 60, 'step' => 1],
                'css' => 'min-width:100px'
            ],
            ['type' => 'sectionend', 'id' => 'hgezlpfcr_pro_performance_section'],

            // SECTION 4: Execution Order Info
            [
                'title' => __('Execution Order', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro'),
                'type' => 'title',
                'desc' => '<strong>Automatic execution order:</strong><br>
                          1️⃣ Order status change is detected<br>
                          2️⃣ If status is in "Automatic AWB generation" list → AWB is generated <em>(async)</em><br>
                          3️⃣ After successful AWB generation → check if order should be completed<br>
                          4️⃣ If "Automatic completion" is enabled and conditions are met → order is moved to new status <em>(async with throttling)</em>',
                'id' => 'hgezlpfcr_pro_execution_order_section'
            ],
            ['type' => 'sectionend', 'id' => 'hgezlpfcr_pro_execution_order_section'],
        ];

        return $pro_settings;
    }

    /**
     * Get all WooCommerce order statuses for dropdown
     */
    public static function get_order_statuses_options() {
        $statuses = wc_get_order_statuses();
        $options = [];
        foreach ($statuses as $slug => $label) {
            // Remove "wc-" prefix from slug
            $slug = str_replace('wc-', '', $slug);
            $options[$slug] = $label;
        }
        return $options;
    }

    /**
     * Get setting value
     */
    public static function get($key, $default = '') {
        return get_option($key, $default);
    }

    /**
     * Check if setting is enabled (yes/no)
     */
    public static function yes($key) {
        return 'yes' === get_option($key, 'no');
    }

    /**
     * Check if auto AWB generation should run for given order status
     */
    public static function should_auto_generate_awb($order_status) {
        // Check if auto AWB is enabled
        if (!self::yes('hgezlpfcr_pro_auto_awb_enabled')) {
            return false;
        }

        // Get configured statuses
        $configured_statuses = get_option('hgezlpfcr_pro_auto_awb_statuses', []);

        // If no statuses configured, don't auto-generate
        if (empty($configured_statuses) || !is_array($configured_statuses)) {
            return false;
        }

        // Check if current order status is in the configured list
        return in_array($order_status, $configured_statuses);
    }

    /**
     * Check if order should be auto-closed after AWB generation
     */
    public static function should_auto_close_order($order_status) {
        // Check if auto close is enabled
        if (!self::yes('hgezlpfcr_pro_auto_close_order')) {
            return false;
        }

        // Get configured "from" statuses
        $from_statuses = get_option('hgezlpfcr_pro_auto_close_from_statuses', []);

        // If specific statuses are configured, check if current status is in the list
        if (!empty($from_statuses) && is_array($from_statuses)) {
            if (!in_array($order_status, $from_statuses)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the target status for auto-close
     */
    public static function get_auto_close_target_status() {
        return get_option('hgezlpfcr_pro_auto_close_status', 'completed');
    }

    /**
     * Check if invoice is required for auto-close
     */
    public static function requires_invoice_for_auto_close() {
        return self::yes('hgezlpfcr_pro_auto_close_only_with_invoice');
    }
}
