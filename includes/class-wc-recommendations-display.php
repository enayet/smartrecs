<?php
/**
 * Frontend display functionality.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Frontend display functionality.
 *
 * Handles the frontend display of recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Display {

    /**
     * Display recommendations on product pages.
     *
     * @since    1.0.0
     */
    public function display_product_recommendations() {
        global $product;

        if (!$product) {
            return;
        }

        // Get settings
        $settings = get_option('wc_recommendations_settings', []);
        $recommendation_type = !empty($settings['product_page_type']) ? $settings['product_page_type'] : 'frequently_bought';
        $limit = !empty($settings['limit']) ? intval($settings['limit']) : 4;

        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $recommendations = [];

        // Get recommendations based on type
        switch ($recommendation_type) {
            case 'frequently_bought':
                $recommendations = $engine->get_frequently_bought_together($product->get_id(), $limit);
                $title = __('Frequently Bought Together', 'wc-recommendations');
                break;

            case 'also_viewed':
                $recommendations = $engine->get_also_viewed($product->get_id(), $limit);
                $title = __('Customers Also Viewed', 'wc-recommendations');
                break;

            case 'similar':
                $recommendations = $engine->get_similar_products($product->get_id(), $limit);
                $title = __('Similar Products', 'wc-recommendations');
                break;

            case 'personalized':
                $recommendations = $engine->get_personalized_recommendations($limit);
                $title = __('Recommended For You', 'wc-recommendations');
                break;

            case 'trending':
                // Add this case to handle trending products
                $ml = new WC_Recommendations_ML();
                $recommendations = $ml->get_trending_products($limit);
                $title = __('Trending Products', 'wc-recommendations');
                break;

            case 'seasonal':
                // Add this case to handle seasonal products
                $ml = new WC_Recommendations_ML();
                $recommendations = $ml->get_seasonal_recommendations($limit);
                $title = __('Seasonal Recommendations', 'wc-recommendations');
                break;

            case 'enhanced':
                // Add this case to handle enhanced recommendations
                $ml = new WC_Recommendations_ML();
                $recommendations = $ml->get_enhanced_recommendations($product->get_id(), get_current_user_id(), $limit);
                $title = __('Enhanced Recommendations', 'wc-recommendations');
                break;

            default:
                return;
        }

        // Only display if we have recommendations
        if (empty($recommendations)) {
            return;
        }

        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $recommendations);

        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($recommendation_type, $product->get_id(), $recommendation_ids, 'product');

        // Render the recommendations
        $this->render_recommendations(
            $recommendations,
            $title,
            $recommendation_type,
            $product->get_id(),
            'product'
        );
    }
    
    /**
     * Display recommendations on cart page.
     *
     * @since    1.0.0
     */
    public function display_cart_recommendations() {
        // Check if cart is empty
        if (WC()->cart->is_empty()) {
            return;
        }
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', []);
        $recommendation_type = !empty($settings['cart_page_type']) ? $settings['cart_page_type'] : 'also_viewed';
        $limit = !empty($settings['limit']) ? intval($settings['limit']) : 4;
        
        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $recommendations = [];
        
        // Get recommendations based on type
        switch ($recommendation_type) {
            case 'personalized':
                $recommendations = $engine->get_personalized_recommendations($limit);
                $title = __('Recommended For You', 'wc-recommendations');
                $context_id = 0; // No specific product ID for personalized
                break;
                
            case 'frequently_bought':
                // Get the first product in cart
                $cart_items = WC()->cart->get_cart();
                $first_item = reset($cart_items);
                $product_id = !empty($first_item['variation_id']) ? $first_item['variation_id'] : $first_item['product_id'];
                
                $recommendations = $engine->get_frequently_bought_together($product_id, $limit);
                $title = __('Frequently Bought Together', 'wc-recommendations');
                $context_id = $product_id;
                break;
                
            case 'also_viewed':
            default:
                // Get all products in cart
                $cart_items = WC()->cart->get_cart();
                $product_ids = [];
                
                foreach ($cart_items as $item) {
                    $product_ids[] = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
                }
                
                // Get recommendations for first product
                if (!empty($product_ids)) {
                    $context_id = $product_ids[0];
                    $recommendations = $engine->get_also_viewed($context_id, $limit);
                    $title = __('Customers Also Viewed', 'wc-recommendations');
                }
                break;
        }
        
        // Only display if we have recommendations
        if (empty($recommendations)) {
            return;
        }
        
        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $recommendations);
        
        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($recommendation_type, $context_id, $recommendation_ids, 'cart');
        
        // Render the recommendations
        $this->render_recommendations(
            $recommendations,
            $title,
            $recommendation_type,
            $context_id,
            'cart'
        );
    }
    
    /**
     * Display recommendations on checkout page.
     *
     * @since    1.0.0
     */
    public function display_checkout_recommendations() {
        // Get settings
        $settings = get_option('wc_recommendations_settings', []);
        $recommendation_type = !empty($settings['checkout_page_type']) ? $settings['checkout_page_type'] : 'personalized';
        $limit = !empty($settings['limit']) ? intval($settings['limit']) : 4;
        
        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $recommendations = [];
        
        // For checkout, we primarily use personalized recommendations
        $recommendations = $engine->get_personalized_recommendations($limit);
        $title = __('You Might Also Like', 'wc-recommendations');
        $context_id = 0; // No specific product ID for personalized
        
        // Only display if we have recommendations
        if (empty($recommendations)) {
            return;
        }
        
        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $recommendations);
        
        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($recommendation_type, $context_id, $recommendation_ids, 'checkout');
        
        // Render the recommendations
        $this->render_recommendations(
            $recommendations,
            $title,
            $recommendation_type,
            $context_id,
            'checkout'
        );
    }
    
    /**
     * Display recommendations on thank you page.
     *
     * @since    1.0.0
     * @param    int       $order_id    The order ID
     */
    public function display_thankyou_recommendations($order_id) {
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', []);
        $recommendation_type = !empty($settings['thankyou_page_type']) ? $settings['thankyou_page_type'] : 'similar';
        $limit = !empty($settings['limit']) ? intval($settings['limit']) : 4;
        
        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $recommendations = [];
        
        // Get the first product from the order
        $items = $order->get_items();
        $first_item = reset($items);
        
        if (!$first_item) {
            return;
        }
        
        $product_id = $first_item->get_variation_id() ? $first_item->get_variation_id() : $first_item->get_product_id();
        
        // Get recommendations based on type
        switch ($recommendation_type) {
            case 'similar':
                $recommendations = $engine->get_similar_products($product_id, $limit);
                $title = __('Similar Products', 'wc-recommendations');
                break;
                
            case 'frequently_bought':
                $recommendations = $engine->get_frequently_bought_together($product_id, $limit);
                $title = __('Frequently Bought Together', 'wc-recommendations');
                break;
                
            case 'personalized':
                $recommendations = $engine->get_personalized_recommendations($limit);
                $title = __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'also_viewed':
            default:
                $recommendations = $engine->get_also_viewed($product_id, $limit);
                $title = __('Customers Also Viewed', 'wc-recommendations');
                break;
        }
        
        // Only display if we have recommendations
        if (empty($recommendations)) {
            return;
        }
        
        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $recommendations);
        
        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($recommendation_type, $product_id, $recommendation_ids, 'thankyou');
        
        // Render the recommendations
        $this->render_recommendations(
            $recommendations,
            $title,
            $recommendation_type,
            $product_id,
            'thankyou'
        );
    }
    
    /**
     * Render recommendations template.
     *
     * @since    1.0.0
     * @param    array     $products             Array of WC_Product objects
     * @param    string    $title                The recommendation title
     * @param    string    $recommendation_type  The recommendation type
     * @param    int       $context_id           The context product ID
     * @param    string    $placement            The placement location
     */
    public function render_recommendations($products, $title, $recommendation_type, $context_id, $placement) {
        // Get settings
        $settings = get_option('wc_recommendations_settings', []);
        $layout = !empty($settings['layout']) ? $settings['layout'] : 'grid';
        $columns = !empty($settings['columns']) ? intval($settings['columns']) : 4;
        
        // Locate template
        $template = '';
        
        switch ($layout) {
            case 'carousel':
                $template = $this->locate_template('carousel.php');
                break;
                
            case 'list':
                $template = $this->locate_template('list.php');
                break;
                
            case 'grid':
            default:
                $template = $this->locate_template('grid.php');
                break;
        }
        
        // Generate nonce for tracking clicks
        $nonce = wp_create_nonce('wc_recommendations_click');
        
        // Include template with variables
        include $template;
    }
    
    /**
     * Locate a template file.
     *
     * @since    1.0.0
     * @param    string    $template_name    Template file name
     * @return   string                      Template file path
     */
    private function locate_template($template_name) {
        // Look in theme first
        $template = locate_template([
            'woocommerce/recommendations/' . $template_name,
            'recommendations/' . $template_name
        ]);
        
        // If not found in theme, use plugin template
        if (!$template) {
            $template = WC_RECOMMENDATIONS_PLUGIN_DIR . 'templates/' . $template_name;
        }
        
        return $template;
    }
}