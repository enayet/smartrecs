<?php
/**
 * Carousel layout template for recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Generate a unique ID for this carousel
$carousel_id = 'wc-recommendations-carousel-' . uniqid();
?>

<div class="wc-recommendations wc-recommendations-carousel" data-context-id="<?php echo esc_attr($context_id); ?>" data-type="<?php echo esc_attr($recommendation_type); ?>" data-placement="<?php echo esc_attr($placement); ?>">
    <h3 class="wc-recommendations-title"><?php echo esc_html($title); ?></h3>
    
    <div class="wc-recommendations-carousel-container" id="<?php echo esc_attr($carousel_id); ?>">
        <div class="wc-recommendations-carousel-prev">
            <span>&lsaquo;</span>
        </div>
        
        <div class="wc-recommendations-carousel-wrapper">
            <div class="wc-recommendations-carousel-track">
                <?php foreach ($products as $product) : ?>
                    <div class="wc-recommendations-product">
                        <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="wc-recommendations-product-link" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
                            <div class="wc-recommendations-product-image">
                                <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                            </div>
                            <h4 class="wc-recommendations-product-title"><?php echo esc_html($product->get_name()); ?></h4>
                            <div class="wc-recommendations-product-price"><?php echo $product->get_price_html(); ?></div>
                            <?php if ($product->get_average_rating() > 0) : ?>
                                <div class="wc-recommendations-product-rating">
                                    <?php echo wc_get_rating_html($product->get_average_rating(), $product->get_rating_count()); ?>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <?php if ($product->is_in_stock() && $product->is_purchasable()) : ?>
                            <div class="wc-recommendations-product-add-to-cart">
                                <?php
                                echo apply_filters(
                                    'woocommerce_loop_add_to_cart_link',
                                    sprintf(
                                        '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                                        esc_url($product->add_to_cart_url()),
                                        esc_attr(1),
                                        esc_attr('button add_to_cart_button'),
                                        'product_type="' . esc_attr($product->get_type()) . '"',
                                        esc_html($product->add_to_cart_text())
                                    ),
                                    $product
                                );
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="wc-recommendations-carousel-next">
            <span>&rsaquo;</span>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Set up carousel
        var $carousel = $('#<?php echo esc_js($carousel_id); ?>');
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
        
        // Track recommendation clicks
        $('.wc-recommendations-product-link').on('click', function(e) {
            var $this = $(this);
            var $container = $this.closest('.wc-recommendations');
            
            // Track click via AJAX
            $.ajax({
                url: woocommerce_params.ajax_url,
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
    });
</script>

<style>
    .wc-recommendations {
        margin: 2em 0;
        position: relative;
    }
    
    .wc-recommendations-title {
        margin-bottom: 1em;
        text-align: center;
    }
    
    .wc-recommendations-carousel-container {
        display: flex;
        align-items: center;
        position: relative;
    }
    
    .wc-recommendations-carousel-wrapper {
        width: 100%;
        overflow: hidden;
        position: relative;
    }
    
    .wc-recommendations-carousel-track {
        display: flex;
        transition: transform 0.3s ease-out;
    }
    
    .wc-recommendations-product {
        flex: 0 0 auto;
        width: calc(100% / <?php echo esc_attr($columns); ?> - 20px);
        margin: 0 10px;
        text-align: center;
        padding: 10px;
        border: 1px solid #e2e2e2;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .wc-recommendations-product:hover {
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .wc-recommendations-product-title {
        margin-top: 10px;
        margin-bottom: 5px;
        font-size: 1em;
    }
    
    .wc-recommendations-product-price {
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .wc-recommendations-product-rating {
        margin-bottom: 10px;
    }
    
    .wc-recommendations-product-add-to-cart {
        margin-top: 10px;
    }
    
    .wc-recommendations-carousel-prev,
    .wc-recommendations-carousel-next {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: #f5f5f5;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 10;
        transition: all 0.2s ease;
    }
    
    .wc-recommendations-carousel-prev:hover,
    .wc-recommendations-carousel-next:hover {
        background: #e5e5e5;
    }
    
    .wc-recommendations-carousel-prev.disabled,
    .wc-recommendations-carousel-next.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .wc-recommendations-carousel-prev span,
    .wc-recommendations-carousel-next span {
        font-size: 24px;
        line-height: 1;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .wc-recommendations-product {
            width: calc(50% - 20px);
        }
    }
    
    @media (max-width: 480px) {
        .wc-recommendations-product {
            width: calc(100% - 20px);
        }
    }
</style>