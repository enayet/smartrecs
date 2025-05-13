<?php
/**
 * Product metabox handler.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Product metabox handler.
 *
 * Adds metaboxes to product edit pages for recommendation settings.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Metabox {

    /**
     * Initialize metaboxes.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // No initialization needed
    }
    
    /**
     * Add meta boxes to product edit page.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wc_recommendations_product_settings',
            __('Product Recommendations', 'wc-recommendations'),
            array($this, 'render_product_recommendations_metabox'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render product recommendations metabox.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    Post object.
     */
    public function render_product_recommendations_metabox($post) {
        // Add nonce for security
        wp_nonce_field('wc_recommendations_product_settings', 'wc_recommendations_product_settings_nonce');
        
        // Get current values
        $disable_recommendations = get_post_meta($post->ID, '_wc_disable_recommendations', true);
        $custom_recommendation_type = get_post_meta($post->ID, '_wc_custom_recommendation_type', true);
        $custom_recommendation_ids = get_post_meta($post->ID, '_wc_custom_recommendation_ids', true);
        
        if ($custom_recommendation_ids && !is_array($custom_recommendation_ids)) {
            $custom_recommendation_ids = explode(',', $custom_recommendation_ids);
        }
        
        // Get settings for default values
        $settings = WC_Recommendations_Settings::get_settings();
        
        ?>
        <div class="wc-recommendations-metabox">
            <p>
                <label for="wc_disable_recommendations">
                    <input type="checkbox" id="wc_disable_recommendations" name="wc_disable_recommendations" value="1" <?php checked($disable_recommendations, '1'); ?>>
                    <?php _e('Disable recommendations for this product', 'wc-recommendations'); ?>
                </label>
            </p>
            
            <p>
                <label for="wc_custom_recommendation_type"><?php _e('Override recommendation type:', 'wc-recommendations'); ?></label>
                <select id="wc_custom_recommendation_type" name="wc_custom_recommendation_type">
                    <option value="" <?php selected($custom_recommendation_type, ''); ?>><?php _e('Use default settings', 'wc-recommendations'); ?></option>
                    <option value="frequently_bought" <?php selected($custom_recommendation_type, 'frequently_bought'); ?>><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                    <option value="also_viewed" <?php selected($custom_recommendation_type, 'also_viewed'); ?>><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                    <option value="similar" <?php selected($custom_recommendation_type, 'similar'); ?>><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                    <option value="personalized" <?php selected($custom_recommendation_type, 'personalized'); ?>><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                    <option value="enhanced" <?php selected($custom_recommendation_type, 'enhanced'); ?>><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                    <option value="seasonal" <?php selected($custom_recommendation_type, 'seasonal'); ?>><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                    <option value="trending" <?php selected($custom_recommendation_type, 'trending'); ?>><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                    <option value="ai_hybrid" <?php selected($custom_recommendation_type, 'ai_hybrid'); ?>><?php _e('AI Hybrid (Advanced)', 'wc-recommendations'); ?></option>
                    <option value="context_aware" <?php selected($custom_recommendation_type, 'context_aware'); ?>><?php _e('Context-Aware (Advanced)', 'wc-recommendations'); ?></option>
                    <option value="custom" <?php selected($custom_recommendation_type, 'custom'); ?>><?php _e('Custom Products', 'wc-recommendations'); ?></option>
                </select>
            </p>
            
            <div id="wc_custom_recommendations_container" <?php echo $custom_recommendation_type !== 'custom' ? 'style="display:none;"' : ''; ?>>
                <p>
                    <label for="wc_custom_recommendation_ids"><?php _e('Custom product recommendations:', 'wc-recommendations'); ?></label>
                    <select id="wc_custom_recommendation_ids" name="wc_custom_recommendation_ids[]" class="wc-product-search" multiple="multiple" style="width: 100%;" data-placeholder="<?php esc_attr_e('Search for products&hellip;', 'wc-recommendations'); ?>" data-action="woocommerce_json_search_products">
                        <?php
                        if (!empty($custom_recommendation_ids)) {
                            foreach ($custom_recommendation_ids as $product_id) {
                                $product = wc_get_product($product_id);
                                if ($product) {
                                    echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . wp_kses_post($product->get_formatted_name()) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </p>
            </div>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=wc-recommendations'); ?>" target="_blank"><?php _e('Global recommendation settings', 'wc-recommendations'); ?></a>
            </p>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Toggle custom recommendations container
                $('#wc_custom_recommendation_type').on('change', function() {
                    if ($(this).val() === 'custom') {
                        $('#wc_custom_recommendations_container').show();
                    } else {
                        $('#wc_custom_recommendations_container').hide();
                    }
                });
                
                // Initialize select2 for product search
                $('.wc-product-search').select2({
                    ajax: {
                        url: woocommerce_admin_meta_boxes.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                action: 'woocommerce_json_search_products_and_variations',
                                security: woocommerce_admin_meta_boxes.search_products_nonce
                            };
                        },
                        processResults: function(data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function(id, text) {
                                    terms.push({
                                        id: id,
                                        text: text
                                    });
                                });
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            });
        </script>
        <?php
    }
    
    /**
     * Save product meta box data.
     *
     * @since    1.0.0
     * @param    int       $post_id    Post ID.
     * @param    WP_Post   $post       Post object.
     */
    public function save_meta_boxes($post_id, $post) {
        // Check if nonce is set
        if (!isset($_POST['wc_recommendations_product_settings_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['wc_recommendations_product_settings_nonce'], 'wc_recommendations_product_settings')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save disable recommendations setting
        $disable_recommendations = isset($_POST['wc_disable_recommendations']) ? '1' : '';
        update_post_meta($post_id, '_wc_disable_recommendations', $disable_recommendations);
        
        // Save custom recommendation type
        $custom_recommendation_type = isset($_POST['wc_custom_recommendation_type']) ? sanitize_text_field($_POST['wc_custom_recommendation_type']) : '';
        update_post_meta($post_id, '_wc_custom_recommendation_type', $custom_recommendation_type);
        
        // Save custom recommendation IDs
        $custom_recommendation_ids = isset($_POST['wc_custom_recommendation_ids']) && is_array($_POST['wc_custom_recommendation_ids']) ? array_map('intval', $_POST['wc_custom_recommendation_ids']) : array();
        update_post_meta($post_id, '_wc_custom_recommendation_ids', $custom_recommendation_ids);
    }
}