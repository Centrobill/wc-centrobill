<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Crypto')) {
    /**
     * Class WC_Centrobill_Gateway_Crypto
     */
    class WC_Centrobill_Gateway_Crypto extends WC_Centrobill_Gateway_Abstract
    {
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
    }
}
