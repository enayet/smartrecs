<?php
/**
 * AJAX handlers.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * AJAX handlers.
 *
 * Handles AJAX requests for the plugin.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_AJAX {

    /**
     * Initialize AJAX hooks.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Track recommendation clicks
        add_action('wp_ajax_wc_recommendations_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_wc_recommendations_track_click', array($this, 'track_click'));
        
        // Get recommendations via AJAX
        add_action('wp_ajax_wc_recommendations_get_recommendations', array($this, 'get_recommendations'));
        add_action('wp_ajax_nopriv_wc_recommendations_get_recommendations', array($this, 'get_recommendations'));
    }
    
    /**
     * Track recommendation click via AJAX.
     *
     * @since    1.0.0
     */
    public function track_click() {
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
     * Get recommendations via AJAX.
     *
     * @since    1.0.0
     */
    public function get_recommendations() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_get')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check required data
        if (!isset($_POST['context']) || !isset($_POST['product_id'])) {
            wp_send_json_error('Missing required data');
            return;
        }
        
        // Get data
        $context = sanitize_text_field($_POST['context']);
        $product_id = intval($_POST['product_id']);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 4;
        
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Get recommendations
        $engine = new WC_Recommendations_Engine();
        $recommendation_type = '';
        $title = '';
        
        switch ($context) {
            case 'product':
                $recommendation_type = !empty($settings['product_page_type']) ? $settings['product_page_type'] : 'frequently_bought';
                break;
                
            case 'cart':
                $recommendation_type = !empty($settings['cart_page_type']) ? $settings['cart_page_type'] : 'also_viewed';
                break;
                
            case 'checkout':
                $recommendation_type = !empty($settings['checkout_page_type']) ? $settings['checkout_page_type'] : 'personalized';
                break;
                
            case 'thankyou':
                $recommendation_type = !empty($settings['thankyou_page_type']) ? $settings['thankyou_page_type'] : 'similar';
                break;
                
            default:
                $recommendation_type = 'frequently_bought';
                break;
        }
        
        // Get recommendations based on type
        switch ($recommendation_type) {
            case 'frequently_bought':
                $products = $engine->get_frequently_bought_together($product_id, $limit);
                $title = __('Frequently Bought Together', 'wc-recommendations');
                break;
                
            case 'also_viewed':
                $products = $engine->get_also_viewed($product_id, $limit);
                $title = __('Customers Also Viewed', 'wc-recommendations');
                break;
                
            case 'similar':
                $products = $engine->get_similar_products($product_id, $limit);
                $title = __('Similar Products', 'wc-recommendations');
                break;
                
            case 'personalized':
                $products = $engine->get_personalized_recommendations($limit);
                $title = __('Recommended For You', 'wc-recommendations');
                break;
                
            default:
                $products = $engine->get_frequently_bought_together($product_id, $limit);
                $title = __('Frequently Bought Together', 'wc-recommendations');
                break;
        }
        
        // If we have no recommendations, return error
        if (empty($products)) {
            wp_send_json_error('No recommendations found');
            return;
        }
        
        // Format products for JSON response
        $formatted_products = array();
        foreach ($products as $product) {
            $formatted_products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price_html' => $product->get_price_html(),
                'url' => get_permalink($product->get_id()),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'rating_html' => wc_get_rating_html($product->get_average_rating(), $product->get_rating_count())
            );
        }
        
        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $products);
        
        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($recommendation_type, $product_id, $recommendation_ids, $context);
        
        // Return formatted response
        wp_send_json_success(array(
            'title' => $title,
            'products' => $formatted_products,
            'recommendation_type' => $recommendation_type,
            'context' => $context,
            'nonce' => wp_create_nonce('wc_recommendations_click')
        ));
    }
}