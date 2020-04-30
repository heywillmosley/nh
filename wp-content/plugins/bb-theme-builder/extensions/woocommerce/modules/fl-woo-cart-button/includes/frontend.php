<?php

$button = FLPageDataWooCommerce::get_add_to_cart_button();

if ( function_exists( 'YITH_YWRAQ_Frontend' ) ) {
	global $product;
	if ( 'yes' !== get_option( 'ywraq_hide_add_to_cart' ) ) {
		echo $button;
	}

	if ( is_object( $product ) ) {
		YITH_YWRAQ_Frontend()->add_button_single_page();
	}
} else {
	echo $button;
}

if ( class_exists( 'WooCommerce_Waitlist_Plugin' ) ) {
	global $product;

	if ( ! ( empty( $product ) || $product->is_type( 'external' ) || $product->is_type( 'composite' ) || $product->is_type( 'variable' ) ) ) {
		echo do_shortcode( '[woocommerce_waitlist]' );
	}
}
