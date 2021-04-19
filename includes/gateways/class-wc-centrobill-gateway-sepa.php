<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Sepa')) {
    /**
     * Class WC_Centrobill_Gateway_Sepa
     */
    class WC_Centrobill_Gateway_Sepa extends WC_Centrobill_Gateway_Abstract
    {
        public function __construct()
        {
            $this->id = sprintf('centrobill_%s', PAYMENT_TYPE_SEPA);
            $this->method_title = __('Centrobill SEPA', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('sepa.png');
            $this->has_fields = true;

            parent::__construct();
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadSepaFormFields();
        }

        /**
         * {@inheritDoc}
         */
        public function payment_form()
        {
            wc_centrobill_get_template('centrobill-sepa-form.php');
        }

        /**
         * {@inheritDoc}
         */
        public function before_process_payment(array $data, $errors)
        {
            if ($data['payment_method'] !== $this->id) {
                return;
            }

            if (empty(wc_centrobill_retrieve_post_param('centrobill_iban'))) {
                $errors->add('required-field', '<strong>IBAN</strong> is a required field.');
            }
        }

        /**
         * {@inheritDoc}
         */
        public function gateway_process_payment($orderId)
        {
            $paymentSource = [
                'type' => PAYMENT_TYPE_SEPA,
                'iban' => wc_centrobill_retrieve_post_param('centrobill_iban'),
            ];

            if ($bic = wc_centrobill_retrieve_post_param('centrobill_bic')) {
                $paymentSource['bic'] = $bic;
            }

            return wc_centrobill()->api->pay($paymentSource, $orderId);
        }
    }
}
