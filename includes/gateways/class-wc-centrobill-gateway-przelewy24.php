<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Przelewy24')) {
    /**
     * Class WC_Centrobill_Gateway_Przelewy24
     */
    class WC_Centrobill_Gateway_Przelewy24 extends WC_Centrobill_Gateway_Local_payment
    {
        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_PRZELEWY24);
            $this->method_title = __('Centrobill Przelewy24', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('przelewy24.png');

            parent::__construct();
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadPrzelewy24FormFields();
        }
    }
}
