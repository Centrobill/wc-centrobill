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
            $this->id = 'centrobill';
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
                (empty($this->get_option(SETTING_KEY_AUTH_KEY)) || empty($this->get_option(SETTING_KEY_SITE_ID)))
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
        public function is_available()
        {
            return $this->enabled === SETTING_VALUE_YES;
        }

        /**
         * {@inheritDoc}
         */
        public function payment_form()
        {
            wc_centrobill_get_template('centrobill-cc-form.php', [
                'show_cardholder_name' => $this->isNeedShowCardholderName(),
            ]);
        }

        /**
         * {@inheritDoc}
         */
        public function before_process_payment(array $data, $errors)
        {
            if ($data['payment_method'] !== $this->id) {
                return;
            }

            $data = array_merge(wc_centrobill_retrieve_post_param(), $data);

            $tokenRequiredFields = [
                'centrobill_card_number' => __('Card Number', 'woocommerce-gateway-centrobill'),
                'centrobill_expiration_date' => __('Expiry (MM/YY)', 'woocommerce-gateway-centrobill'),
                'centrobill_cvv' => __('CVN/CVV', 'woocommerce-gateway-centrobill'),
            ];

            if ($this->isNeedShowCardholderName()) {
                $tokenRequiredFields['centrobill_cardholder_name'] = __("Cardholder's name", 'woocommerce-gateway-centrobill');
            }

            foreach ($tokenRequiredFields as $field => $label) {
                if (!array_key_exists($field, $data)) {
                    continue;
                }

                if (empty($data[$field])) {
                    $errors->add('required-field', sprintf('<strong>%s</strong> is a required field.', $label));
                }
                if ($field === 'centrobill_expiration_date' && !empty($data[$field]) && !wc_centrobill_check_expiration_date($data[$field])) {
                    $errors->add('validation', sprintf('<strong>%s</strong> has invalid format.', $label));
                }
                if ($field === 'centrobill_cardholder_name' && !empty($data[$field])) {
                    $names = wc_centrobill_parse_cardholder_name($data[$field]);
                    if (empty($names[0]) || empty($names[1])) {
                        $errors->add('validation', sprintf('<strong>%s</strong> is invalid.', $label));
                    }
                }
            }
        }

        /**
         * {@inheritDoc}
         * @throws WC_Data_Exception
         */
        public function gateway_process_payment($orderId)
        {
            try {
                $response = wc_centrobill()->api->getToken(wc_centrobill_retrieve_post_param());
            } catch (WC_Centrobill_Exception $e) {
                wc_centrobill()->logger->error(__METHOD__, $e->getMessage());

                throw new $e;
            }

            if (empty($response['token'])) {
                throw new WC_Centrobill_Exception('Payment token was not received.');
            }

            if ($this->isNeedShowCardholderName()) {
                $this->saveCardholderName($orderId);
            }

            return wc_centrobill()->api->pay(
                [
                    'type' => PAYMENT_TYPE_TOKEN,
                    'value' => $response['token'],
                ],
                $orderId
            );
        }

        /**
         * @param int $orderId
         *
         * @throws WC_Data_Exception
         * @return void
         */
        private function saveCardholderName($orderId)
        {
            $order = wc_get_order($orderId);

            $cardholderName = wc_centrobill_retrieve_post_param('centrobill_cardholder_name');
            list($firstName, $lastName) = wc_centrobill_parse_cardholder_name($cardholderName);

            $order->set_billing_first_name($firstName);
            $order->set_billing_last_name($lastName);
            $order->save();
        }

        /**
         * @return bool
         */
        private function isNeedShowCardholderName()
        {
            return $this->get_option(SETTING_KEY_CC_CARDHOLDER) === SETTING_VALUE_YES;
        }
    }
}
