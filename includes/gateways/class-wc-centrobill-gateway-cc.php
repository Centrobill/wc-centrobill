<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_CC')) {
    /**
     * Class WC_Centrobill_Gateway_CC
     */
    class WC_Centrobill_Gateway_CC extends WC_Centrobill_Gateway_Abstract
    {
        public function __construct()
        {
            $this->id = 'centrobill_cc';
            $this->method_title = __('Centrobill Credit Cards', 'woocommerce-gateway-centrobill');
            $this->icon = wc_centrobill_image_url('centrobill_logo.png');
            $this->has_fields = true;

            add_action('admin_notices', [$this, 'show_admin_notice']);

            parent::__construct();
        }

        /**
         * @return void
         */
        public function show_admin_notice()
        {
            if (
                $this->enabled === SETTING_VALUE_YES &&
                empty($this->get_option(SETTING_KEY_AUTH_KEY)) &&
                empty($this->get_option(SETTING_KEY_SITE_ID))
            ) {
                echo sprintf('<div class="error"><p>%s</p></div>', __('CentroBill is enabled but credentials are not set.', 'woocommerce-gateway-centrobill'));
            }
        }

        /**
         * {@inheritDoc}
         */
        public function init_form_fields()
        {
            $this->form_fields = WC_Centrobill_Admin_Widget::loadAdminFormFields();
        }

        /**
         * {@inheritDoc}
         */
        public function payment_form()
        {
            wc_centrobill_get_template('centrobill-cc-form.php');
        }

        /**
         * {@inheritDoc}
         */
        public function before_process_payment(array $data, $errors)
        {
            if ($data['payment_method'] !== $this->id) {
                return;
            }

            $tokenRequiredFields = [
                'centrobill_card_number' => __('Card Number', 'centrobill'),
                'centrobill_expiration_date' => __('Expiry (MM/YY)', 'centrobill'),
                'centrobill_cvv' => __('CVN/CVV', 'centrobill'),
            ];

            foreach ($tokenRequiredFields as $field => $label) {
                if (!array_key_exists($field, $data)) {
                    continue;
                }

                if (empty($data[$field])) {
                    $errors->add('required-field', sprintf('<strong>%s</strong> is a required field.', $label));
                }
                if ($field === 'centrobill-expiration-date' && !empty($data[$field]) && !wc_centrobill_check_expiration_date($data[$field])) {
                    $errors->add('validation', sprintf('<strong>%s</strong> has invalid format.', $label));
                }
            }
        }

        /**
         * {@inheritDoc}
         */
        public function gateway_process_payment($orderId)
        {
            try {
                $response = wc_centrobill()->api->getToken(wc_centrobill_retrieve_post_param());
                wc_centrobill_set_post_param('centrobill_card_token', $response['token']);
                wc_centrobill_set_post_param('centrobill_card_token_expire', $response['expireAt']);
            } catch (WC_Centrobill_Exception $e) {
                wc_centrobill()->logger->error(__METHOD__, $e->getMessage());

                throw new $e;
            }

            if (!wc_centrobill_retrieve_post_param('centrobill_card_token')) {
                throw new WC_Centrobill_Exception('Payment token was not received.');
            }

            return wc_centrobill()->api->pay(
                [
                    'type' => PAYMENT_TYPE_TOKEN,
                    'value' => wc_centrobill_retrieve_post_param('centrobill_card_token'),
                ],
                $orderId
            );
        }
    }
}
