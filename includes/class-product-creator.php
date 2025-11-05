<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class APM_Product_Creator {
    /**
     * create_wp_post_from_product - create a WP post (or WooCommerce product if available) from product array
     */
    public static function create_wp_post_from_product( $product ) {
        if ( empty( $product ) || empty( $product['name'] ) ) return 0;
        $post = [
            'post_title'   => sanitize_text_field( $product['name'] ),
            'post_content' => wp_kses_post( $product['description'] ?? '' ),
            'post_status'  => 'publish',
            'post_type'    => ( class_exists( 'WooCommerce' ) && ! empty( $product['product'] ) ) ? 'product' : 'post',
        ];
        $post_id = wp_insert_post( $post );
        if ( is_wp_error( $post_id ) ) return 0;
        // Set meta like price or image - keep minimal here.
        if ( ! empty( $product['price'] ) ) update_post_meta( $post_id, '_apm_price', floatval( $product['price'] ) );
        if ( ! empty( $product['currency'] ) ) update_post_meta( $post_id, '_apm_currency', sanitize_text_field( $product['currency'] ) );
        return $post_id;
    }
}
