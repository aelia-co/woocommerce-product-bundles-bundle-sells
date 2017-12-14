<?php
/*
* Plugin Name: WooCommerce Product Bundles - Bundle-Sells
* Plugin URI: http://woocommerce.com/products/product-bundles/
* Description: Add "Frequently Bought Together" recommendations to your WooCommerce product pages. Powered by WooCommerce Product Bundles.
* Version: 1.0.4
* Author: SomewhereWarm
* Author URI: http://somewherewarm.gr/
*
* Text Domain: woocommerce-product-bundles-bundle-sells
* Domain Path: /languages/
*
* Requires at least: 4.1
* Tested up to: 4.9
*
* WC requires at least: 3.0
* WC tested up to: 3.2
*
* Copyright: © 2017 SomewhereWarm SMPC.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * @class    WC_PB_Bundle_Sells
 * @version  1.0.4
 */
class WC_PB_Bundle_Sells {

	/**
	 * Version.
	 * @var string
	 */
	public static $version = '1.0.4';

	/**
	 * Required PB version.
	 * @var string
	 */
	public static $req_pb_version = '5.6';

	/**
	 * Plugin URL.
	 */
	public static function plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	/**
	 * Plugin path.
	 */
	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Entry point.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_plugin' ) );
	}

	/**
	 * Lights on.
	 */
	public static function load_plugin() {

		// Check dependencies.
		if ( ! function_exists( 'WC_PB' ) || version_compare( WC_PB()->version, self::$req_pb_version ) < 0 ) {
			add_action( 'admin_notices', array( __CLASS__, 'version_notice' ) );
			return false;
		}

		// Localize plugin.
		add_action( 'init', array( __CLASS__, 'localize_plugin' ) );

		self::includes();
	}

	/**
	 * Class includes.
	 */
	private static function includes() {

		// Admin.
		if ( is_admin() ) {
			require_once( 'includes/admin/class-wc-pb-bs-admin.php' );
		}

		// Global-scope functions.
		require_once( 'includes/wc-pb-bs-functions.php' );

		// Product-related functions and hooks.
		require_once( 'includes/class-wc-pb-bs-product.php' );

		// Cart-related functions and hooks.
		require_once( 'includes/class-wc-pb-bs-cart.php' );

		// Order-related functions and hooks.
		require_once( 'includes/class-wc-pb-bs-order.php' );

		// Display-related functions and hooks.
		require_once( 'includes/class-wc-pb-bs-display.php' );
	}

	/**
	 * Load textdomain.
	 */
	public static function localize_plugin() {
		load_plugin_textdomain( 'woocommerce-product-bundles-bundle-sells', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * PB version check notice.
	 */
	public static function version_notice() {
	    echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Product Bundles &ndash; Bundle-Sells</strong> requires Product Bundles <strong>%s</strong> or higher.', 'woocommerce-product-bundles-bundle-sells' ), self::$req_pb_version ) . '</p></div>';
	}
}

WC_PB_Bundle_Sells::init();
