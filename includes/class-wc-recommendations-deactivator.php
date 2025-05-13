<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Clear any scheduled hooks and transients.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hooks('wc_recommendations_cleanup_old_data');
        
        // Remove any transients
        delete_transient('wc_recommendations_activation_redirect');
    }
}