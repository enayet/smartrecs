<?php
/**
 * Admin settings view.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get default values for settings
$defaults = array(
    'show_on_product'     => 'yes',
    'show_on_cart'        => 'yes',
    'show_on_checkout'    => 'no',
    'show_on_thankyou'    => 'yes',
    'product_page_type'   => 'frequently_bought',
    'cart_page_type'      => 'also_viewed',
    'checkout_page_type'  => 'personalized',
    'thankyou_page_type'  => 'similar',
    'layout'              => 'grid',
    'columns'             => 4,
    'limit'               => 4,
    'track_anonymous'     => 'yes',
    'track_logged_in'     => 'yes',
    'privacy_compliant'   => 'yes',
    'data_retention_days' => 90
);

// Merge defaults with saved settings
$settings = wp_parse_args($settings, $defaults);
?>

<div class="wrap wc-recommendations-settings">
    <h1><?php _e('WooCommerce Product Recommendations', 'wc-recommendations'); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="#display-settings" class="nav-tab nav-tab-active"><?php _e('Display Settings', 'wc-recommendations'); ?></a>
        <a href="#algorithm-settings" class="nav-tab"><?php _e('Algorithm Settings', 'wc-recommendations'); ?></a>
        <a href="#tracking-settings" class="nav-tab"><?php _e('Tracking Settings', 'wc-recommendations'); ?></a>
        <a href="#help" class="nav-tab"><?php _e('Help & Support', 'wc-recommendations'); ?></a>
    </h2>
    
    <div class="notice notice-success settings-saved-notice" style="display: none;">
        <p><?php _e('Settings saved successfully!', 'wc-recommendations'); ?></p>
    </div>
    
    <form id="wc-recommendations-settings-form" class="wc-recommendations-settings-form">
        <!-- Display Settings -->
        <div id="display-settings" class="tab-content">
            <h2><?php _e('Display Settings', 'wc-recommendations'); ?></h2>
            <p><?php _e('Configure where and how recommendations are displayed in your store.', 'wc-recommendations'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Placement Locations', 'wc-recommendations'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Placement Locations', 'wc-recommendations'); ?></span></legend>
                            
                            <label for="show_on_product">
                                <input type="checkbox" name="settings[show_on_product]" id="show_on_product" value="yes" <?php checked($settings['show_on_product'], 'yes'); ?>>
                                <?php _e('Show on product pages', 'wc-recommendations'); ?>
                            </label><br>
                            
                            <label for="show_on_cart">
                                <input type="checkbox" name="settings[show_on_cart]" id="show_on_cart" value="yes" <?php checked($settings['show_on_cart'], 'yes'); ?>>
                                <?php _e('Show on cart page', 'wc-recommendations'); ?>
                            </label><br>
                            
                            <label for="show_on_checkout">
                                <input type="checkbox" name="settings[show_on_checkout]" id="show_on_checkout" value="yes" <?php checked($settings['show_on_checkout'], 'yes'); ?>>
                                <?php _e('Show on checkout page', 'wc-recommendations'); ?>
                            </label><br>
                            
                            <label for="show_on_thankyou">
                                <input type="checkbox" name="settings[show_on_thankyou]" id="show_on_thankyou" value="yes" <?php checked($settings['show_on_thankyou'], 'yes'); ?>>
                                <?php _e('Show on thank you page', 'wc-recommendations'); ?>
                            </label><br>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Layout Style', 'wc-recommendations'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('Layout Style', 'wc-recommendations'); ?></span></legend>
                            
                            <label>
                                <input type="radio" name="settings[layout]" value="grid" <?php checked($settings['layout'], 'grid'); ?>>
                                <?php _e('Grid', 'wc-recommendations'); ?>
                            </label><br>
                            
                            <label>
                                <input type="radio" name="settings[layout]" value="carousel" <?php checked($settings['layout'], 'carousel'); ?>>
                                <?php _e('Carousel', 'wc-recommendations'); ?>
                            </label><br>
                            
                            <label>
                                <input type="radio" name="settings[layout]" value="list" <?php checked($settings['layout'], 'list'); ?>>
                                <?php _e('List', 'wc-recommendations'); ?>
                            </label><br>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Number of Columns', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[columns]" id="columns">
                            <?php for ($i = 1; $i <= 6; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php selected($settings['columns'], $i); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="description"><?php _e('Number of columns for grid and carousel layouts.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Number of Products', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[limit]" id="limit">
                            <?php for ($i = 1; $i <= 12; $i++) : ?>
                                <option value="<?php echo $i; ?>" <?php selected($settings['limit'], $i); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="description"><?php _e('Number of products to display in each recommendation section.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Algorithm Settings -->
        <div id="algorithm-settings" class="tab-content" style="display: none;">
            <h2><?php _e('Algorithm Settings', 'wc-recommendations'); ?></h2>
            <p><?php _e('Configure which recommendation algorithms are used in different locations.', 'wc-recommendations'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Product Page Recommendations', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[product_page_type]" id="product_page_type">
                            <option value="frequently_bought" <?php selected($settings['product_page_type'], 'frequently_bought'); ?>><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                            <option value="also_viewed" <?php selected($settings['product_page_type'], 'also_viewed'); ?>><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                            <option value="similar" <?php selected($settings['product_page_type'], 'similar'); ?>><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                            <option value="personalized" <?php selected($settings['product_page_type'], 'personalized'); ?>><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                            <option value="enhanced" <?php selected($settings['product_page_type'], 'enhanced'); ?>><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                            <option value="seasonal" <?php selected($settings['product_page_type'], 'seasonal'); ?>><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                            <option value="trending" <?php selected($settings['product_page_type'], 'trending'); ?>><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Cart Page Recommendations', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[cart_page_type]" id="cart_page_type">
                            <option value="frequently_bought" <?php selected($settings['cart_page_type'], 'frequently_bought'); ?>><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                            <option value="also_viewed" <?php selected($settings['cart_page_type'], 'also_viewed'); ?>><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                            <option value="similar" <?php selected($settings['cart_page_type'], 'similar'); ?>><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                            <option value="personalized" <?php selected($settings['cart_page_type'], 'personalized'); ?>><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                            <option value="enhanced" <?php selected($settings['cart_page_type'], 'enhanced'); ?>><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                            <option value="seasonal" <?php selected($settings['cart_page_type'], 'seasonal'); ?>><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                            <option value="trending" <?php selected($settings['cart_page_type'], 'trending'); ?>><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Checkout Page Recommendations', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[checkout_page_type]" id="checkout_page_type">
                            <option value="frequently_bought" <?php selected($settings['checkout_page_type'], 'frequently_bought'); ?>><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                            <option value="also_viewed" <?php selected($settings['checkout_page_type'], 'also_viewed'); ?>><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                            <option value="similar" <?php selected($settings['checkout_page_type'], 'similar'); ?>><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                            <option value="personalized" <?php selected($settings['checkout_page_type'], 'personalized'); ?>><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                            <option value="enhanced" <?php selected($settings['checkout_page_type'], 'enhanced'); ?>><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                            <option value="seasonal" <?php selected($settings['checkout_page_type'], 'seasonal'); ?>><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                            <option value="trending" <?php selected($settings['checkout_page_type'], 'trending'); ?>><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Thank You Page Recommendations', 'wc-recommendations'); ?></th>
                    <td>
                        <select name="settings[thankyou_page_type]" id="thankyou_page_type">
                            <option value="frequently_bought" <?php selected($settings['thankyou_page_type'], 'frequently_bought'); ?>><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                            <option value="also_viewed" <?php selected($settings['thankyou_page_type'], 'also_viewed'); ?>><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                            <option value="similar" <?php selected($settings['thankyou_page_type'], 'similar'); ?>><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                            <option value="personalized" <?php selected($settings['thankyou_page_type'], 'personalized'); ?>><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                            <option value="enhanced" <?php selected($settings['thankyou_page_type'], 'enhanced'); ?>><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                            <option value="seasonal" <?php selected($settings['thankyou_page_type'], 'seasonal'); ?>><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                            <option value="trending" <?php selected($settings['thankyou_page_type'], 'trending'); ?>><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Algorithm Descriptions', 'wc-recommendations'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Shows products that are often purchased together with the current product. Uses collaborative filtering based on order history.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Shows products that customers typically view after viewing the current product. Based on browsing behavior.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Similar Products', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Shows products that are similar to the current product based on categories, tags, and attributes. Uses content-based filtering.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Shows products tailored to the individual customer based on their browsing and purchase history.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Uses machine learning to combine multiple signals (views, purchases, attributes) for more accurate recommendations.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Seasonal Products', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Shows products relevant to the current season or upcoming holidays.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Trending Products', 'wc-recommendations'); ?></th>
                    <td>
                        <p><?php _e('Shows currently popular products based on recent views and purchases.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Tracking Settings -->
        <div id="tracking-settings" class="tab-content" style="display: none;">
            <h2><?php _e('Tracking Settings', 'wc-recommendations'); ?></h2>
            <p><?php _e('Configure user behavior tracking settings for generating recommendations.', 'wc-recommendations'); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('User Tracking', 'wc-recommendations'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e('User Tracking', 'wc-recommendations'); ?></span></legend>
                            
                            <label for="track_anonymous">
                                <input type="checkbox" name="settings[track_anonymous]" id="track_anonymous" value="yes" <?php checked($settings['track_anonymous'], 'yes'); ?>>
                                <?php _e('Track anonymous users (using cookies)', 'wc-recommendations'); ?>
                            </label><br>
                            
                            <label for="track_logged_in">
                                <input type="checkbox" name="settings[track_logged_in]" id="track_logged_in" value="yes" <?php checked($settings['track_logged_in'], 'yes'); ?>>
                                <?php _e('Track logged in users', 'wc-recommendations'); ?>
                            </label><br>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Privacy Compliance', 'wc-recommendations'); ?></th>
                    <td>
                        <label for="privacy_compliant">
                            <input type="checkbox" name="settings[privacy_compliant]" id="privacy_compliant" value="yes" <?php checked($settings['privacy_compliant'], 'yes'); ?>>
                            <?php _e('Enable GDPR/Privacy compliance features', 'wc-recommendations'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, tracking will only occur after users have accepted cookies, and data will be automatically cleaned up based on retention settings.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Data Retention', 'wc-recommendations'); ?></th>
                    <td>
                        <input type="number" min="1" max="365" name="settings[data_retention_days]" id="data_retention_days" value="<?php echo esc_attr($settings['data_retention_days']); ?>">
                        <p class="description"><?php _e('Number of days to keep user interaction data (views, clicks). Set to a higher value for better recommendations but shorter for privacy. Purchase data is kept indefinitely.', 'wc-recommendations'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Help & Support -->
        <div id="help" class="tab-content" style="display: none;">
            <h2><?php _e('Help & Support', 'wc-recommendations'); ?></h2>
            
            <h3><?php _e('Shortcodes', 'wc-recommendations'); ?></h3>
            <p><?php _e('Use the following shortcode to display recommendations anywhere in your content:', 'wc-recommendations'); ?></p>
            <code>[product_recommendations type="frequently_bought" product_id="123" limit="4" title="Custom Title"]</code>
            
            <h4><?php _e('Available Parameters:', 'wc-recommendations'); ?></h4>
            <ul>
                <li><code>type</code>: <?php _e('Recommendation algorithm type (frequently_bought, also_viewed, similar, personalized, enhanced, seasonal, trending)', 'wc-recommendations'); ?></li>
                <li><code>product_id</code>: <?php _e('Specify a product ID (optional - uses current product on product pages)', 'wc-recommendations'); ?></li>
                <li><code>limit</code>: <?php _e('Number of products to display (default: 4)', 'wc-recommendations'); ?></li>
                <li><code>title</code>: <?php _e('Custom title for the recommendations section (optional)', 'wc-recommendations'); ?></li>
            </ul>
            
            <h3><?php _e('Widget', 'wc-recommendations'); ?></h3>
            <p><?php _e('You can also use the Product Recommendations widget in any widget area. Find it in Appearance > Widgets.', 'wc-recommendations'); ?></p>
            
            <h3><?php _e('Support', 'wc-recommendations'); ?></h3>
            <p><?php _e('For support, please contact us at:', 'wc-recommendations'); ?> <a href="mailto:support@example.com">support@example.com</a></p>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary" id="save-settings"><?php _e('Save Settings', 'wc-recommendations'); ?></button>
            <span class="spinner" style="float: none; margin-top: 0;"></span>
        </p>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tab navigation
        $('.nav-tab-wrapper a').on('click', function(e) {
            e.preventDefault();
            
            // Hide all tab content
            $('.tab-content').hide();
            
            // Remove active class from all tabs
            $('.nav-tab').removeClass('nav-tab-active');
            
            // Show the selected tab content
            $($(this).attr('href')).show();
            
            // Add active class to the clicked tab
            $(this).addClass('nav-tab-active');
        });
        
        // Save settings via AJAX
        $('#wc-recommendations-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            // Show spinner
            $(this).find('.spinner').css('visibility', 'visible');
            
            // Get form data
            var formData = $(this).serializeArray();
            
            // Add action and nonce
            formData.push({
                name: 'action',
                value: 'wc_recommendations_save_settings'
            });
            
            formData.push({
                name: 'nonce',
                value: wc_recommendations_admin.nonce.settings
            });
            
            // Submit via AJAX
            $.post(ajaxurl, formData, function(response) {
                // Hide spinner
                $('.spinner').css('visibility', 'hidden');
                
                if (response.success) {
                    // Show success message
                    $('.settings-saved-notice').fadeIn().delay(3000).fadeOut();
                } else {
                    // Show error
                    alert(wc_recommendations_admin.i18n.error);
                }
            });
        });
    });
</script>