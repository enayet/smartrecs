<?php
/**
 * Admin A/B testing view.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap wc-recommendations-testing">
    <h1><?php _e('A/B Testing for Recommendations', 'wc-recommendations'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('A/B testing allows you to compare different recommendation algorithms and find out which ones perform best for your store. Create tests, analyze results, and improve your recommendation strategy.', 'wc-recommendations'); ?></p>
    </div>
    
    <div class="ab-testing-container">
        <!-- Test List -->
        <div class="tests-list">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Your A/B Tests', 'wc-recommendations'); ?></h2>
                    <button type="button" class="button button-primary new-test-button"><?php _e('Create New Test', 'wc-recommendations'); ?></button>
                </div>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'wc-recommendations'); ?></th>
                                <th><?php _e('Status', 'wc-recommendations'); ?></th>
                                <th><?php _e('Start Date', 'wc-recommendations'); ?></th>
                                <th><?php _e('End Date', 'wc-recommendations'); ?></th>
                                <th><?php _e('Actions', 'wc-recommendations'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tests)) : ?>
                                <tr>
                                    <td colspan="5"><?php _e('No tests created yet. Create your first A/B test to start optimizing your recommendations.', 'wc-recommendations'); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($tests as $test_id => $test) : ?>
                                    <tr>
                                        <td><?php echo esc_html($test['name']); ?></td>
                                        <td>
                                            <?php if (!empty($test['active']) && $test['active']) : ?>
                                                <span class="status-active"><?php _e('Active', 'wc-recommendations'); ?></span>
                                            <?php elseif (!empty($test['end_date'])) : ?>
                                                <span class="status-completed"><?php _e('Completed', 'wc-recommendations'); ?></span>
                                            <?php else : ?>
                                                <span class="status-inactive"><?php _e('Inactive', 'wc-recommendations'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($test['start_date']))); ?></td>
                                        <td>
                                            <?php
                                            if (!empty($test['end_date'])) {
                                                echo esc_html(date_i18n(get_option('date_format'), strtotime($test['end_date'])));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button view-results-button" data-test-id="<?php echo esc_attr($test_id); ?>"><?php _e('View Results', 'wc-recommendations'); ?></button>
                                            
                                            <?php if (empty($test['active']) || !$test['active']) : ?>
                                                <button type="button" class="button activate-test-button" data-test-id="<?php echo esc_attr($test_id); ?>"><?php _e('Activate', 'wc-recommendations'); ?></button>
                                            <?php else : ?>
                                                <button type="button" class="button end-test-button" data-test-id="<?php echo esc_attr($test_id); ?>"><?php _e('End Test', 'wc-recommendations'); ?></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Create New Test Form -->
        <div class="create-test-form" style="display: none;">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Create New A/B Test', 'wc-recommendations'); ?></h2>
                    <button type="button" class="button cancel-button"><?php _e('Cancel', 'wc-recommendations'); ?></button>
                </div>
                <div class="inside">
                    <form id="new-test-form">
                        <div class="form-field">
                            <label for="test-name"><?php _e('Test Name', 'wc-recommendations'); ?></label>
                            <input type="text" id="test-name" name="test_name" required>
                            <p class="description"><?php _e('Give your test a descriptive name (e.g. "Product Page Algorithm Comparison").', 'wc-recommendations'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="test-description"><?php _e('Description', 'wc-recommendations'); ?></label>
                            <textarea id="test-description" name="test_description" rows="3"></textarea>
                            <p class="description"><?php _e('Optional: Describe the purpose of this test.', 'wc-recommendations'); ?></p>
                        </div>
                        
                        <h3><?php _e('Test Variants', 'wc-recommendations'); ?></h3>
                        <p><?php _e('Create at least two variants to compare different recommendation algorithms.', 'wc-recommendations'); ?></p>
                        
                        <div class="variants-container">
                            <!-- Variant A -->
                            <div class="variant-item">
                                <h4><?php _e('Variant A', 'wc-recommendations'); ?></h4>
                                <div class="form-field">
                                    <label for="variant-a-name"><?php _e('Name', 'wc-recommendations'); ?></label>
                                    <input type="text" id="variant-a-name" name="variants[0][name]" value="<?php _e('Variant A', 'wc-recommendations'); ?>" required>
                                </div>
                                
                                <div class="form-field">
                                    <label for="variant-a-type"><?php _e('Algorithm Type', 'wc-recommendations'); ?></label>
                                    <select id="variant-a-type" name="variants[0][type]" required>
                                        <option value="frequently_bought"><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                                        <option value="also_viewed"><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                                        <option value="similar"><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                                        <option value="personalized"><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                                        <option value="enhanced"><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                                        <option value="seasonal"><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                                        <option value="trending"><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                                        <option value="ai_hybrid"><?php _e('AI Hybrid (Advanced)', 'wc-recommendations'); ?></option>
                                        <option value="context_aware"><?php _e('Context-Aware (Advanced)', 'wc-recommendations'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="form-field">
                                    <label for="variant-a-title"><?php _e('Display Title', 'wc-recommendations'); ?></label>
                                    <input type="text" id="variant-a-title" name="variants[0][title]" value="<?php _e('Recommended Products', 'wc-recommendations'); ?>">
                                    <p class="description"><?php _e('The title shown to customers above the recommendations.', 'wc-recommendations'); ?></p>
                                </div>
                                
                                <input type="hidden" name="variants[0][id]" value="a">
                            </div>
                            
                            <!-- Variant B -->
                            <div class="variant-item">
                                <h4><?php _e('Variant B', 'wc-recommendations'); ?></h4>
                                <div class="form-field">
                                    <label for="variant-b-name"><?php _e('Name', 'wc-recommendations'); ?></label>
                                    <input type="text" id="variant-b-name" name="variants[1][name]" value="<?php _e('Variant B', 'wc-recommendations'); ?>" required>
                                </div>
                                
                                <div class="form-field">
                                    <label for="variant-b-type"><?php _e('Algorithm Type', 'wc-recommendations'); ?></label>
                                    <select id="variant-b-type" name="variants[1][type]" required>
                                        <option value="frequently_bought"><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                                        <option value="also_viewed" selected><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                                        <option value="similar"><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                                        <option value="personalized"><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                                        <option value="enhanced"><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                                        <option value="seasonal"><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                                        <option value="trending"><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                                        <option value="ai_hybrid"><?php _e('AI Hybrid (Advanced)', 'wc-recommendations'); ?></option>
                                        <option value="context_aware"><?php _e('Context-Aware (Advanced)', 'wc-recommendations'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="form-field">
                                    <label for="variant-b-title"><?php _e('Display Title', 'wc-recommendations'); ?></label>
                                    <input type="text" id="variant-b-title" name="variants[1][title]" value="<?php _e('Recommended Products', 'wc-recommendations'); ?>">
                                    <p class="description"><?php _e('The title shown to customers above the recommendations.', 'wc-recommendations'); ?></p>
                                </div>
                                
                                <input type="hidden" name="variants[1][id]" value="b">
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <button type="button" class="button add-variant-button"><?php _e('+ Add Another Variant', 'wc-recommendations'); ?></button>
                        </div>
                        
                        <div class="form-field">
                            <label for="test-active">
                                <input type="checkbox" id="test-active" name="test_active" value="1">
                                <?php _e('Activate test immediately', 'wc-recommendations'); ?>
                            </label>
                            <p class="description"><?php _e('If checked, this test will be activated as soon as it\'s created. Only one test can be active at a time.', 'wc-recommendations'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="test-location"><?php _e('Test Location', 'wc-recommendations'); ?></label>
                            <select id="test-location" name="test_location">
                                <option value="product"><?php _e('Product Pages', 'wc-recommendations'); ?></option>
                                <option value="cart"><?php _e('Cart Page', 'wc-recommendations'); ?></option>
                                <option value="checkout"><?php _e('Checkout Page', 'wc-recommendations'); ?></option>
                                <option value="thankyou"><?php _e('Thank You Page', 'wc-recommendations'); ?></option>
                            </select>
                            <p class="description"><?php _e('Where to run this test.', 'wc-recommendations'); ?></p>
                        </div>
                        
                        <div class="submit-container">
                            <button type="submit" class="button button-primary"><?php _e('Create Test', 'wc-recommendations'); ?></button>
                            <span class="spinner"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Test Results View -->
        <div class="test-results-view" style="display: none;">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Test Results', 'wc-recommendations'); ?></h2>
                    <button type="button" class="button back-to-tests-button"><?php _e('Back to Tests', 'wc-recommendations'); ?></button>
                </div>
                <div class="inside">
                    <div class="test-info">
                        <h3 id="test-results-name"></h3>
                        <p id="test-results-description"></p>
                        <div class="test-meta">
                            <span id="test-results-dates"></span>
                            <span id="test-results-status"></span>
                        </div>
                    </div>
                    
                    <div class="test-results-summary">
                        <div class="postbox">
                            <h3 class="hndle"><?php _e('Results Summary', 'wc-recommendations'); ?></h3>
                            <div class="inside">
                                <p id="test-summary-text"></p>
                                <div id="test-winner-container" style="display: none;">
                                    <h4><?php _e('Winner:', 'wc-recommendations'); ?> <span id="test-winner"></span></h4>
                                    <p id="test-winner-description"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="test-results-data">
                        <h3><?php _e('Variant Performance', 'wc-recommendations'); ?></h3>
                        <div class="test-chart-container">
                            <canvas id="test-results-chart" height="300"></canvas>
                        </div>
                        
                        <table class="wp-list-table widefat fixed striped test-results-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Variant', 'wc-recommendations'); ?></th>
                                    <th><?php _e('Algorithm', 'wc-recommendations'); ?></th>
                                    <th><?php _e('Impressions', 'wc-recommendations'); ?></th>
                                    <th><?php _e('Clicks', 'wc-recommendations'); ?></th>
                                    <th><?php _e('CTR', 'wc-recommendations'); ?></th>
                                    <th><?php _e('Conversions', 'wc-recommendations'); ?></th>
                                    <th><?php _e('Conv. Rate', 'wc-recommendations'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="test-results-table-body">
                                <tr>
                                    <td colspan="7"><?php _e('Loading results...', 'wc-recommendations'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        var variantCount = 2;
        
        // Show create test form
        $('.new-test-button').on('click', function() {
            $('.tests-list').hide();
            $('.create-test-form').show();
        });
        
        // Cancel creating test
        $('.cancel-button').on('click', function() {
            $('.create-test-form').hide();
            $('.tests-list').show();
        });
        
        // Add another variant
        $('.add-variant-button').on('click', function() {
            var nextLetter = String.fromCharCode(97 + variantCount); // a, b, c, ...
            
            var variantHtml = `
                <div class="variant-item">
                    <h4><?php _e('Variant', 'wc-recommendations'); ?> ${nextLetter.toUpperCase()}</h4>
                    <div class="form-field">
                        <label for="variant-${nextLetter}-name"><?php _e('Name', 'wc-recommendations'); ?></label>
                        <input type="text" id="variant-${nextLetter}-name" name="variants[${variantCount}][name]" value="<?php _e('Variant', 'wc-recommendations'); ?> ${nextLetter.toUpperCase()}" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="variant-${nextLetter}-type"><?php _e('Algorithm Type', 'wc-recommendations'); ?></label>
                        <select id="variant-${nextLetter}-type" name="variants[${variantCount}][type]" required>
                            <option value="frequently_bought"><?php _e('Frequently Bought Together', 'wc-recommendations'); ?></option>
                            <option value="also_viewed"><?php _e('Customers Also Viewed', 'wc-recommendations'); ?></option>
                            <option value="similar" selected><?php _e('Similar Products', 'wc-recommendations'); ?></option>
                            <option value="personalized"><?php _e('Personalized Recommendations', 'wc-recommendations'); ?></option>
                            <option value="enhanced"><?php _e('Enhanced Recommendations (ML)', 'wc-recommendations'); ?></option>
                            <option value="seasonal"><?php _e('Seasonal Products', 'wc-recommendations'); ?></option>
                            <option value="trending"><?php _e('Trending Products', 'wc-recommendations'); ?></option>
                            <option value="ai_hybrid"><?php _e('AI Hybrid (Advanced)', 'wc-recommendations'); ?></option>
                            <option value="context_aware"><?php _e('Context-Aware (Advanced)', 'wc-recommendations'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-field">
                        <label for="variant-${nextLetter}-title"><?php _e('Display Title', 'wc-recommendations'); ?></label>
                        <input type="text" id="variant-${nextLetter}-title" name="variants[${variantCount}][title]" value="<?php _e('Recommended Products', 'wc-recommendations'); ?>">
                        <p class="description"><?php _e('The title shown to customers above the recommendations.', 'wc-recommendations'); ?></p>
                    </div>
                    
                    <input type="hidden" name="variants[${variantCount}][id]" value="${nextLetter}">
                    
                    <button type="button" class="button button-link remove-variant-button"><?php _e('Remove', 'wc-recommendations'); ?></button>
                </div>
            `;
            
            $('.variants-container').append(variantHtml);
            variantCount++;
        });
        
        // Remove variant
        $(document).on('click', '.remove-variant-button', function() {
            $(this).closest('.variant-item').remove();
            variantCount--;
        });
        
        // Create new test
        $('#new-test-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $spinner = $form.find('.spinner');
            
            // Show spinner
            $spinner.css('visibility', 'visible');
            
            // Get form data
            var formData = $form.serializeArray();
            
            // Add action
            formData.push({
                name: 'action',
                value: 'wc_recommendations_create_test'
            });
            
            formData.push({
                name: 'nonce',
                value: wc_recommendations_admin.nonce.settings
            });
            
            // Submit via AJAX
            $.post(ajaxurl, formData, function(response) {
                // Hide spinner
                $spinner.css('visibility', 'hidden');
                
                if (response.success) {
                    // Reload page to show new test
                    location.reload();
                } else {
                    alert('<?php _e('Error creating test. Please try again.', 'wc-recommendations'); ?>');
                }
            });
        });
        
        // View test results
        $('.view-results-button').on('click', function() {
            var testId = $(this).data('test-id');
            
            // Show loading state
            $('#test-results-table-body').html('<tr><td colspan="7"><?php _e('Loading results...', 'wc-recommendations'); ?></td></tr>');
            
            // Load test results
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_recommendations_get_test_results',
                    nonce: wc_recommendations_admin.nonce.test_results,
                    test_id: testId
                },
                success: function(response) {
                    if (response.success) {
                        window.wc_recommendations_charts.displayTestResults(response.data);
                    } else {
                        alert('<?php _e('Error loading test results. Please try again.', 'wc-recommendations'); ?>');
                    }
                }
            });
            
            // Show results view
            $('.tests-list').hide();
            $('.test-results-view').show();
        });
        
        // Back to tests list
        $('.back-to-tests-button').on('click', function() {
            $('.test-results-view').hide();
            $('.tests-list').show();
        });
        
        // Activate test
        $('.activate-test-button').on('click', function() {
            var testId = $(this).data('test-id');
            
            if (confirm('<?php _e('Are you sure you want to activate this test? Any currently active test will be deactivated.', 'wc-recommendations'); ?>')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_activate_test',
                        nonce: wc_recommendations_admin.nonce.settings,
                        test_id: testId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error activating test. Please try again.', 'wc-recommendations'); ?>');
                        }
                    }
                });
            }
        });
        
        // End test
        $('.end-test-button').on('click', function() {
            var testId = $(this).data('test-id');
            
            if (confirm('<?php _e('Are you sure you want to end this test? This will deactivate the test and finalize the results.', 'wc-recommendations'); ?>')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_end_test',
                        nonce: wc_recommendations_admin.nonce.settings,
                        test_id: testId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('<?php _e('Error ending test. Please try again.', 'wc-recommendations'); ?>');
                        }
                    }
                });
            }
        });
    });
</script>