<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Crypto')) {
    /**
     * Class WC_Centrobill_Gateway_Crypto
     */
    class WC_Centrobill_Gateway_Crypto extends WC_Centrobill_Gateway_Abstract
    {
        const MIN_AVAILABLE_AMOUNT_USD = 37;

        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_CRYPTO);
            $this->method_title = __('Centrobill Crypto', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('crypto.png');

            parent::__construct();
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadCryptoFormFields();
        }

        /**
         * {@inheritDoc}
         */
        public function is_accessible()
        {
            $option = $this->get_option(SETTING_KEY_CRYPTO_AVAILABILITY);
            $subscriptionAmount = $this->getCartSubscriptionProductsTotal();

            if ($option === SETTING_OPTION_CRYPTO_ONETIME && $subscriptionAmount !== null) {
                return false;
            }

            if ($option === SETTING_OPTION_CRYPTO_ALL_WITH_EXCLUDING && $subscriptionAmount !== null) {
                if ($subscriptionAmount < self::MIN_AVAILABLE_AMOUNT_USD) {
                    return false;
                }
            }

            return parent::is_accessible();
        }

        /**
         * {@inheritDoc}
         */
        public function payment_form()
        {
        }

        /**
         * {@inheritDoc}
         */
        public function before_process_payment(array $data, $errors)
        {
        }

        /**
         * {@inheritDoc}
         */
        public function gateway_process_payment($orderId)
        {
            $paymentSource = [
                'type' => str_replace('centrobill_', '', $this->id),
            ];

            return wc_centrobill()->api->pay($paymentSource, $orderId);
        }

        /**
         * If null, no subscription products
         *
         * @return int|null
         */
        private function getCartSubscriptionProductsTotal()
        {
            $total = null;
            if (WC()->cart instanceof WC_Cart && ($products = WC()->cart->get_cart_contents())) {
                foreach ($products as $product) {
                    if (!empty($product['data']) && $product['data'] instanceof WC_Product_Subscription) {
                        $total = (int)$product['line_total'];
                        break;
                    }
                }
            }

            return $total;
        }
    }
}
