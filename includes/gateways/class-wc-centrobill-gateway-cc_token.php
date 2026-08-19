<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_CC_Token')) {
    /**
     * Class WC_Centrobill_Gateway_CC_Token
     */
    class WC_Centrobill_Gateway_CC_Token extends WC_Centrobill_Gateway_Local_payment
    {
        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_CC_TOKEN);
            $this->method_title = __('Centrobill Credit Card (Hosted)', 'woocommerce-gateway-centrobill');

            parent::__construct();

            $this->supports[] = 'refunds';

            if ($this->get_option(SETTING_KEY_SHOW_ICON, SETTING_VALUE_YES) === SETTING_VALUE_YES) {
                $this->icon = wc_centrobill_image_url('cc.png');
            }
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadCcTokenFormFields();
        }

        /**
         * {@inheritDoc}
         */
        public function get_icon()
        {
            if (empty($this->icon)) {
                return '';
            }

            $icon = sprintf(
                '<img src="%s" alt="%s" style="max-height:16px;width:auto;vertical-align:middle;" />',
                esc_url(WC_HTTPS::force_https_url($this->icon)),
                esc_attr($this->get_title())
            );

            return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
        }
    }
}
