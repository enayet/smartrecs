<?php
/**
 * Shortcode handlers.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Shortcode handlers.
 *
 * Provides shortcodes for displaying recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Shortcodes {

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     */
    public function __construct() {
        add_shortcode('product_recommendations', array($this, 'product_recommendations_shortcode'));
    }
    
    /**
     * Shortcode for displaying product recommendations.
     *
     * Usage: [product_recommendations type="frequently_bought" product_id="123" limit="4" title="Custom Title"]
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Shortcode output
     */
    public function product_recommendations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type'       => 'frequently_bought',
            'product_id' => 0,
            'limit'      => 4,
            'title'      => '',
            'columns'    => 4
        ), $atts, 'product_recommendations');
        
        // Get product ID
        $product_id = intval($atts['product_id']);
        
        // If no product ID is specified, try to get the current product
        if (!$product_id && is_product()) {
            global $product;
            $product_id = $product->get_id();
        }
        
        // For personalized recommendations, we don't need a product ID
        if ($atts['type'] === 'personalized' && !$product_id) {
            $product_id = 0;
        } else if (!$product_id) {
            return '';
        }
        
        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $ml = new WC_Recommendations_ML();
        $recommendations = array();
        
        // Get recommendations based on type
        switch ($atts['type']) {
            case 'frequently_bought':
                $recommendations = $engine->get_frequently_bought_together($product_id, $atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Frequently Bought Together', 'wc-recommendations');
                break;
                
            case 'also_viewed':
                $recommendations = $engine->get_also_viewed($product_id, $atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Customers Also Viewed', 'wc-recommendations');
                break;
                
            case 'similar':
                $recommendations = $engine->get_similar_products($product_id, $atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Similar Products', 'wc-recommendations');
                break;
                
            case 'personalized':
                $recommendations = $engine->get_personalized_recommendations($atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'enhanced':
                $recommendations = $ml->get_enhanced_recommendations($product_id, get_current_user_id(), $atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'seasonal':
                $recommendations = $ml->get_seasonal_recommendations($atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Seasonal Recommendations', 'wc-recommendations');
                break;
                
            case 'trending':
                $recommendations = $ml->get_trending_products($atts['limit']);
                $title = !empty($atts['title']) ? $atts['title'] : __('Trending Now', 'wc-recommendations');
                break;
                
            default:
                return '';
        }
        
        // Only display if we have recommendations
        if (empty($recommendations)) {
            return '';
        }
        
        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $recommendations);
        
        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($atts['type'], $product_id, $recommendation_ids, 'shortcode');
        
        // Start output buffer
        ob_start();
        
        // Display recommendations
        $display = new WC_Recommendations_Display();
        $display->render_recommendations(
            $recommendations,
            $title,
            $atts['type'],
            $product_id,
            'shortcode'
        );
        
        return ob_get_clean();
    }
}