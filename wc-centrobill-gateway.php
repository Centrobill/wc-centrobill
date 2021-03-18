<?php
/**
 * Plugin Name: CentroBill Payment Gateway
 * Plugin URI:
 * Description: Allows you to use CentroBill payment gateway with the WooCommerce plugin
 * Version: 2.0.0
 * Author: CentroBill
 * Author URI: https://centrobill.com/
 *
 * Tested up to: 4.9.16
 * WC tested up to: 3.7.0
 */
defined('ABSPATH') || exit;

define('WC_CENTROBILL_VERSION', '2.0.0');
define('WC_CENTROBILL_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WC_CENTROBILL_PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('WC_CENTROBILL_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));

if (!class_exists('WC_Centrobill')) {
    /**
     * Class WC_Centrobill
     */
    class WC_Centrobill
    {
        /**
         * @var WC_Centrobill $instance
         */
        private static $instance;

        /**
         * @var WC_Centrobill_Api $api
         */
        public $api;

        /**
         * @var WC_Centrobill_Logger $logger
         */
        public $logger;

        /**
         * @return WC_Centrobill
         */
        public static function getInstance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            add_action('plugins_loaded', [$this, 'init']);
        }

        /**
         * Initialize the gateway
         */
        public function init()
        {
            if (!class_exists('WC_Payment_Gateway')) {
                return;
            }

            $classes = [
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-api.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-gateway.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-subscription.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-logger.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-exception.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-webhook-handler.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/class-wc-centrobill-widget.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/wc-centrobill-functions.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/wc-centrobill-constants.php',
            ];

            foreach ($classes as $class) {
                if (file_exists($class)) {
                    include_once $class;
                }
            }

            $settings = get_option('woocommerce_centrobill_settings', []);

            $this->api = new WC_Centrobill_Api($settings);
            $this->logger = new WC_Centrobill_Logger($settings);

            add_filter('woocommerce_payment_gateways', [$this, 'addGateways']);
        }

        /**
         * Add the gateways to WooCommerce
         *
         * @param array $methods
         *
         * @return array
         */
        public function addGateways($methods)
        {
            if (WC_Centrobill_Subscription::isWCSubscriptionsPluginActive()) {
                $methods[] = WC_Centrobill_Subscription::class;
            } else {
                $methods[] = WC_Centrobill_Gateway::class;
            }

            return $methods;
        }

        private function __clone() {}
        private function __wakeup() {}
    }

    WC_Centrobill::getInstance();
}

/**
 * Returns the main instance of WC_Centrobill
 *
 * @return WC_Centrobill
 */
function wc_centrobill() {
    return WC_Centrobill::getInstance();
}
