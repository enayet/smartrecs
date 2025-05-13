<?php
/**
 * Data collection functionality.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Data collection functionality.
 *
 * Collects user behavior data to power the recommendation algorithms.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Tracker {

    /**
     * Track when a product is viewed.
     *
     * @since    1.0.0
     */
    public function track_product_view() {
        // Check if we're on a product page
        if (!is_product()) {
            return;
        }
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Check if tracking is enabled for this user type
        $user_id = get_current_user_id();
        
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            return;
        }
        
        // Get product and session data
        global $product;
        $product_id = $product->get_id();
        $session_id = WC()->session->get_customer_id();
        
        // Store the interaction
        WC_Recommendations_Database::insert_interaction('view', $product_id, $user_id, $session_id);
    }
    
    /**
     * Track when a product is added to cart.
     *
     * @since    1.0.0
     * @param    string    $cart_item_key      The cart item key
     * @param    int       $product_id         The product ID
     * @param    int       $quantity           The quantity
     * @param    int       $variation_id       The variation ID
     * @param    array     $variation          The variation data
     * @param    array     $cart_item_data     The cart item data
     */
    public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Check if tracking is enabled for this user type
        $user_id = get_current_user_id();
        
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            return;
        }
        
        // Get the actual product ID (use variation ID if it exists)
        $actual_product_id = $variation_id > 0 ? $variation_id : $product_id;
        $session_id = WC()->session->get_customer_id();
        
        // Store the interaction
        WC_Recommendations_Database::insert_interaction('add_to_cart', $actual_product_id, $user_id, $session_id, $quantity);
    }
    
    /**
     * Track when a product is removed from cart.
     *
     * @since    1.0.0
     * @param    string    $cart_item_key      The cart item key
     * @param    object    $cart                The cart object
     */
    public function track_remove_from_cart($cart_item_key, $cart) {
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Check if tracking is enabled for this user type
        $user_id = get_current_user_id();
        
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            return;
        }
        
        // Check if the cart item exists
        if (!isset(WC()->cart->cart_contents[$cart_item_key])) {
            return;
        }
        
        $cart_item = WC()->cart->cart_contents[$cart_item_key];
        $product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];
        $session_id = WC()->session->get_customer_id();
        
        // Store the interaction
        WC_Recommendations_Database::insert_interaction('remove_from_cart', $product_id, $user_id, $session_id, $cart_item['quantity']);
    }
    
    /**
     * Track when a purchase is completed.
     *
     * @since    1.0.0
     * @param    int    $order_id    The order ID
     */
    public function track_purchase($order_id) {
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if tracking is enabled for this user type
        $user_id = $order->get_user_id();
        
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            return;
        }
        
        // Process each item in the order
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $quantity = $item->get_quantity();
            $price = $item->get_total();
            
            // Store the purchase
            WC_Recommendations_Database::insert_purchase($order_id, $product_id, $user_id, $quantity, $price);
        }
    }
    
    /**
     * Track search queries.
     *
     * @since    1.0.0
     * @param    object    $query    The WP_Query object
     */
    public function track_search_query($query) {
        // Only track main queries that are searches
        if (!$query->is_main_query() || !$query->is_search() || !is_woocommerce()) {
            return;
        }
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Check if tracking is enabled for this user type
        $user_id = get_current_user_id();
        
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            return;
        }
        
        // Get search query
        $search_query = get_search_query();
        
        if (empty($search_query)) {
            return;
        }
        
        // Store as interaction with special product ID 0 (represents searches)
        $session_id = WC()->session->get_customer_id();
        
        // Store the interaction with the search term as metadata
        $interaction_id = WC_Recommendations_Database::insert_interaction('search', 0, $user_id, $session_id);
        
        // In a real implementation, we would store the search query as metadata
        // For now, we're just storing the interaction
    }
    
    /**
     * Track recommendation clicks via AJAX.
     *
     * @since    1.0.0
     */
    public function track_recommendation_click() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_click')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check required data
        if (!isset($_POST['product_id']) || !isset($_POST['recommended_id']) || 
            !isset($_POST['recommendation_type']) || !isset($_POST['placement'])) {
            wp_send_json_error('Missing required data');
            return;
        }
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Get data
        $product_id = intval($_POST['product_id']);
        $recommended_id = intval($_POST['recommended_id']);
        $recommendation_type = sanitize_text_field($_POST['recommendation_type']);
        $placement = sanitize_text_field($_POST['placement']);
        
        // Get user and session
        $user_id = get_current_user_id();
        $session_id = WC()->session->get_customer_id();
        
        // Check if tracking is enabled for this user type
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            wp_send_json_error('Tracking disabled for logged in users');
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            wp_send_json_error('Tracking disabled for anonymous users');
            return;
        }
        
        // Track the click
        $result = WC_Recommendations_Database::insert_tracking(
            'click',
            $recommendation_type,
            $product_id,
            $recommended_id,
            $user_id,
            $session_id,
            $placement
        );
        
        if ($result) {
            wp_send_json_success('Click tracked');
        } else {
            wp_send_json_error('Failed to track click');
        }
    }
    
    /**
     * Track recommendation impressions.
     *
     * @since    1.0.0
     * @param    string    $recommendation_type   The recommendation type
     * @param    int       $product_id            The main product ID
     * @param    array     $recommended_ids       Array of recommended product IDs
     * @param    string    $placement             The placement location
     */
    public function track_impressions($recommendation_type, $product_id, $recommended_ids, $placement) {
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Get user and session
        $user_id = get_current_user_id();
        $session_id = WC()->session->get_customer_id();
        
        // Check if tracking is enabled for this user type
        if ($user_id > 0 && (!isset($settings['track_logged_in']) || $settings['track_logged_in'] !== 'yes')) {
            return;
        }
        
        if ($user_id === 0 && (!isset($settings['track_anonymous']) || $settings['track_anonymous'] !== 'yes')) {
            return;
        }
        
        // Track each impression
        foreach ($recommended_ids as $recommended_id) {
            WC_Recommendations_Database::insert_tracking(
                'impression',
                $recommendation_type,
                $product_id,
                $recommended_id,
                $user_id,
                $session_id,
                $placement
            );
        }
    }
}