<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Onlinebanking')) {
    /**
     * Class WC_Centrobill_Gateway_Onlinebanking
     */
    class WC_Centrobill_Gateway_Onlinebanking extends WC_Centrobill_Gateway_Onlinebanking_Abstract
    {
        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_ONLINEBANKING);
            $this->method_title = __('Centrobill Onlinebanking', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('onlinebanking.png');
            $this->has_fields = false;

            parent::__construct();
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadOnlinebankingFormFields();
        }

        /**
         * {@inheritDoc}
         */
        public function gateway_process_payment($orderId)
        {
            $paymentSource = [
                'type' => str_replace('centrobill_', '', $this->id),
            ];

            if ($bic = wc_centrobill_retrieve_post_param('centrobill_bic')) {
                $paymentSource['bic'] = $bic;
            }

            return wc_centrobill()->api->pay($paymentSource, $orderId);
        }
    }
}
