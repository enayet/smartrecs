/**
 * WooCommerce Product Recommendations Tracking Scripts
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

(function($) {
    'use strict';

    // Initialize tracking functionality
    $(document).ready(function() {
        // Check if tracking is enabled and consent is given
        if (typeof wc_recommendations_params === 'undefined' || 
            typeof wc_recommendations_params.tracking_enabled === 'undefined' || 
            !wc_recommendations_params.tracking_enabled) {
            return;
        }
        
        // Check for privacy compliance
        if (wc_recommendations_params.privacy_compliant === 'yes' && 
            !hasTrackingConsent()) {
            // If compliance is required but no consent, attach consent hooks and exit
            attachConsentHooks();
            return;
        }
        
        // Initialize tracking
        initTracking();
        
        /**
         * Check if tracking consent has been given
         */
        function hasTrackingConsent() {
            // Check for WooCommerce cookie consent if available
            if (typeof Cookies !== 'undefined') {
                return Cookies.get('woocommerce_recommendations_consent') === 'yes';
            }
            
            // Check localStorage
            if (window.localStorage) {
                return localStorage.getItem('woocommerce_recommendations_consent') === 'yes';
            }
            
            // Default to false if no storage method is available
            return false;
        }
        
        /**
         * Attach hooks for consent mechanisms
         */
        function attachConsentHooks() {
            // Listen for cookie consent events from various cookie plugins
            
            // Listen for WooCommerce cookie consent
            $(document).on('click', '.woocommerce-cookie-notice-accept', function() {
                setTrackingConsent(true);
                initTracking();
            });
            
            // Listen for our own consent button
            $(document).on('click', '.wc-recommendations-consent-accept', function() {
                setTrackingConsent(true);
                initTracking();
                hideConsentNotice();
            });
            
            // Show our consent notice if no other notice is present
            if ($('.woocommerce-cookie-notice').length === 0 && 
                $('.cookie-notice').length === 0 && 
                $('.cookie-law-info-bar').length === 0) {
                showConsentNotice();
            }
        }
        
        /**
         * Set tracking consent
         */
        function setTrackingConsent(consent) {
            // Set cookie if available
            if (typeof Cookies !== 'undefined') {
                Cookies.set('woocommerce_recommendations_consent', consent ? 'yes' : 'no', { expires: 365 });
            }
            
            // Set localStorage
            if (window.localStorage) {
                localStorage.setItem('woocommerce_recommendations_consent', consent ? 'yes' : 'no');
            }
        }
        
        /**
         * Show consent notice
         */
        function showConsentNotice() {
            // Create consent notice if it doesn't exist
            if ($('.wc-recommendations-consent-notice').length === 0) {
                var consentHtml = `
                    <div class="wc-recommendations-consent-notice">
                        <div class="wc-recommendations-consent-content">
                            <p>${wc_recommendations_params.consent_text || 'This site uses cookies to provide personalized product recommendations. Do you consent to our tracking cookies?'}</p>
                            <div class="wc-recommendations-consent-buttons">
                                <button class="wc-recommendations-consent-accept">${wc_recommendations_params.accept_text || 'Accept'}</button>
                                <button class="wc-recommendations-consent-decline">${wc_recommendations_params.decline_text || 'Decline'}</button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(consentHtml);
                
                // Handle decline button
                $('.wc-recommendations-consent-decline').on('click', function() {
                    setTrackingConsent(false);
                    hideConsentNotice();
                });
            }
            
            // Show the notice
            $('.wc-recommendations-consent-notice').fadeIn();
        }
        
        /**
         * Hide consent notice
         */
        function hideConsentNotice() {
            $('.wc-recommendations-consent-notice').fadeOut();
        }
        
        /**
         * Initialize tracking functionality
         */
        function initTracking() {
            // User identification (anonymous or logged in)
            var userId = wc_recommendations_params.user_id || 0;
            var sessionId = wc_recommendations_params.session_id || '';
            var userToken = generateUserToken();
            
            // Set up click tracking for recommendations
            trackRecommendationInteractions();
            
            // Track product views
            trackProductViews();
            
            // Track search queries
            trackSearchQueries();
            
            // Track cart interactions
            trackCartInteractions();
            
            // Track advanced behaviors (time on page, scroll depth, etc.)
            trackAdvancedBehaviors();
            
            // Send tracking data to server periodically
            setupPeriodicSync();
            
            /**
             * Generate consistent user token
             */
            function generateUserToken() {
                // Use existing token if available
                if (wc_recommendations_params.user_token) {
                    return wc_recommendations_params.user_token;
                }
                
                var token = '';
                
                // Use user ID if logged in
                if (userId > 0) {
                    token = 'u_' + userId;
                } 
                // Use session ID if available
                else if (sessionId) {
                    token = 's_' + sessionId;
                } 
                // Generate random ID and store in localStorage
                else if (window.localStorage) {
                    token = localStorage.getItem('wc_recommendations_user_token');
                    
                    if (!token) {
                        token = 'v_' + Math.random().toString(36).substring(2, 15) + 
                                Math.random().toString(36).substring(2, 15);
                        localStorage.setItem('wc_recommendations_user_token', token);
                    }
                }
                
                return token;
            }
            
            /**
             * Track recommendation interactions
             */
            function trackRecommendationInteractions() {
                // Track impressions
                $('.wc-recommendations').each(function() {
                    var $container = $(this);
                    var contextId = $container.data('context-id');
                    var type = $container.data('type');
                    var placement = $container.data('placement');
                    
                    // Only track if visible in viewport
                    if (isElementInViewport($container[0])) {
                        var recommendedIds = [];
                        
                        // Collect all displayed product IDs
                        $container.find('.wc-recommendations-product-link').each(function() {
                            recommendedIds.push($(this).data('product-id'));
                        });
                        
                        // Track impressions
                        if (recommendedIds.length > 0) {
                            trackEvent('impression', {
                                context_id: contextId,
                                type: type,
                                placement: placement,
                                recommended_ids: recommendedIds
                            });
                        }
                    }
                });
                
                // Track clicks - already handled in main public.js
            }
            
            /**
             * Track product views
             */
            function trackProductViews() {
                // Check if we're on a product page
                if ($('body').hasClass('single-product')) {
                    // Get product data
                    var productId = wc_recommendations_params.product_id || 0;
                    
                    if (productId > 0) {
                        trackEvent('product_view', {
                            product_id: productId
                        });
                    }
                    
                    // Track time spent on product page
                    var startTime = new Date();
                    var timeSpent = 0;
                    var timeInterval = setInterval(function() {
                        timeSpent = Math.floor((new Date() - startTime) / 1000);
                        
                        // Track every 10 seconds
                        if (timeSpent % 10 === 0 && timeSpent > 0) {
                            trackEvent('product_view_duration', {
                                product_id: productId,
                                duration: timeSpent
                            });
                        }
                    }, 1000);
                    
                    // Clear interval when leaving page
                    $(window).on('beforeunload', function() {
                        clearInterval(timeInterval);
                        
                        // Final duration tracking
                        trackEvent('product_view_duration', {
                            product_id: productId,
                            duration: Math.floor((new Date() - startTime) / 1000),
                            final: true
                        });
                    });
                }
            }
            
            /**
             * Track search queries
             */
            function trackSearchQueries() {
                // Check if we're on a search results page
                if ($('body').hasClass('search') || $('body').hasClass('woocommerce-search')) {
                    // Get search query from URL
                    var searchQuery = new URLSearchParams(window.location.search).get('s');
                    
                    if (searchQuery) {
                        trackEvent('search_query', {
                            query: searchQuery,
                            results_count: $('.products .product').length
                        });
                    }
                }
                
                // Track search form submissions
                $(document).on('submit', '.woocommerce-product-search, .search-form', function() {
                    var searchInput = $(this).find('input[type="search"], input[name="s"]').val();
                    
                    if (searchInput) {
                        trackEvent('search_submit', {
                            query: searchInput
                        });
                    }
                });
            }
            
            /**
             * Track cart interactions
             */
            function trackCartInteractions() {
                // Listen for WooCommerce add to cart events
                $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                    if ($button) {
                        var productId = $button.data('product_id');
                        var quantity = $button.data('quantity') || 1;
                        
                        trackEvent('add_to_cart', {
                            product_id: productId,
                            quantity: quantity
                        });
                    }
                });
                
                // Track cart page interactions
                if ($('body').hasClass('woocommerce-cart')) {
                    // Track cart updates
                    $(document).on('click', '[name="update_cart"]', function() {
                        var cartItems = [];
                        
                        $('.woocommerce-cart-form__cart-item').each(function() {
                            var $item = $(this);
                            var productId = $item.find('.product-remove a').data('product_id');
                            var quantity = $item.find('.qty').val();
                            
                            if (productId && quantity) {
                                cartItems.push({
                                    product_id: productId,
                                    quantity: quantity
                                });
                            }
                        });
                        
                        trackEvent('update_cart', {
                            items: cartItems
                        });
                    });
                    
                    // Track cart removals
                    $(document).on('click', '.product-remove > a', function() {
                        var productId = $(this).data('product_id');
                        
                        trackEvent('remove_from_cart', {
                            product_id: productId
                        });
                    });
                }
            }
            
            /**
             * Track advanced behaviors
             */
            function trackAdvancedBehaviors() {
                // Track scroll depth on product pages
                if ($('body').hasClass('single-product')) {
                    var productId = wc_recommendations_params.product_id || 0;
                    var scrollDepths = [25, 50, 75, 100];
                    var scrollDepthsReached = {};
                    
                    // Initialize all depths as not reached
                    scrollDepths.forEach(function(depth) {
                        scrollDepthsReached[depth] = false;
                    });
                    
                    // Track scroll depth
                    $(window).on('scroll', debounce(function() {
                        var scrollPercent = getScrollPercent();
                        
                        scrollDepths.forEach(function(depth) {
                            if (!scrollDepthsReached[depth] && scrollPercent >= depth) {
                                scrollDepthsReached[depth] = true;
                                
                                trackEvent('scroll_depth', {
                                    product_id: productId,
                                    depth: depth
                                });
                            }
                        });
                    }, 200));
                }
                
                // Track return visits
                if (window.localStorage) {
                    var visitCount = parseInt(localStorage.getItem('wc_recommendations_visit_count') || '0');
                    visitCount++;
                    localStorage.setItem('wc_recommendations_visit_count', visitCount);
                    
                    trackEvent('visit', {
                        count: visitCount
                    });
                    
                    // Track if user has viewed product before
                    if ($('body').hasClass('single-product')) {
                        var productId = wc_recommendations_params.product_id || 0;
                        var viewedProducts = JSON.parse(localStorage.getItem('wc_recommendations_viewed_products') || '[]');
                        
                        if (productId > 0) {
                            if (viewedProducts.includes(productId)) {
                                trackEvent('return_view', {
                                    product_id: productId
                                });
                            } else {
                                viewedProducts.push(productId);
                                localStorage.setItem('wc_recommendations_viewed_products', JSON.stringify(viewedProducts));
                            }
                        }
                    }
                }
            }
            
            /**
             * Set up periodic sync of tracking data
             */
            function setupPeriodicSync() {
                // If we have queued events in localStorage, try to send them now
                if (window.localStorage) {
                    var queuedEvents = JSON.parse(localStorage.getItem('wc_recommendations_event_queue') || '[]');
                    
                    if (queuedEvents.length > 0) {
                        sendEvents(queuedEvents);
                        localStorage.setItem('wc_recommendations_event_queue', '[]');
                    }
                }
                
                // Set up periodic sync every 30 seconds
                setInterval(function() {
                    if (window.localStorage) {
                        var queuedEvents = JSON.parse(localStorage.getItem('wc_recommendations_event_queue') || '[]');
                        
                        if (queuedEvents.length > 0) {
                            sendEvents(queuedEvents);
                            localStorage.setItem('wc_recommendations_event_queue', '[]');
                        }
                    }
                }, 30000);
                
                // Sync on page unload
                $(window).on('beforeunload', function() {
                    if (window.localStorage) {
                        var queuedEvents = JSON.parse(localStorage.getItem('wc_recommendations_event_queue') || '[]');
                        
                        if (queuedEvents.length > 0) {
                            // Use navigator.sendBeacon for reliability during page unload
                            if (navigator.sendBeacon) {
                                var formData = new FormData();
                                formData.append('action', 'wc_recommendations_bulk_track');
                                formData.append('nonce', wc_recommendations_params.track_nonce);
                                formData.append('events', JSON.stringify(queuedEvents));
                                
                                navigator.sendBeacon(wc_recommendations_params.ajax_url, formData);
                            } else {
                                // Fall back to synchronous AJAX
                                $.ajax({
                                    url: wc_recommendations_params.ajax_url,
                                    type: 'POST',
                                    async: false,
                                    data: {
                                        action: 'wc_recommendations_bulk_track',
                                        nonce: wc_recommendations_params.track_nonce,
                                        events: queuedEvents
                                    }
                                });
                            }
                            
                            localStorage.setItem('wc_recommendations_event_queue', '[]');
                        }
                    }
                });
            }
            
            /**
             * Track an event
             */
            function trackEvent(eventType, eventData) {
                // Add common data
                eventData.event_type = eventType;
                eventData.user_id = userId;
                eventData.session_id = sessionId;
                eventData.user_token = userToken;
                eventData.timestamp = new Date().toISOString();
                eventData.page_url = window.location.href;
                
                // Queue the event
                if (window.localStorage) {
                    var queuedEvents = JSON.parse(localStorage.getItem('wc_recommendations_event_queue') || '[]');
                    queuedEvents.push(eventData);
                    localStorage.setItem('wc_recommendations_event_queue', JSON.stringify(queuedEvents));
                    
                    // If queue is getting large, trigger a sync
                    if (queuedEvents.length >= 10) {
                        sendEvents(queuedEvents);
                        localStorage.setItem('wc_recommendations_event_queue', '[]');
                    }
                } else {
                    // If localStorage is not available, send immediately
                    sendEvent(eventData);
                }
            }
            
            /**
             * Send a single event
             */
            function sendEvent(eventData) {
                $.ajax({
                    url: wc_recommendations_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_track_event',
                        nonce: wc_recommendations_params.track_nonce,
                        event: eventData
                    }
                });
            }
            
            /**
             * Send multiple events
             */
            function sendEvents(events) {
                $.ajax({
                    url: wc_recommendations_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wc_recommendations_bulk_track',
                        nonce: wc_recommendations_params.track_nonce,
                        events: events
                    }
                });
            }
            
            /**
             * Utility: Check if element is in viewport
             */
            function isElementInViewport(el) {
                var rect = el.getBoundingClientRect();
                
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            /**
             * Utility: Get scroll percentage
             */
            function getScrollPercent() {
                var scrollPosition = window.pageYOffset;
                var documentHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                
                return (scrollPosition / documentHeight) * 100;
            }
            
            /**
             * Utility: Debounce function for scroll events
             */
            function debounce(func, wait) {
                var timeout;
                
                return function() {
                    var context = this;
                    var args = arguments;
                    
                    clearTimeout(timeout);
                    
                    timeout = setTimeout(function() {
                        func.apply(context, args);
                    }, wait);
                };
            }
        }
    });

})(jQuery);