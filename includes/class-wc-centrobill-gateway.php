<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway')) {
    /**
     * Class WC_Centrobill_Gateway
     */
    class WC_Centrobill_Gateway extends WC_Payment_Gateway
    {
        /**
         * @var string
         */
        protected $authKey;

        /**
         * @var integer
         */
        protected $siteId;

        public function __construct()
        {
            $this->id = 'centrobill';
            $this->icon = apply_filters('woocommerce_centrobill_icon', WC_CENTROBILL_PLUGIN_URL . '/assets/images/centrobill_logo.png');
            $this->method_description = __('Take payments online with CentroBill', 'woocommerce');
            $this->has_fields = true;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            list($this->authKey, $this->siteId) = explode(':', $this->get_option('token'));
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Actions
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
            add_action('woocommerce_after_checkout_validation', [$this, 'before_process_payment'], 10, 2);

            // Save options
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

            // Enqueue JS
            add_filter('wp_enqueue_scripts', [$this, 'payment_scripts']);

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_centrobill', [new WC_Centrobill_Webhook_Handler(), 'process']);

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's country
         *
         * @access public
         * @return bool
         */
        public function is_valid_for_use()
        {
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_centrobill_supported_currencies', ['RUB', 'USD', 'EUR', 'UAH']))) {
                return false;
            }

            return true;
        }

        /**
         * {@inheritDoc}
         */
        public function admin_options()
        {
            WC_Centrobill_Admin_Widget::showAdminOptions(
                $this->is_valid_for_use(),
                $this->generate_settings_html([], false)
            );
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
        public function payment_fields()
        {
            wp_enqueue_script('centrobill-payment-form');
            wc_centrobill_get_template('centrobill-cc-form.php');
        }

        /**
         * @param int $orderId
         */
        public function receipt_page($orderId)
        {
            wp_enqueue_script('centrobill-payment-form');
            wc_centrobill_get_template('centrobill-cc-form.php');
        }

        /**
         * @param array $data
         * @param WP_Error $errors
         */
        public function before_process_payment(array $data, $errors)
        {
            if ($data['payment_method'] !== $this->id) {
                return;
            }

            $data = array_merge(wc_centrobill_retrieve_post_param(), $data);

            $tokenRequiredFields = [
                'centrobill-card-number' => 'Card Number',
                'centrobill-expiration-date' => 'Expiry (MM/YY)',
                'centrobill-cvv' => 'CVN/CVV',
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

            if (!empty($errors->errors)) {
                return;
            }

            try {
                $response = wc_centrobill()->api->getToken($data);

                wc_centrobill_set_post_param('centrobill_card_token', $response['token']);
                wc_centrobill_set_post_param('centrobill_card_token_expire', $response['expireAt']);

            } catch (WC_Centrobill_Exception $e) {
                $errors->add('error', $e->getMessage());
                wc_centrobill()->logger->error(sprintf('%s | %s', __METHOD__, $e->getMessage()));
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param int $orderId
         *
         * @return array
         */
        public function process_payment($orderId)
        {
            try {
                if (!wc_centrobill_retrieve_post_param('centrobill_card_token')) {
                    throw new WC_Centrobill_Exception('Gateway error. Payment token was not received');
                }

                $order = wc_get_order($orderId);
                $response = wc_centrobill()->api->pay(wc_centrobill_retrieve_post_param('centrobill_card_token'), $order);

                return [
                    'result' => 'success',
                    'redirect' => wc_centrobill_is_3ds_redirect($response) ?
                        $response['payment']['url'] : $this->get_return_url($order),
                ];
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                wc_centrobill()->logger->error('Process payment failed. ' . $e->getMessage());

                return [
                    'result' => 'fail',
                    'redirect' => '',
                ];
            }
        }

        /**
         * @return void
         */
        public function payment_scripts()
        {
            if (!is_checkout() || is_order_received_page() || $this->enabled === 'no') {
                return;
            }

            wp_register_script(
                'centrobill-payment-form',
                WC_CENTROBILL_PLUGIN_URL . '/assets/js/centrobill-payment-form.js',
                ['jquery', 'jquery-payment'],
                WC_CENTROBILL_VERSION,
                true
            );

            wp_localize_script(
                'centrobill-payment-form',
                'centrobill_params',
                [
                    'tokenize_url' => sprintf('%s/tokenize', WC_Centrobill_Api::CENTROBILL_API_URL),
                ]
            );
        }
    }
}
