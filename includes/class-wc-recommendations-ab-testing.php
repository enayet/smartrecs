<?php
/**
 * A/B Testing framework.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * A/B Testing framework.
 *
 * Provides functionality for running A/B tests on recommendation strategies.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_AB_Testing {

    /**
     * Get active test for current user/session.
     *
     * @since    1.0.0
     * @return   array|bool    Active test data or false if no active test
     */
    public function get_active_test() {
        $tests = get_option('wc_recommendation_ab_tests', []);
        
        if (empty($tests) || !is_array($tests)) {
            return false;
        }
        
        // Filter active tests
        $active_tests = array_filter($tests, function($test) {
            return !empty($test['active']) && $test['active'] === true;
        });
        
        if (empty($active_tests)) {
            return false;
        }
        
        // Get current user/session
        $user_id = get_current_user_id();
        $session_id = WC()->session->get_customer_id();
        
        // Generate a consistent hash for this user/session
        $visitor_id = $user_id > 0 ? 'u_' . $user_id : 's_' . $session_id;
        $hash_base = md5($visitor_id);
        $hash_value = hexdec(substr($hash_base, 0, 8)) / 0xffffffff; // 0-1 value
        
        // Assign test variant based on hash
        foreach ($active_tests as $test_id => $test) {
            $variant_count = count($test['variants']);
            $variant_index = floor($hash_value * $variant_count);
            
            $assigned_variant = $test['variants'][$variant_index];
            
            // Track impression if needed
            $this->track_test_impression($test_id, $assigned_variant['id']);
            
            return [
                'test_id' => $test_id,
                'variant' => $assigned_variant
            ];
        }
        
        return false;
    }
    
    /**
     * Get recommendations based on active test variant.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID
     * @param    int       $limit         The maximum number of recommendations
     * @return   array                    Array with recommendations and test info
     */
    public function get_test_recommendations($product_id, $limit = 4) {
        // Get active test
        $test_data = $this->get_active_test();
        
        if (!$test_data) {
            // No active test, return standard recommendations
            $engine = new WC_Recommendations_Engine();
            return [
                'products' => $engine->get_frequently_bought_together($product_id, $limit),
                'title' => __('Frequently Bought Together', 'wc-recommendations'),
                'test' => false
            ];
        }
        
        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $ml = new WC_Recommendations_ML();
        
        // Get recommendations based on variant
        $variant = $test_data['variant'];
        $products = [];
        $title = '';
        
        switch ($variant['type']) {
            case 'frequently_bought':
                $products = $engine->get_frequently_bought_together($product_id, $limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Frequently Bought Together', 'wc-recommendations');
                break;
                
            case 'also_viewed':
                $products = $engine->get_also_viewed($product_id, $limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Customers Also Viewed', 'wc-recommendations');
                break;
                
            case 'similar':
                $products = $engine->get_similar_products($product_id, $limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Similar Products', 'wc-recommendations');
                break;
                
            case 'personalized':
                $products = $engine->get_personalized_recommendations($limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'enhanced':
                $products = $ml->get_enhanced_recommendations($product_id, get_current_user_id(), $limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'seasonal':
                $products = $ml->get_seasonal_recommendations($limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Seasonal Recommendations', 'wc-recommendations');
                break;
                
            case 'trending':
                $products = $ml->get_trending_products($limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Trending Now', 'wc-recommendations');
                break;
                
            default:
                $products = $engine->get_frequently_bought_together($product_id, $limit);
                $title = !empty($variant['title']) ? $variant['title'] : __('Frequently Bought Together', 'wc-recommendations');
                break;
        }
        
        return [
            'products' => $products,
            'title' => $title,
            'test' => $test_data
        ];
    }
    
    /**
     * Track test impression.
     *
     * @since    1.0.0
     * @param    int       $test_id       The test ID
     * @param    string    $variant_id    The variant ID
     */
    private function track_test_impression($test_id, $variant_id) {
        $user_id = get_current_user_id();
        $session_id = WC()->session->get_customer_id();
        
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['ab_impressions'];
        
        $wpdb->insert(
            $table,
            [
                'test_id' => $test_id,
                'variant_id' => $variant_id,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'created_at' => current_time('mysql', true)
            ]
        );
    }
    
    /**
     * Track test conversion.
     *
     * @since    1.0.0
     * @param    string    $conversion_type    The conversion type
     * @param    int       $order_id           The order ID (optional)
     * @param    float     $value              The conversion value (optional)
     */
    public function track_conversion($conversion_type = 'purchase', $order_id = null, $value = null) {
        $test = $this->get_active_test();
        
        if (!$test) {
            return;
        }
        
        $user_id = get_current_user_id();
        $session_id = WC()->session->get_customer_id();
        
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['ab_conversions'];
        
        $wpdb->insert(
            $table,
            [
                'test_id' => $test['test_id'],
                'variant_id' => $test['variant']['id'],
                'conversion_type' => $conversion_type,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'order_id' => $order_id,
                'value' => $value,
                'created_at' => current_time('mysql', true)
            ]
        );
    }
    
    /**
     * Create a new A/B test.
     *
     * @since    1.0.0
     * @param    string    $name           Test name
     * @param    string    $description    Test description
     * @param    array     $variants       Test variants
     * @param    bool      $active         Whether the test is active
     * @return   int                       Test ID
     */
    public function create_test($name, $description, $variants, $active = false) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['ab_tests'];
        
        $wpdb->insert(
            $table,
            [
                'name' => $name,
                'description' => $description,
                'active' => $active ? 1 : 0,
                'start_date' => current_time('mysql', true),
                'created_at' => current_time('mysql', true)
            ]
        );
        
        $test_id = $wpdb->insert_id;
        
        // Save variants in options
        $tests = get_option('wc_recommendation_ab_tests', []);
        
        $tests[$test_id] = [
            'name' => $name,
            'description' => $description,
            'active' => $active,
            'start_date' => current_time('mysql', true),
            'variants' => $variants
        ];
        
        update_option('wc_recommendation_ab_tests', $tests);
        
        return $test_id;
    }
    
    /**
     * Activate an A/B test.
     *
     * @since    1.0.0
     * @param    int       $test_id    Test ID
     * @return   bool                  Success status
     */
    public function activate_test($test_id) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['ab_tests'];
        
        // First, deactivate all tests
        $wpdb->update(
            $table,
            ['active' => 0],
            []
        );
        
        // Then activate the specified test
        $result = $wpdb->update(
            $table,
            [
                'active' => 1,
                'start_date' => current_time('mysql', true)
            ],
            ['id' => $test_id]
        );
        
        // Update in options
        $tests = get_option('wc_recommendation_ab_tests', []);
        
        if (isset($tests[$test_id])) {
            foreach ($tests as $id => $test) {
                $tests[$id]['active'] = ($id == $test_id);
            }
            
            $tests[$test_id]['start_date'] = current_time('mysql', true);
            
            update_option('wc_recommendation_ab_tests', $tests);
        }
        
        return $result !== false;
    }
    
    /**
     * End an A/B test.
     *
     * @since    1.0.0
     * @param    int       $test_id    Test ID
     * @return   bool                  Success status
     */
    public function end_test($test_id) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        $table = $tables['ab_tests'];
        
        $result = $wpdb->update(
            $table,
            [
                'active' => 0,
                'end_date' => current_time('mysql', true)
            ],
            ['id' => $test_id]
        );
        
        // Update in options
        $tests = get_option('wc_recommendation_ab_tests', []);
        
        if (isset($tests[$test_id])) {
            $tests[$test_id]['active'] = false;
            $tests[$test_id]['end_date'] = current_time('mysql', true);
            
            update_option('wc_recommendation_ab_tests', $tests);
        }
        
        return $result !== false;
    }
    
    /**
     * Get test results.
     *
     * @since    1.0.0
     * @param    int       $test_id    Test ID
     * @return   array                 Test results
     */
    public function get_test_results($test_id) {
        global $wpdb;
        
        $tables = WC_Recommendations_Database::get_table_names();
        
        // Get test details
        $tests = get_option('wc_recommendation_ab_tests', []);
        
        if (!isset($tests[$test_id])) {
            return false;
        }
        
        $test = $tests[$test_id];
        $variants = $test['variants'];
        
        // Get impressions for each variant
        $impressions_query = $wpdb->prepare("
            SELECT variant_id, COUNT(*) as count
            FROM {$tables['ab_impressions']}
            WHERE test_id = %d
            GROUP BY variant_id
        ", $test_id);
        
        $impressions = $wpdb->get_results($impressions_query, OBJECT_K);
        
        // Get conversions for each variant
        $conversions_query = $wpdb->prepare("
            SELECT variant_id, conversion_type, COUNT(*) as count, SUM(value) as total_value
            FROM {$tables['ab_conversions']}
            WHERE test_id = %d
            GROUP BY variant_id, conversion_type
        ", $test_id);
        
        $conversions = $wpdb->get_results($conversions_query);
        
        // Process conversion data
        $conversion_data = [];
        foreach ($conversions as $conversion) {
            if (!isset($conversion_data[$conversion->variant_id])) {
                $conversion_data[$conversion->variant_id] = [];
            }
            
            $conversion_data[$conversion->variant_id][$conversion->conversion_type] = [
                'count' => $conversion->count,
                'value' => $conversion->total_value
            ];
        }
        
        // Combine results
        $results = [];
        
        foreach ($variants as $variant) {
            $variant_id = $variant['id'];
            $impression_count = isset($impressions[$variant_id]) ? $impressions[$variant_id]->count : 0;
            
            $variant_results = [
                'id' => $variant_id,
                'name' => $variant['name'],
                'type' => $variant['type'],
                'impressions' => $impression_count,
                'conversions' => []
            ];
            
            // Add conversion data
            if (isset($conversion_data[$variant_id])) {
                foreach ($conversion_data[$variant_id] as $type => $data) {
                    $variant_results['conversions'][$type] = [
                        'count' => $data['count'],
                        'rate' => $impression_count > 0 ? ($data['count'] / $impression_count) * 100 : 0,
                        'value' => $data['value']
                    ];
                }
            }
            
            $results[] = $variant_results;
        }
        
        return [
            'id' => $test_id,
            'name' => $test['name'],
            'description' => $test['description'],
            'active' => $test['active'],
            'start_date' => $test['start_date'],
            'end_date' => isset($test['end_date']) ? $test['end_date'] : null,
            'variants' => $results
        ];
    }
}