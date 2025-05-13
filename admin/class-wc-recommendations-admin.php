<?php
/**
 * Admin functionality.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Admin functionality.
 *
 * Handles admin-specific functionality.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Admin {

    /**
     * Initialize admin hooks.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Admin notices for onboarding
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Settings link in plugins page
        add_filter('plugin_action_links_woocommerce-product-recommendations/woocommerce-product-recommendations.php', array($this, 'add_action_links'));
        
        // Admin redirect after activation
        add_action('admin_init', array($this, 'activation_redirect'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_wc_recommendations_save_settings', array($this, 'save_settings'));
    }
    
    /**
     * Enqueue admin styles.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Only enqueue on our plugin pages
        if ($screen && strpos($screen->id, 'wc-recommendations') !== false) {
            wp_enqueue_style('wc-recommendations-admin', plugin_dir_url(__FILE__) . 'css/wc-recommendations-admin.css', array(), WC_RECOMMENDATIONS_VERSION, 'all');
        }
    }
    
    /**
     * Enqueue admin scripts.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only enqueue on our plugin pages
        if ($screen && strpos($screen->id, 'wc-recommendations') !== false) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array(), '3.7.1', true);
            wp_enqueue_script('wc-recommendations-admin', plugin_dir_url(__FILE__) . 'js/wc-recommendations-admin.js', array('jquery', 'chart-js'), WC_RECOMMENDATIONS_VERSION, true);
            
            wp_localize_script('wc-recommendations-admin', 'wc_recommendations_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => array(
                    'settings' => wp_create_nonce('wc_recommendations_settings'),
                    'analytics' => wp_create_nonce('wc_recommendations_analytics'),
                    'test_results' => wp_create_nonce('wc_recommendations_test_results')
                ),
                'i18n' => array(
                    'saved' => __('Settings saved successfully.', 'wc-recommendations'),
                    'error' => __('An error occurred.', 'wc-recommendations')
                )
            ));
        }
    }
    
    /**
     * Add admin menu items.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WC Recommendations', 'wc-recommendations'),
            __('Recommendations', 'wc-recommendations'),
            'manage_woocommerce',
            'wc-recommendations',
            array($this, 'display_settings_page'),
            'dashicons-feedback',
            58 // After WooCommerce
        );
        
        add_submenu_page(
            'wc-recommendations',
            __('Settings', 'wc-recommendations'),
            __('Settings', 'wc-recommendations'),
            'manage_woocommerce',
            'wc-recommendations',
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            'wc-recommendations',
            __('Analytics', 'wc-recommendations'),
            __('Analytics', 'wc-recommendations'),
            'manage_woocommerce',
            'wc-recommendations-analytics',
            array($this, 'display_analytics_page')
        );
        
        add_submenu_page(
            'wc-recommendations',
            __('A/B Testing', 'wc-recommendations'),
            __('A/B Testing', 'wc-recommendations'),
            'manage_woocommerce',
            'wc-recommendations-testing',
            array($this, 'display_testing_page')
        );
    }
    
    /**
     * Display settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Include settings page template
        include_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Display analytics page.
     *
     * @since    1.0.0
     */
    public function display_analytics_page() {
        // Get date range
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-30 days'));
        
        // Include analytics page template
        include_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/views/analytics.php';
    }
    
    /**
     * Display A/B testing page.
     *
     * @since    1.0.0
     */
    public function display_testing_page() {
        // Get tests
        $tests = get_option('wc_recommendation_ab_tests', array());
        
        // Include testing page template
        include_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/views/testing.php';
    }
    
    /**
     * Add action links on plugins page.
     *
     * @since    1.0.0
     * @param    array    $links    Plugin action links
     * @return   array              Modified action links
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-recommendations') . '">' . __('Settings', 'wc-recommendations') . '</a>',
            '<a href="' . admin_url('admin.php?page=wc-recommendations-analytics') . '">' . __('Analytics', 'wc-recommendations') . '</a>'
        );
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Admin notices for onboarding.
     *
     * @since    1.0.0
     */
    public function admin_notices() {
        // Check if we need to show welcome notice
        if (get_option('wc_recommendations_show_welcome', false)) {
            ?>
            <div class="notice notice-info is-dismissible">
                <h3><?php _e('Thank you for installing WooCommerce Product Recommendations!', 'wc-recommendations'); ?></h3>
                <p><?php _e('Get started by visiting the settings page to configure how recommendations are displayed in your store.', 'wc-recommendations'); ?></p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wc-recommendations'); ?>" class="button button-primary"><?php _e('Go to Settings', 'wc-recommendations'); ?></a>
                    <a href="#" class="button wc-recommendations-dismiss-welcome"><?php _e('Dismiss', 'wc-recommendations'); ?></a>
                </p>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('.wc-recommendations-dismiss-welcome').on('click', function(e) {
                        e.preventDefault();
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: 'wc_recommendations_dismiss_welcome'
                            }
                        });
                        $(this).closest('.notice').fadeOut();
                    });
                });
            </script>
            <?php
            
            // Delete the option, so the notice is only shown once
            delete_option('wc_recommendations_show_welcome');
        }
    }
    
    /**
     * Redirect after activation.
     *
     * @since    1.0.0
     */
    public function activation_redirect() {
        // Check if we should redirect
        if (get_transient('wc_recommendations_activation_redirect')) {
            delete_transient('wc_recommendations_activation_redirect');
            
            // Set welcome notice flag
            update_option('wc_recommendations_show_welcome', true);
            
            // Redirect to settings page
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=wc-recommendations'));
                exit;
            }
        }
    }
    
    /**
     * Save settings via AJAX.
     *
     * @since    1.0.0
     */
    public function save_settings() {
        // Check admin capability
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_settings')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Get settings data
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        // Sanitize settings
        $sanitized_settings = array();
        
        // Display settings
        $sanitized_settings['show_on_product'] = isset($settings['show_on_product']) ? sanitize_text_field($settings['show_on_product']) : 'no';
        $sanitized_settings['show_on_cart'] = isset($settings['show_on_cart']) ? sanitize_text_field($settings['show_on_cart']) : 'no';
        $sanitized_settings['show_on_checkout'] = isset($settings['show_on_checkout']) ? sanitize_text_field($settings['show_on_checkout']) : 'no';
        $sanitized_settings['show_on_thankyou'] = isset($settings['show_on_thankyou']) ? sanitize_text_field($settings['show_on_thankyou']) : 'no';
        
        // Algorithm settings
        $sanitized_settings['product_page_type'] = isset($settings['product_page_type']) ? sanitize_text_field($settings['product_page_type']) : 'frequently_bought';
        $sanitized_settings['cart_page_type'] = isset($settings['cart_page_type']) ? sanitize_text_field($settings['cart_page_type']) : 'also_viewed';
        $sanitized_settings['checkout_page_type'] = isset($settings['checkout_page_type']) ? sanitize_text_field($settings['checkout_page_type']) : 'personalized';
        $sanitized_settings['thankyou_page_type'] = isset($settings['thankyou_page_type']) ? sanitize_text_field($settings['thankyou_page_type']) : 'similar';
        
        // Layout settings
        $sanitized_settings['layout'] = isset($settings['layout']) ? sanitize_text_field($settings['layout']) : 'grid';
        $sanitized_settings['columns'] = isset($settings['columns']) ? max(1, min(6, intval($settings['columns']))) : 4;
        $sanitized_settings['limit'] = isset($settings['limit']) ? max(1, min(12, intval($settings['limit']))) : 4;
        
        // Tracking settings
        $sanitized_settings['track_anonymous'] = isset($settings['track_anonymous']) ? sanitize_text_field($settings['track_anonymous']) : 'yes';
        $sanitized_settings['track_logged_in'] = isset($settings['track_logged_in']) ? sanitize_text_field($settings['track_logged_in']) : 'yes';
        $sanitized_settings['privacy_compliant'] = isset($settings['privacy_compliant']) ? sanitize_text_field($settings['privacy_compliant']) : 'yes';
        $sanitized_settings['data_retention_days'] = isset($settings['data_retention_days']) ? max(1, intval($settings['data_retention_days'])) : 90;
        
        // Save settings
        update_option('wc_recommendations_settings', $sanitized_settings);
        
        wp_send_json_success('Settings saved');
    }
}