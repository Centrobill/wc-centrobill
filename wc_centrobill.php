<?php
/*
  Plugin Name: CentroBill Payment Gateway
  Plugin URI:
  Description: Allows you to use CentroBill payment gateway with the WooCommerce plugin
  Version: 1.0.1
  Author: CentroBill
  Author URI: https://centrobill.com/
 */

defined('ABSPATH') or exit();

/* Add a custom payment class to WC */
add_action('plugins_loaded', 'woocommerce_centrobill_init');

function woocommerce_centrobill_init()
{
    if(!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once('includes/class-wc-centrobill-gateway-plugin.php');
    require_once('includes/class-wc-centrobill-widget.php');
    require_once('includes/class-wc-centrobill-webhook-handler.php');
    require_once('includes/class-wc-centrobill-api.php');

    /**
     * Add the gateway to WooCommerce
     **/
    function add_centrobill_gateway($methods)
    {
        $methods[] = 'WC_Centrobill_Gateway_Plugin';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_centrobill_gateway');
}