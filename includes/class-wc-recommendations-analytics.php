<?php
/**
 * Analytics processing.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Analytics processing.
 *
 * Handles analytics data processing and reporting.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Analytics {

    /**
     * Get impressions data for a date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @return   array                    Impressions data
     */
    public function get_impressions_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get impressions by recommendation type
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
        $data = [];
        $types = [];
        $dates = [];
        
        foreach ($results as $result) {
            if (!in_array($result->recommendation_type, $types)) {
                $types[] = $result->recommendation_type;
            }
            
            if (!in_array($result->date, $dates)) {
                $dates[] = $result->date;
            }
            
            if (!isset($data[$result->date])) {
                $data[$result->date] = [];
            }
            
            $data[$result->date][$result->recommendation_type] = $result->count;
        }
        
        // Fill in missing data points
        $formatted_data = [];
        
        foreach ($dates as $date) {
            $date_data = [
                'date' => $date
            ];
            
            foreach ($types as $type) {
                $date_data[$type] = isset($data[$date][$type]) ? $data[$date][$type] : 0;
            }
            
            $formatted_data[] = $date_data;
        }
        
        return [
            'data' => $formatted_data,
            'types' => $types
        ];
    }
    
    /**
     * Get clicks data for a date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @return   array                    Clicks data
     */
    public function get_clicks_data($start_date, $end_date) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Convert dates to MySQL format
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
        
        // Get clicks by recommendation type
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
        $data = [];
        $types = [];
        $dates = [];
        
        foreach ($results as $result) {
            if (!in_array($result->recommendation_type, $types)) {
                $types[] = $result->recommendation_type;
            }
            
            if (!in_array($result->date, $dates)) {
                $dates[] = $result->date;
            }
            
            if (!isset($data[$result->date])) {
                $data[$result->date] = [];
            }
            
            $data[$result->date][$result->recommendation_type] = $result->count;
        }
        
        // Fill in missing data points
        $formatted_data = [];
        
        foreach ($dates as $date) {
            $date_data = [
                'date' => $date
            ];
            
            foreach ($types as $type) {
                $date_data[$type] = isset($data[$date][$type]) ? $data[$date][$type] : 0;
            }
            
            $formatted_data[] = $date_data;
        }
        
        return [
            'data' => $formatted_data,
            'types' => $types
        ];
    }
    
    /**
     * Get conversion data for a date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @return   array                    Conversion data
     */
    public function get_conversion_data($start_date, $end_date) {
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
        $conversion_data = [];
        
        foreach ($impressions as $type => $impression) {
            $click_count = isset($clicks[$type]) ? $clicks[$type]->count : 0;
            $impression_count = $impression->count;
            
            $ctr = $impression_count > 0 ? ($click_count / $impression_count) * 100 : 0;
            
            $conversion_data[] = [
                'type' => $this->get_recommendation_type_label($type),
                'impressions' => $impression_count,
                'clicks' => $click_count,
                'ctr' => round($ctr, 2)
            ];
        }
        
        return $conversion_data;
    }
    
    /**
     * Get revenue data for a date range.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @return   array                    Revenue data
     */
    public function get_revenue_data($start_date, $end_date) {
        global $wpdb;
        
        // For a real implementation, we would track when a clicked recommendation
        // leads to a purchase and calculate the revenue. For now, we'll return
        // sample data.
        
        return [
            [
                'type' => __('Frequently Bought Together', 'wc-recommendations'),
                'revenue' => 1250.75,
                'orders' => 42
            ],
            [
                'type' => __('Customers Also Viewed', 'wc-recommendations'),
                'revenue' => 975.50,
                'orders' => 35
            ],
            [
                'type' => __('Similar Products', 'wc-recommendations'),
                'revenue' => 725.25,
                'orders' => 28
            ],
            [
                'type' => __('Personalized Recommendations', 'wc-recommendations'),
                'revenue' => 1450.00,
                'orders' => 47
            ]
        ];
    }
    
    /**
     * Get placement performance data.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @return   array                    Placement performance data
     */
    public function get_placement_performance($start_date, $end_date) {
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
        $placement_data = [];
        
        foreach ($impressions as $placement => $impression) {
            $click_count = isset($clicks[$placement]) ? $clicks[$placement]->count : 0;
            $impression_count = $impression->count;
            
            $ctr = $impression_count > 0 ? ($click_count / $impression_count) * 100 : 0;
            
            $placement_data[] = [
                'placement' => $this->get_placement_label($placement),
                'impressions' => $impression_count,
                'clicks' => $click_count,
                'ctr' => round($ctr, 2)
            ];
        }
        
        return $placement_data;
    }
    
    /**
     * Get top performing products.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @param    int       $limit         Maximum number of products to return
     * @return   array                    Top performing products
     */
    public function get_top_products($start_date, $end_date, $limit = 10) {
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
        $products = [];
        
        foreach ($results as $result) {
            $product = wc_get_product($result->recommended_product_id);
            
            if ($product) {
                $products[] = [
                    'id' => $result->recommended_product_id,
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'clicks' => $result->clicks,
                    'url' => get_permalink($result->recommended_product_id),
                    'thumbnail' => wp_get_attachment_url($product->get_image_id())
                ];
            }
        }
        
        return $products;
    }
    
    /**
     * Get user engagement data.
     *
     * @since    1.0.0
     * @param    string    $start_date    Start date (Y-m-d)
     * @param    string    $end_date      End date (Y-m-d)
     * @return   array                    User engagement data
     */
    public function get_user_engagement($start_date, $end_date) {
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
        $engagement = [
            'logged_in' => 0,
            'anonymous' => 0
        ];
        
        foreach ($results as $result) {
            $engagement[$result->user_type] = $result->clicks;
        }
        
        return $engagement;
    }
    
    /**
     * Get a readable label for a recommendation type.
     *
     * @since    1.0.0
     * @param    string    $type    The recommendation type
     * @return   string             The readable label
     */
    private function get_recommendation_type_label($type) {
        $labels = [
            'frequently_bought' => __('Frequently Bought Together', 'wc-recommendations'),
            'also_viewed' => __('Customers Also Viewed', 'wc-recommendations'),
            'similar' => __('Similar Products', 'wc-recommendations'),
            'personalized' => __('Personalized Recommendations', 'wc-recommendations'),
            'enhanced' => __('Enhanced Recommendations', 'wc-recommendations'),
            'seasonal' => __('Seasonal Recommendations', 'wc-recommendations'),
            'trending' => __('Trending Products', 'wc-recommendations')
        ];
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
    
    /**
     * Get a readable label for a placement.
     *
     * @since    1.0.0
     * @param    string    $placement    The placement
     * @return   string                  The readable label
     */
    private function get_placement_label($placement) {
        $labels = [
            'product' => __('Product Page', 'wc-recommendations'),
            'cart' => __('Cart Page', 'wc-recommendations'),
            'checkout' => __('Checkout Page', 'wc-recommendations'),
            'thankyou' => __('Thank You Page', 'wc-recommendations'),
            'shop' => __('Shop Page', 'wc-recommendations'),
            'homepage' => __('Homepage', 'wc-recommendations')
        ];
        
        return isset($labels[$placement]) ? $labels[$placement] : $placement;
    }
}