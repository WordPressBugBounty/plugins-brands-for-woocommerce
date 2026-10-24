<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** A dynamic Gutenberg wrapper around the established BeRocket catalogue. */
class BeRocket_Brands_Catalog_Block {
    const NAME = 'berocket/brand-catalog';

    public function __construct() {
        add_action( 'init', array( $this, 'register' ), 20 );
        add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
    }

    public static function attributes() {
        return array(
            'groupBy' => array( 'type' => 'string', 'default' => 'alphabet', 'enum' => array( 'alphabet', 'category', 'none' ) ),
            'layout' => array( 'type' => 'string', 'default' => 'vertical', 'enum' => array( 'vertical', 'horizontal' ) ),
            'columns' => array( 'type' => 'integer', 'default' => 2 ),
            'categoryId' => array( 'type' => 'integer', 'default' => 0 ),
            'showImages' => array( 'type' => 'boolean', 'default' => true ),
            'showCounts' => array( 'type' => 'boolean', 'default' => true ),
            'hideEmpty' => array( 'type' => 'boolean', 'default' => true ),
            'showAll' => array( 'type' => 'boolean', 'default' => true ),
        );
    }

    public static function normalize( $attributes ) {
        $attributes = is_array( $attributes ) ? $attributes : array();
        $result = array();
        foreach ( self::attributes() as $name => $definition ) {
            $value = isset( $attributes[$name] ) ? $attributes[$name] : $definition['default'];
            if ( isset( $definition['enum'] ) ) {
                $value = in_array( $value, $definition['enum'], true ) ? $value : $definition['default'];
            } elseif ( 'boolean' === $definition['type'] ) {
                $value = (bool) brfr_sanitize_shortcode_bool( $value );
            } else {
                $value = is_numeric( $value ) && is_finite( (float) $value ) ? max( 0, (int) $value ) : $definition['default'];
            }
            $result[$name] = $value;
        }
        $result['columns'] = max( 1, min( 6, $result['columns'] ) );
        return $result;
    }

    private function api_version() {
        $version = get_bloginfo( 'version' );
        return version_compare( $version, '6.3', '>=' ) ? 3 : ( version_compare( $version, '5.6', '>=' ) ? 2 : 1 );
    }

    public function register() {
        if ( ! function_exists( 'register_block_type' ) ) { return; }
        brfr_register_front_assets();
        $editor_dependency = wp_script_is( 'wp-block-editor', 'registered' ) ? 'wp-block-editor' : 'wp-editor';
        $dependencies = array( 'wp-blocks', 'wp-element', 'wp-components', $editor_dependency, 'wp-i18n' );
        if ( wp_script_is( 'wp-server-side-render', 'registered' ) ) { $dependencies[] = 'wp-server-side-render'; }
        wp_register_script( 'br-brands-catalog-block-editor', plugins_url( '../js/catalog-block.js', __FILE__ ),
            $dependencies,
            BeRocket_product_brand_version, true );
        wp_register_style( 'br-brands-catalog-block', plugins_url( '../css/catalog-block.css', __FILE__ ),
            array( 'berocket_product_brand_style' ), BeRocket_product_brand_version );
        register_block_type( self::NAME, array(
            'api_version' => $this->api_version(),
            'attributes' => self::attributes(),
            'editor_script' => 'br-brands-catalog-block-editor',
            'style' => 'br-brands-catalog-block',
            'supports' => array( 'html' => false, 'customClassName' => false ),
            'render_callback' => array( $this, 'render' ),
        ) );
    }

    public function editor_data() {
        $categories = array( array( 'value' => '0', 'label' => __( 'All categories', 'brands-for-woocommerce' ) ) );
        // Local terms are not truncated to the REST API's first page of 100 results.
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = array( 'value' => (string) $term->term_id, 'label' => $term->name );
            }
        }
        wp_localize_script( 'br-brands-catalog-block-editor', 'brBrandsCatalogBlock', array(
            'apiVersion' => $this->api_version(), 'attributes' => self::attributes(), 'categories' => $categories,
        ) );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'br-brands-catalog-block-editor', 'brands-for-woocommerce', dirname( __DIR__ ) . '/languages' );
        }
    }

    public function render( $attributes ) {
        $attributes = self::normalize( $attributes );
        $wrapper = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes( array( 'class' => 'wp-block-berocket-brand-catalog' ) )
            : 'class="wp-block-berocket-brand-catalog"';
        $empty = '<p class="br-brands-catalog-empty">' . esc_html__( 'No brands found.', 'brands-for-woocommerce' ) . '</p>';
        $catalog = '';
        if ( shortcode_exists( 'brb_catalog' ) && taxonomy_exists( 'berocket_brand' ) ) {
            $atts = array(
                'groupby' => $attributes['groupBy'], 'style' => $attributes['layout'], 'column' => $attributes['columns'],
                'img_display' => (int) $attributes['showImages'], 'count' => (int) $attributes['showCounts'],
                'hide_empty' => (int) $attributes['hideEmpty'], 'show_all' => (int) $attributes['showAll'],
            );
            if ( $attributes['categoryId'] ) {
                $category = get_term( $attributes['categoryId'], 'product_cat' );
                $ids = array();
                if ( $category instanceof WP_Term ) {
                    $brands = get_terms( array( 'taxonomy' => 'berocket_brand', 'hide_empty' => false,
                        'meta_query' => array( array( 'key' => 'br_brand_category', 'value' => $category->name, 'compare' => 'LIKE' ) ) ) );
                    if ( ! is_wp_error( $brands ) ) {
                        foreach ( $brands as $brand ) {
                            $assigned = get_term_meta( $brand->term_id, 'br_brand_category', true );
                            if ( is_array( $assigned ) && in_array( $category->name, $assigned, true ) ) {
                                $ids[] = $brand->term_id;
                            }
                        }
                    }
                }
                // An empty include string means ALL brands to the existing shortcode.
                if ( ! $ids ) { return '<div ' . $wrapper . '>' . $empty . '</div>'; }
                $atts['include'] = implode( ',', array_map( 'absint', $ids ) );
            }
            static $instance = 0;
            $atts['cache_key'] = 'block_' . substr( md5( wp_json_encode( $atts ) ), 0, 12 ) . '_' . ++$instance;
            $shortcode = '[brb_catalog';
            foreach ( $atts as $name => $value ) {
                // Every value comes from an enum, integer or generated safe cache key.
                $shortcode .= ' ' . $name . '="' . $value . '"';
            }
            $catalog = do_shortcode( $shortcode . ']' );
        }
        return '<div ' . $wrapper . '>' . ( '' === trim( $catalog ) ? $empty : $catalog ) . '</div>';
    }
}
new BeRocket_Brands_Catalog_Block();
