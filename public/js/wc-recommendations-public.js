/**
 * WooCommerce Product Recommendations Public Scripts
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

(function($) {
    'use strict';

    // Initialize the public functionality
    $(document).ready(function() {
        // Track recommendation clicks
        function initClickTracking() {
            $(document).on('click', '.wc-recommendations-product-link', function(e) {
                var $this = $(this);
                var $container = $this.closest('.wc-recommendations');
                
                // Track click via AJAX
                $.ajax({
                    url: wc_recommendations_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_track_click',
                        nonce: $this.data('nonce'),
                        product_id: $container.data('context-id'),
                        recommended_id: $this.data('product-id'),
                        recommendation_type: $container.data('type'),
                        placement: $container.data('placement')
                    }
                });
            });
        }
        
        // Initialize carousel functionality
        function initCarousels() {
            $('.wc-recommendations-carousel').each(function() {
                var $carousel = $(this).find('.wc-recommendations-carousel-container');
                var $track = $carousel.find('.wc-recommendations-carousel-track');
                var $items = $track.find('.wc-recommendations-product');
                var $prev = $carousel.find('.wc-recommendations-carousel-prev');
                var $next = $carousel.find('.wc-recommendations-carousel-next');
                var itemWidth = $items.first().outerWidth(true);
                var visibleItems = Math.floor($carousel.find('.wc-recommendations-carousel-wrapper').width() / itemWidth);
                var currentPosition = 0;
                var totalItems = $items.length;
                
                // Set track width
                $track.width(itemWidth * totalItems);
                
                // Move to position
                function moveToPosition(position) {
                    currentPosition = position;
                    $track.css('transform', 'translateX(' + (-itemWidth * position) + 'px)');
                    
                    // Update button states
                    $prev.toggleClass('disabled', currentPosition <= 0);
                    $next.toggleClass('disabled', currentPosition >= totalItems - visibleItems);
                }
                
                // Initial setup
                moveToPosition(0);
                
                // Previous button click
                $prev.on('click', function() {
                    if (currentPosition > 0) {
                        moveToPosition(currentPosition - 1);
                    }
                });
                
                // Next button click
                $next.on('click', function() {
                    if (currentPosition < totalItems - visibleItems) {
                        moveToPosition(currentPosition + 1);
                    }
                });
                
                // Handle window resize
                $(window).on('resize', function() {
                    itemWidth = $items.first().outerWidth(true);
                    visibleItems = Math.floor($carousel.find('.wc-recommendations-carousel-wrapper').width() / itemWidth);
                    $track.width(itemWidth * totalItems);
                    moveToPosition(Math.min(currentPosition, totalItems - visibleItems));
                });
                
                // Add swipe support for mobile
                var touchStartX = 0;
                var touchEndX = 0;
                
                $carousel.on('touchstart', function(e) {
                    touchStartX = e.originalEvent.touches[0].clientX;
                });
                
                $carousel.on('touchend', function(e) {
                    touchEndX = e.originalEvent.changedTouches[0].clientX;
                    
                    // Calculate swipe distance
                    var swipeDistance = touchEndX - touchStartX;
                    
                    // If swipe is significant enough
                    if (Math.abs(swipeDistance) > 50) {
                        if (swipeDistance > 0) {
                            // Swipe right - go to previous
                            if (currentPosition > 0) {
                                moveToPosition(currentPosition - 1);
                            }
                        } else {
                            // Swipe left - go to next
                            if (currentPosition < totalItems - visibleItems) {
                                moveToPosition(currentPosition + 1);
                            }
                        }
                    }
                });
            });
        }
        
        // Ajax-load recommendations
        function initAjaxRecommendations() {
            $('.wc-recommendations-ajax-load').each(function() {
                var $container = $(this);
                var productId = $container.data('product-id');
                var context = $container.data('context');
                var limit = $container.data('limit');
                
                // Load recommendations via AJAX
                $.ajax({
                    url: wc_recommendations_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_get_recommendations',
                        nonce: wc_recommendations_params.get_nonce,
                        product_id: productId,
                        context: context,
                        limit: limit
                    },
                    success: function(response) {
                        if (response.success) {
                            $container.html(response.data.html);
                            
                            // Initialize carousels if needed
                            if ($container.find('.wc-recommendations-carousel').length) {
                                initCarousels();
                            }
                            
                            // Initialize click tracking
                            initClickTracking();
                        } else {
                            $container.hide();
                        }
                    }
                });
            });
        }
        
        // Real-time personalization
        function initRealTimePersonalization() {
            if (typeof wc_recommendations_params.user_token !== 'undefined') {
                // Track page views for personalization
                $.ajax({
                    url: wc_recommendations_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_track_page_view',
                        nonce: wc_recommendations_params.track_nonce,
                        url: window.location.href,
                        user_token: wc_recommendations_params.user_token
                    }
                });
                
                // Update recommendations based on real-time events
                $(document).on('added_to_cart', function(e, fragments, cart_hash, $button) {
                    // Get product ID that was added
                    var productId = $button.data('product_id');
                    
                    // Refresh personalized recommendations
                    $('.wc-recommendations[data-type="personalized"], .wc-recommendations[data-type="enhanced"], .wc-recommendations[data-type="ai_hybrid"]').each(function() {
                        var $rec = $(this);
                        
                        $.ajax({
                            url: wc_recommendations_params.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'wc_recommendations_refresh',
                                nonce: wc_recommendations_params.refresh_nonce,
                                product_id: productId,
                                context: $rec.data('placement'),
                                type: $rec.data('type')
                            },
                            success: function(response) {
                                if (response.success) {
                                    $rec.fadeOut(300, function() {
                                        $(this).html(response.data.html).fadeIn(300);
                                        
                                        // Reinitialize carousels if needed
                                        if ($rec.find('.wc-recommendations-carousel').length) {
                                            initCarousels();
                                        }
                                    });
                                }
                            }
                        });
                    });
                });
            }
        }
        
        // Context-aware recommendations display
        function initContextAwareRecommendations() {
            // Check for scroll-triggered recommendations
            var $scrollTriggers = $('.wc-recommendations-scroll-trigger');
            if ($scrollTriggers.length) {
                // Create intersection observer
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var $target = $(entry.target);
                            
                            // Load recommendations when scrolled into view
                            $.ajax({
                                url: wc_recommendations_params.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'wc_recommendations_get_recommendations',
                                    nonce: wc_recommendations_params.get_nonce,
                                    product_id: $target.data('product-id'),
                                    context: $target.data('context'),
                                    limit: $target.data('limit')
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $target.html(response.data.html).addClass('loaded');
                                        
                                        // Initialize carousels if needed
                                        if ($target.find('.wc-recommendations-carousel').length) {
                                            initCarousels();
                                        }
                                        
                                        // Initialize click tracking
                                        initClickTracking();
                                        
                                        // Stop observing once loaded
                                        observer.unobserve(entry.target);
                                    }
                                }
                            });
                        }
                    });
                }, {
                    rootMargin: '0px 0px 200px 0px' // Load when within 200px of viewport
                });
                
                // Observe each trigger element
                $scrollTriggers.each(function() {
                    observer.observe(this);
                });
            }
            
            // Exit intent recommendations
            if ($('.wc-recommendations-exit-intent').length && typeof wc_recommendations_params.exit_intent === 'object') {
                var exitIntentShown = false;
                
                // Track mouse movement to detect exit intent
                $(document).on('mouseleave', function(e) {
                    // Exit intent is triggered when mouse leaves through the top of the page
                    if (!exitIntentShown && e.clientY < 20) {
                        exitIntentShown = true;
                        
                        // Show exit intent popup
                        var $exitPopup = $('.wc-recommendations-exit-intent');
                        $exitPopup.fadeIn(300);
                        
                        // Close button
                        $exitPopup.find('.wc-exit-intent-close').on('click', function() {
                            $exitPopup.fadeOut(300);
                        });
                        
                        // Click outside to close
                        $(document).on('click', function(e) {
                            if ($(e.target).closest('.wc-exit-intent-content').length === 0 && 
                                $(e.target).closest('.wc-exit-intent-close').length === 0) {
                                $exitPopup.fadeOut(300);
                            }
                        });
                        
                        // Track impression
                        $.ajax({
                            url: wc_recommendations_params.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'wc_recommendations_track_exit_intent',
                                nonce: wc_recommendations_params.track_nonce
                            }
                        });
                    }
                });
            }
        }
        
        // Initialize features that use AI-powered recommendation engine
        function initAIFeatures() {
            // Check if AI features are enabled
            if (typeof wc_recommendations_params.ai_enabled !== 'undefined' && wc_recommendations_params.ai_enabled) {
                // Product page summary recommendations
                if ($('.wc-recommendations-ai-summary').length) {
                    // Show loading state
                    $('.wc-recommendations-ai-summary').html('<div class="wc-ai-loading"><span>Analyzing your preferences...</span></div>');
                    
                    // Get current product context
                    var currentProductId = $('.wc-recommendations-ai-summary').data('product-id');
                    
                    // Load AI-generated content
                    $.ajax({
                        url: wc_recommendations_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wc_recommendations_get_ai_content',
                            nonce: wc_recommendations_params.ai_nonce,
                            product_id: currentProductId,
                            content_type: 'summary'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('.wc-recommendations-ai-summary').html(response.data.html);
                            } else {
                                $('.wc-recommendations-ai-summary').hide();
                            }
                        }
                    });
                }
                
                // Smart product bundles with dynamic pricing
                if ($('.wc-recommendations-smart-bundle').length) {
                    // Initialize smart bundle UI
                    $('.wc-recommendations-smart-bundle').each(function() {
                        var $bundle = $(this);
                        var baseProductId = $bundle.data('product-id');
                        var basePrice = parseFloat($bundle.data('base-price'));
                        var currency = $bundle.data('currency');
                        
                        // Bundle selection changes
                        $bundle.find('.bundle-product-checkbox').on('change', function() {
                            var totalPrice = basePrice;
                            var bundleDiscount = 0;
                            var selectedCount = 0;
                            
                            // Calculate total price based on selections
                            $bundle.find('.bundle-product-checkbox:checked').each(function() {
                                totalPrice += parseFloat($(this).data('price'));
                                selectedCount++;
                            });
                            
                            // Apply bundle discount (more items = bigger discount)
                            if (selectedCount > 0) {
                                // Progressive discount: 5% for 1 item, 10% for 2, 15% for 3+
                                var discountRate = Math.min(0.05 * selectedCount, 0.15);
                                bundleDiscount = totalPrice * discountRate;
                                totalPrice -= bundleDiscount;
                            }
                            
                            // Update price display
                            $bundle.find('.bundle-total-price').text(formatCurrency(totalPrice, currency));
                            $bundle.find('.bundle-discount').text(formatCurrency(bundleDiscount, currency));
                            
                            // Enable/disable add to cart button
                            $bundle.find('.add-bundle-to-cart').prop('disabled', selectedCount === 0);
                        });
                        
                        // Add bundle to cart
                        $bundle.find('.add-bundle-to-cart').on('click', function(e) {
                            e.preventDefault();
                            
                            var selectedProducts = [];
                            $bundle.find('.bundle-product-checkbox:checked').each(function() {
                                selectedProducts.push($(this).val());
                            });
                            
                            if (selectedProducts.length > 0) {
                                // Add bundle to cart via AJAX
                                $.ajax({
                                    url: wc_recommendations_params.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'wc_recommendations_add_bundle',
                                        nonce: wc_recommendations_params.cart_nonce,
                                        base_product: baseProductId,
                                        bundle_products: selectedProducts
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            // Show success message
                                            $bundle.find('.bundle-message').html('<div class="bundle-success">Bundle added to cart!</div>').fadeIn().delay(3000).fadeOut();
                                            
                                            // Update WooCommerce mini cart if it exists
                                            if (typeof response.data.fragments !== 'undefined') {
                                                $.each(response.data.fragments, function(key, value) {
                                                    $(key).replaceWith(value);
                                                });
                                            }
                                        } else {
                                            // Show error
                                            $bundle.find('.bundle-message').html('<div class="bundle-error">Error adding bundle to cart. Please try again.</div>').fadeIn().delay(3000).fadeOut();
                                        }
                                    }
                                });
                            }
                        });
                    });
                }
            }
        }
        
        // Utility function to format currency
        function formatCurrency(amount, currency) {
            return currency + parseFloat(amount).toFixed(2);
        }
        
        // Initialize all modules
        initClickTracking();
        initCarousels();
        initAjaxRecommendations();
        initRealTimePersonalization();
        initContextAwareRecommendations();
        initAIFeatures();
    });

})(jQuery);