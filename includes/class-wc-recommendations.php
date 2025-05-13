<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The core plugin class.
 */
class WC_Recommendations {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WC_Recommendations_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_tracker_hooks();
        $this->define_display_hooks();
        $this->define_shortcode_hooks();
        $this->define_widget_hooks();
        $this->define_api_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - WC_Recommendations_Loader. Orchestrates the hooks of the plugin.
     * - WC_Recommendations_i18n. Defines internationalization functionality.
     * - WC_Recommendations_Admin. Defines all hooks for the admin area.
     * - WC_Recommendations_Public. Defines all hooks for the public side of the site.
     * - WC_Recommendations_Tracker. Defines all data collection functionality.
     * - WC_Recommendations_Engine. Defines the core recommendation algorithms.
     * - WC_Recommendations_Display. Defines the frontend display functionality.
     * - WC_Recommendations_Database. Defines database operations.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-i18n.php';
        
        /**
         * The class responsible for database operations.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-database.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/class-wc-recommendations-admin.php';
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/class-wc-recommendations-settings.php';
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/class-wc-recommendations-metabox.php';
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'admin/class-wc-recommendations-dashboard.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'public/class-wc-recommendations-public.php';
        
        /**
         * The class responsible for data collection.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-tracker.php';
        
        /**
         * The class responsible for recommendation algorithms.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-engine.php';
        
        /**
         * The class responsible for frontend display.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-display.php';
        
        /**
         * The class responsible for machine learning enhancements.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-ml.php';
        
        /**
         * The class responsible for A/B testing.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-ab-testing.php';
        
        /**
         * The class responsible for analytics processing.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-analytics.php';
        
        /**
         * The class responsible for AJAX handlers.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'includes/class-wc-recommendations-ajax.php';
        
        /**
         * The class responsible for shortcodes.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'shortcodes/class-wc-recommendations-shortcodes.php';
        
        /**
         * The class responsible for widgets.
         */
        require_once WC_RECOMMENDATIONS_PLUGIN_DIR . 'widgets/class-wc-recommendations-widget.php';

        $this->loader = new WC_Recommendations_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the WC_Recommendations_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new WC_Recommendations_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new WC_Recommendations_Admin();
        $plugin_settings = new WC_Recommendations_Settings();
        $plugin_metabox = new WC_Recommendations_Metabox();
        $plugin_dashboard = new WC_Recommendations_Dashboard();

        // Admin assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        
        // Settings
        $this->loader->add_action('admin_init', $plugin_settings, 'register_settings');
        
        // Product metabox
        $this->loader->add_action('add_meta_boxes', $plugin_metabox, 'add_meta_boxes');
        $this->loader->add_action('woocommerce_process_product_meta', $plugin_metabox, 'save_meta_boxes', 10, 2);
        
        // Dashboard
        $this->loader->add_action('wp_ajax_wc_recommendations_get_analytics', $plugin_dashboard, 'ajax_get_analytics');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new WC_Recommendations_Public();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }
    
    /**
     * Register all of the hooks related to data tracking functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_tracker_hooks() {
        $plugin_tracker = new WC_Recommendations_Tracker();
        
        // Track product views
        $this->loader->add_action('template_redirect', $plugin_tracker, 'track_product_view');
        
        // Track cart actions
        $this->loader->add_action('woocommerce_add_to_cart', $plugin_tracker, 'track_add_to_cart', 10, 6);
        $this->loader->add_action('woocommerce_cart_item_removed', $plugin_tracker, 'track_remove_from_cart', 10, 2);
        
        // Track purchases
        $this->loader->add_action('woocommerce_order_status_completed', $plugin_tracker, 'track_purchase');
        
        // Track search queries
        $this->loader->add_action('pre_get_posts', $plugin_tracker, 'track_search_query');
        
        // Track recommendation clicks
        $this->loader->add_action('wp_ajax_wc_recommendations_click', $plugin_tracker, 'track_recommendation_click');
        $this->loader->add_action('wp_ajax_nopriv_wc_recommendations_click', $plugin_tracker, 'track_recommendation_click');
    }
    
    /**
     * Register all of the hooks related to frontend display functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_display_hooks() {
        $plugin_display = new WC_Recommendations_Display();
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', []);
        
        // Product page recommendations
        if (!empty($settings['show_on_product']) && $settings['show_on_product'] === 'yes') {
            $this->loader->add_action('woocommerce_after_single_product_summary', $plugin_display, 'display_product_recommendations', 15);
        }
        
        // Cart page recommendations
        if (!empty($settings['show_on_cart']) && $settings['show_on_cart'] === 'yes') {
            $this->loader->add_action('woocommerce_after_cart', $plugin_display, 'display_cart_recommendations', 10);
        }
        
        // Checkout recommendations
        if (!empty($settings['show_on_checkout']) && $settings['show_on_checkout'] === 'yes') {
            $this->loader->add_action('woocommerce_review_order_before_payment', $plugin_display, 'display_checkout_recommendations', 10);
        }
        
        // Thank you page recommendations
        if (!empty($settings['show_on_thankyou']) && $settings['show_on_thankyou'] === 'yes') {
            $this->loader->add_action('woocommerce_thankyou', $plugin_display, 'display_thankyou_recommendations', 20);
        }
    }
    
    /**
     * Register all of the hooks related to shortcodes.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcode_hooks() {
        $plugin_shortcodes = new WC_Recommendations_Shortcodes();
        
        add_shortcode('product_recommendations', array($plugin_shortcodes, 'product_recommendations_shortcode'));
    }
    
    /**
     * Register all of the hooks related to widgets.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_widget_hooks() {
        $this->loader->add_action('widgets_init', $this, 'register_widgets');
    }
    
    /**
     * Register all of the hooks related to the REST API.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_api_hooks() {
        $this->loader->add_action('rest_api_init', $this, 'register_rest_routes');
    }
    
    /**
     * Register widgets.
     *
     * @since    1.0.0
     */
    public function register_widgets() {
        register_widget('WC_Recommendations_Widget');
    }
    
    /**
     * Register REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        // TODO: Implement REST API endpoints
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}