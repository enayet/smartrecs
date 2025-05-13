<?php
/**
 * Admin analytics view.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap wc-recommendations-analytics">
    <h1><?php _e('Recommendation Analytics', 'wc-recommendations'); ?></h1>
    
    <div class="date-range-filter">
        <form id="analytics-date-form">
            <label for="start-date"><?php _e('From:', 'wc-recommendations'); ?></label>
            <input type="date" id="start-date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            
            <label for="end-date"><?php _e('To:', 'wc-recommendations'); ?></label>
            <input type="date" id="end-date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            
            <button type="submit" class="button"><?php _e('Filter', 'wc-recommendations'); ?></button>
        </form>
    </div>
    
    <div class="analytics-summary">
        <div class="analytics-card">
            <h3><?php _e('Total Impressions', 'wc-recommendations'); ?></h3>
            <div class="analytics-count" id="total-impressions">--</div>
        </div>
        
        <div class="analytics-card">
            <h3><?php _e('Total Clicks', 'wc-recommendations'); ?></h3>
            <div class="analytics-count" id="total-clicks">--</div>
        </div>
        
        <div class="analytics-card">
            <h3><?php _e('Click-Through Rate', 'wc-recommendations'); ?></h3>
            <div class="analytics-count" id="total-ctr">--</div>
        </div>
        
        <div class="analytics-card">
            <h3><?php _e('Estimated Revenue', 'wc-recommendations'); ?></h3>
            <div class="analytics-count" id="total-revenue">--</div>
        </div>
    </div>
    
    <div class="postbox">
        <div class="postbox-header">
            <h2><?php _e('Impressions & Clicks Over Time', 'wc-recommendations'); ?></h2>
        </div>
        <div class="inside">
            <div class="analytics-chart-container">
                <canvas id="impressions-clicks-chart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="analytics-row">
        <div class="analytics-col">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Recommendation Type Performance', 'wc-recommendations'); ?></h2>
                </div>
                <div class="inside">
                    <div class="analytics-chart-container">
                        <canvas id="recommendation-types-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="analytics-col">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Placement Performance', 'wc-recommendations'); ?></h2>
                </div>
                <div class="inside">
                    <div class="analytics-chart-container">
                        <canvas id="placement-performance-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="analytics-row">
        <div class="analytics-col">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Top Performing Products', 'wc-recommendations'); ?></h2>
                </div>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'wc-recommendations'); ?></th>
                                <th><?php _e('Clicks', 'wc-recommendations'); ?></th>
                                <th><?php _e('Price', 'wc-recommendations'); ?></th>
                                <th><?php _e('Actions', 'wc-recommendations'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="top-products-table">
                            <tr>
                                <td colspan="4"><?php _e('Loading...', 'wc-recommendations'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="analytics-col">
            <div class="postbox">
                <div class="postbox-header">
                    <h2><?php _e('Revenue by Recommendation Type', 'wc-recommendations'); ?></h2>
                </div>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped" id="revenue-table">
                        <thead>
                            <tr>
                                <th><?php _e('Recommendation Type', 'wc-recommendations'); ?></th>
                                <th><?php _e('Revenue', 'wc-recommendations'); ?></th>
                                <th><?php _e('Orders', 'wc-recommendations'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3"><?php _e('Loading...', 'wc-recommendations'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Check if AI integration is enabled
    $settings = WC_Recommendations_Settings::get_settings();
    if ($settings['enable_ai'] === 'yes') :
    ?>
    <div class="postbox">
        <div class="postbox-header">
            <h2><?php _e('AI Insights', 'wc-recommendations'); ?></h2>
        </div>
        <div class="inside">
            <div class="ai-assistant-container">
                <div class="ai-assistant-header">
                    <div class="ai-assistant-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 1.11-.9 2-2 2-1.1 0-2 .9-2 2v2h2v-2c2.21 0 4-1.79 4-4s-1.79-4-4-4zm-2 10h4v2h-4v-2z" fill="currentColor"/></svg>
                    </div>
                    <div class="ai-assistant-title"><?php _e('AI Recommendation Insights', 'wc-recommendations'); ?></div>
                </div>
                <div class="ai-assistant-message">
                    <?php _e('Click "Generate AI Insights" to analyze your recommendation data and get intelligent suggestions for improving performance.', 'wc-recommendations'); ?>
                </div>
                <div class="ai-assistant-suggestions"></div>
                <div class="ai-assistant-actions">
                    <button type="button" class="button run-ai-analysis-button"><?php _e('Generate AI Insights', 'wc-recommendations'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Load initial data
        loadAnalyticsData();
        
        // Handle date form submission
        $('#analytics-date-form').on('submit', function(e) {
            e.preventDefault();
            loadAnalyticsData();
        });
        
        function loadAnalyticsData() {
            var startDate = $('#start-date').val();
            var endDate = $('#end-date').val();
            
            // Update URL parameters
            var url = new URL(window.location.href);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            window.history.replaceState({}, '', url);
            
            // Load summary data
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_recommendations_get_analytics',
                    nonce: wc_recommendations_admin.nonce.analytics,
                    data_type: 'summary',
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        // Update summary cards
                        $('#total-impressions').text(response.data.impressions.toLocaleString());
                        $('#total-clicks').text(response.data.clicks.toLocaleString());
                        $('#total-ctr').text(response.data.ctr + '%');
                        $('#total-revenue').text('$' + response.data.estimated_revenue.toLocaleString());
                    }
                }
            });
            
            // Load various analytics data types
            loadAnalyticsDataType('impressions', startDate, endDate);
            loadAnalyticsDataType('clicks', startDate, endDate);
            loadAnalyticsDataType('conversions', startDate, endDate);
            loadAnalyticsDataType('placements', startDate, endDate);
            loadAnalyticsDataType('revenue', startDate, endDate);
            loadAnalyticsDataType('top_products', startDate, endDate);
        }
        
        function loadAnalyticsDataType(dataType, startDate, endDate) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_recommendations_get_analytics',
                    nonce: wc_recommendations_admin.nonce.analytics,
                    data_type: dataType,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        switch (dataType) {
                            case 'impressions':
                                window.wc_recommendations_charts.updateImpressionsChart(response.data);
                                break;
                            case 'clicks':
                                window.wc_recommendations_charts.updateClicksChart(response.data);
                                break;
                            case 'conversions':
                                window.wc_recommendations_charts.updateRecommendationTypesChart(response.data);
                                break;
                            case 'placements':
                                window.wc_recommendations_charts.updatePlacementChart(response.data);
                                break;
                            case 'revenue':
                                window.wc_recommendations_charts.updateRevenueSummary(response.data);
                                break;
                            case 'top_products':
                                window.wc_recommendations_charts.updateTopProducts(response.data);
                                break;
                        }
                    }
                }
            });
        }
        
        // AI Analysis button
        $('.run-ai-analysis-button').on('click', function() {
            $(this).prop('disabled', true).text('Analyzing...');
            var $container = $('.ai-assistant-container');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_recommendations_run_ai_analysis',
                    nonce: wc_recommendations_admin.nonce.settings
                },
                success: function(response) {
                    if (response.success) {
                        $container.find('.ai-assistant-message').html(response.data.message);
                        
                        // If there are suggestions, display them
                        if (response.data.suggestions && response.data.suggestions.length > 0) {
                            let suggestionsHtml = '<div class="ai-suggestions-grid">';
                            
                            response.data.suggestions.forEach(function(suggestion) {
                                suggestionsHtml += `
                                    <div class="ai-suggestion-card">
                                        <div class="ai-suggestion-title">${suggestion.title}</div>
                                        <div class="ai-suggestion-desc">${suggestion.description}</div>
                                        <button class="button apply-suggestion-button" data-suggestion-id="${suggestion.id}">Apply</button>
                                    </div>
                                `;
                            });
                            
                            suggestionsHtml += '</div>';
                            $container.find('.ai-assistant-suggestions').html(suggestionsHtml);
                        }
                    } else {
                        $container.find('.ai-assistant-message').html('Error running AI analysis. Please try again.');
                    }
                    
                    $('.run-ai-analysis-button').prop('disabled', false).text('Generate AI Insights');
                }
            });
        });
        
        // Apply AI suggestion
        $(document).on('click', '.apply-suggestion-button', function() {
            const suggestionId = $(this).data('suggestion-id');
            $(this).prop('disabled', true).text('Applying...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_recommendations_apply_suggestion',
                    nonce: wc_recommendations_admin.nonce.settings,
                    suggestion_id: suggestionId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Suggestion applied successfully!');
                        location.reload();
                    } else {
                        alert('Error applying suggestion. Please try again.');
                        $('.apply-suggestion-button').prop('disabled', false).text('Apply');
                    }
                }
            });
        });
    });
</script>