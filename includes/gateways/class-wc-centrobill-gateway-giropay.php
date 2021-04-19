<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Giropay')) {
    /**
     * Class WC_Centrobill_Gateway_Giropay
     */
    class WC_Centrobill_Gateway_Giropay extends WC_Centrobill_Gateway_Local_payment
    {
        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_GIROPAY);
            $this->method_title = __('Centrobill Giropay', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('giropay.png');

            parent::__construct();
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadGiropayFormFields();
        }
    }
}
