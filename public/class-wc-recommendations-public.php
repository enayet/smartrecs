<?php
/**
 * Public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the public-facing side.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Public {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // No initialization needed
    }
    
    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'wc-recommendations-public', 
            plugin_dir_url(__FILE__) . 'css/wc-recommendations-public.css', 
            array(), 
            WC_RECOMMENDATIONS_VERSION, 
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Get settings
        $settings = WC_Recommendations_Settings::get_settings();
        
        // Main script
        wp_enqueue_script(
            'wc-recommendations-public', 
            plugin_dir_url(__FILE__) . 'js/wc-recommendations-public.js', 
            array('jquery'), 
            WC_RECOMMENDATIONS_VERSION, 
            true
        );
        
        // Tracking script
        if (($settings['track_anonymous'] === 'yes' || $settings['track_logged_in'] === 'yes') &&
            (!$settings['privacy_compliant'] === 'yes' || $this->has_tracking_consent())) {
            
            wp_enqueue_script(
                'wc-recommendations-tracking', 
                plugin_dir_url(__FILE__) . 'js/wc-recommendations-tracking.js', 
                array('jquery', 'wc-recommendations-public'), 
                WC_RECOMMENDATIONS_VERSION, 
                true
            );
        }
        
        // Setup script parameters
        $script_params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'get_nonce' => wp_create_nonce('wc_recommendations_get'),
            'track_nonce' => wp_create_nonce('wc_recommendations_click'),
            'refresh_nonce' => wp_create_nonce('wc_recommendations_refresh'),
            'ai_nonce' => wp_create_nonce('wc_recommendations_ai_nonce')
        );
        
        // Add product ID if on product page
        if (is_product()) {
            global $product;
            $script_params['product_id'] = $product->get_id();
        }
        
        // Add user tracking settings
        $script_params['tracking_enabled'] = (
            ($settings['track_anonymous'] === 'yes' && !is_user_logged_in()) || 
            ($settings['track_logged_in'] === 'yes' && is_user_logged_in())
        );
        
        $script_params['privacy_compliant'] = $settings['privacy_compliant'];
        $script_params['user_id'] = get_current_user_id();
        $script_params['session_id'] = WC()->session ? WC()->session->get_customer_id() : '';
        
        // Add user token if available
        if (isset($_COOKIE['wc_recommendations_user_token'])) {
            $script_params['user_token'] = sanitize_text_field($_COOKIE['wc_recommendations_user_token']);
        }
        
        // Add AI settings
        $script_params['ai_enabled'] = ($settings['enable_ai'] === 'yes' && !empty($settings['ai_api_key']));
        
        // Add exit intent settings
        if ($settings['enable_exit_intent'] === 'yes') {
            $script_params['exit_intent'] = array(
                'enabled' => true,
                'title' => __('Wait! Before You Go...', 'wc-recommendations'),
                'message' => __('Check out these personalized recommendations just for you!', 'wc-recommendations')
            );
        }
        
        // Add consent text
        $script_params['consent_text'] = __('This site uses cookies to provide personalized product recommendations. Do you consent to our tracking cookies?', 'wc-recommendations');
        $script_params['accept_text'] = __('Accept', 'wc-recommendations');
        $script_params['decline_text'] = __('Decline', 'wc-recommendations');
        
        // Localize script
        wp_localize_script('wc-recommendations-public', 'wc_recommendations_params', $script_params);
    }
    
    /**
     * Check if user has given tracking consent.
     *
     * @since    1.0.0
     * @return   bool    Whether consent has been given.
     */
    private function has_tracking_consent() {
        // Check for cookie consent
        if (isset($_COOKIE['woocommerce_recommendations_consent']) && $_COOKIE['woocommerce_recommendations_consent'] === 'yes') {
            return true;
        }
        
        // Check if WooCommerce cookie consent is used
        if (function_exists('wc_privacy_is_cookie_consent_needed') && !wc_privacy_is_cookie_consent_needed()) {
            return true;
        }
        
        // Check for other common cookie consent plugins
        if (
            // Cookie Notice & Compliance
            (isset($_COOKIE['cookie_notice_accepted']) && $_COOKIE['cookie_notice_accepted'] === 'true') ||
            // GDPR Cookie Compliance
            (isset($_COOKIE['moove_gdpr_popup']) && strpos($_COOKIE['moove_gdpr_popup'], 'strict') !== false) ||
            // Cookie Law Info
            (isset($_COOKIE['viewed_cookie_policy']) && $_COOKIE['viewed_cookie_policy'] === 'yes')
        ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add exit intent popup to footer.
     *
     * @since    1.0.0
     */
    public function add_exit_intent_popup() {
        // Get settings
        $settings = WC_Recommendations_Settings::get_settings();
        
        // Check if exit intent is enabled
        if ($settings['enable_exit_intent'] !== 'yes') {
            return;
        }
        
        // Get recommendations for popup
        $engine = new WC_Recommendations_Engine();
        $recommendations = $engine->get_personalized_recommendations(4);
        
        // If no recommendations, try popular products
        if (empty($recommendations)) {
            $recommendations = $engine->get_popular_products(4);
        }
        
        // If still no recommendations, exit
        if (empty($recommendations)) {
            return;
        }
        
        // Generate nonce for tracking clicks
        $nonce = wp_create_nonce('wc_recommendations_click');
        
        // Render popup
        ?>
        <div class="wc-recommendations-exit-intent">
            <div class="wc-exit-intent-content">
                <button class="wc-exit-intent-close">&times;</button>
                <h3 class="wc-exit-intent-title"><?php echo esc_html__('Wait! Before You Go...', 'wc-recommendations'); ?></h3>
                <p class="wc-exit-intent-message"><?php echo esc_html__('Check out these personalized recommendations just for you!', 'wc-recommendations'); ?></p>
                
                <div class="wc-recommendations wc-recommendations-columns-2" data-context-id="0" data-type="personalized" data-placement="exit_intent">
                    <div class="wc-recommendations-grid">
                        <?php foreach ($recommendations as $product) : ?>
                            <div class="wc-recommendations-product">
                                <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="wc-recommendations-product-link" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
                                    <div class="wc-recommendations-product-image">
                                        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                                    </div>
                                    <h4 class="wc-recommendations-product-title"><?php echo esc_html($product->get_name()); ?></h4>
                                    <div class="wc-recommendations-product-price"><?php echo $product->get_price_html(); ?></div>
                                </a>
                                
                                <?php if ($product->is_in_stock() && $product->is_purchasable()) : ?>
                                    <div class="wc-recommendations-product-add-to-cart">
                                        <?php
                                        echo apply_filters(
                                            'woocommerce_loop_add_to_cart_link',
                                            sprintf(
                                                '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                                esc_url($product->add_to_cart_url()),
                                                esc_attr(1),
                                                esc_attr('button add_to_cart_button'),
                                                'product_type="' . esc_attr($product->get_type()) . '"',
                                                esc_html($product->add_to_cart_text())
                                            ),
                                            $product
                                        );
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display Smart Product Bundle.
     *
     * @since    1.0.0
     */
    public function display_smart_bundle() {
        // Check if we're on a product page
        if (!is_product()) {
            return;
        }
        
        // Get settings
        $settings = WC_Recommendations_Settings::get_settings();
        
        // Check if smart bundles are enabled
        if ($settings['enable_smart_bundles'] !== 'yes' || $settings['enable_ai'] !== 'yes') {
            return;
        }
        
        global $product;
        
        // Check if recommendations are disabled for this product
        $disable_recommendations = get_post_meta($product->get_id(), '_wc_disable_recommendations', true);
        
        if ($disable_recommendations === '1') {
            return;
        }
        
        // Get AI class
        $ai = new WC_Recommendations_AI();
        
        // Get bundle recommendations
        $bundle = $ai->get_smart_bundle_recommendations($product->get_id(), get_current_user_id(), 3);
        
        // If no bundled products, exit
        if (empty($bundle['bundled_products'])) {
            return;
        }
        
        // Calculate total prices
        $base_price = $bundle['base_product']->get_price();
        $bundle_total = $base_price;
        
        foreach ($bundle['bundled_products'] as $bundled_product) {
            $bundle_total += $bundled_product->get_price();
        }
        
        // Calculate discounted price
        $discount_amount = $bundle_total * $bundle['discount_rate'];
        $discounted_total = $bundle_total - $discount_amount;
        
        // Format prices according to WooCommerce settings
        $formatted_base_price = wc_price($base_price);
        $formatted_bundle_total = wc_price($bundle_total);
        $formatted_discount = wc_price($discount_amount);
        $formatted_discounted_total = wc_price($discounted_total);
        
        // Render bundle
        ?>
        <div class="wc-recommendations-smart-bundle" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-base-price="<?php echo esc_attr($base_price); ?>" data-currency="<?php echo esc_attr(get_woocommerce_currency_symbol()); ?>">
            <h3 class="wc-recommendations-bundle-title"><?php echo esc_html($bundle['bundle_name']); ?></h3>
            
            <?php if (!empty($bundle['explanation'])) : ?>
                <p class="wc-recommendations-bundle-description"><?php echo esc_html($bundle['explanation']); ?></p>
            <?php endif; ?>
            
            <div class="wc-recommendations-bundle-products">
                <div class="bundle-product-item">
                    <input type="checkbox" class="bundle-product-checkbox" disabled checked>
                    <div class="bundle-product-image">
                        <?php echo $bundle['base_product']->get_image('thumbnail'); ?>
                    </div>
                    <div class="bundle-product-info">
                        <div class="bundle-product-title"><?php echo esc_html($bundle['base_product']->get_name()); ?> (This item)</div>
                        <div class="bundle-product-price"><?php echo $formatted_base_price; ?></div>
                    </div>
                </div>
                
                <?php foreach ($bundle['bundled_products'] as $bundled_product) : ?>
                    <div class="bundle-product-item">
                        <input type="checkbox" class="bundle-product-checkbox" value="<?php echo esc_attr($bundled_product->get_id()); ?>" data-price="<?php echo esc_attr($bundled_product->get_price()); ?>">
                        <div class="bundle-product-image">
                            <?php echo $bundled_product->get_image('thumbnail'); ?>
                        </div>
                        <div class="bundle-product-info">
                            <div class="bundle-product-title"><?php echo esc_html($bundled_product->get_name()); ?></div>
                            <div class="bundle-product-price"><?php echo wc_price($bundled_product->get_price()); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="wc-recommendations-bundle-summary">
                <div class="bundle-summary-row">
                    <span><?php _e('Items Total:', 'wc-recommendations'); ?></span>
                    <span class="bundle-items-total"><?php echo $formatted_base_price; ?></span>
                </div>
                <div class="bundle-summary-row">
                    <span><?php _e('Bundle Discount:', 'wc-recommendations'); ?></span>
                    <span class="bundle-discount">-<?php echo wc_price(0); ?></span>
                </div>
                <div class="bundle-summary-row">
                    <span><?php _e('Bundle Price:', 'wc-recommendations'); ?></span>
                    <span class="bundle-total-price"><?php echo $formatted_base_price; ?></span>
                </div>
            </div>
            
            <p>
                <button type="button" class="button alt add-bundle-to-cart" disabled><?php _e('Add Bundle to Cart', 'wc-recommendations'); ?></button>
            </p>
            
            <div class="bundle-message"></div>
        </div>
        <?php
    }
    
    /**
     * Display AI-generated product content.
     *
     * @since    1.0.0
     */
    public function display_ai_content() {
        // Check if we're on a product page
        if (!is_product()) {
            return;
        }
        
        // Get settings
        $settings = WC_Recommendations_Settings::get_settings();
        
        // Check if AI content is enabled
        if ($settings['enable_ai_content'] !== 'yes' || $settings['enable_ai'] !== 'yes') {
            return;
        }
        
        global $product;
        
        // Check if recommendations are disabled for this product
        $disable_recommendations = get_post_meta($product->get_id(), '_wc_disable_recommendations', true);
        
        if ($disable_recommendations === '1') {
            return;
        }
        
        // Create placeholder for AJAX-loaded content
        ?>
        <div class="wc-recommendations-ai-summary" data-product-id="<?php echo esc_attr($product->get_id()); ?>"></div>
        <?php
    }
    
    /**
     * Add AJAX-loadable recommendation container to product page.
     *
     * @since    1.0.0
     */
    public function add_ajax_recommendation_container() {
        // Check if we're on a product page
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        // Check if recommendations are disabled for this product
        $disable_recommendations = get_post_meta($product->get_id(), '_wc_disable_recommendations', true);
        
        if ($disable_recommendations === '1') {
            return;
        }
        
        // Get settings
        $settings = WC_Recommendations_Settings::get_settings();
        
        // Check if real-time personalization is enabled
        if ($settings['enable_real_time_personalization'] === 'yes') {
            // Add container for AJAX-loaded recommendations
            echo '<div class="wc-recommendations-ajax-load" data-product-id="' . esc_attr($product->get_id()) . '" data-context="product" data-limit="' . esc_attr($settings['limit']) . '"></div>';
        }
    }
    
    /**
     * Add scroll-triggered recommendation container.
     *
     * @since    1.0.0
     */
    public function add_scroll_triggered_recommendations() {
        // Check if we're on a product page
        if (!is_product()) {
            return;
        }
        
        global $product;
        
        // Check if recommendations are disabled for this product
        $disable_recommendations = get_post_meta($product->get_id(), '_wc_disable_recommendations', true);
        
        if ($disable_recommendations === '1') {
            return;
        }
        
        // Get settings
        $settings = WC_Recommendations_Settings::get_settings();
        
        // Check if this is enabled - in a real implementation, this would be a separate setting
        $enable_scroll_trigger = ($settings['enable_real_time_personalization'] === 'yes');
        
        if ($enable_scroll_trigger) {
            // Add container for scroll-triggered recommendations
            echo '<div class="wc-recommendations-scroll-trigger" data-product-id="' . esc_attr($product->get_id()) . '" data-context="product_scroll" data-limit="' . esc_attr($settings['limit']) . '"></div>';
        }
    }
}