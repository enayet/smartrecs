<?php
/**
 * Grid layout template for recommendations.
 *
 * @since      1.0.0
 * @package    WC_Recommendations
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add container class based on columns
$container_class = 'wc-recommendations-container';
$columns_class = 'wc-recommendations-columns-' . $columns;
?>

<div class="wc-recommendations <?php echo esc_attr($container_class); ?> <?php echo esc_attr($columns_class); ?>" data-context-id="<?php echo esc_attr($context_id); ?>" data-type="<?php echo esc_attr($recommendation_type); ?>" data-placement="<?php echo esc_attr($placement); ?>">
    <h3 class="wc-recommendations-title"><?php echo esc_html($title); ?></h3>
    
    <div class="wc-recommendations-grid">
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
        text-align: center;
    }
    
    .wc-recommendations-grid {
        display: grid;
        grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);
        grid-gap: 20px;
    }
    
    .wc-recommendations-product {
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
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .wc-recommendations-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .wc-recommendations-grid {
            grid-template-columns: 1fr;
        }
    }
</style>