<?php
/**
 * Plugin Name: WooCommerce Product Recommendations
 * Plugin URI: https://example.com/woocommerce-product-recommendations
 * Description: Intelligent product recommendations for WooCommerce to increase average order value through personalized suggestions
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wc-recommendations
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('WC_RECOMMENDATIONS_VERSION', '1.0.0');

/**
 * Plugin base file.
 */
define('WC_RECOMMENDATIONS_PLUGIN_FILE', __FILE__);

/**
 * Plugin base directory.
 */
define('WC_RECOMMENDATIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin URL.
 */
define('WC_RECOMMENDATIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
function wc_recommendations_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());
    
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    
    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * The code that runs during plugin activation.
 */
function activate_wc_recommendations() {
    require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-activator.php';
    WC_Recommendations_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wc_recommendations() {
    require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-deactivator.php';
    WC_Recommendations_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wc_recommendations');
register_deactivation_hook(__FILE__, 'deactivate_wc_recommendations');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations.php';

/**
 * Begins execution of the plugin.
 */
function run_wc_recommendations() {
    // Check if WooCommerce is active
    if (!wc_recommendations_is_woocommerce_active()) {
        add_action('admin_notices', 'wc_recommendations_woocommerce_missing_notice');
        return;
    }
    
    $plugin = new WC_Recommendations();
    $plugin->run();
}

/**
 * Admin notice for missing WooCommerce
 */
function wc_recommendations_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('WooCommerce Product Recommendations requires WooCommerce to be installed and active.', 'wc-recommendations'); ?></p>
    </div>
    <?php
}

add_action('plugins_loaded', 'run_wc_recommendations');