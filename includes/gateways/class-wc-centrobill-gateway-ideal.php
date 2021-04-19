<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Ideal')) {
    /**
     * Class WC_Centrobill_Gateway_Ideal
     */
    class WC_Centrobill_Gateway_Ideal extends WC_Centrobill_Gateway_Local_payment
    {
        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_IDEAL);
            $this->method_title = __('Centrobill iDEAL', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('ideal.png');

            parent::__construct();
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadIdealFormFields();
        }
    }
}
