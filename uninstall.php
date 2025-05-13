<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define database tables to drop
function wc_recommendations_drop_tables() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'wc_recommendation_interactions',
        $wpdb->prefix . 'wc_recommendation_purchases',
        $wpdb->prefix . 'wc_recommendation_tracking',
        $wpdb->prefix . 'wc_recommendation_ab_tests',
        $wpdb->prefix . 'wc_recommendation_ab_impressions',
        $wpdb->prefix . 'wc_recommendation_ab_conversions'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Delete options
function wc_recommendations_delete_options() {
    delete_option('wc_recommendations_settings');
    delete_option('wc_recommendations_db_version');
    delete_option('wc_recommendations_version');
    delete_option('wc_recommendations_show_welcome');
    delete_option('wc_recommendation_ab_tests');
    delete_option('wc_recommendations_metrics');
}

// Check if we should completely uninstall
$settings = get_option('wc_recommendations_settings', array());
$complete_uninstall = isset($settings['complete_uninstall']) ? $settings['complete_uninstall'] : false;

if ($complete_uninstall === 'yes') {
    // Drop database tables
    wc_recommendations_drop_tables();
    
    // Delete options
    wc_recommendations_delete_options();
}