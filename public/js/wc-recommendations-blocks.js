/**
 * WooCommerce Product Recommendations Blocks Scripts
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

(function($) {
    'use strict';

    // Wait for DOM and blocks to be ready
    $(function() {
        // Function to initialize tracking on blocks
        function initBlocksTracking() {
            // Check if tracking is enabled
            if (typeof wc_recommendations_params === 'undefined' || 
                typeof wc_recommendations_params.tracking_enabled === 'undefined' || 
                !wc_recommendations_params.tracking_enabled) {
                return;
            }
            
            // Use event delegation for product links in blocks
            $(document.body).on('click', '.wc-block-grid__product-link, .wc-block-components-product-link', function(e) {
                // Only track if we have the proper data
                var $productElement = $(this).closest('.wc-block-grid__product, .wc-block-components-product');
                
                if (!$productElement.length) {
                    return;
                }
                
                // Get product ID - blocks store it differently
                var productId = $productElement.data('product-id') || 
                               $productElement.find('[data-product-id]').data('product-id') ||
                               $productElement.attr('id')?.replace('product-', '') || 0;
                
                if (!productId) {
                    return;
                }
                
                // Determine context - what page we're on
                var contextId = 0;
                var type = 'blocks';
                var placement = 'unknown';
                
                // Are we on a product page?
                if (wc_recommendations_params.product_id) {
                    contextId = wc_recommendations_params.product_id;
                    placement = 'product';
                } 
                // Are we on cart page?
                else if ($('.wc-block-cart').length) {
                    placement = 'cart';
                }
                // Are we on checkout page?
                else if ($('.wc-block-checkout').length) {
                    placement = 'checkout';
                }
                
                // Track click via AJAX
                $.ajax({
                    url: wc_recommendations_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_track_click',
                        nonce: wc_recommendations_params.track_nonce,
                        product_id: contextId,
                        recommended_id: productId,
                        recommendation_type: type,
                        placement: placement
                    }
                });
            });
        }
        
        // Initialize when blocks are loaded
        if (typeof wp !== 'undefined' && wp.blocks) {
            // Initialize now
            initBlocksTracking();
            
            // Re-initialize when navigating between pages (for SPA support)
            if (typeof wp.url !== 'undefined' && typeof wp.url.getPath === 'function') {
                let lastPath = wp.url.getPath();
                
                setInterval(function() {
                    const currentPath = wp.url.getPath();
                    if (currentPath !== lastPath) {
                        lastPath = currentPath;
                        initBlocksTracking();
                    }
                }, 500);
            }
        }
        
        // Listen for WooCommerce blocks events
        $(document.body).on('wc-blocks-checkout-update-checkout-totals wc-blocks-loaded', function() {
            initBlocksTracking();
        });
    });

})(jQuery);