<?php
/**
 * Admin settings handler.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Admin settings handler.
 *
 * Handles plugin settings registration and management.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */
class WC_Recommendations_Settings {

    /**
     * Initialize settings.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // No initialization needed
    }
    
    /**
     * Register settings fields.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'wc_recommendations_settings',
            'wc_recommendations_settings',
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * Sanitize settings values.
     *
     * @since    1.0.0
     * @param    array    $input    Raw settings values.
     * @return   array              Sanitized settings values.
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Display settings
        $sanitized['show_on_product'] = isset($input['show_on_product']) ? 'yes' : 'no';
        $sanitized['show_on_cart'] = isset($input['show_on_cart']) ? 'yes' : 'no';
        $sanitized['show_on_checkout'] = isset($input['show_on_checkout']) ? 'yes' : 'no';
        $sanitized['show_on_thankyou'] = isset($input['show_on_thankyou']) ? 'yes' : 'no';
        
        // Algorithm settings
        $sanitized['product_page_type'] = isset($input['product_page_type']) ? sanitize_text_field($input['product_page_type']) : 'frequently_bought';
        $sanitized['cart_page_type'] = isset($input['cart_page_type']) ? sanitize_text_field($input['cart_page_type']) : 'also_viewed';
        $sanitized['checkout_page_type'] = isset($input['checkout_page_type']) ? sanitize_text_field($input['checkout_page_type']) : 'personalized';
        $sanitized['thankyou_page_type'] = isset($input['thankyou_page_type']) ? sanitize_text_field($input['thankyou_page_type']) : 'similar';
        
        // Layout settings
        $sanitized['layout'] = isset($input['layout']) ? sanitize_text_field($input['layout']) : 'grid';
        $sanitized['columns'] = isset($input['columns']) ? max(1, min(6, intval($input['columns']))) : 4;
        $sanitized['limit'] = isset($input['limit']) ? max(1, min(12, intval($input['limit']))) : 4;
        
        // Tracking settings
        $sanitized['track_anonymous'] = isset($input['track_anonymous']) ? 'yes' : 'no';
        $sanitized['track_logged_in'] = isset($input['track_logged_in']) ? 'yes' : 'no';
        $sanitized['privacy_compliant'] = isset($input['privacy_compliant']) ? 'yes' : 'no';
        $sanitized['data_retention_days'] = isset($input['data_retention_days']) ? max(1, intval($input['data_retention_days'])) : 90;
        
        // Uninstall settings
        $sanitized['complete_uninstall'] = isset($input['complete_uninstall']) ? 'yes' : 'no';
        
        // AI Integration settings
        $sanitized['enable_ai'] = isset($input['enable_ai']) ? 'yes' : 'no';
        $sanitized['ai_api_key'] = isset($input['ai_api_key']) ? sanitize_text_field($input['ai_api_key']) : '';
        $sanitized['ai_api_endpoint'] = isset($input['ai_api_endpoint']) ? esc_url_raw($input['ai_api_endpoint']) : 'https://api.openai.com/v1';
        $sanitized['ai_model'] = isset($input['ai_model']) ? sanitize_text_field($input['ai_model']) : 'gpt-3.5-turbo';
        
        // Advanced features
        $sanitized['enable_smart_bundles'] = isset($input['enable_smart_bundles']) ? 'yes' : 'no';
        $sanitized['enable_exit_intent'] = isset($input['enable_exit_intent']) ? 'yes' : 'no';
        $sanitized['enable_real_time_personalization'] = isset($input['enable_real_time_personalization']) ? 'yes' : 'no';
        $sanitized['enable_ai_content'] = isset($input['enable_ai_content']) ? 'yes' : 'no';
        
        return $sanitized;
    }
    
    /**
     * Get default settings.
     *
     * @since    1.0.0
     * @return   array    Default settings.
     */
    public static function get_defaults() {
        return array(
            'show_on_product'                => 'yes',
            'show_on_cart'                   => 'yes',
            'show_on_checkout'               => 'no',
            'show_on_thankyou'               => 'yes',
            'product_page_type'              => 'frequently_bought',
            'cart_page_type'                 => 'also_viewed',
            'checkout_page_type'             => 'personalized',
            'thankyou_page_type'             => 'similar',
            'layout'                         => 'grid',
            'columns'                        => 4,
            'limit'                          => 4,
            'track_anonymous'                => 'yes',
            'track_logged_in'                => 'yes',
            'privacy_compliant'              => 'yes',
            'data_retention_days'            => 90,
            'complete_uninstall'             => 'no',
            'enable_ai'                      => 'no',
            'ai_api_key'                     => '',
            'ai_api_endpoint'                => 'https://api.openai.com/v1',
            'ai_model'                       => 'gpt-3.5-turbo',
            'enable_smart_bundles'           => 'no',
            'enable_exit_intent'             => 'no',
            'enable_real_time_personalization' => 'no',
            'enable_ai_content'              => 'no'
        );
    }
    
    /**
     * Get settings with defaults applied.
     *
     * @since    1.0.0
     * @return   array    Settings with defaults.
     */
    public static function get_settings() {
        $settings = get_option('wc_recommendations_settings', array());
        $defaults = self::get_defaults();
        
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Add AI settings tab content.
     *
     * @since    1.0.0
     * @param    array    $settings    Current settings.
     */
    public static function render_ai_settings($settings) {
        ?>
        <div id="ai-settings" class="tab-content" style="display: none;">
            <h2><?php _e('AI Integration Settings', 'wc-recommendations'); ?></h2>
            <p><?php _e('Configure AI-powered recommendation features.', 'wc-recommendations'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable AI Integration', 'wc-recommendations'); ?></th>
                    <td>
                        <label for="enable_ai">
                            <input type="checkbox" name="settings[enable_ai]" id="enable_ai" value="yes" <?php checked($settings['enable_ai'], 'yes'); ?>>
                            <?php _e('Enable advanced AI-powered recommendation algorithms', 'wc-recommendations'); ?>
                        </label>
                        <p class="description"><?php _e('This enables AI-powered hybrid recommendations, context-aware recommendations, and other AI features.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr class="ai-setting" <?php echo $settings['enable_ai'] !== 'yes' ? 'style="display:none"' : ''; ?>>
                    <th scope="row"><?php _e('OpenAI API Key', 'wc-recommendations'); ?></th>
                    <td>
                        <input type="password" class="regular-text" name="settings[ai_api_key]" id="ai_api_key" value="<?php echo esc_attr($settings['ai_api_key']); ?>">
                        <p class="description"><?php _e('Enter your OpenAI API key. You can get one from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a>.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr class="ai-setting" <?php echo $settings['enable_ai'] !== 'yes' ? 'style="display:none"' : ''; ?>>
                    <th scope="row"><?php _e('API Endpoint', 'wc-recommendations'); ?></th>
                    <td>
                        <input type="url" class="regular-text" name="settings[ai_api_endpoint]" id="ai_api_endpoint" value="<?php echo esc_url($settings['ai_api_endpoint']); ?>">
                        <p class="description"><?php _e('OpenAI API endpoint URL. Default is https://api.openai.com/v1', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr class="ai-setting" <?php echo $settings['enable_ai'] !== 'yes' ? 'style="display:none"' : ''; ?>>
                    <th scope="row"><?php _e('AI Model', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[ai_model]" id="ai_model">
                            <option value="gpt-3.5-turbo" <?php selected($settings['ai_model'], 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php selected($settings['ai_model'], 'gpt-4'); ?>>GPT-4</option>
                            <option value="gpt-4-turbo" <?php selected($settings['ai_model'], 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                        </select>
                        <p class="description"><?php _e('Select the AI model to use for recommendations. More advanced models may provide better results but cost more.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr class="ai-setting" <?php echo $settings['enable_ai'] !== 'yes' ? 'style="display:none"' : ''; ?>>
                    <th scope="row"><?php _e('AI Features', 'wc-recommendations'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('AI Features', 'wc-recommendations'); ?></span></legend>
                            
                            <label for="enable_smart_bundles">
                                <input type="checkbox" name="settings[enable_smart_bundles]" id="enable_smart_bundles" value="yes" <?php checked($settings['enable_smart_bundles'], 'yes'); ?>>
                                <?php _e('Enable Smart Product Bundles', 'wc-recommendations'); ?>
                            </label>
                            <p class="description"><?php _e('AI-generated product bundles with automatic complementary product selections and dynamic pricing.', 'wc-recommendations'); ?></p>
                            <br>
                            
                            <label for="enable_ai_content">
                                <input type="checkbox" name="settings[enable_ai_content]" id="enable_ai_content" value="yes" <?php checked($settings['enable_ai_content'], 'yes'); ?>>
                                <?php _e('Enable AI-Generated Content', 'wc-recommendations'); ?>
                            </label>
                            <p class="description"><?php _e('Generate personalized product descriptions, recommendation explanations, and product summaries.', 'wc-recommendations'); ?></p>
                            <br>
                            
                            <label for="enable_real_time_personalization">
                                <input type="checkbox" name="settings[enable_real_time_personalization]" id="enable_real_time_personalization" value="yes" <?php checked($settings['enable_real_time_personalization'], 'yes'); ?>>
                                <?php _e('Enable Real-Time Personalization', 'wc-recommendations'); ?>
                            </label>
                            <p class="description"><?php _e('Update recommendations in real-time based on user behavior during their current session.', 'wc-recommendations'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <script>
                jQuery(document).ready(function($) {
                    // Toggle AI settings
                    $('#enable_ai').on('change', function() {
                        if ($(this).is(':checked')) {
                            $('.ai-setting').show();
                        } else {
                            $('.ai-setting').hide();
                        }
                    });
                });
            </script>
        </div>
        <?php
    }
    
    /**
     * Add advanced settings tab content.
     *
     * @since    1.0.0
     * @param    array    $settings    Current settings.
     */
    public static function render_advanced_settings($settings) {
        ?>
        <div id="advanced-settings" class="tab-content" style="display: none;">
            <h2><?php _e('Advanced Settings', 'wc-recommendations'); ?></h2>
            <p><?php _e('Configure advanced features and options.', 'wc-recommendations'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Exit Intent Popup', 'wc-recommendations'); ?></th>
                    <td>
                        <label for="enable_exit_intent">
                            <input type="checkbox" name="settings[enable_exit_intent]" id="enable_exit_intent" value="yes" <?php checked($settings['enable_exit_intent'], 'yes'); ?>>
                            <?php _e('Enable exit intent recommendation popup', 'wc-recommendations'); ?>
                        </label>
                        <p class="description"><?php _e('Shows personalized recommendations in a popup when users are about to leave your site.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Uninstall Settings', 'wc-recommendations'); ?></th>
                    <td>
                        <label for="complete_uninstall">
                            <input type="checkbox" name="settings[complete_uninstall]" id="complete_uninstall" value="yes" <?php checked($settings['complete_uninstall'], 'yes'); ?>>
                            <?php _e('Complete uninstall (remove all data when plugin is deleted)', 'wc-recommendations'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, all plugin data, including database tables and settings, will be removed when the plugin is deleted.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}