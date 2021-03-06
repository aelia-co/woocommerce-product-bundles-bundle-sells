<?php
/**
 * WC_PB_BS_Cart class
 *
 * @author   SomewhereWarm <info@somewherewarm.gr>
 * @package  WooCommerce Product Bundles - Bundle-Sells
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart-related functions and filters.
 *
 * @class    WC_PB_BS_Cart
 * @version  1.0.4
 */
class WC_PB_BS_Cart {

	/**
	 * Internal flag for bypassing filters.
	 *
	 * @var array
	 */
	private static $bypass_filters = array();

	/**
	 * Setup hooks.
	 */
	public static function init() {

		// Validate bundle-sell add-to-cart.
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 100, 6 );

		// Add bundle-sells to the cart. Must run before WooCommerce sets the session data on 'woocommerce_add_to_cart' (20).
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'bundle_sells_add_to_cart' ), 15, 6 );

		// Filter the add-to-cart success message.
		add_filter( 'wc_add_to_cart_message_html', array( __CLASS__, 'bundle_sells_add_to_cart_message_html' ), 10, 2 );
	}

	/*
	|--------------------------------------------------------------------------
	| Application layer functions.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the posted bundle-sells configuration of a product.
	 *
	 * @param  WC_Product  $product
	 * @return array
	 */
	public static function get_posted_bundle_sells_configuration( $product ) {

		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}

		$bundle_sells_add_to_cart_configuration = array();

		// Any bundle-sell IDs present?
		$bundle_sell_ids = WC_PB_BS_Product::get_bundle_sell_ids( $product );

		if ( ! empty( $bundle_sell_ids ) ) {

			// Construct a dummy bundle to collect the posted form content.
			$bundle        = WC_PB_BS_Product::get_bundle( $bundle_sell_ids, $product );
			$bundled_items = $bundle->get_bundled_items();
			$configuration = WC_PB()->cart->get_posted_bundle_configuration( $bundle );

			foreach ( $bundled_items as $bundled_item_id => $bundled_item ) {

				if ( isset( $configuration[ $bundled_item_id ] ) ) {
					$bundled_item_configuration = $configuration[ $bundled_item_id ];
				} else {
					continue;
				}

				if ( isset( $bundled_item_configuration[ 'optional_selected' ] ) && 'no' === $bundled_item_configuration[ 'optional_selected' ] ) {
					continue;
				}

				if ( isset( $bundled_item_configuration[ 'quantity' ] ) && absint( $bundled_item_configuration[ 'quantity' ] ) === 0 ) {
					continue;
				}

				$bundle_sell_quantity = isset( $bundled_item_configuration[ 'quantity' ] ) ? absint( $bundled_item_configuration[ 'quantity' ] ) : $bundled_item->get_quantity();

				$bundle_sells_add_to_cart_configuration[ $bundled_item_id ] = array(
					'product_id' => $bundled_item->get_product()->get_id(),
					'quantity'   => $bundle_sell_quantity
				);
			}
		}

		return $bundle_sells_add_to_cart_configuration;
	}

	/*
	|--------------------------------------------------------------------------
	| Filter hooks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Validates add-to-cart for bundle-sells.
	 *
	 * @param  boolean  $add
	 * @param  int      $product_id
	 * @param  int      $quantity
	 * @param  mixed    $variation_id
	 * @param  array    $variations
	 * @param  array    $cart_item_data
	 * @return boolean
	 */
	public static function validate_add_to_cart( $add, $product_id, $quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {

		if ( $add ) {

			$product         = wc_get_product( $product_id );
			$bundle_sell_ids = WC_PB_BS_Product::get_bundle_sell_ids( $product );

			if ( ! empty( $bundle_sell_ids ) ) {

				// Construct a dummy bundle to validate the posted form content.
				$bundle = WC_PB_BS_Product::get_bundle( $bundle_sell_ids, $product );

				if ( is_a( $bundle, 'WC_Product_Bundle' ) && false === WC_PB()->cart->validate_bundle_add_to_cart( $bundle, $quantity, $cart_item_data ) ) {
					$add = false;
				}
			}
		}

		return $add;
	}

	/**
	 * Adds bundle-sells to the cart on the 'woocommerce_add_to_cart' action.
	 * Important: This must run before WooCommerce sets cart session data on 'woocommerce_add_to_cart' (20).
	 *
	 * @param  string  $parent_cart_item_key
	 * @param  int     $parent_id
	 * @param  int     $parent_quantity
	 * @param  int     $variation_id
	 * @param  array   $variation
	 * @param  array   $cart_item_data
	 * @return void
	 */
	public static function bundle_sells_add_to_cart( $parent_cart_item_key, $parent_id, $parent_quantity, $variation_id, $variation, $cart_item_data ) {

		// Only proceed if the product was added to the cart via a form or query string.
		if ( empty( $_REQUEST[ 'add-to-cart' ] ) || absint( $_REQUEST[ 'add-to-cart' ] ) !== absint( $parent_id ) ) {
			return;
		}

		$product = $variation_id > 0 ? wc_get_product( $parent_id ) : WC()->cart->cart_contents[ $parent_cart_item_key ][ 'data' ];

		$bundle_sells_configuration = self::get_posted_bundle_sells_configuration( $product );

		if ( ! empty( $bundle_sells_configuration ) ) {
			foreach ( $bundle_sells_configuration as $bundle_sell_configuration ) {

				// Unique way to identify bundle-sells in the cart.
				$bundle_sell_cart_data = array( 'bundle_sell_of' => $parent_cart_item_key );

				// Add the bundle-sell to the cart.
				$bundle_sell_cart_item_key = WC()->cart->add_to_cart( $bundle_sell_configuration[ 'product_id' ], $bundle_sell_configuration[ 'quantity' ], '', '', $bundle_sell_cart_data );

				// Add a reference in the parent cart item.
				if ( isset( WC()->cart->cart_contents[ $parent_cart_item_key ] ) ) {
					if ( ! isset( WC()->cart->cart_contents[ $parent_cart_item_key ][ 'bundle_sells' ] ) ) {
						WC()->cart->cart_contents[ $parent_cart_item_key ][ 'bundle_sells' ] = array( $bundle_sell_cart_item_key );
					} else {
						WC()->cart->cart_contents[ $parent_cart_item_key ][ 'bundle_sells' ][] = $bundle_sell_cart_item_key;
					}
				}
			}
		}
	}

	/**
	 * Filter the add-to-cart success message to include bundle-sells.
	 *
	 * @param  string  $message
	 * @param  array   $products
	 * @return string
	 */
	public static function bundle_sells_add_to_cart_message_html( $message, $products ) {

		if ( isset( self::$bypass_filters[ 'add_to_cart_message_html' ] ) && self::$bypass_filters[ 'add_to_cart_message_html' ] === 1 ) {
			return $message;
		}

		$parent_product_ids = array_keys( $products );
		$parent_product_id  = current( $parent_product_ids );

		$bundle_sells_configuration = self::get_posted_bundle_sells_configuration( $parent_product_id );

		if ( ! empty( $bundle_sells_configuration ) ) {

			foreach ( $bundle_sells_configuration as $bundle_sell_configuration ) {
				$products[ $bundle_sell_configuration[ 'product_id' ] ] = $bundle_sell_configuration[ 'quantity' ];
			}

			self::$bypass_filters[ 'add_to_cart_message_html' ] = 1;
			$message = wc_add_to_cart_message( $products, true );
			self::$bypass_filters[ 'add_to_cart_message_html' ] = 0;
		}

		return $message;
	}
}

WC_PB_BS_Cart::init();
