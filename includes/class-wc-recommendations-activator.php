<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Activator {

    /**
     * Activate the plugin.
     *
     * Creates database tables and sets default options.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_database_tables();
        self::create_default_options();
    }
    
    /**
     * Create database tables.
     *
     * @since    1.0.0
     */
    private static function create_database_tables() {
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-database.php';
        WC_Recommendations_Database::create_tables();
    }
    
    /**
     * Create default options.
     *
     * @since    1.0.0
     */
    private static function create_default_options() {
        // Default settings
        $default_settings = array(
            'show_on_product'     => 'yes',
            'show_on_cart'        => 'yes',
            'show_on_checkout'    => 'no',
            'show_on_thankyou'    => 'yes',
            'product_page_type'   => 'frequently_bought',
            'cart_page_type'      => 'also_viewed',
            'checkout_page_type'  => 'personalized',
            'thankyou_page_type'  => 'similar',
            'layout'              => 'grid',
            'columns'             => 4,
            'limit'               => 4,
            'track_anonymous'     => 'yes',
            'track_logged_in'     => 'yes',
            'privacy_compliant'   => 'yes',
            'data_retention_days' => 90
        );
        
        // Add options only if they don't exist
        if (get_option('wc_recommendations_settings') === false) {
            add_option('wc_recommendations_settings', $default_settings);
        }
        
        // Set version
        update_option('wc_recommendations_version', WC_RECOMMENDATIONS_VERSION);
        
        // Create a transient to redirect to settings page
        set_transient('wc_recommendations_activation_redirect', true, 30);
    }
}