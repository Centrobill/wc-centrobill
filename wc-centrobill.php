<?php
/**
 * Plugin Name:     CentroBill Payment Gateway
 * Plugin URI:      https://centrobill.com
 * Description:     Allows you to use CentroBill payment gateway with the WooCommerce plugin
 * Version:         2.2.7
 * Author:          CentroBill
 * Author URI:      https://centrobill.com/
 * License:         GPL v3 or later
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 */
defined('ABSPATH') || exit;

define('WC_CENTROBILL_VERSION', '2.2.7');
define('WC_CENTROBILL_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WC_CENTROBILL_PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('WC_CENTROBILL_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));

if (!class_exists('WC_Centrobill')) {
    /**
     * Class WC_Centrobill
     */
    class WC_Centrobill
    {
        const TRANSIENT_PM_TTL = 60 * 10;

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
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-webhook-handler.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/class-wc-centrobill-gateway.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-cc.php',
                WC_CENTROBILL_PLUGIN_PATH . '/includes/gateways/class-wc-centrobill-gateway-local-payment.php',
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
         * @param string $data
         */
        public static function updatePaymentGateways($data)
        {
            if (wc_centrobill_is_payment_page_enabled()) {
                return;
            }

            $user = wp_get_current_user();
            parse_str($data, $result);

            if ($user->exists() || (!empty($result['billing_email']) && is_email($result['billing_email']))) {
                wc_centrobill()->logger->info(__METHOD__, [
                    'is_logged_in' => $user->exists(),
                    'is_email' => !empty($result['billing_email']),
                ]);
                $email = !empty($result['billing_email']) ? $result['billing_email'] : $user->user_email;
                WC()->session->set(SESSION_KEY_EMAIL, sanitize_email($email));

                add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'getAvailablePaymentGateways']);
            }
        }

        /**
         * @param array $gateways
         *
         * @return array
         */
        public static function getAvailablePaymentGateways(array $gateways)
        {
            if (wc_centrobill_is_payment_page_enabled()) {
                return $gateways;
            }

            $email = WC()->session->get(SESSION_KEY_EMAIL);
            if (false === ($response = get_transient(self::getPMTransientKey($email)))) {
                try {
                    $response = wc_centrobill()->api->getPaymentMethods($email);
                    set_transient(self::getPMTransientKey($email), $response, self::TRANSIENT_PM_TTL);
                } catch (Exception $e) {
                    $response = [];
                    wc_centrobill()->logger->error($e->getMessage());
                }
            }

            foreach ($response as $paymentMethod) {
                $gateway = sprintf('WC_Centrobill_Gateway_%s', strtoupper($paymentMethod));
                if (class_exists($gateway)) {
                    $gateway = new $gateway();
                }
                if ($gateway instanceof WC_Centrobill_Gateway_Abstract && $gateway->is_accessible()) {
                    $gateways[$gateway->id] = $gateway;
                }
            }

            return $gateways;
        }

        /**
         * @param string $email
         *
         * @return string
         */
        public static function getPMTransientKey($email)
        {
            return sprintf(SESSION_KEY_PM, md5($email));
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

add_action('woocommerce_checkout_update_order_review', ['WC_Centrobill', 'updatePaymentGateways']);
