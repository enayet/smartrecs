/**
 * WooCommerce Product Recommendations Admin Scripts
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

(function($) {
    'use strict';

    // Initialize the admin functionality
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
        
        // Initialize all modules
        initTabs();
        initSettingsForm();
    });

})(jQuery);