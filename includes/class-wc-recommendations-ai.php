<?php

* Generate smart product bundle recommendations.
     *
     * @since    1.0.0
     * @param    int       $product_id    The base product ID.
     * @param    int       $user_id       The user ID.
     * @param    int       $limit         The maximum number of bundled products.
     * @return   array                    Array of bundle data.
     */
    public function get_smart_bundle_recommendations($product_id, $user_id = 0, $limit = 3) {
        // If AI is not enabled, use frequently bought together
        if (!$this->is_enabled) {
            $engine = new WC_Recommendations_Engine();
            $bundle_products = $engine->get_frequently_bought_together($product_id, $limit);
            
            // Format as bundle data
            $bundle = array(
                'base_product' => wc_get_product($product_id),
                'bundled_products' => $bundle_products,
                'discount_rate' => 0.1, // 10% discount
                'bundle_name' => __('Product Bundle', 'wc-recommendations'),
                'explanation' => ''
            );
            
            return $bundle;
        }
        
        // Get base product
        $base_product = wc_get_product($product_id);
        if (!$base_product) {
            return array();
        }
        
        // Get product data
        $product_data = $this->get_product_data($product_id);
        
        // Get user profile
        $user_profile = $this->get_user_profile($user_id);
        
        // Get potential bundle products using traditional algorithms
        $engine = new WC_Recommendations_Engine();
        $candidates = array(
            'frequently_bought' => $engine->get_frequently_bought_together($product_id, $limit * 2),
            'similar' => $engine->get_similar_products($product_id, $limit)
        );
        
        // Flatten and deduplicate candidates
        $candidate_products = array();
        foreach ($candidates as $type => $products) {
            foreach ($products as $product) {
                if ($product->get_id() != $product_id) { // Exclude base product
                    $candidate_products[$product->get_id()] = $product;
                }
            }
        }
        
        // If we don't have enough candidates, add personalized recommendations
        if (count($candidate_products) < $limit) {
            $personalized = $engine->get_personalized_recommendations($limit * 2);
            foreach ($personalized as $product) {
                if ($product->get_id() != $product_id && !isset($candidate_products[$product->get_id()])) {
                    $candidate_products[$product->get_id()] = $product;
                }
            }
        }
        
        // If we still don't have enough, return what we have
        if (empty($candidate_products)) {
            return array(
                'base_product' => $base_product,
                'bundled_products' => array(),
                'discount_rate' => 0.1,
                'bundle_name' => __('Product Bundle', 'wc-recommendations'),
                'explanation' => ''
            );
        }
        
        // Create AI prompt for bundle optimization
        $prompt = "Create an optimal product bundle with complementary items based on a main product. Select the best " . min($limit, count($candidate_products)) . " products from the candidates to create a bundle that makes sense together.\n\n";
        
        $prompt .= "Main product:\n";
        $prompt .= "- Name: " . $base_product->get_name() . "\n";
        $prompt .= "- Categories: " . implode(', ', wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'))) . "\n";
        $prompt .= "- Tags: " . implode(', ', wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'))) . "\n";
        $prompt .= "- Price: $" . $base_product->get_price() . "\n\n";
        
        $prompt .= "Candidate products:\n";
        foreach ($candidate_products as $id => $product) {
            $prompt .= $id . ". " . $product->get_name() . " - $" . $product->get_price() . "\n";
        }
        
        $prompt .= "\nSelect the product IDs that would make the best bundle with the main product, separated by commas. Then suggest a name for this bundle, and a brief explanation of why these products work well together.\n";
        $prompt .= "Format your answer like this:\n";
        $prompt .= "Selected IDs: [comma-separated IDs]\n";
        $prompt .= "Bundle name: [bundle name]\n";
        $prompt .= "Explanation: [explanation]";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response
        $selected_ids = array();
        $bundle_name = __('Product Bundle', 'wc-recommendations');
        $explanation = '';
        
        if ($response) {
            // Extract selected IDs
            if (preg_match('/Selected IDs: ([\d, ]+)/i', $response, $matches)) {
                $id_string = $matches[1];
                $selected_ids = array_map('trim', explode(',', $id_string));
            }
            
            // Extract bundle name
            if (preg_match('/Bundle name: (.+?)(?:\n|$)/i', $response, $matches)) {
                $bundle_name = trim($matches[1]);
            }
            
            // Extract explanation
            if (preg_match('/Explanation: (.+?)(?:\n|$)/is', $response, $matches)) {
                $explanation = trim($matches[1]);
            }
        }
        
        // Get selected products
        $bundled_products = array();
        foreach ($selected_ids as $id) {
            $id = (int) $id;
            if (isset($candidate_products[$id])) {
                $bundled_products[] = $candidate_products[$id];
            }
        }
        
        // If no products were selected, use top candidates
        if (empty($bundled_products) && !empty($candidate_products)) {
            $bundled_products = array_slice(array_values($candidate_products), 0, $limit);
        }
        
        // Calculate the optimal discount based on bundle size
        $discount_rate = 0.05; // Base 5% discount
        $discount_rate += min(0.15, 0.03 * count($bundled_products)); // Add up to 15% more based on bundle size
        
        // Return bundle data
        return array(
            'base_product' => $base_product,
            'bundled_products' => $bundled_products,
            'discount_rate' => $discount_rate,
            'bundle_name' => $bundle_name,
            'explanation' => $explanation
        );
    }
    
    /**
     * Run AI analysis on store data for admin dashboard.
     *
     * @since    1.0.0
     * @return   array    Analysis results and recommendations.
     */
    public function run_admin_ai_analysis() {
        // If AI is not enabled, return empty results
        if (!$this->is_enabled) {
            return array(
                'message' => __('AI analysis is not enabled. Please enable AI integration in the settings.', 'wc-recommendations'),
                'suggestions' => array()
            );
        }
        
        // Get store stats
        $stats = $this->get_store_stats();
        
        // Create prompt for OpenAI
        $prompt = "You are an AI assistant for an e-commerce store using WooCommerce. Analyze the following store metrics and provide actionable recommendations to improve sales and customer engagement through product recommendations.\n\n";
        
        // Add store stats to prompt
        $prompt .= "Store metrics:\n";
        $prompt .= "- Total products: " . $stats['total_products'] . "\n";
        $prompt .= "- Total orders: " . $stats['total_orders'] . "\n";
        $prompt .= "- Average order value: $" . $stats['average_order_value'] . "\n";
        $prompt .= "- Recommendation impressions: " . $stats['recommendation_impressions'] . "\n";
        $prompt .= "- Recommendation clicks: " . $stats['recommendation_clicks'] . "\n";
        $prompt .= "- Click-through rate: " . $stats['click_through_rate'] . "%\n";
        $prompt .= "- Top performing recommendation type: " . $stats['top_recommendation_type'] . "\n";
        $prompt .= "- Worst performing recommendation type: " . $stats['worst_recommendation_type'] . "\n";
        $prompt .= "- Top product categories: " . implode(', ', $stats['top_categories']) . "\n";
        $prompt .= "- Recent search queries: " . implode(', ', $stats['recent_searches']) . "\n\n";
        
        $prompt .= "Based on this data, provide:\n";
        $prompt .= "1. A brief analysis (3-4 sentences) of the store's recommendation performance\n";
        $prompt .= "2. Three specific, actionable suggestions to improve product recommendations\n\n";
        
        $prompt .= "Format your response as follows:\n";
        $prompt .= "Analysis: [your analysis here]\n\n";
        $prompt .= "Suggestions:\n";
        $prompt .= "1. [Title of suggestion 1]: [Description]\n";
        $prompt .= "2. [Title of suggestion 2]: [Description]\n";
        $prompt .= "3. [Title of suggestion 3]: [Description]";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response
        $analysis = '';
        $suggestions = array();
        
        if ($response) {
            // Extract analysis
            if (preg_match('/Analysis: (.+?)(?:\n\nSuggestions:|\Z)/is', $response, $matches)) {
                $analysis = trim($matches[1]);
            }
            
            // Extract suggestions
            if (preg_match('/Suggestions:(.*)/is', $response, $matches)) {
                $suggestions_text = $matches[1];
                
                // Parse numbered suggestions
                preg_match_all('/\d+\.\s+\[([^\]]+)\]:\s+([^\n]+)/', $suggestions_text, $matches, PREG_SET_ORDER);
                
                foreach ($matches as $index => $match) {
                    $suggestions[] = array(
                        'id' => 'suggestion_' . ($index + 1),
                        'title' => trim($match[1]),
                        'description' => trim($match[2])
                    );
                }
            }
        }
        
        // Return results
        return array(
            'message' => $analysis ?: __('AI analysis completed. Here are some recommendations for improving your store.', 'wc-recommendations'),
            'suggestions' => $suggestions
        );
    }
    
    /**
     * Apply an AI-suggested optimization.
     *
     * @since    1.0.0
     * @param    string    $suggestion_id    The suggestion ID.
     * @return   bool                        Whether the suggestion was successfully applied.
     */
    public function apply_ai_suggestion($suggestion_id) {
        // This would implement the specific suggestion
        // For now, just a placeholder that returns success
        return true;
    }
    
    /**
     * Complete training of the AI model with store data.
     *
     * @since    1.0.0
     * @return   bool    Whether training was successful.
     */
    public function complete_ai_training() {
        // This would actually train or fine-tune a model
        // For now, just update the 'last trained' timestamp
        update_option('wc_recommendations_ai_last_trained', current_time('mysql'));
        return true;
    }
    
    /**
     * Get AI-generated content for display.
     *
     * @since    1.0.0
     * @param    int       $product_id     The product ID.
     * @param    string    $content_type   The type of content to generate.
     * @return   string                    Generated content.
     */
    public function get_ai_content($product_id, $content_type = 'summary') {
        // If AI is not enabled, return empty string
        if (!$this->is_enabled) {
            return '';
        }
        
        // Get product
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }
        
        // Get product data
        $name = $product->get_name();
        $description = $product->get_description();
        $short_description = $product->get_short_description();
        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
        $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
        $price = $product->get_price();
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $average_rating = $product->get_average_rating();
        
        // Create different prompts based on content type
        switch ($content_type) {
            case 'summary':
                $prompt = "Create a brief, engaging summary for the following product that highlights its key features and benefits. The summary should be 2-3 sentences.\n\n";
                $prompt .= "Product information:\n";
                $prompt .= "Name: $name\n";
                $prompt .= "Categories: " . implode(', ', $categories) . "\n";
                $prompt .= "Tags: " . implode(', ', $tags) . "\n";
                $prompt .= "Price: $price\n";
                $prompt .= "Rating: $average_rating out of 5\n";
                $prompt .= "Description: $description\n\n";
                $prompt .= "Product summary (2-3 sentences):";
                break;
                
            case 'features':
                $prompt = "List the top 4-5 features or benefits of this product based on its description. Format as bullet points.\n\n";
                $prompt .= "Product information:\n";
                $prompt .= "Name: $name\n";
                $prompt .= "Description: $description\n";
                $prompt .= "Short description: $short_description\n\n";
                $prompt .= "Top features/benefits (bullet points):";
                break;
                
            case 'usage_tips':
                $prompt = "Provide 3 helpful tips for using this product effectively.\n\n";
                $prompt .= "Product information:\n";
                $prompt .= "Name: $name\n";
                $prompt .= "Categories: " . implode(', ', $categories) . "\n";
                $prompt .= "Description: $description\n\n";
                $prompt .= "Usage tips (numbered list):";
                break;
                
            default:
                return '';
        }
        
        // Call OpenAI API
        $content = $this->call_openai_completion($prompt);
        
        // Clean up response
        $content = trim($content);
        
        // Return content or empty string on failure
        return $content ?: '';
    }
    
    /**
     * AJAX handler for running AI analysis.
     *
     * @since    1.0.0
     */
    public function ajax_run_ai_analysis() {
        // Check nonce and capabilities
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_settings') || !current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        // Run analysis
        $results = $this->run_admin_ai_analysis();
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for applying AI suggestion.
     *
     * @since    1.0.0
     */
    public function ajax_apply_suggestion() {
        // Check nonce and capabilities
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_settings') || !current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        // Get suggestion ID
        $suggestion_id = isset($_POST['suggestion_id']) ? sanitize_text_field($_POST['suggestion_id']) : '';
        
        if (empty($suggestion_id)) {
            wp_send_json_error('Missing suggestion ID');
            return;
        }
        
        // Apply suggestion
        $result = $this->apply_ai_suggestion($suggestion_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to apply suggestion');
        }
    }
    
    /**
     * AJAX handler for completing AI training.
     *
     * @since    1.0.0
     */
    public function ajax_complete_training() {
        // Check nonce and capabilities
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_settings') || !current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        // Complete training
        $result = $this->complete_ai_training();
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to complete training');
        }
    }
    
    /**
     * AJAX handler for getting AI-generated content.
     *
     * @since    1.0.0
     */
    public function ajax_get_ai_content() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_recommendations_ai_nonce')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        // Get parameters
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : 'summary';
        
        if (empty($product_id)) {
            wp_send_json_error('Missing product ID');
            return;
        }
        
        // Get content
        $content = $this->get_ai_content($product_id, $content_type);
        
        if ($content) {
            // Prepare HTML based on content type
            $html = '';
            
            switch ($content_type) {
                case 'summary':
                    $html = '<div class="wc-ai-summary">';
                    $html .= '<p>' . esc_html($content) . '</p>';
                    $html .= '<div class="wc-recommendations-ai-badge">';
                    $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-14v4h2V6h-2zm0 6v2h2v-2h-2z" fill="currentColor"/></svg>';
                    $html .= esc_html__('AI Generated', 'wc-recommendations');
                    $html .= '</div>';
                    $html .= '</div>';
                    break;
                    
                case 'features':
                    $html = '<div class="wc-ai-features">';
                    $html .= $content; // Already formatted as HTML
                    $html .= '</div>';
                    break;
                    
                case 'usage_tips':
                    $html = '<div class="wc-ai-usage-tips">';
                    $html .= $content; // Already formatted as HTML
                    $html .= '</div>';
                    break;
            }
            
            wp_send_json_success(array('content' => $content, 'html' => $html));
        } else {
            wp_send_json_error('Failed to generate content');
        }
    }
    
    /**
     * Get user profile for personalization.
     *
     * @since    1.0.0
     * @param    int       $user_id    The user ID.
     * @return   array                 User profile data.
     */
    private function get_user_profile($user_id = 0) {
        global $wpdb;
        
        $profile = array(
            'user_id' => $user_id,
            'is_logged_in' => $user_id > 0,
            'purchases' => array(),
            'views' => array(),
            'searches' => array(),
            'interests' => array()
        );
        
        // If no user ID, return empty profile
        if ($user_id <= 0) {
            return $profile;
        }
        
        // Get purchase history
        $tables = WC_Recommendations_Database::get_table_names();
        $purchases_table = $tables['purchases'];
        
        $purchases_query = $wpdb->prepare("
            SELECT product_id, COUNT(*) as count
            FROM $purchases_table
            WHERE user_id = %d
            GROUP BY product_id
            ORDER BY count DESC
            LIMIT 20
        ", $user_id);
        
        $purchases = $wpdb->get_results($purchases_query, ARRAY_A);
        
        if ($purchases) {
            $profile['purchases'] = $purchases;
            
            // Extract interests from purchases
            foreach ($purchases as $purchase) {
                $product_id = $purchase['product_id'];
                
                // Add categories as interests
                $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
                foreach ($categories as $category) {
                    if (!isset($profile['interests'][$category])) {
                        $profile['interests'][$category] = 0;
                    }
                    $profile['interests'][$category] += $purchase['count'];
                }
                
                // Add tags as interests
                $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
                foreach ($tags as $tag) {
                    if (!isset($profile['interests'][$tag])) {
                        $profile['interests'][$tag] = 0;
                    }
                    $profile['interests'][$tag] += $purchase['count'];
                }
            }
        }
        
        // Get view history
        $interactions_table = $tables['interactions'];
        
        $views_query = $wpdb->prepare("
            SELECT product_id, COUNT(*) as count
            FROM $interactions_table
            WHERE user_id = %d AND interaction_type = 'view'
            GROUP BY product_id
            ORDER BY count DESC
            LIMIT 50
        ", $user_id);
        
        $views = $wpdb->get_results($views_query, ARRAY_A);
        
        if ($views) {
            $profile['views'] = $views;
            
            // Extract interests from views (with lower weight than purchases)
            foreach ($views as $view) {
                $product_id = $view['product_id'];
                
                // Add categories as interests
                $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
                foreach ($categories as $category) {
                    if (!isset($profile['interests'][$category])) {
                        $profile['interests'][$category] = 0;
                    }
                    $profile['interests'][$category] += $view['count'] * 0.5; // Lower weight
                }
                
                // Add tags as interests
                $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
                foreach ($tags as $tag) {
                    if (!isset($profile['interests'][$tag])) {
                        $profile['interests'][$tag] = 0;
                    }
                    $profile['interests'][$tag] += $view['count'] * 0.5; // Lower weight
                }
            }
        }
        
        // Sort interests by weight
        arsort($profile['interests']);
        
        return $profile;
    }
    
    /**
     * Get product data for AI analysis.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @return   array                    Product data.
     */
    private function get_product_data($product_id) {
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return array();
        }
        
        // Basic product data
        $data = array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'categories' => array(),
            'tags' => array(),
            'attributes' => array(),
            'average_rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'sku' => $product->get_sku()
        );
        
        // Get categories
        $categories = wp_get_post_terms($product_id, 'product_cat');
        foreach ($categories as $category) {
            $data['categories'][] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug
            );
        }
        
        // Get tags
        $tags = wp_get_post_terms($product_id, 'product_tag');
        foreach ($tags as $tag) {
            $data['tags'][] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug
            );
        }
        
        // Get attributes
        $attributes = $product->get_attributes();
        foreach ($attributes as $attribute) {
            $attribute_data = array(
                'name' => wc_attribute_label($attribute->get_name()),
                'values' => array()
            );
            
            if ($attribute->is_taxonomy()) {
                $terms = wp_get_post_terms($product_id, $attribute->get_name());
                foreach ($terms as $term) {
                    $attribute_data['values'][] = $term->name;
                }
            } else {
                $attribute_data['values'] = $attribute->get_options();
            }
            
            $data['attributes'][] = $attribute_data;
        }
        
        return $data;
    }
    
    /**
     * Get store statistics for AI analysis.
     *
     * @since    1.0.0
     * @return   array    Store statistics.
     */
    private function get_store_stats() {
        global $wpdb;
        
        $stats = array(
            'total_products' => 0,
            'total_orders' => 0,
            'average_order_value' => 0,
            'recommendation_impressions' => 0,
            'recommendation_clicks' => 0,
            'click_through_rate' => 0,
            'top_recommendation_type' => '',
            'worst_recommendation_type' => '',
            'top_categories' => array(),
            'recent_searches' => array()
        );
        
        // Get total products
        $stats['total_products'] = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'product'
            AND post_status = 'publish'
        ");
        
        // Get total orders and average order value
        $order_stats = $wpdb->get_row("
            SELECT COUNT(*) as total_orders, AVG(meta_value) as avg_order_value
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-completed', 'wc-processing')
            AND pm.meta_key = '_order_total'
        ");
        
        if ($order_stats) {
            $stats['total_orders'] = $order_stats->total_orders;
            $stats['average_order_value'] = round($order_stats->avg_order_value, 2);
        }
        
        // Get recommendation impressions and clicks
        $tables = WC_Recommendations_Database::get_table_names();
        $tracking_table = $tables['tracking'];
        
        $impressions = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $tracking_table
            WHERE event_type = 'impression'
        ");
        
        $clicks = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $tracking_table
            WHERE event_type = 'click'
        ");
        
        $stats['recommendation_impressions'] = $impressions ?: 0;
        $stats['recommendation_clicks'] = $clicks ?: 0;
        
        // Calculate CTR
        if ($impressions > 0) {
            $stats['click_through_rate'] = round(($clicks / $impressions) * 100, 2);
        }
        
        // Get top and worst performing recommendation types
        $recommendation_types = $wpdb->get_results("
            SELECT recommendation_type, 
                   COUNT(CASE WHEN event_type = 'impression' THEN 1 END) as impressions,
                   COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks
            FROM $tracking_table
            GROUP BY recommendation_type
            HAVING impressions > 0
        ");
        
        if ($recommendation_types) {
            $type_ctrs = array();
            
            foreach ($recommendation_types as $type) {
                if ($type->impressions > 0) {
                    $ctr = ($type->clicks / $type->impressions) * 100;
                    $type_ctrs[$type->recommendation_type] = $ctr;
                }
            }
            
            if (!empty($type_ctrs)) {
                $stats['top_recommendation_type'] = array_keys($type_ctrs, max($type_ctrs))[0];
                $stats['worst_recommendation_type'] = array_keys($type_ctrs, min($type_ctrs))[0];
            }
        }
        
        // Get top categories
        $top_categories = $wpdb->get_results("
            SELECT t.name, COUNT(*) as count
            FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'product_cat'
            AND p.post_type = 'product'
            AND p.post_status = 'publish'
            GROUP BY t.name
            ORDER BY count DESC
            LIMIT 5
        ");
        
        if ($top_categories) {
            foreach ($top_categories as $category) {
                $stats['top_categories'][] = $category->name;
            }
        }
        
        // Get recent searches
        $interactions_table = $tables['interactions'];
        
        $recent_searches = $wpdb->get_col("
            SELECT meta_value
            FROM $interactions_table
            WHERE interaction_type = 'search'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        
        if ($recent_searches) {
            $stats['recent_searches'] = $recent_searches;
        } else {
            // Fallback sample searches if no data available
            $stats['recent_searches'] = array('shirt', 'shoes', 'jacket', 'accessories');
        }
        
        return $stats;
    }
    
    /**
     * Rank products using AI for personalized recommendations.
     *
     * @since    1.0.0
     * @param    array     $candidate_products    Array of WC_Product objects.
     * @param    array     $product_data          Product context data.
     * @param    array     $user_profile          User profile data.
     * @param    int       $limit                 The maximum number of recommendations.
     * @return   array                            Ranked array of WC_Product objects.
     */
    private function rank_products_with_ai($candidate_products, $product_data, $user_profile, $limit = 4) {
        // If we have fewer candidates than limit, return all
        if (count($candidate_products) <= $limit) {
            return array_values($candidate_products);
        }
        
        // Create a prompt for the ranking
        $prompt = "Rank the following product recommendations based on relevance to the reference product and user interests. Return ONLY the IDs of the top {$limit} products in order from most to least relevant, comma-separated.\n\n";
        
        // Add reference product info
        $prompt .= "Reference Product:\n";
        $prompt .= "- Name: " . $product_data['name'] . "\n";
        $prompt .= "- Categories: " . implode(', ', array_column($product_data['categories'], 'name')) . "\n";
        $prompt .= "- Tags: " . implode(', ', array_column($product_data['tags'], 'name')) . "\n";
        $prompt .= "- Price: $" . $product_data['price'] . "\n\n";
        
        // Add user interests if available
        if (!empty($user_profile['interests'])) {
            $prompt .= "User Interests (in order of importance):\n";
            $interest_count = 0;
            foreach ($user_profile['interests'] as $interest => $weight) {
                $prompt .= "- " . $interest . "\n";
                $interest_count++;
                if ($interest_count >= 10) break; // Limit to top 10 interests
            }
            $prompt .= "\n";
        }
        
        // Add candidate products
        $prompt .= "Candidate Products to Rank:\n";
        foreach ($candidate_products as $product) {
            $product_id = $product->get_id();
            $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
            $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
            
            $prompt .= "ID {$product_id}: " . $product->get_name() . "\n";
            $prompt .= "  Categories: " . implode(', ', $categories) . "\n";
            $prompt .= "  Tags: " . implode(', ', $tags) . "\n";
            $prompt .= "  Price: $" . $product->get_price() . "\n";
        }
        
        $prompt .= "\nReturn only the top {$limit} product IDs in order of relevance, comma-separated:";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response to get ranked product IDs
        $ranked_ids = array();
        if ($response) {
            // Extract IDs from response
            preg_match_all('/\b\d+\b/', $response, $matches);
            $ranked_ids = $matches[0];
        }
        
        // If no valid response, fall back to random selection
        if (empty($ranked_ids)) {
            return $this->fallback_ranking($candidate_products, $limit);
        }
        
        // Return products in ranked order
        $ranked_products = array();
        foreach ($ranked_ids as $id) {
            $id = (int) $id;
            if (isset($candidate_products[$id])) {
                $ranked_products[] = $candidate_products[$id];
                
                // Stop once we have enough
                if (count($ranked_products) >= $limit) {
                    break;
                }
            }
        }
        
        // If we don't have enough ranked products, add more from candidates
        if (count($ranked_products) < $limit) {
            foreach ($candidate_products as $id => $product) {
                if (!in_array($product, $ranked_products)) {
                    $ranked_products[] = $product;
                    
                    if (count($ranked_products) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return $ranked_products;
    }
    
    /**
     * Fallback ranking method when AI is not available.
     *
     * @since    1.0.0
     * @param    array     $candidate_products    Array of WC_Product objects.
     * @param    int       $limit                 The maximum number of recommendations.
     * @return   array                            Ranked array of WC_Product objects.
     */
    private function fallback_ranking($candidate_products, $limit = 4) {
        // Shuffle candidates for random selection
        $products = array_values($candidate_products);
        shuffle($products);
        
        // Return up to the limit
        return array_slice($products, 0, $limit);
    }
    
    /**
     * Get cart context recommendations.
     *
     * @since    1.0.0
     * @param    array     $user_profile    User profile data.
     * @param    array     $context_data    Context data including cart items.
     * @param    int       $limit           The maximum number of recommendations.
     * @return   array                      Array of WC_Product objects.
     */
    private function get_cart_context_recommendations($user_profile, $context_data, $limit = 4) {
        // Get cart items
        $cart_items = isset($context_data['cart_items']) ? $context_data['cart_items'] : WC()->cart->get_cart();
        
        if (empty($cart_items)) {
            // If cart is empty, return personalized recommendations
            $engine = new WC_Recommendations_Engine();
            return $engine->get_personalized_recommendations($limit);
        }
        
        // Extract product IDs from cart
        $product_ids = array();
        foreach ($cart_items as $item) {
            $product_ids[] = isset($item['product_id']) ? $item['product_id'] : $item['data']->get_id();
        }
        
        // Create AI prompt for cart context recommendations
        $prompt = "Suggest products that would complement the items in this shopping cart. Return ONLY the IDs of the top {$limit} suggested products, comma-separated.\n\n";
        
        // Add cart items
        $prompt .= "Cart Items:\n";
        foreach ($product_ids as $id) {
            $product = wc_get_product($id);
            if ($product) {
                $categories = wp_get_post_terms($id, 'product_cat', array('fields' => 'names'));
                $tags = wp_get_post_terms($id, 'product_tag', array('fields' => 'names'));
                
                $prompt .= "- " . $product->get_name() . "\n";
                $prompt .= "  Categories: " . implode(', ', $categories) . "\n";
                $prompt .= "  Tags: " . implode(', ', $tags) . "\n";
                $prompt .= "  Price: $" . $product->get_price() . "\n";
            }
        }
        
        // Add user interests if available
        if (!empty($user_profile['interests'])) {
            $prompt .= "\nUser Interests (in order of importance):\n";
            $interest_count = 0;
            foreach ($user_profile['interests'] as $interest => $weight) {
                $prompt .= "- " . $interest . "\n";
                $interest_count++;
                if ($interest_count >= 5) break; // Limit to top 5 interests
            }
        }
        
        // Get product catalog sample (exclude cart items)
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'post__not_in' => $product_ids,
            'orderby' => 'rand'
        );
        
        $products_query = new WP_Query($args);
        $candidate_products = array();
        
        if ($products_query->have_posts()) {
            $prompt .= "\nCandidate Products:\n";
            
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                
                if ($product) {
                    $product_id = $product->get_id();
                    $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
                    $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
                    
                    $prompt .= "ID {$product_id}: " . $product->get_name() . "\n";
                    $prompt .= "  Categories: " . implode(', ', $categories) . "\n";
                    $prompt .= "  Tags: " . implode(', ', $tags) . "\n";
                    $prompt .= "  Price: $" . $product->get_price() . "\n";
                    
                    $candidate_products[$product_id] = $product;
                }
            }
            
            wp_reset_postdata();
        }
        
        $prompt .= "\nReturn only the top {$limit} product IDs that would best complement the cart items, comma-separated:";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response
        $recommended_ids = array();
        if ($response) {
            preg_match_all('/\b\d+\b/', $response, $matches);
            $recommended_ids = $matches[0];
        }
        
        // Get recommended products
        $recommended_products = array();
        foreach ($recommended_ids as $id) {
            $id = (int) $id;
            
            // Skip products already in the cart
            if (in_array($id, $product_ids)) {
                continue;
            }
            
            $product = isset($candidate_products[$id]) ? $candidate_products[$id] : wc_get_product($id);
            
            if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                $recommended_products[] = $product;
                
                if (count($recommended_products) >= $limit) {
                    break;
                }
            }
        }
        
        // If we don't have enough recommendations, add more from candidates
        if (count($recommended_products) < $limit && !empty($candidate_products)) {
            foreach ($candidate_products as $product) {
                if (!in_array($product, $recommended_products)) {
                    $recommended_products[] = $product;
                    
                    if (count($recommended_products) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return $recommended_products;
    }
    
    /**
     * Get product context recommendations.
     *
     * @since    1.0.0
     * @param    array     $user_profile    User profile data.
     * @param    int       $product_id      The product ID.
     * @param    int       $limit           The maximum number of recommendations.
     * @return   array                      Array of WC_Product objects.
     */
    private function get_product_context_recommendations($user_profile, $product_id, $limit = 4) {
        // If no product ID, return personalized recommendations
        if (empty($product_id)) {
            $engine = new WC_Recommendations_Engine();
            return $engine->get_personalized_recommendations($limit);
        }
        
        // Use AI hybrid recommendations
        return $this->get_ai_hybrid_recommendations($product_id, $user_profile['user_id'], $limit);
    }
    
    /**
     * Get category context recommendations.
     *
     * @since    1.0.0
     * @param    array     $user_profile    User profile data.
     * @param    int       $category_id     The category ID.
     * @param    int       $limit           The maximum number of recommendations.
     * @return   array                      Array of WC_Product objects.
     */
    private function get_category_context_recommendations($user_profile, $category_id, $limit = 4) {
        // If no category ID, return personalized recommendations
        if (empty($category_id)) {
            $engine = new WC_Recommendations_Engine();
            return $engine->get_personalized_recommendations($limit);
        }
        
        // Get category name
        $category = get_term($category_id, 'product_cat');
        $category_name = $category ? $category->name : '';
        
        // Get products in this category
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_key' => 'total_sales',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id
                )
            )
        );
        
        $products_query = new WP_Query($args);
        $candidate_products = array();
        
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                $product = wc_get_product(get_the_ID());
                
                if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                    $candidate_products[$product->get_id()] = $product;
                }
            }
            
            wp_reset_postdata();
        }
        
        // If we have fewer candidates than limit, return all
        if (count($candidate_products) <= $limit) {
            return array_values($candidate_products);
        }
        
        // Create AI prompt for category context recommendations
        $prompt = "Recommend the best products from the category '{$category_name}' based on the user's interests. Return ONLY the IDs of the top {$limit} products, comma-separated.\n\n";
        
        // Add user interests if available
        if (!empty($user_profile['interests'])) {
            $prompt .= "User Interests (in order of importance):\n";
            $interest_count = 0;
            foreach ($user_profile['interests'] as $interest => $weight) {
                $prompt .= "- " . $interest . "\n";
                $interest_count++;
                if ($interest_count >= 10) break; // Limit to top 10 interests
            }
            $prompt .= "\n";
        }
        
        // Add candidate products
        $prompt .= "Products in Category '{$category_name}':\n";
        foreach ($candidate_products as $product) {
            $product_id = $product->get_id();
            $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
            
            $prompt .= "ID {$product_id}: " . $product->get_name() . "\n";
            $prompt .= "  Tags: " . implode(', ', $tags) . "\n";
            $prompt .= "  Price: $" . $product->get_price() . "\n";
            $prompt .= "  Rating: " . $product->get_average_rating() . "/5\n";
        }
        
        $prompt .= "\nReturn only the top {$limit} product IDs that would best match the user's interests, comma-separated:";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response
        $recommended_ids = array();
        if ($response) {
            preg_match_all('/\b\d+\b/', $response, $matches);
            $recommended_ids = $matches[0];
        }
        
        // Get recommended products
        $recommended_products = array();
        foreach ($recommended_ids as $id) {
            $id = (int) $id;
            
            if (isset($candidate_products[$id])) {
                $recommended_products[] = $candidate_products[$id];
                
                if (count($recommended_products) >= $limit) {
                    break;
                }
            }
        }
        
        // If we don't have enough recommendations, add more from candidates
        if (count($recommended_products) < $limit) {
            foreach ($candidate_products as $product) {
                if (!in_array($product, $recommended_products)) {
                    $recommended_products[] = $product;
                    
                    if (count($recommended_products) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return $recommended_products;
    }
    
    /**
     * Get search context recommendations.
     *
     * @since    1.0.0
     * @param    array     $user_profile    User profile data.
     * @param    string    $search_query    The search query.
     * @param    int       $limit           The maximum number of recommendations.
     * @return   array                      Array of WC_Product objects.
     */
    private function get_search_context_recommendations($user_profile, $search_query, $limit = 4) {
        // If no search query, return personalized recommendations
        if (empty($search_query)) {
            $engine = new WC_Recommendations_Engine();
            return $engine->get_personalized_recommendations($limit);
        }
        
        // Create AI prompt for search context recommendations
        $prompt = "Based on the search query '{$search_query}', recommend related products that the user might be interested in. Return ONLY the names of {$limit} types of products the user might be looking for, comma-separated.\n\n";
        
        // Add user interests if available
        if (!empty($user_profile['interests'])) {
            $prompt .= "User Interests (in order of importance):\n";
            $interest_count = 0;
            foreach ($user_profile['interests'] as $interest => $weight) {
                $prompt .= "- " . $interest . "\n";
                $interest_count++;
                if ($interest_count >= 5) break; // Limit to top 5 interests
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Return only product types related to '{$search_query}' that this user might be interested in, comma-separated:";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response
        $product_types = array();
        if ($response) {
            $product_types = array_map('trim', explode(',', $response));
        }
        
        // If no product types returned, use search query
        if (empty($product_types)) {
            $product_types = array($search_query);
        }
        
        // Use product types to find matching products
        $recommended_products = array();
        
        foreach ($product_types as $type) {
            // Search for products matching this type
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 2, // Get a few per type
                's' => $type,
                'orderby' => 'relevance'
            );
            
            $search_query = new WP_Query($args);
            
            if ($search_query->have_posts()) {
                while ($search_query->have_posts()) {
                    $search_query->the_post();
                    $product = wc_get_product(get_the_ID());
                    
                    if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                        $recommended_products[$product->get_id()] = $product;
                        
                        if (count($recommended_products) >= $limit) {
                            break 2; // Break both loops
                        }
                    }
                }
                
                wp_reset_postdata();
            }
        }
        
        // If we don't have enough recommendations, add popular products
        if (count($recommended_products) < $limit) {
            $engine = new WC_Recommendations_Engine();
            $popular = $engine->get_popular_products($limit - count($recommended_products));
            
            foreach ($popular as $product) {
                if (!isset($recommended_products[$product->get_id()])) {
                    $recommended_products[$product->get_id()] = $product;
                    
                    if (count($recommended_products) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return array_values($recommended_products);
    }
    
    /**
     * Get general context-aware recommendations.
     *
     * @since    1.0.0
     * @param    array     $user_profile    User profile data.
     * @param    int       $limit           The maximum number of recommendations.
     * @return   array                      Array of WC_Product objects.
     */
    private function get_general_context_recommendations($user_profile, $limit = 4) {
        // Use a combination of trending, seasonal, and personalized recommendations
        $current_time = current_time('timestamp');
        $month = date('n', $current_time);
        $day = date('j', $current_time);
        $hour = date('G', $current_time);
        
        // Determine the current context based on time
        $context = '';
        
        // Seasonal context
        if (($month == 11 || $month == 12) && $day <= 25) {
            $context = 'holiday_shopping';
        } elseif ($month >= 5 && $month <= 8) {
            $context = 'summer';
        } elseif ($month >= 2 && $month <= 4) {
            $context = 'spring';
        } elseif ($month >= 9 && $month <= 10) {
            $context = 'fall';
        } elseif ($month == 1 || $month == 2) {
            $context = 'winter';
        }
        
        // Time of day context
        $time_context = '';
        if ($hour >= 5 && $hour < 12) {
            $time_context = 'morning';
        } elseif ($hour >= 12 && $hour < 17) {
            $time_context = 'afternoon';
        } elseif ($hour >= 17 && $hour < 22) {
            $time_context = 'evening';
        } else {
            $time_context = 'night';
        }
        
        // Combine contexts
        if ($context) {
            $context .= '_' . $time_context;
        } else {
            $context = $time_context;
        }
        
        // Create prompt based on context and user profile
        $prompt = "Recommend product types that would be relevant for a user during {$context} shopping. Return ONLY {$limit} product types, comma-separated.\n\n";
        
        // Add user interests if available
        if (!empty($user_profile['interests'])) {
            $prompt .= "User Interests (in order of importance):\n";
            $interest_count = 0;
            foreach ($user_profile['interests'] as $interest => $weight) {
                $prompt .= "- " . $interest . "\n";
                $interest_count++;
                if ($interest_count >= 8) break; // Limit to top 8 interests
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Return only product types appropriate for {$context}, comma-separated:";
        
        // Call OpenAI API
        $response = $this->call_openai_completion($prompt);
        
        // Parse response
        $product_types = array();
        if ($response) {
            $product_types = array_map('trim', explode(',', $response));
        }
        
        // If no product types returned, use default categories
        if (empty($product_types)) {
            // Default categories based on context
            switch ($context) {
                case 'holiday_shopping_morning':
                case 'holiday_shopping_afternoon':
                case 'holiday_shopping_evening':
                case 'holiday_shopping_night':
                    $product_types = array('gifts', 'decorations', 'winter clothing', 'electronics');
                    break;
                case 'summer_morning':
                    $product_types = array('breakfast', 'activewear', 'sunscreen', 'water bottles');
                    break;
                case 'summer_afternoon':
                    $product_types = array('sunglasses', 'beach gear', 'cold drinks', 'outdoor games');
                    break;
                case 'summer_evening':
                    $product_types = array('barbecue', 'casual clothing', 'outdoor lighting', 'insect repellent');
                    break;
                case 'summer_night':
                    $product_types = array('sleepwear', 'fans', 'books', 'lightweight bedding');
                    break;
                case 'winter_morning':
                    $product_types = array('hot drinks', 'warm breakfast', 'winter coats', 'gloves');
                    break;
                default:
                    $product_types = array('popular', 'trending', 'seasonal', 'essentials');
                    break;
            }
        }
        
        // Use product types to find matching products
        $recommended_products = array();
        
        foreach ($product_types as $type) {
            // Search for products matching this type
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 2, // Get a few per type
                's' => $type,
                'orderby' => 'rand'
            );
            
            $search_query = new WP_Query($args);
            
            if ($search_query->have_posts()) {
                while ($search_query->have_posts()) {
                    $search_query->the_post();
                    $product = wc_get_product(get_the_ID());
                    
                    if ($product && $product->is_purchasable() && $product->is_in_stock()) {
                        $recommended_products[$product->get_id()] = $product;
                        
                        if (count($recommended_products) >= $limit) {
                            break 2; // Break both loops
                        }
                    }
                }
                
                wp_reset_postdata();
            }
        }
        
        // If we don't have enough recommendations, add personalized recommendations
        if (count($recommended_products) < $limit) {
            $engine = new WC_Recommendations_Engine();
            $personalized = $engine->get_personalized_recommendations($limit - count($recommended_products));
            
            foreach ($personalized as $product) {
                if (!isset($recommended_products[$product->get_id()])) {
                    $recommended_products[$product->get_id()] = $product;
                    
                    if (count($recommended_products) >= $limit) {
                        break;
                    }
                }
            }
        }
        
        return array_values($recommended_products);
    }
    
    /**
     * Call OpenAI API for completion.
     *
     * @since    1.0.0
     * @param    string    $prompt     The prompt to send to OpenAI.
     * @param    float     $temperature The temperature parameter (0.0-1.0).
     * @param    int       $max_tokens The maximum number of tokens to generate.
     * @return   string                The generated text or empty string on failure.
     */
    private function call_openai_completion($prompt, $temperature = 0.5, $max_tokens = 500) {
        // If API key is not set, return empty string
        if (empty($this->api_key)) {
            return '';
        }
        
        // Prepare request body
        $request_body = array(
            'model' => $this->ai_model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful e-commerce assistant that provides concise, accurate responses for product recommendations.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        );
        
        // Make API request
        $response = wp_remote_post(
            $this->api_endpoint . '/chat/completions',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($request_body),
                'timeout' => 60
            )
        );
        
        // Check for errors
        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return '';
        }
        
        // Parse response
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['choices'][0]['message']['content'])) {
            return $response_body['choices'][0]['message']['content'];
        }
        
        // Log error if no content returned
        if (!empty($response_body['error'])) {
            error_log('OpenAI API Error: ' . json_encode($response_body['error']));
        }
        
        return '';
    }
}
.name, COUNT(*) as count
            FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt<?php
/**
 * AI Integration for Advanced Recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * AI Integration for Advanced Recommendations.
 *
 * Provides advanced AI-powered recommendation algorithms and features.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_AI {

    /**
     * API endpoint for AI service.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_endpoint    API endpoint for AI service.
     */
    private $api_endpoint;

    /**
     * API key for AI service.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    API key for AI service.
     */
    private $api_key;

    /**
     * OpenAI model to use.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $ai_model    The model to use.
     */
    private $ai_model;

    /**
     * Whether AI integration is enabled.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $is_enabled    Whether AI integration is enabled.
     */
    private $is_enabled;

    /**
     * AI model embeding size
     *
     * @since    1.0.0 
     * @access   private
     * @var      int    $embedding_size    The embedding vector size.
     */
    private $embedding_size;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Get settings
        $settings = get_option('wc_recommendations_settings', array());
        
        // Set API endpoint and key
        $this->api_endpoint = isset($settings['ai_api_endpoint']) ? $settings['ai_api_endpoint'] : 'https://api.openai.com/v1';
        $this->api_key = isset($settings['ai_api_key']) ? $settings['ai_api_key'] : '';
        $this->ai_model = isset($settings['ai_model']) ? $settings['ai_model'] : 'gpt-3.5-turbo';
        $this->embedding_size = 1536; // Default for OpenAI embeddings
        
        // Check if AI integration is enabled
        $this->is_enabled = !empty($settings['enable_ai']) && $settings['enable_ai'] === 'yes' && !empty($this->api_key);
        
        // Register hooks for admin AI features
        if ($this->is_enabled) {
            add_action('wp_ajax_wc_recommendations_run_ai_analysis', array($this, 'ajax_run_ai_analysis'));
            add_action('wp_ajax_wc_recommendations_apply_suggestion', array($this, 'ajax_apply_suggestion'));
            add_action('wp_ajax_wc_recommendations_complete_training', array($this, 'ajax_complete_training'));
            
            // Frontend AI hooks
            add_action('wp_ajax_wc_recommendations_get_ai_content', array($this, 'ajax_get_ai_content'));
            add_action('wp_ajax_nopriv_wc_recommendations_get_ai_content', array($this, 'ajax_get_ai_content'));
        }
    }
    
    /**
     * Check if AI integration is enabled.
     *
     * @since    1.0.0
     * @return   bool    Whether AI integration is enabled.
     */
    public function is_enabled() {
        return $this->is_enabled;
    }
    
    /**
     * Get AI-powered hybrid recommendations.
     *
     * Combines traditional recommendation algorithms with AI-powered insights.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @param    int       $user_id       The user ID.
     * @param    int       $limit         The maximum number of recommendations.
     * @return   array                    Array of WC_Product objects.
     */
    public function get_ai_hybrid_recommendations($product_id, $user_id = 0, $limit = 4) {
        // If AI is not enabled, fall back to ML recommendations
        if (!$this->is_enabled) {
            $ml = new WC_Recommendations_ML();
            return $ml->get_enhanced_recommendations($product_id, $user_id, $limit);
        }
        
        // Get basic recommendations from traditional algorithms
        $engine = new WC_Recommendations_Engine();
        $traditional_recs = array(
            'frequently_bought' => $engine->get_frequently_bought_together($product_id, $limit * 2),
            'also_viewed' => $engine->get_also_viewed($product_id, $limit * 2),
            'similar' => $engine->get_similar_products($product_id, $limit * 2),
            'popular' => $engine->get_popular_products($limit * 2)
        );
        
        // Flatten and deduplicate traditional recommendations
        $candidate_products = array();
        foreach ($traditional_recs as $type => $products) {
            foreach ($products as $product) {
                $candidate_products[$product->get_id()] = $product;
            }
        }
        
        // If we don't have enough candidates, add more popular products
        if (count($candidate_products) < $limit * 3) {
            $more_popular = $engine->get_popular_products($limit * 3 - count($candidate_products));
            foreach ($more_popular as $product) {
                if (!isset($candidate_products[$product->get_id()])) {
                    $candidate_products[$product->get_id()] = $product;
                }
            }
        }
        
        // If we still don't have enough, return what we have
        if (count($candidate_products) <= $limit) {
            return array_values($candidate_products);
        }
        
        // Get user data for personalization
        $user_profile = $this->get_user_profile($user_id);
        
        // Get product data
        $product_data = $this->get_product_data($product_id);
        
        // Rank candidates using AI
        $ranked_products = $this->rank_products_with_ai($candidate_products, $product_data, $user_profile, $limit);
        
        // If AI ranking fails, fall back to weighted random selection
        if (empty($ranked_products)) {
            $ranked_products = $this->fallback_ranking($candidate_products, $limit);
        }
        
        return $ranked_products;
    }
    
    /**
     * Get context-aware recommendations based on user behavior and context.
     *
     * @since    1.0.0
     * @param    int       $user_id       The user ID.
     * @param    string    $context       The context (e.g., 'cart', 'product', 'category').
     * @param    array     $context_data  Additional context data.
     * @param    int       $limit         The maximum number of recommendations.
     * @return   array                    Array of WC_Product objects.
     */
    public function get_context_aware_recommendations($user_id = 0, $context = '', $context_data = array(), $limit = 4) {
        // If AI is not enabled, fall back to personalized recommendations
        if (!$this->is_enabled) {
            $engine = new WC_Recommendations_Engine();
            return $engine->get_personalized_recommendations($limit);
        }
        
        // Get user profile
        $user_profile = $this->get_user_profile($user_id);
        
        // Process context
        switch ($context) {
            case 'cart':
                return $this->get_cart_context_recommendations($user_profile, $context_data, $limit);
            
            case 'product':
                $product_id = isset($context_data['product_id']) ? $context_data['product_id'] : 0;
                return $this->get_product_context_recommendations($user_profile, $product_id, $limit);
            
            case 'category':
                $category_id = isset($context_data['category_id']) ? $context_data['category_id'] : 0;
                return $this->get_category_context_recommendations($user_profile, $category_id, $limit);
            
            case 'search':
                $search_query = isset($context_data['search_query']) ? $context_data['search_query'] : '';
                return $this->get_search_context_recommendations($user_profile, $search_query, $limit);
            
            default:
                // Default to general context-aware recommendations
                return $this->get_general_context_recommendations($user_profile, $limit);
        }
    }
    
    /**
     * Generate personalized product explanation for why a product is recommended.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @param    int       $user_id       The user ID.
     * @return   string                   Personalized explanation.
     */
    public function get_recommendation_explanation($product_id, $user_id = 0) {
        // If AI is not enabled, return generic explanation
        if (!$this->is_enabled) {
            return '';
        }
        
        // Get product data
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }
        
        // Get user profile
        $user_profile = $this->get_user_profile($user_id);
        
        // Create a simple product description
        $product_data = array(
            'name' => $product->get_name(),
            'categories' => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
            'tags' => wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names')),
            'price' => $product->get_price(),
            'average_rating' => $product->get_average_rating()
        );
        
        // Get user's purchase history product names
        $purchase_history = array();
        if (!empty($user_profile['purchases'])) {
            foreach ($user_profile['purchases'] as $purchase) {
                $purchased_product = wc_get_product($purchase['product_id']);
                if ($purchased_product) {
                    $purchase_history[] = $purchased_product->get_name();
                }
            }
        }
        
        // Create prompt for OpenAI
        $prompt = "Generate a brief, personalized explanation for why this product is being recommended to the customer. Make it conversational and friendly, about 1-2 sentences.\n\n";
        $prompt .= "Product information:\n";
        $prompt .= "Name: " . $product_data['name'] . "\n";
        $prompt .= "Categories: " . implode(', ', $product_data['categories']) . "\n";
        $prompt .= "Tags: " . implode(', ', $product_data['tags']) . "\n";
        $prompt .= "Price: $" . $product_data['price'] . "\n";
        $prompt .= "Rating: " . $product_data['average_rating'] . " out of 5\n\n";
        
        if (!empty($purchase_history)) {
            $prompt .= "Customer's previous purchases include: " . implode(', ', array_slice($purchase_history, 0, 5));
            if (count($purchase_history) > 5) {
                $prompt .= ", and " . (count($purchase_history) - 5) . " more products";
            }
            $prompt .= ".\n\n";
        }
        
        $prompt .= "Personalized explanation (1-2 sentences):";
        
        // Call OpenAI API
        $explanation = $this->call_openai_completion($prompt);
        
        // Clean up response
        $explanation = trim($explanation);
        
        // Return explanation or empty string on failure
        return $explanation ?: '';
    }
    
    /**
     * Generate smart product bundle recommendations.
     *
     * @since    1.0.0
     * @param    int       $product_id    The base product ID.
     * @param    int       $user_id       The user ID.
     * @param    int       $limit         The maximum number of bundled products.
     * @
