/**
 * WooCommerce Product Recommendations Admin Scripts
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

(function($) {
    'use strict';

    // Initialize the admin dashboard
    $(document).ready(function() {
        // Tab navigation
        function initTabs() {
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
                
                // Update URL hash
                window.location.hash = $(this).attr('href');
            });
            
            // Check for hash in URL
            if (window.location.hash) {
                const tab = $('.nav-tab-wrapper a[href="' + window.location.hash + '"]');
                if (tab.length) {
                    tab.trigger('click');
                }
            }
        }
        
        // Settings form submission
        function initSettingsForm() {
            $('#wc-recommendations-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                // Show spinner
                $(this).find('.spinner').css('visibility', 'visible');
                
                // Get form data
                const formData = $(this).serializeArray();
                
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
        }
        
        // Charts and analytics
        function initAnalytics() {
            if ($('#analytics-date-form').length) {
                // Date filter form submission
                $('#analytics-date-form').on('submit', function(e) {
                    e.preventDefault();
                    loadAnalyticsData();
                });
                
                // Load initial data
                loadAnalyticsData();
            }
        }
        
        // Load analytics data from AJAX
        function loadAnalyticsData() {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            
            // Update URL parameters
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date', endDate);
            window.history.replaceState({}, '', url);
            
            // Load various analytics data types
            loadAnalyticsDataType('impressions', startDate, endDate);
            loadAnalyticsDataType('clicks', startDate, endDate);
            loadAnalyticsDataType('conversions', startDate, endDate);
            loadAnalyticsDataType('placements', startDate, endDate);
            loadAnalyticsDataType('revenue', startDate, endDate);
            loadAnalyticsDataType('top_products', startDate, endDate);
            loadAnalyticsDataType('user_engagement', startDate, endDate);
            
            // Load AI insights if available
            if (typeof loadAIInsights === 'function') {
                loadAIInsights(startDate, endDate);
            }
        }
        
        // Load specific analytics data type
        function loadAnalyticsDataType(dataType, startDate, endDate, callback) {
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
                                updateImpressionsChart(response.data);
                                break;
                            case 'clicks':
                                updateClicksChart(response.data);
                                break;
                            case 'conversions':
                                updateRecommendationTypesChart(response.data);
                                break;
                            case 'placements':
                                updatePlacementChart(response.data);
                                break;
                            case 'revenue':
                                updateRevenueSummary(response.data);
                                break;
                            case 'top_products':
                                updateTopProducts(response.data);
                                break;
                        }
                        
                        if (typeof callback === 'function') {
                            callback(response.data);
                        }
                    }
                }
            });
        }
        
        // A/B testing
        function initABTesting() {
            if ($('.wc-recommendations-testing').length) {
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
                    const variantCount = $('.variant-item').length;
                    const nextLetter = String.fromCharCode(97 + variantCount); // a, b, c, ...
                    
                    const variantHtml = `
                        <div class="variant-item">
                            <h4>Variant ${nextLetter.toUpperCase()}</h4>
                            <div class="form-field">
                                <label for="variant-${nextLetter}-name">Name</label>
                                <input type="text" id="variant-${nextLetter}-name" name="variants[${variantCount}][name]" value="Variant ${nextLetter.toUpperCase()}" required>
                            </div>
                            
                            <div class="form-field">
                                <label for="variant-${nextLetter}-type">Algorithm Type</label>
                                <select id="variant-${nextLetter}-type" name="variants[${variantCount}][type]" required>
                                    <option value="frequently_bought">Frequently Bought Together</option>
                                    <option value="also_viewed">Customers Also Viewed</option>
                                    <option value="similar" selected>Similar Products</option>
                                    <option value="personalized">Personalized Recommendations</option>
                                    <option value="enhanced">Enhanced Recommendations (ML)</option>
                                    <option value="seasonal">Seasonal Products</option>
                                    <option value="trending">Trending Products</option>
                                    <option value="ai_hybrid">AI Hybrid (Advanced)</option>
                                    <option value="context_aware">Context-Aware (Advanced)</option>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="variant-${nextLetter}-title">Display Title</label>
                                <input type="text" id="variant-${nextLetter}-title" name="variants[${variantCount}][title]" value="Recommended Products">
                                <p class="description">The title shown to customers above the recommendations.</p>
                            </div>
                            
                            <input type="hidden" name="variants[${variantCount}][id]" value="${nextLetter}">
                            
                            <button type="button" class="button button-link remove-variant-button">Remove</button>
                        </div>
                    `;
                    
                    $('.variants-container').append(variantHtml);
                });
                
                // Remove variant
                $(document).on('click', '.remove-variant-button', function() {
                    $(this).closest('.variant-item').remove();
                });
                
                // Create new test
                $('#new-test-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    const $form = $(this);
                    const $spinner = $form.find('.spinner');
                    
                    // Show spinner
                    $spinner.css('visibility', 'visible');
                    
                    // Get form data
                    const formData = $form.serializeArray();
                    
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
                            alert('Error creating test. Please try again.');
                        }
                    });
                });
                
                // View test results
                $('.view-results-button').on('click', function() {
                    const testId = $(this).data('test-id');
                    
                    // Show loading state
                    $('#test-results-table-body').html('<tr><td colspan="7">Loading results...</td></tr>');
                    
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
                                displayTestResults(response.data);
                            } else {
                                alert('Error loading test results. Please try again.');
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
                    const testId = $(this).data('test-id');
                    
                    if (confirm('Are you sure you want to activate this test? Any currently active test will be deactivated.')) {
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
                                    alert('Error activating test. Please try again.');
                                }
                            }
                        });
                    }
                });
                
                // End test
                $('.end-test-button').on('click', function() {
                    const testId = $(this).data('test-id');
                    
                    if (confirm('Are you sure you want to end this test? This will deactivate the test and finalize the results.')) {
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
                                    alert('Error ending test. Please try again.');
                                }
                            }
                        });
                    }
                });
            }
        }
        
        // AI Assistant functionality
        function initAIAssistant() {
            if ($('.ai-assistant-container').length) {
                // Run AI analysis
                $('.run-ai-analysis-button').on('click', function() {
                    $(this).prop('disabled', true).text('Analyzing...');
                    const $container = $('.ai-assistant-container');
                    
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
                            
                            $('.run-ai-analysis-button').prop('disabled', false).text('Run AI Analysis');
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
                
                // Train AI model
                $('.train-ai-model-button').on('click', function() {
                    $(this).prop('disabled', true).text('Training...');
                    
                    const $progressContainer = $('.ai-training-progress');
                    const $progressBar = $progressContainer.find('.ai-progress-bar-inner');
                    const $status = $progressContainer.find('.ai-training-status');
                    
                    $progressContainer.show();
                    
                    // Simulate training progress
                    let progress = 0;
                    const interval = setInterval(function() {
                        progress += Math.random() * 10;
                        if (progress > 100) {
                            progress = 100;
                            clearInterval(interval);
                        }
                        
                        $progressBar.css('width', progress + '%');
                        $status.text(`Training in progress: ${Math.floor(progress)}%`);
                        
                        if (progress === 100) {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'wc_recommendations_complete_training',
                                    nonce: wc_recommendations_admin.nonce.settings
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $status.text('Training completed successfully!');
                                        $('.train-ai-model-button').prop('disabled', false).text('Train AI Model');
                                        
                                        // Update AI model status
                                        $('.ai-model-status').text('Trained: ' + new Date().toLocaleString());
                                    } else {
                                        $status.text('Error completing training. Please try again.');
                                        $('.train-ai-model-button').prop('disabled', false).text('Train AI Model');
                                    }
                                }
                            });
                        }
                    }, 500);
                });
            }
        }
        
        // Initialize all modules
        initTabs();
        initSettingsForm();
        initAnalytics();
        initABTesting();
        initAIAssistant();
    });

})(jQuery);