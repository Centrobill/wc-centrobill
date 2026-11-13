<?php
/**
 * Plugin Name:     CentroBill Payment Gateway
 * Plugin URI:      https://centrobill.com
 * Description:     Allows you to use CentroBill payment gateway with the WooCommerce plugin
 * Version:         2.6.0
 * Author:          CentroBill
 * Author URI:      https://centrobill.com/
 * License:         GPL v3 or later
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 */
defined('ABSPATH') || exit;

define('WC_CENTROBILL_VERSION', '2.6.0');
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
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-logger.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-exception.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-validation-exception.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-webhook-handler.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-gateway.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-payment-methods.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-cc.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-local-payment.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-cc_token.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-onlinebanking.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-sepa.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-ideal.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-giropay.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-bancontact.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-sofortbanking.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-przelewy24.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-mybank.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-eps.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-crypto.php',
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

            WC_Centrobill_Payment_Methods::init();

            // Wire up the CartFlows one-click offer integration only when CartFlows Pro is present.
            if (defined('CARTFLOWS_PRO_FILE')) {
                add_filter('cartflows_offer_supported_payment_gateways', [$this, 'addCartflowsOfferGateway']);
                add_filter('cartflows_offer_supported_payment_gateway_slugs', [$this, 'addCartflowsOfferGatewaySlug']);
            }
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
            $methods[] = WC_Centrobill_Gateway_CC::class;
            $methods[] = WC_Centrobill_Gateway_CC_Token::class;
            $methods[] = WC_Centrobill_Gateway_Sepa::class;
            $methods[] = WC_Centrobill_Gateway_Giropay::class;
            $methods[] = WC_Centrobill_Gateway_Ideal::class;
            $methods[] = WC_Centrobill_Gateway_Sofortbanking::class;
            $methods[] = WC_Centrobill_Gateway_Onlinebanking::class;
            $methods[] = WC_Centrobill_Gateway_Bancontact::class;
            $methods[] = WC_Centrobill_Gateway_Przelewy24::class;
            $methods[] = WC_Centrobill_Gateway_Mybank::class;
            $methods[] = WC_Centrobill_Gateway_Eps::class;
            $methods[] = WC_Centrobill_Gateway_Crypto::class;

            return $methods;
        }

        /**
         * @param array $gateways
         * @return array
         */
        public function addCartflowsOfferGateway($gateways)
        {
            require_once WC_CENTROBILL_PLUGIN_PATH . '/includes/cartflows/class-cartflows-centrobill-gateway.php';

            $gatewayFilePath = $this->getCartflowsProLoadedFilePath();
            if ($gatewayFilePath === null) {
                return $gateways;
            }

            $gateways['centrobill'] = [
                'file' => 'class-cartflows-centrobill-gateway.php',
                'class' => 'Cartflows_Pro_Gateway_Centrobill',
                'path' => $gatewayFilePath,
            ];

            return $gateways;
        }

        /**
         * @return string|null
         */
        private function getCartflowsProLoadedFilePath()
        {
            if (!class_exists('Cartflows_Pro_Gateways')) {
                return null;
            }

            try {
                $file = (new ReflectionClass('Cartflows_Pro_Gateways'))->getFileName();
            } catch (ReflectionException $e) {
                return null;
            }

            return is_string($file) && $file !== '' ? $file : null;
        }

        /**
         * Mark Centrobill as an offer-capable gateway so CartFlows starts the
         * upsell flow after checkout.
         *
         * @param array $slugs
         * @return array
         */
        public function addCartflowsOfferGatewaySlug($slugs)
        {
            // Only join the "start the flow" list, never the PayPal skip list.
            if (in_array('cod', $slugs, true) || in_array('bacs', $slugs, true)) {
                $slugs[] = 'centrobill';
            }

            return $slugs;
        }

        private function __clone() {}
        public function __wakeup() {}
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
