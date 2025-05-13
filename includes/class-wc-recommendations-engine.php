<?php
/**
 * Recommendation algorithms.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Recommendation algorithms.
 *
 * Contains the core recommendation algorithms.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Engine {

    /**
     * Get "Frequently Bought Together" recommendations.
     *
     * Uses collaborative filtering to find products commonly purchased together.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_frequently_bought_together($product_id, $limit = 4) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['purchases'];
        
        // Find orders containing this product
        $query = $wpdb->prepare("
            SELECT DISTINCT order_id FROM $table 
            WHERE product_id = %d
        ", $product_id);
        
        $orders = $wpdb->get_col($query);
        
        if (empty($orders)) {
            return $this->get_fallback_recommendations($product_id, $limit);
        }
        
        // Find products frequently purchased in the same orders
        $orders_string = implode(',', array_map('intval', $orders));
        
        $query = $wpdb->prepare("
            SELECT product_id, COUNT(*) as frequency 
            FROM $table 
            WHERE order_id IN ($orders_string) 
            AND product_id != %d
            GROUP BY product_id
            ORDER BY frequency DESC
            LIMIT %d
        ", $product_id, $limit);
        
        $product_ids = $wpdb->get_col($query);
        
        return $this->get_products_from_ids($product_ids);
    }
    
    /**
     * Get "Customers Also Viewed" recommendations.
     *
     * Finds products commonly viewed by users who viewed this product.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_also_viewed($product_id, $limit = 4) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['interactions'];
        
        // Find users/sessions who viewed this product
        $query = $wpdb->prepare("
            SELECT DISTINCT 
                CASE WHEN user_id > 0 THEN CONCAT('u:', user_id) ELSE CONCAT('s:', session_id) END as visitor
            FROM $table 
            WHERE product_id = %d 
            AND interaction_type = 'view'
        ", $product_id);
        
        $visitors = $wpdb->get_col($query);
        
        if (empty($visitors)) {
            return $this->get_fallback_recommendations($product_id, $limit);
        }
        
        // Prepare for IN clause with proper escaping
        $placeholders = implode(',', array_fill(0, count($visitors), '%s'));
        $query_args = array_merge([$product_id], $visitors);
        
        // Find products also viewed by these visitors
        $query = $wpdb->prepare("
            SELECT product_id, COUNT(*) as frequency 
            FROM $table 
            WHERE interaction_type = 'view'
            AND product_id != %d
            AND (
                CASE WHEN user_id > 0 THEN CONCAT('u:', user_id) ELSE CONCAT('s:', session_id) END
            ) IN ($placeholders)
            GROUP BY product_id
            ORDER BY frequency DESC
            LIMIT %d
        ", array_merge($query_args, [$limit]));
        
        $product_ids = $wpdb->get_col($query);
        
        return $this->get_products_from_ids($product_ids);
    }
    
    /**
     * Get similar products based on attributes and categories.
     *
     * Uses content-based filtering to find products with similar attributes.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_similar_products($product_id, $limit = 4) {
        // Get the product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return [];
        }
        
        // Get product categories
        $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        
        if (empty($categories) || is_wp_error($categories)) {
            return $this->get_fallback_recommendations($product_id, $limit);
        }
        
        // Get product tags
        $tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
        
        // Query arguments
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'post__not_in'   => [$product_id],
            'tax_query'      => [
                'relation'   => 'OR',
                [
                    'taxonomy'  => 'product_cat',
                    'field'     => 'term_id',
                    'terms'     => $categories,
                    'operator'  => 'IN'
                ]
            ],
            'meta_query'     => [
                'relation'   => 'AND',
                [
                    'key'       => '_price',
                    'value'     => 0,
                    'compare'   => '>',
                    'type'      => 'NUMERIC'
                ]
            ]
        ];
        
        // Add tags to query if they exist
        if (!empty($tags) && !is_wp_error($tags)) {
            $args['tax_query'][] = [
                'taxonomy'  => 'product_tag',
                'field'     => 'term_id',
                'terms'     => $tags,
                'operator'  => 'IN'
            ];
        }
        
        // Run the query
        $query = new WP_Query($args);
        
        $products = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $products[] = wc_get_product(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // If we didn't get enough products, get fallback recommendations
        if (count($products) < $limit) {
            $fallback_products = $this->get_fallback_recommendations($product_id, $limit - count($products));
            $products = array_merge($products, $fallback_products);
        }
        
        return $products;
    }
    
    /**
     * Get personalized recommendations for the current user.
     *
     * Uses a hybrid approach combining user history with collaborative filtering.
     *
     * @since    1.0.0
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_personalized_recommendations($limit = 4) {
        $user_id = get_current_user_id();
        $session_id = WC()->session->get_customer_id();
        
        // Get recently viewed products
        $recent_views = $this->get_recent_views($user_id, $session_id);
        
        if (empty($recent_views)) {
            return $this->get_popular_products($limit);
        }
        
        // Get similar products to recently viewed
        $recommendations = [];
        
        foreach ($recent_views as $viewed_id) {
            // Get a mix of frequently bought together and similar products
            $bought_together = $this->get_frequently_bought_together($viewed_id, 2);
            $similar = $this->get_similar_products($viewed_id, 2);
            
            // Merge recommendations
            $recommendations = array_merge($recommendations, $bought_together, $similar);
            
            if (count($recommendations) >= $limit * 2) {
                break;
            }
        }
        
        // Remove duplicates
        $unique_products = [];
        foreach ($recommendations as $product) {
            $product_id = $product->get_id();
            if (!isset($unique_products[$product_id])) {
                $unique_products[$product_id] = $product;
                
                if (count($unique_products) >= $limit) {
                    break;
                }
            }
        }
        
        // Ensure we have enough recommendations
        if (count($unique_products) < $limit) {
            $fallback = $this->get_popular_products($limit - count($unique_products));
            foreach ($fallback as $product) {
                $product_id = $product->get_id();
                if (!isset($unique_products[$product_id])) {
                    $unique_products[$product_id] = $product;
                    
                    if (count($unique_products) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return array_values($unique_products);
    }
    
    /**
     * Get recently viewed products for a user or session.
     *
     * @since    1.0.0
     * @param    int       $user_id     The user ID
     * @param    string    $session_id  The session ID
     * @param    int       $limit       The maximum number of products
     * @return   array                  Array of product IDs
     */
    public function get_recent_views($user_id, $session_id, $limit = 5) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['interactions'];
        
        $query = "";
        $query_args = [];
        
        if ($user_id > 0) {
            // For logged in users
            $query = "
                SELECT product_id
                FROM $table
                WHERE interaction_type = 'view'
                AND user_id = %d
                ORDER BY created_at DESC
                LIMIT %d
            ";
            $query_args = [$user_id, $limit];
        } else {
            // For anonymous users with session
            $query = "
                SELECT product_id
                FROM $table
                WHERE interaction_type = 'view'
                AND session_id = %s
                ORDER BY created_at DESC
                LIMIT %d
            ";
            $query_args = [$session_id, $limit];
        }
        
        $product_ids = $wpdb->get_col($wpdb->prepare($query, $query_args));
        
        return $product_ids;
    }
    
    /**
     * Get popular products as a fallback.
     *
     * @since    1.0.0
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_popular_products($limit = 4) {
        // Use WooCommerce's built-in popularity sorting
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'meta_key'       => 'total_sales',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'       => '_price',
                    'value'     => 0,
                    'compare'   => '>',
                    'type'      => 'NUMERIC'
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        $products = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $products[] = wc_get_product(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        return $products;
    }
    
    /**
     * Get fallback recommendations when primary algorithm returns no results.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array of WC_Product objects
     */
    public function get_fallback_recommendations($product_id, $limit = 4) {
        // Try to get similar products based on category first
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $this->get_popular_products($limit);
        }
        
        $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        
        if (empty($categories) || is_wp_error($categories)) {
            return $this->get_popular_products($limit);
        }
        
        // Query for products in the same category
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'post__not_in'   => [$product_id],
            'tax_query'      => [
                [
                    'taxonomy'  => 'product_cat',
                    'field'     => 'term_id',
                    'terms'     => $categories,
                    'operator'  => 'IN'
                ]
            ],
            'meta_query'     => [
                [
                    'key'       => '_price',
                    'value'     => 0,
                    'compare'   => '>',
                    'type'      => 'NUMERIC'
                ]
            ],
            'orderby'        => 'rand'
        ];
        
        $query = new WP_Query($args);
        
        $products = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $products[] = wc_get_product(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // If we still don't have enough products, get popular products
        if (count($products) < $limit) {
            $popular_products = $this->get_popular_products($limit - count($products));
            $products = array_merge($products, $popular_products);
        }
        
        return $products;
    }
    
    /**
     * Get products from an array of product IDs.
     *
     * @since    1.0.0
     * @param    array     $ids           Array of product IDs
     * @return   array                    Array of WC_Product objects
     */
    private function get_products_from_ids($ids) {
        $products = [];
        
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            
            if ($product && $product->is_visible()) {
                $products[] = $product;
            }
        }
        
        return $products;
    }
}