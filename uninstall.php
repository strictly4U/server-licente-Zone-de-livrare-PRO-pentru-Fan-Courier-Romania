<?php
/**
 * Uninstall script for HgE PRO: Additional Shipping Services for FAN Courier Romania
 * Fired when the plugin is deleted via WordPress admin
 *
 * @package HgE_Pro_FAN_Courier
 * @since 2.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all PRO options from wp_options table
$pro_options = [
    // Activation and notices
    'hgezlpfcr_pro_activation_redirect',

    // Automation settings
    'hgezlpfcr_pro_auto_awb_enabled',
    'hgezlpfcr_pro_auto_awb_statuses',
    'hgezlpfcr_pro_auto_close_order',
    'hgezlpfcr_pro_auto_close_from_statuses',
    'hgezlpfcr_pro_auto_close_status',
    'hgezlpfcr_pro_auto_close_only_with_invoice',
    'hgezlpfcr_pro_use_async',
    'hgezlpfcr_pro_throttle_delay',

    // Service enable/disable toggles
    'hgezlpfcr_pro_service_fanbox_enabled',
    'hgezlpfcr_pro_service_express_loco_enabled',
    'hgezlpfcr_pro_service_redcode_enabled',
    'hgezlpfcr_pro_service_paypoint_enabled',
    'hgezlpfcr_pro_service_omv_enabled',
    'hgezlpfcr_pro_service_produse_albe_enabled',
    'hgezlpfcr_pro_service_cargo_enabled',
    'hgezlpfcr_pro_service_export_enabled',

    // Service settings (cost, free shipping, etc.)
    'hgezlpfcr_pro_service_fanbox_cost_fixed',
    'hgezlpfcr_pro_service_fanbox_free_shipping_min',
    'hgezlpfcr_pro_service_express_loco_cost_fixed',
    'hgezlpfcr_pro_service_express_loco_free_shipping_min',
    'hgezlpfcr_pro_service_redcode_cost_fixed',
    'hgezlpfcr_pro_service_redcode_free_shipping_min',
    'hgezlpfcr_pro_service_paypoint_cost_fixed',
    'hgezlpfcr_pro_service_paypoint_free_shipping_min',
    'hgezlpfcr_pro_service_omv_cost_fixed',
    'hgezlpfcr_pro_service_omv_free_shipping_min',
    'hgezlpfcr_pro_service_produse_albe_cost_fixed',
    'hgezlpfcr_pro_service_produse_albe_free_shipping_min',
    'hgezlpfcr_pro_service_cargo_cost_fixed',
    'hgezlpfcr_pro_service_cargo_free_shipping_min',
    'hgezlpfcr_pro_service_export_cost_fixed',
    'hgezlpfcr_pro_service_export_free_shipping_min',
];

foreach ($pro_options as $option) {
    delete_option($option);
}

// Delete all PRO transients
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
    WHERE option_name LIKE '_transient_hgezlpfcr_pro_%'
    OR option_name LIKE '_transient_timeout_hgezlpfcr_pro_%'"
);

// Optional: Delete PRO post meta from orders
// IMPORTANT: Commented out by default to preserve historical data
// Uncomment if you want complete cleanup including order history
/*
$pro_meta_keys = [
    '_hgezlpfcr_pro_awb_generated',
    '_hgezlpfcr_pro_auto_closed',
    '_hgezlpfcr_pro_closed_from_status',
    '_hgezlpfcr_pro_closed_to_status',
    '_hgezlpfcr_pro_closed_awb',
    '_fc_fanbox_id',
    '_fc_fanbox_name',
    '_fc_fanbox_address',
    '_fc_paypoint_id',
    '_fc_paypoint_name',
    '_fc_paypoint_address',
    '_fc_omv_id',
    '_fc_omv_name',
    '_fc_omv_address',
];

foreach ($pro_meta_keys as $meta_key) {
    $wpdb->delete(
        $wpdb->postmeta,
        ['meta_key' => $meta_key],
        ['%s']
    );
}
*/

// Clean up scheduled actions from Action Scheduler
// Only if Action Scheduler is available
if (function_exists('as_unschedule_all_actions')) {
    as_unschedule_all_actions('hgezlpfcr_pro_auto_close_order_async');
    as_unschedule_all_actions('hgezlpfcr_generate_awb_async'); // This is shared with Standard, be careful!
}

// Optional: Delete shipping method instances from zones
// IMPORTANT: Commented out by default to preserve zone configurations
// Uncomment if you want complete cleanup
/*
if (class_exists('WC_Shipping_Zones')) {
    $zones = WC_Shipping_Zones::get_zones();
    $pro_method_ids = [
        'fc_pro_fanbox',
        'fc_pro_express_loco',
        'fc_pro_redcode',
        'fc_pro_paypoint',
        'fc_pro_omv',
        'fc_pro_produse_albe',
        'fc_pro_cargo',
        'fc_pro_export',
    ];

    foreach ($zones as $zone_data) {
        $zone = WC_Shipping_Zones::get_zone($zone_data['id']);
        $shipping_methods = $zone->get_shipping_methods();

        foreach ($shipping_methods as $instance_id => $shipping_method) {
            if (in_array($shipping_method->id, $pro_method_ids, true)) {
                $zone->delete_shipping_method($instance_id);
            }
        }
    }
}
*/

// Clear any cached data
wp_cache_flush();
