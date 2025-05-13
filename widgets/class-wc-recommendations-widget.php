<?php
/**
 * Recommendation widget.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Recommendation widget.
 *
 * Provides a widget for displaying recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Widget extends WP_Widget {

    /**
     * Initialize the widget.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'wc_recommendations_widget',
            'description' => __('Display product recommendations', 'wc-recommendations'),
            'customize_selective_refresh' => true,
        );
        
        parent::__construct(
            'wc_recommendations_widget',
            __('WC Product Recommendations', 'wc-recommendations'),
            $widget_ops
        );
    }
    
    /**
     * Front-end display of widget.
     *
     * @since    1.0.0
     * @param    array    $args        Widget arguments
     * @param    array    $instance    Saved values from database
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $type = !empty($instance['type']) ? $instance['type'] : 'frequently_bought';
        $product_id = !empty($instance['product_id']) ? intval($instance['product_id']) : 0;
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 4;
        $custom_title = !empty($instance['custom_title']) ? $instance['custom_title'] : '';
        
        // If no product ID is specified and we're on a product page, use current product
        if (!$product_id && is_product()) {
            global $product;
            $product_id = $product->get_id();
        }
        
        // For personalized recommendations, we don't need a product ID
        if ($type === 'personalized' && !$product_id) {
            $product_id = 0;
        } else if (!$product_id && $type !== 'trending' && $type !== 'seasonal') {
            // For trending and seasonal, we don't need a specific product ID
            echo $args['after_widget'];
            return;
        }
        
        // Get recommendation engine
        $engine = new WC_Recommendations_Engine();
        $ml = new WC_Recommendations_ML();
        $recommendations = array();
        
        // Get recommendations based on type
        switch ($type) {
            case 'frequently_bought':
                $recommendations = $engine->get_frequently_bought_together($product_id, $limit);
                $title = !empty($custom_title) ? $custom_title : __('Frequently Bought Together', 'wc-recommendations');
                break;
                
            case 'also_viewed':
                $recommendations = $engine->get_also_viewed($product_id, $limit);
                $title = !empty($custom_title) ? $custom_title : __('Customers Also Viewed', 'wc-recommendations');
                break;
                
            case 'similar':
                $recommendations = $engine->get_similar_products($product_id, $limit);
                $title = !empty($custom_title) ? $custom_title : __('Similar Products', 'wc-recommendations');
                break;
                
            case 'personalized':
                $recommendations = $engine->get_personalized_recommendations($limit);
                $title = !empty($custom_title) ? $custom_title : __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'enhanced':
                $recommendations = $ml->get_enhanced_recommendations($product_id, get_current_user_id(), $limit);
                $title = !empty($custom_title) ? $custom_title : __('Recommended For You', 'wc-recommendations');
                break;
                
            case 'seasonal':
                $recommendations = $ml->get_seasonal_recommendations($limit);
                $title = !empty($custom_title) ? $custom_title : __('Seasonal Recommendations', 'wc-recommendations');
                break;
                
            case 'trending':
                $recommendations = $ml->get_trending_products($limit);
                $title = !empty($custom_title) ? $custom_title : __('Trending Now', 'wc-recommendations');
                break;
                
            default:
                echo $args['after_widget'];
                return;
        }
        
        // Only display if we have recommendations
        if (empty($recommendations)) {
            echo $args['after_widget'];
            return;
        }
        
        // Get recommendation IDs for tracking
        $recommendation_ids = array_map(function($product) {
            return $product->get_id();
        }, $recommendations);
        
        // Track impressions
        $tracker = new WC_Recommendations_Tracker();
        $tracker->track_impressions($type, $product_id, $recommendation_ids, 'widget');
        
        // Display recommendations
        $display = new WC_Recommendations_Display();
        $display->render_recommendations(
            $recommendations,
            $title,
            $type,
            $product_id,
            'widget'
        );
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form.
     *
     * @since    1.0.0
     * @param    array    $instance    Previously saved values from database
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Product Recommendations', 'wc-recommendations');
        $type = !empty($instance['type']) ? $instance['type'] : 'frequently_bought';
        $product_id = !empty($instance['product_id']) ? intval($instance['product_id']) : 0;
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 4;
        $custom_title = !empty($instance['custom_title']) ? $instance['custom_title'] : '';
        
        // Get all recommendation types
        $recommendation_types = array(
            'frequently_bought' => __('Frequently Bought Together', 'wc-recommendations'),
            'also_viewed' => __('Customers Also Viewed', 'wc-recommendations'),
            'similar' => __('Similar Products', 'wc-recommendations'),
            'personalized' => __('Personalized Recommendations', 'wc-recommendations'),
            'enhanced' => __('Enhanced Recommendations', 'wc-recommendations'),
            'seasonal' => __('Seasonal Recommendations', 'wc-recommendations'),
            'trending' => __('Trending Products', 'wc-recommendations')
        );
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Widget Title:', 'wc-recommendations'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('type')); ?>"><?php esc_html_e('Recommendation Type:', 'wc-recommendations'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('type')); ?>" name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                <?php foreach ($recommendation_types as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($type, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('product_id')); ?>"><?php esc_html_e('Product ID (optional):', 'wc-recommendations'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('product_id')); ?>" name="<?php echo esc_attr($this->get_field_name('product_id')); ?>" type="number" value="<?php echo esc_attr($product_id); ?>">
            <small><?php esc_html_e('Leave empty to use current product (on product pages) or for personalized/trending/seasonal recommendations.', 'wc-recommendations'); ?></small>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php esc_html_e('Number of Products:', 'wc-recommendations'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" min="1" max="12" value="<?php echo esc_attr($limit); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('custom_title')); ?>"><?php esc_html_e('Custom Recommendation Title:', 'wc-recommendations'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('custom_title')); ?>" name="<?php echo esc_attr($this->get_field_name('custom_title')); ?>" type="text" value="<?php echo esc_attr($custom_title); ?>">
            <small><?php esc_html_e('Leave empty to use default title for selected recommendation type.', 'wc-recommendations'); ?></small>
        </p>
        <?php
    }
    
    /**
     * Sanitize widget form values as they are saved.
     *
     * @since    1.0.0
     * @param    array    $new_instance    Values just sent to be saved
     * @param    array    $old_instance    Previously saved values from database
     * @return   array                     Updated safe values to be saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['type'] = (!empty($new_instance['type'])) ? sanitize_text_field($new_instance['type']) : 'frequently_bought';
        $instance['product_id'] = (!empty($new_instance['product_id'])) ? intval($new_instance['product_id']) : 0;
        $instance['limit'] = (!empty($new_instance['limit'])) ? max(1, min(12, intval($new_instance['limit']))) : 4;
        $instance['custom_title'] = (!empty($new_instance['custom_title'])) ? sanitize_text_field($new_instance['custom_title']) : '';
        
        return $instance;
    }
}