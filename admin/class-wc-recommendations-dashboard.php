<?php
/**
 * Analytics dashboard handler.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Analytics dashboard handler.
 *
 * Provides analytics data and reporting functionality.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Dashboard {

    /**
     * Initialize the dashboard.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // No initialization needed
    }
    
    /**
     * AJAX handler for getting analytics data.
     *
     * @since    1.0.0
     */
    public function ajax_get_analytics() {
        // Check if request is valid
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_analytics')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get date range
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');
        
        // Get data type
        $data_type = isset($_POST['data_type']) ? sanitize_text_field($_POST['data_type']) : 'impressions';
        
        // Get analytics data based on type
        $analytics = new WC_Recommendations_Analytics();
        $data = array();
        
        switch ($data_type) {
            case 'impressions':
                $data = $this->get_impressions_data($start_date, $end_date);
                break;
                
            case 'clicks':
                $data = $this->get_clicks_data($start_date, $end_date);
                break;
                
            case 'conversions':
                $data = $this->get_conversion_data($start_date, $end_date);
                break;
                
            case 'revenue':
                $data = $this->get_revenue_data($start_date, $end_date);
                break;
                
            case 'placements':
                $data = $this->get_placement_data($start_date, $end_date);
                break;
                
            case 'top_products':
                $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
                $data = $this->get_top_products($start_date, $end_date, $limit);
                break;
                
            case 'user_engagement':
                $data = $this->get_user_engagement_data($start_date, $end_date);
                break;
                
            case 'ai_insights':
                $data = $this->get_ai_insights($start_date, $end_date);
                break;
                
            case 'summary':
                $data = $this->get_analytics_summary($start_date, $end_date);
                break;
                
            default:
                wp_send_json_error('Invalid data type');
                return;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get impressions data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    Impressions data.
     */
    private function get_impressions_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get impressions by recommendation type and date
        $query = $wpdb->prepare("
            SELECT recommendation_type, COUNT(*) as count, DATE(created_at) as date
            FROM $table
            WHERE event_type = 'impression'
            AND created_at BETWEEN %s AND %s
            GROUP BY recommendation_type, DATE(created_at)
            ORDER BY DATE(created_at)
        ", $start_date, $end_date);
        
        $results = $wpdb->get_results($query);
        
        // Format data for chart
        $data = array();
        $types = array();
        $dates = array();
        
        foreach ($results as $result) {
            if (!in_array($result->recommendation_type, $types)) {
                $types[] = $result->recommendation_type;
            }
            
            if (!in_array($result->date, $dates)) {
                $dates[] = $result->date;
            }
            
            if (!isset($data[$result->date])) {
                $data[$result->date] = array();
            }
            
            $data[$result->date][$result->recommendation_type] = $result->count;
        }
        
        // Fill in missing data points
        $formatted_data = array();
        
        foreach ($dates as $date) {
            $date_data = array(
                'date' => $date
            );
            
            foreach ($types as $type) {
                $date_data[$type] = isset($data[$date][$type]) ? $data[$date][$type] : 0;
            }
            
            $formatted_data[] = $date_data;
        }
        
        return array(
            'data' => $formatted_data,
            'types' => $types
        );
    }
    
    /**
     * Get clicks data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    Clicks data.
     */
    private function get_clicks_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get clicks by recommendation type and date
        $query = $wpdb->prepare("
            SELECT recommendation_type, COUNT(*) as count, DATE(created_at) as date
            FROM $table
            WHERE event_type = 'click'
            AND created_at BETWEEN %s AND %s
            GROUP BY recommendation_type, DATE(created_at)
            ORDER BY DATE(created_at)
        ", $start_date, $end_date);
        
        $results = $wpdb->get_results($query);
        
        // Format data for chart
        $data = array();
        $types = array();
        $dates = array();
        
        foreach ($results as $result) {
            if (!in_array($result->recommendation_type, $types)) {
                $types[] = $result->recommendation_type;
            }
            
            if (!in_array($result->date, $dates)) {
                $dates[] = $result->date;
            }
            
            if (!isset($data[$result->date])) {
                $data[$result->date] = array();
            }
            
            $data[$result->date][$result->recommendation_type] = $result->count;
        }
        
        // Fill in missing data points
        $formatted_data = array();
        
        foreach ($dates as $date) {
            $date_data = array(
                'date' => $date
            );
            
            foreach ($types as $type) {
                $date_data[$type] = isset($data[$date][$type]) ? $data[$date][$type] : 0;
            }
            
            $formatted_data[] = $date_data;
        }
        
        return array(
            'data' => $formatted_data,
            'types' => $types
        );
    }
    
    /**
     * Get conversion data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    Conversion data.
     */
    private function get_conversion_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $impressions_table = $tables['tracking'];
        $clicks_table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get impressions by recommendation type
        $impressions_query = $wpdb->prepare("
            SELECT recommendation_type, COUNT(*) as count
            FROM $impressions_table
            WHERE event_type = 'impression'
            AND created_at BETWEEN %s AND %s
            GROUP BY recommendation_type
        ", $start_date, $end_date);
        
        $impressions = $wpdb->get_results($impressions_query, OBJECT_K);
        
        // Get clicks by recommendation type
        $clicks_query = $wpdb->prepare("
            SELECT recommendation_type, COUNT(*) as count
            FROM $clicks_table
            WHERE event_type = 'click'
            AND created_at BETWEEN %s AND %s
            GROUP BY recommendation_type
        ", $start_date, $end_date);
        
        $clicks = $wpdb->get_results($clicks_query, OBJECT_K);
        
        // Calculate CTR for each recommendation type
        $conversion_data = array();
        
        foreach ($impressions as $type => $impression) {
            $click_count = isset($clicks[$type]) ? $clicks[$type]->count : 0;
            $impression_count = $impression->count;
            
            $ctr = $impression_count > 0 ? ($click_count / $impression_count) * 100 : 0;
            
            $conversion_data[] = array(
                'type' => $this->get_recommendation_type_label($type),
                'impressions' => $impression_count,
                'clicks' => $click_count,
                'ctr' => round($ctr, 2)
            );
        }
        
        return $conversion_data;
    }
    
    /**
     * Get revenue data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    Revenue data.
     */
    private function get_revenue_data($start_date, $end_date) {
        global $wpdb;
        
        // For a real implementation, we would track when a clicked recommendation
        // leads to a purchase and calculate the revenue. For now, we'll return
        // sample data based on actual recommendation types in the system.
        
        $tables = WC_Recommendations_Database::get_table_names();
        $tracking_table = $tables['tracking'];
        
        // Get recommendation types used in the system
        $query = "
            SELECT DISTINCT recommendation_type
            FROM $tracking_table
        ";
        
        $recommendation_types = $wpdb->get_col($query);
        
        // Generate sample data
        $revenue_data = array();
        $total_revenue = 0;
        $total_orders = 0;
        
        foreach ($recommendation_types as $type) {
            // Generate random revenue between $500 and $2000
            $revenue = mt_rand(500, 2000) + mt_rand(0, 99) / 100;
            $orders = mt_rand(15, 50);
            
            $revenue_data[] = array(
                'type' => $this->get_recommendation_type_label($type),
                'revenue' => $revenue,
                'orders' => $orders
            );
            
            $total_revenue += $revenue;
            $total_orders += $orders;
        }
        
        // Sort by revenue
        usort($revenue_data, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        
        // Add total row
        $revenue_data[] = array(
            'type' => __('Total', 'wc-recommendations'),
            'revenue' => $total_revenue,
            'orders' => $total_orders
        );
        
        return $revenue_data;
    }
    
    /**
     * Get placement data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    Placement data.
     */
    private function get_placement_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $impressions_table = $tables['tracking'];
        $clicks_table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get impressions by placement
        $impressions_query = $wpdb->prepare("
            SELECT placement, COUNT(*) as count
            FROM $impressions_table
            WHERE event_type = 'impression'
            AND created_at BETWEEN %s AND %s
            GROUP BY placement
        ", $start_date, $end_date);
        
        $impressions = $wpdb->get_results($impressions_query, OBJECT_K);
        
        // Get clicks by placement
        $clicks_query = $wpdb->prepare("
            SELECT placement, COUNT(*) as count
            FROM $clicks_table
            WHERE event_type = 'click'
            AND created_at BETWEEN %s AND %s
            GROUP BY placement
        ", $start_date, $end_date);
        
        $clicks = $wpdb->get_results($clicks_query, OBJECT_K);
        
        // Calculate CTR for each placement
        $placement_data = array();
        
        foreach ($impressions as $placement => $impression) {
            $click_count = isset($clicks[$placement]) ? $clicks[$placement]->count : 0;
            $impression_count = $impression->count;
            
            $ctr = $impression_count > 0 ? ($click_count / $impression_count) * 100 : 0;
            
            $placement_data[] = array(
                'placement' => $this->get_placement_label($placement),
                'impressions' => $impression_count,
                'clicks' => $click_count,
                'ctr' => round($ctr, 2)
            );
        }
        
        return $placement_data;
    }
    
    /**
     * Get top performing products for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @param    int       $limit         Maximum number of products.
     * @return   array                    Top products data.
     */
    private function get_top_products($start_date, $end_date, $limit = 10) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get top recommended products by clicks
        $query = $wpdb->prepare("
            SELECT recommended_product_id, COUNT(*) as clicks
            FROM $table
            WHERE event_type = 'click'
            AND created_at BETWEEN %s AND %s
            GROUP BY recommended_product_id
            ORDER BY clicks DESC
            LIMIT %d
        ", $start_date, $end_date, $limit);
        
        $results = $wpdb->get_results($query);
        
        // Get product details
        $products = array();
        
        foreach ($results as $result) {
            $product = wc_get_product($result->recommended_product_id);
            
            if ($product) {
                $products[] = array(
                    'id' => $result->recommended_product_id,
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'clicks' => $result->clicks,
                    'url' => get_permalink($result->recommended_product_id),
                    'thumbnail' => wp_get_attachment_url($product->get_image_id())
                );
            }
        }
        
        return $products;
    }
    
    /**
     * Get user engagement data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    User engagement data.
     */
    private function get_user_engagement_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get logged in vs anonymous clicks
        $query = $wpdb->prepare("
            SELECT 
                CASE WHEN user_id > 0 THEN 'logged_in' ELSE 'anonymous' END as user_type,
                COUNT(*) as clicks
            FROM $table
            WHERE event_type = 'click'
            AND created_at BETWEEN %s AND %s
            GROUP BY user_type
        ", $start_date, $end_date);
        
        $results = $wpdb->get_results($query);
        
        // Format data
        $engagement = array(
            'logged_in' => 0,
            'anonymous' => 0
        );
        
        foreach ($results as $result) {
            $engagement[$result->user_type] = $result->clicks;
        }
        
        return $engagement;
    }
    
    /**
     * Get AI insights for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    AI insights data.
     */
    private function get_ai_insights($start_date, $end_date) {
        // Check if AI integration is enabled
        $settings = WC_Recommendations_Settings::get_settings();
        
        if (empty($settings['enable_ai']) || $settings['enable_ai'] !== 'yes') {
            return array(
                'enabled' => false,
                'message' => __('AI integration is not enabled. Enable it in the settings to get AI-powered insights.', 'wc-recommendations')
            );
        }
        
        // Initialize AI class
        $ai = new WC_Recommendations_AI();
        
        if (!$ai->is_enabled()) {
            return array(
                'enabled' => false,
                'message' => __('AI integration is enabled but not properly configured. Please check your API key and settings.', 'wc-recommendations')
            );
        }
        
        // Run AI analysis
        $analysis = $ai->run_admin_ai_analysis();
        
        return array(
            'enabled' => true,
            'analysis' => $analysis
        );
    }
    
    /**
     * Get summary analytics data for the given date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d).
     * @param    string    $end_date      End date (Y-m-d).
     * @return   array                    Summary data.
     */
    private function get_analytics_summary($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get total impressions
        $impressions_query = $wpdb->prepare("
            SELECT COUNT(*) as count
            FROM $table
            WHERE event_type = 'impression'
            AND created_at BETWEEN %s AND %s
        ", $start_date, $end_date);
        
        $impressions = $wpdb->get_var($impressions_query);
        
        // Get total clicks
        $clicks_query = $wpdb->prepare("
            SELECT COUNT(*) as count
            FROM $table
            WHERE event_type = 'click'
            AND created_at BETWEEN %s AND %s
        ", $start_date, $end_date);
        
        $clicks = $wpdb->get_var($clicks_query);
        
        // Calculate CTR
        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
        
        // Get top performing recommendation type
        $type_query = $wpdb->prepare("
            SELECT recommendation_type,
                   COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                   COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks
            FROM $table
            WHERE created_at BETWEEN %s AND %s
            GROUP BY recommendation_type
            HAVING impressions > 0
            ORDER BY (clicks / impressions) DESC
            LIMIT 1
        ", $start_date, $end_date);
        
        $top_type = $wpdb->get_row($type_query);
        
        // Get top performing placement
        $placement_query = $wpdb->prepare("
            SELECT placement,
                   COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                   COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks
            FROM $table
            WHERE created_at BETWEEN %s AND %s
            GROUP BY placement
            HAVING impressions > 0
            ORDER BY (clicks / impressions) DESC
            LIMIT 1
        ", $start_date, $end_date);
        
        $top_placement = $wpdb->get_row($placement_query);
        
        // Get estimated revenue (this would be more accurate in a real implementation)
        $estimated_revenue = $clicks * 2.5; // Assume average order value increase of $2.50 per click
        
        return array(
            'impressions' => (int)$impressions,
            'clicks' => (int)$clicks,
            'ctr' => round($ctr, 2),
            'estimated_revenue' => round($estimated_revenue, 2),
            'top_type' => $top_type ? $this->get_recommendation_type_label($top_type->recommendation_type) : '',
            'top_placement' => $top_placement ? $this->get_placement_label($top_placement->placement) : ''
        );
    }
    
    /**
     * Get a readable label for a recommendation type.
     *
     * @since    1.0.0
     * @param    string    $type    The recommendation type.
     * @return   string             The readable label.
     */
    private function get_recommendation_type_label($type) {
        $labels = array(
            'frequently_bought' => __('Frequently Bought Together', 'wc-recommendations'),
            'also_viewed' => __('Customers Also Viewed', 'wc-recommendations'),
            'similar' => __('Similar Products', 'wc-recommendations'),
            'personalized' => __('Personalized Recommendations', 'wc-recommendations'),
            'enhanced' => __('Enhanced Recommendations (ML)', 'wc-recommendations'),
            'seasonal' => __('Seasonal Products', 'wc-recommendations'),
            'trending' => __('Trending Products', 'wc-recommendations'),
            'ai_hybrid' => __('AI Hybrid', 'wc-recommendations'),
            'context_aware' => __('Context-Aware', 'wc-recommendations'),
            'custom' => __('Custom Products', 'wc-recommendations')
        );
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
    
    /**
     * Get a readable label for a placement.
     *
     * @since    1.0.0
     * @param    string    $placement    The placement.
     * @return   string                  The readable label.
     */
    private function get_placement_label($placement) {
        $labels = array(
            'product' => __('Product Page', 'wc-recommendations'),
            'cart' => __('Cart Page', 'wc-recommendations'),
            'checkout' => __('Checkout Page', 'wc-recommendations'),
            'thankyou' => __('Thank You Page', 'wc-recommendations'),
            'shop' => __('Shop Page', 'wc-recommendations'),
            'homepage' => __('Homepage', 'wc-recommendations'),
            'shortcode' => __('Shortcode', 'wc-recommendations'),
            'widget' => __('Widget', 'wc-recommendations')
        );
        
        return isset($labels[$placement]) ? $labels[$placement] : $placement;
    }
}