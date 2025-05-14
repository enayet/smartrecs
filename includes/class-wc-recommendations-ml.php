<?php
/**
 * Machine learning enhancements.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Machine learning enhancements.
 *
 * Provides advanced recommendation algorithms with machine learning capabilities.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_ML {

    /**
     * Generate weighted recommendations based on multiple signals.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID
     * @param    int       $user_id       The user ID (0 for anonymous)
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_enhanced_recommendations($product_id, $user_id = 0, $limit = 4) {
        // Get base recommendation sets
        $engine = new WC_Recommendations_Engine();
        $frequently_bought = $engine->get_frequently_bought_together($product_id);
        $also_viewed = $engine->get_also_viewed($product_id);
        $similar = $engine->get_similar_products($product_id);
        
        // Get user preference weights if available
        $weights = $this->get_user_preference_weights($user_id);
        
        // Default weights
        if (empty($weights)) {
            $weights = [
                'frequently_bought' => 0.5,
                'also_viewed' => 0.3,
                'similar' => 0.2
            ];
        }
        
        // Score products based on weights and frequency
        $scored_products = [];
        
        // Add frequently bought products with weight
        foreach ($frequently_bought as $idx => $product) {
            $score = $weights['frequently_bought'] * (1 - ($idx / count($frequently_bought)));
            $product_id = $product->get_id();
            
            if (!isset($scored_products[$product_id])) {
                $scored_products[$product_id] = [
                    'product' => $product,
                    'score' => $score
                ];
            } else {
                $scored_products[$product_id]['score'] += $score;
            }
        }
        
        // Add also viewed products with weight
        foreach ($also_viewed as $idx => $product) {
            $score = $weights['also_viewed'] * (1 - ($idx / count($also_viewed)));
            $product_id = $product->get_id();
            
            if (!isset($scored_products[$product_id])) {
                $scored_products[$product_id] = [
                    'product' => $product,
                    'score' => $score
                ];
            } else {
                $scored_products[$product_id]['score'] += $score;
            }
        }
        
        // Add similar products with weight
        foreach ($similar as $idx => $product) {
            $score = $weights['similar'] * (1 - ($idx / count($similar)));
            $product_id = $product->get_id();
            
            if (!isset($scored_products[$product_id])) {
                $scored_products[$product_id] = [
                    'product' => $product,
                    'score' => $score
                ];
            } else {
                $scored_products[$product_id]['score'] += $score;
            }
        }
        
        // Sort by score
        usort($scored_products, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return top products
        $result = [];
        foreach (array_slice($scored_products, 0, $limit) as $item) {
            $result[] = $item['product'];
        }
        
        return $result;
    }
    
    /**
     * Get user preference weights based on past behavior.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID
     * @return   array                 Array of weights by recommendation type
     */
    private function get_user_preference_weights($user_id) {
        if (!$user_id) {
            return null;
        }
        
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['tracking'];
        
        // Get click counts by recommendation type
        $query = $wpdb->prepare("
            SELECT recommendation_type, COUNT(*) as clicks
            FROM $table
            WHERE user_id = %d
            AND event_type = 'click'
            GROUP BY recommendation_type
        ", $user_id);
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return null;
        }
        
        // Calculate total clicks
        $total = 0;
        foreach ($results as $result) {
            $total += $result->clicks;
        }
        
        // Create weights based on click distribution
        $weights = [];
        foreach ($results as $result) {
            $weights[$result->recommendation_type] = $result->clicks / $total;
        }
        
        return $weights;
    }
    
    /**
     * Improve recommendation accuracy based on user feedback.
     *
     * @since    1.0.0
     */
    public function improve_recommendations() {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        
        // Get tracking data for analysis
        $query = "
            SELECT recommendation_type, COUNT(*) as impressions,
            SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
            FROM {$tables['tracking']}
            GROUP BY recommendation_type
        ";
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return;
        }
        
        // Calculate CTR for each recommendation type
        $metrics = [];
        foreach ($results as $result) {
            $impressions = max(1, $result->impressions); // Avoid division by zero
            $ctr = $result->clicks / $impressions;
            
            $metrics[$result->recommendation_type] = [
                'impressions' => $result->impressions,
                'clicks' => $result->clicks,
                'ctr' => $ctr
            ];
        }
        
        // Save metrics for future use
        update_option('wc_recommendations_metrics', $metrics);
    }
    
    /**
     * Get seasonal recommendations based on time of year.
     *
     * @since    1.0.0
     * @param    int       $limit    The maximum number of recommendations
     * @return   array              Array of WC_Product objects
     */
    public function get_seasonal_recommendations($limit = 4) {
        // Get current month
        $current_month = date('n');
        
        // Define seasonal terms
        $seasonal_terms = [];
        
        // Basic seasonal mapping
        if ($current_month >= 11 || $current_month <= 1) {
            // Winter
            $seasonal_terms = ['winter', 'christmas', 'holiday', 'snow'];
        } elseif ($current_month >= 2 && $current_month <= 4) {
            // Spring
            $seasonal_terms = ['spring', 'easter', 'floral'];
        } elseif ($current_month >= 5 && $current_month <= 7) {
            // Summer
            $seasonal_terms = ['summer', 'beach', 'vacation'];
        } elseif ($current_month >= 8 && $current_month <= 10) {
            // Fall
            $seasonal_terms = ['fall', 'autumn', 'halloween', 'thanksgiving'];
        }
        
        // Get products matching seasonal terms
        $products = [];
        
        // Search in product title, description and tags
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => '_price',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC'
                ]
            ],
            'tax_query' => [
                'relation' => 'OR'
            ]
        ];
        
        // Add tag queries
        foreach ($seasonal_terms as $term) {
            $args['tax_query'][] = [
                'taxonomy' => 'product_tag',
                'field' => 'name',
                'terms' => $term,
                'operator' => 'LIKE'
            ];
        }
        
        // Run query
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $products[] = wc_get_product(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // If we don't have enough seasonal products, supplement with popular products
        if (count($products) < $limit) {
            $engine = new WC_Recommendations_Engine();
            $popular_products = $engine->get_popular_products($limit - count($products));
            $products = array_merge($products, $popular_products);
        }
        
        return $products;
    }
    
    /**
     * Get trending products based on recent popularity.
     *
     * @since    1.0.0
     * @param    int       $limit         The maximum number of recommendations
     * @param    int       $days          The number of days to look back
     * @return   array                    Array of WC_Product objects
     */
    public function get_trending_products($limit = 4, $days = 7) {
        global $wpdb;

        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['interactions'];

        // Get date threshold
        $date_threshold = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

        if (!$table_exists) {
            // Table doesn't exist, fall back to popular products
            $engine = new WC_Recommendations_Engine();
            return $engine->get_popular_products($limit);
        }

        // Get products with increasing views
        $query = $wpdb->prepare("
            SELECT product_id, COUNT(*) as views
            FROM $table
            WHERE interaction_type = 'view'
            AND created_at >= %s
            GROUP BY product_id
            ORDER BY views DESC
            LIMIT %d
        ", $date_threshold, $limit);

        $results = $wpdb->get_results($query);

        if (empty($results)) {
            // No trending data, fall back to popular products
            $engine = new WC_Recommendations_Engine();
            return $engine->get_popular_products($limit);
        }

        // Get products
        $products = [];
        foreach ($results as $result) {
            $product = wc_get_product($result->product_id);
            if ($product && $product->is_visible()) {
                $products[] = $product;
            }
        }

        // If we don't have enough products, supplement with popular products
        if (count($products) < $limit) {
            $engine = new WC_Recommendations_Engine();
            $popular_products = $engine->get_popular_products($limit - count($products));
            $products = array_merge($products, $popular_products);
        }

        return $products;
    }
    
}