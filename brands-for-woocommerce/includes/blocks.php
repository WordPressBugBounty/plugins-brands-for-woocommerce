<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

class BeRocket_Brands_Blocks {
    public static function settings($data) {
        if ( ! isset($data['Shop Page']['shop_display_position']) ) return $data;
        $data['Shop Page']['shop_display_position']['options'][] = array(
            'value' => 'block_product_collection', 'text' => __('Product Collection - after product content', 'brands-for-woocommerce'),
        );
        $data['Product Page']['product_display_position']['options'][] = array(
            'value' => 'block_single_product', 'text' => __('Single product - after Add to Cart block', 'brands-for-woocommerce'),
        );
        return $data;
    }

    public static function mark_product_template($block) {
        if ( $block['blockName'] === 'woocommerce/product-template' ) {
            // WooCommerce copies these attributes to the core/null block for each product.
            $block['attrs']['br_brands_product_template'] = true;
        }
        return $block;
    }

    public static function render_brands($content, $block, $instance) {
        $options = BeRocket_product_brand::getInstance()->get_option();
        if ( $block['blockName'] === 'core/null' ) {
            if ( empty($block['attrs']['br_brands_product_template']) ) {
                return $content;
            }
            $type = 'shop';
            $position = 'block_product_collection';
            $product_id = get_the_ID();
        } else {
            if ( ! is_product() || ! empty($instance->context['woocommerce/isDescendantOfAddToCartWithOptions']) ) {
                return $content;
            }
            $type = 'product';
            $position = 'block_single_product';
            $product_id = empty($instance->context['postId']) ? get_queried_object_id() : $instance->context['postId'];
        }
        if ( empty($options[$type.'_display_brand']) || ($options[$type.'_display_position'] ?? '') !== $position
            || ! wc_get_product($product_id) ) {
            return $content;
        }
        global $product, $post;
        $previous_product = $product;
        $previous_post = $post;
        $product = wc_get_product($product_id);
        $post = get_post($product_id);
        ob_start();
        try {
            do_action('br_brands_show_'.$type);
            return $content . ob_get_clean();
        } finally {
            $product = $previous_product;
            $post = $previous_post;
        }
    }

    public static function render_shortcode($content) {
        return has_shortcode($content, 'brb_prod_binfo') ? do_shortcode($content) : $content;
    }
}

add_filter('brfr_data_product_brand', array('BeRocket_Brands_Blocks', 'settings'), 20);
add_filter('render_block_data', array('BeRocket_Brands_Blocks', 'mark_product_template'));
foreach ( array('core/null', 'woocommerce/add-to-cart-form', 'woocommerce/add-to-cart-with-options') as $block_name ) {
    add_filter('render_block_'.$block_name, array('BeRocket_Brands_Blocks', 'render_brands'), 10, 3);
}
add_filter('render_block_core/shortcode', array('BeRocket_Brands_Blocks', 'render_shortcode'));
