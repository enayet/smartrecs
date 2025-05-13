<?php
/**
 * List layout template for recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wc-recommendations wc-recommendations-list" data-context-id="<?php echo esc_attr($context_id); ?>" data-type="<?php echo esc_attr($recommendation_type); ?>" data-placement="<?php echo esc_attr($placement); ?>">
    <h3 class="wc-recommendations-title"><?php echo esc_html($title); ?></h3>
    
    <ul class="wc-recommendations-list-items">
        <?php foreach ($products as $product) : ?>
            <li class="wc-recommendations-product">
                <div class="wc-recommendations-product-inner">
                    <div class="wc-recommendations-product-image">
                        <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="wc-recommendations-product-link" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
                            <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                        </a>
                    </div>
                    
                    <div class="wc-recommendations-product-details">
                        <h4 class="wc-recommendations-product-title">
                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="wc-recommendations-product-link" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </a>
                        </h4>
                        
                        <?php if ($product->get_short_description()) : ?>
                            <div class="wc-recommendations-product-description">
                                <?php echo wp_trim_words($product->get_short_description(), 15, '...'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="wc-recommendations-product-price"><?php echo $product->get_price_html(); ?></div>
                        
                        <?php if ($product->get_average_rating() > 0) : ?>
                            <div class="wc-recommendations-product-rating">
                                <?php echo wc_get_rating_html($product->get_average_rating(), $product->get_rating_count()); ?>
                            </div>
                        <?php endif; ?>
                        
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
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<script>
    jQuery(document).ready(function($) {
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
    }
    
    .wc-recommendations-title {
        margin-bottom: 1em;
    }
    
    .wc-recommendations-list-items {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .wc-recommendations-product {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e2e2e2;
    }
    
    .wc-recommendations-product:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .wc-recommendations-product-inner {
        display: flex;
        align-items: center;
    }
    
    .wc-recommendations-product-image {
        flex: 0 0 100px;
        margin-right: 20px;
    }
    
    .wc-recommendations-product-details {
        flex: 1;
    }
    
    .wc-recommendations-product-title {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 1.1em;
    }
    
    .wc-recommendations-product-description {
        color: #666;
        margin-bottom: 10px;
        font-size: 0.9em;
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
    
    /* Responsive adjustments */
    @media (max-width: 480px) {
        .wc-recommendations-product-inner {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .wc-recommendations-product-image {
            margin-right: 0;
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
        }
    }
</style>