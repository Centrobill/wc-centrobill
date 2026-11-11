<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Payment_Methods')) {
    /**
     * Decides which Centrobill gateways are shown on the checkout.
     */
    class WC_Centrobill_Payment_Methods
    {
        const TRANSIENT_TTL = 60 * 10;

        /**
         * Register hooks.
         */
        public static function init()
        {
            add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'filterAvailableGateways']);
        }

        /**
         * @param array $gateways
         *
         * @return array
         */
        public static function filterAvailableGateways($gateways)
        {
            if (!is_array($gateways) || !is_checkout() || wc_centrobill_is_payment_page_enabled()) {
                return $gateways;
            }

            if (!($params = self::getRequestParams())) {
                return $gateways;
            }

            foreach (self::getMethods($params) as $paymentMethod) {
                $class = sprintf('WC_Centrobill_Gateway_%s', strtoupper($paymentMethod));
                if (!class_exists($class)) {
                    continue;
                }

                $gateway = new $class();
                if ($gateway instanceof WC_Centrobill_Gateway_Abstract && $gateway->is_accessible()) {
                    $gateways[$gateway->id] = $gateway;
                }
            }

            return $gateways;
        }

        /**
         * Invalidate all cached payment-method lists at once.
         */
        public static function flushCache()
        {
            update_option(OPTION_PM_CACHE_VERSION, self::getCacheVersion() + 1, true);
        }

        /**
         * @param array $params
         *
         * @return string[]
         */
        private static function getMethods(array $params)
        {
            static $resolved = [];

            try {
                $siteId = wc_centrobill()->api->getSiteId();
            } catch (Exception $e) {
                if (!isset($resolved[''])) {
                    wc_centrobill()->logger->error('[API] Available payment methods error', ['message' => $e->getMessage()]);
                }

                return $resolved[''] = [];
            }

            $key = self::getTransientKey($siteId, $params);
            if (isset($resolved[$key])) {
                return $resolved[$key];
            }

            $methods = get_transient($key);
            if (is_array($methods)) {
                return $resolved[$key] = $methods;
            }

            try {
                $methods = wc_centrobill()->api->getAvailablePaymentMethods($params);
            } catch (Exception $e) {
                wc_centrobill()->logger->error('[API] Available payment methods error', ['message' => $e->getMessage()]);

                return $resolved[$key] = [];
            }

            if (!empty($methods)) {
                set_transient($key, $methods, self::TRANSIENT_TTL);
            }

            return $resolved[$key] = $methods;
        }

        /**
         * Build the purchase context from the order being paid (order-pay page) or the cart.
         *
         * @return array|null
         */
        private static function getRequestParams()
        {
            $orderId = absint(get_query_var('order-pay'));
            if ($orderId && ($order = wc_get_order($orderId))) {
                $amount = (float)$order->get_total();

                return [
                    'amount' => $amount,
                    'currency' => $order->get_currency(),
                    'type' => self::getSkuType(wc_centrobill_is_order_contains_subscription($order), $amount),
                    'country' => wc_centrobill_country_convert_to_iso3($order->get_billing_country()),
                    'ip' => wc_centrobill_get_ip_address(),
                ];
            }

            if (!function_exists('WC') || !(WC()->cart instanceof WC_Cart) || WC()->cart->is_empty()) {
                return null;
            }

            $isSubscription = class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription();
            $country = WC()->customer instanceof WC_Customer ? WC()->customer->get_billing_country() : '';
            $amount = (float)WC()->cart->get_total('edit');

            return [
                'amount' => $amount,
                'currency' => get_woocommerce_currency(),
                'type' => self::getSkuType($isSubscription, $amount),
                'country' => wc_centrobill_country_convert_to_iso3($country),
                'ip' => wc_centrobill_get_ip_address(),
            ];
        }

        /**
         * @param bool $isSubscription
         * @param float $amount
         *
         * @return string
         */
        private static function getSkuType($isSubscription, $amount)
        {
            if (!$isSubscription) {
                return SKU_TYPE_ONE_TIME;
            }

            return $amount > 0 ? SKU_TYPE_INITIAL : SKU_TYPE_FREE_TRIAL;
        }

        /**
         * @param int|string $siteId
         * @param array $params
         *
         * @return string
         */
        private static function getTransientKey($siteId, array $params)
        {
            if (!empty($params['country'])) {
                unset($params['ip']);
            }

            return sprintf(
                TRANSIENT_KEY_PM,
                md5(wp_json_encode([$siteId, $params, self::getCacheVersion()]))
            );
        }

        /**
         * @return int
         */
        private static function getCacheVersion()
        {
            return (int)get_option(OPTION_PM_CACHE_VERSION, 1);
        }
    }
}
