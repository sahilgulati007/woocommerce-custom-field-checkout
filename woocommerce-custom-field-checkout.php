<?php
/**
 * Plugin Name: woocommerece  custom field checkout
 * Description: Add custom fields to WooCommerce products
 * Version: 1.0.0
 * Author: sahil gulati
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the custom text field
 * @since 1.0.0
 */
function cfwc_create_custom_field() {
	$args = array(
		'id'            => 'custom_text_field_title',
		'label'         => __( 'Custom Text Field Title', 'cfwc' ),
		'class'					=> 'cfwc-custom-field',
		'desc_tip'      => true,
		'description'   => __( 'Enter the title of your custom text field.', 'ctwc' ),
	);
	woocommerce_wp_text_input( $args );
}
add_action( 'woocommerce_product_options_general_product_data', 'cfwc_create_custom_field' );

/**
 * Save the custom field
 * @since 1.0.0
 */
function cfwc_save_custom_field( $post_id ) {
	$product = wc_get_product( $post_id );
	$title = isset( $_POST['custom_text_field_title'] ) ? $_POST['custom_text_field_title'] : '';
	$product->update_meta_data( 'custom_text_field_title', sanitize_text_field( $title ) );
	$product->save();
}
add_action( 'woocommerce_process_product_meta', 'cfwc_save_custom_field' );

/**
 * Display custom field on the front end
 * @since 1.0.0
 */
function cfwc_display_custom_field() {
	global $post;
	// Check for the custom field value
	$product = wc_get_product( $post->ID );
	$title = $product->get_meta( 'custom_text_field_title' );
	if( $title ) {
		// Only display our field if we've got a value for the field title
		printf(
			'<div class="cfwc-custom-field-wrapper"><label for="cfwc-title-field">%s</label><input type="text" id="cfwc-title-field" name="cfwc-title-field" value=""></div>',
			esc_html( $title )
		);
	}
}
add_action( 'woocommerce_before_add_to_cart_button', 'cfwc_display_custom_field' );

/**
 * Validate the text field
 * @since 1.0.0
 * @param Array 		$passed					Validation status.
 * @param Integer   $product_id     Product ID.
 * @param Boolean  	$quantity   		Quantity
 */
function cfwc_validate_custom_field( $passed, $product_id, $quantity ) {
	if( empty( $_POST['cfwc-title-field'] ) ) {
		// Fails validation
		$passed = false;
		wc_add_notice( __( 'Please enter a value into the text field', 'cfwc' ), 'error' );
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'cfwc_validate_custom_field', 10, 3 );

/**
 * Add the text field as item data to the cart object
 * @since 1.0.0
 * @param Array 		$cart_item_data Cart item meta data.
 * @param Integer   $product_id     Product ID.
 * @param Integer   $variation_id   Variation ID.
 * @param Boolean  	$quantity   		Quantity
 */
function cfwc_add_custom_field_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
	if( ! empty( $_POST['cfwc-title-field'] ) ) {
		// Add the item data
		$cart_item_data['title_field'] = $_POST['cfwc-title-field'];
		$product = wc_get_product( $product_id ); // Expanded function
		$price = $product->get_price(); // Expanded function
		$cart_item_data['total_price'] = $price + 100; // Expanded function
	}
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'cfwc_add_custom_field_item_data', 10, 4 );

/**
 * Update the price in the cart
 * @since 1.0.0
 */
function cfwc_before_calculate_totals( $cart_obj ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
    return;
  }
  // Iterate through each cart item
  foreach( $cart_obj->get_cart() as $key=>$value ) {
    if( isset( $value['total_price'] ) ) {
      $price = $value['total_price'];
      $value['data']->set_price( ( $price ) );
    }
  }
}
add_action( 'woocommerce_before_calculate_totals', 'cfwc_before_calculate_totals', 10, 1 );

/**
 * Display the custom field value in the cart
 * @since 1.0.0
 */
function cfwc_cart_item_name( $name, $cart_item, $cart_item_key ) {
	if( isset( $cart_item['title_field'] ) ) {
	  $name .= sprintf(
			'<p>%s</p>',
			esc_html( $cart_item['title_field'] )
		);
	}
	return $name;
}
add_filter( 'woocommerce_cart_item_name', 'cfwc_cart_item_name', 10, 3 );

/**
 * Add custom field to order object
 */
function cfwc_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {
	foreach( $item as $cart_item_key=>$values ) {
		if( isset( $values['title_field'] ) ) {
			$item->add_meta_data( __( 'Custom Field', 'cfwc' ), $values['title_field'], true );
		}
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'cfwc_add_custom_data_to_order', 10, 4 );