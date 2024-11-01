<?php
/**
 * Plugin Name: WooCommerce BitPay
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-bitpay
 * Description: WooCommerce BitPay is a bitcoin payment gateway for WooCommerce
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 1.2.0
 * License: GPLv2 or later
 * Text Domain: wcbitpay
 * Domain Path: /languages/
 */

/**
 * WooCommerce fallback notice.
 */
function wcbitpay_woocommerce_fallback_notice() {
    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce BitPay Gateway depends on the last version of %s to work!', 'wcbitpay' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
}

/**
 * Load functions.
 */
add_action( 'plugins_loaded', 'wcbitpay_gateway_load', 0 );

function wcbitpay_gateway_load() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'wcbitpay_woocommerce_fallback_notice' );

        return;
    }

    /**
     * Load textdomain.
     */
    load_plugin_textdomain( 'wcbitpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    /**
     * Add the gateway to WooCommerce.
     *
     * @param  array $methods WooCommerce payment methods.
     *
     * @return array          Payment methods with BitPay.
     */
    function wcbitpay_add_gateway( $methods ) {
        $methods[] = 'WC_BitPay_Gateway';

        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'wcbitpay_add_gateway' );

    // Include the WC_BitPay_Gateway class.
    require_once plugin_dir_path( __FILE__ ) . 'class-wc-bitpay-gateway.php';
}

/**
 * Adds support to legacy IPN.
 *
 * @return void
 */
function wcbitpay_legacy_ipn() {
    if ( isset( $_POST['posData'] ) && ! isset( $_GET['wc-api'] ) ) {
        global $woocommerce;

        $woocommerce->payment_gateways();

        do_action( 'woocommerce_api_wc_bitpay_gateway' );
    }
}

add_action( 'init', 'wcbitpay_legacy_ipn' );

/**
 * Adds custom settings url in plugins page.
 *
 * @param  array $links Default links.
 *
 * @return array        Default links and settings link.
 */
function wcbitpay_action_links( $links ) {

    $settings = array(
        'settings' => sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_BitPay_Gateway' ),
            __( 'Settings', 'wcbitpay' )
        )
    );

    return array_merge( $settings, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcbitpay_action_links' );
