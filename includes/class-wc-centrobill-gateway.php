<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Abstract')) {
    /**
     * Class WC_Centrobill_Gateway_Abstract
     */
    abstract class WC_Centrobill_Gateway_Abstract extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->method_description = __('Take payments online with CentroBill', 'woocommerce-gateway-centrobill');

            $this->init_form_fields();
            $this->init_settings();
            $this->init_hooks();
            $this->init_supports();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            if (!wc_centrobill_is_valid_for_use()) {
                $this->enabled = SETTING_VALUE_NO;
            }
        }

        /**
         * @return void
         */
        public function init_hooks()
        {
            if (is_admin()) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            }
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
            add_action('woocommerce_after_checkout_validation', [$this, 'before_process_payment'], 8, 2);
            add_action('woocommerce_api_wc_gateway_centrobill', [new WC_Centrobill_Webhook_Handler(), 'process']);
            add_filter('wp_enqueue_scripts', [$this, 'payment_scripts']);
            add_filter('wc_centrobill_settings_nav_tabs', [$this, 'admin_navigation_tabs']);
            add_filter('woocommerce_create_order', [$this, 'remove_awaiting_order']);

            if (wc_centrobill_is_subscriptions_enabled()) {
                add_action('woocommerce_scheduled_subscription_payment_' . $this->id, [$this, 'process_subscription_payment'], 10, 2);
            }
        }

        /**
         * @return void
         */
        public function init_supports()
        {
            if (wc_centrobill_is_subscriptions_enabled()) {
                $this->supports = array_merge(
                    $this->supports,
                    [
                        'subscriptions',
                        'subscription_cancellation',
                        'subscription_suspension',
                        'subscription_reactivation',
                        'subscription_amount_changes',
                        'subscription_date_changes',
                        'subscription_payment_method_change',
                    ]
                );
            }
        }

        /**
         * @return void
         */
        public static function init()
        {
            if (!empty($_REQUEST['billing_email'])) {
                add_filter('woocommerce_available_payment_gateways', ['WC_Centrobill', 'getAvailablePaymentGateways']);
            }
        }

        /**
         * @param array $data
         * @param WP_Error $errors
         */
        abstract public function before_process_payment(array $data, $errors);

        /**
         * Process the payment and return the redirect URL if exists
         *
         * @param int $orderId
         *
         * @return array|null
         * @throws WC_Centrobill_Exception
         */
        abstract public function gateway_process_payment($orderId);

        /**
         * Outputs fields for entering payment information
         *
         * @return void
         */
        abstract public function payment_form();

        /**
         * @param array $tabs
         *
         * @return array
         */
        public function admin_navigation_tabs(array $tabs)
        {
            $tabs[$this->id] = str_replace('Centrobill', '', $this->method_title);

            return $tabs;
        }

        /**
         * The default check is disabled
         *
         * @return bool
         */
        public function is_available()
        {
            return false;
        }

        /**
         * Check if the gateway is accessible for use
         *
         * @return bool
         */
        public function is_accessible()
        {
            $settings = get_option('woocommerce_centrobill_settings', []);

            if (empty($settings[SETTING_KEY_AUTH_KEY]) || empty($settings[SETTING_KEY_SITE_ID])) {
                return false;
            }

            return $this->enabled === SETTING_VALUE_YES;
        }

        /**
         * {@inheritDoc}
         */
        public function admin_options()
        {
            WC_Centrobill_Admin_Widget::showAdminOptions(
                wc_centrobill_is_valid_for_use(),
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
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            if (!wc_centrobill_is_payment_page_enabled()) {
                wp_enqueue_script('centrobill-payment-form');
                $this->payment_form();
            }
        }

        /**
         * @param int $orderId
         */
        public function receipt_page($orderId)
        {
            wp_enqueue_script('centrobill-payment-form');
            $this->payment_form();
        }

        /**
         * If the order is pending payment don't resume it, create a new order instead
         */
        public function remove_awaiting_order()
        {
            if (isset(WC()->session)) {
                WC()->session->set('order_awaiting_payment', null);
            }
        }

        /**
         * {@inheritDoc}
         */
        public function process_payment($orderId)
        {
            $order = wc_get_order($orderId);

            try {
                if (wc_centrobill_is_order_contains_subscription($order) && $order->get_total() == 0) {
                    $order->add_order_note('This subscription has a free trial');
                }

                if (wc_centrobill_is_payment_page_enabled()) {
                    $result = wc_centrobill()->api->getPaymentPage($orderId);
                } else {
                    $result = $this->gateway_process_payment($orderId);
                    $this->clear_cached_data($order);
                }

                return [
                    'result' => 'success',
                    'redirect' => $this->receive_order_redirect_url($order, $result),
                ];
            } catch (Exception $e) {
                wc_add_notice(
                    sprintf(esc_html__('%s payment failed. Payment gateway error.', 'woocommerce-gateway-centrobill'), $this->get_method_title()),
                    'error'
                );

                $note = sprintf(esc_html__('%s payment failed. %s', 'woocommerce-gateway-centrobill'), $this->get_method_title(), $e->getMessage());
                wc_centrobill()->logger->error($note);

                if (!$order->has_status('failed')) {
                    $order->update_status('failed', $note);
                } else {
                    $order->add_order_note($note);
                }

                return [
                    'result' => 'failure',
                    'redirect' => ''
                ];
            }
        }

        /**
         * @param float $amount
         * @param WC_Order $order
         *
         * @throws WC_Centrobill_Exception
         */
        public function process_subscription_payment($amount, WC_Order $order)
        {
            wc_centrobill()->logger->info(__METHOD__, ['renewal order_id' => $order->get_id()]);

            try {
                $response = wc_centrobill()->api->processRecurringPayment($amount, $order);
            } catch (Exception $e) {
                wc_centrobill()->logger->error(__METHOD__, $e->getMessage());

                $response = new WP_Error($e->getCode(), $e->getMessage());
            }

            $this->process_payment_response($order, $response);
        }

        /**
         * @param WC_Order $order
         * @param $response
         *
         * @throws WC_Centrobill_Exception
         */
        protected function process_payment_response(WC_Order $order, $response)
        {
            wc_centrobill()->logger->info(__METHOD__, ['renewal order_id' => $order->get_id()]);

            if (!$order instanceof WC_Order) {
                throw new WC_Centrobill_Exception('Invalid WooCommerce order.');
            }

            if (is_wp_error($response)) {
                $order->add_order_note('Payment transaction failed.');
                $order->update_status(WC_STATUS_FAILED, $response->get_error_message());

                return;
            }

            if (wc_centrobill_is_subscription_payment_successful($response)) {
                $order->payment_complete($response['transactionId']);
            } else {
                $order->update_status(WC_STATUS_FAILED, wc_centrobill_retrieve_response_text($response));
            }
        }

        /**
         * @return void
         */
        public function payment_scripts()
        {
            if (
                !is_checkout()
                || is_order_received_page()
                || wc_centrobill_is_payment_page_enabled()
                || $this->enabled === SETTING_VALUE_NO
            ) {
                return;
            }

            wp_register_script(
                'centrobill-payment-form',
                wc_centrobill_assets_url('js/centrobill-payment-form.js'),
                ['jquery', 'jquery-payment'],
                WC_CENTROBILL_VERSION,
                true
            );

            wp_localize_script('centrobill-payment-form', 'centrobill_params', []);
        }

        /**
         * @param WC_Order $order
         * @param mixed $data
         *
         * @return bool
         */
        protected function receive_order_redirect_url(WC_Order $order, $data)
        {
            $this->update_order_status($order, $data);

            if (!empty($data['url'])) {
                return $data['url'];
            }

            if (
                !empty($data['payment']['url']) &&
                (!empty($data['payment']['action']) && $data['payment']['action'] === ACTION_REDIRECT)
            ) {
                return $data['payment']['url'];
            }

            return $this->get_return_url($order);
        }

        /**
         * @param WC_Order $order
         * @param $data
         */
        private function update_order_status(WC_Order $order, $data)
        {
            if (
                (!empty($data['payment']['code']) && $data['payment']['code'] != RESULT_CODE_SUCCESS)
                || (!empty($data['payment']['status']) && $data['payment']['status'] === STATUS_FAIL)
            ) {
                $message = !empty($data['payment']['description']) ? $data['payment']['description'] : '';
                $order->update_status(WC_STATUS_FAILED, __(sprintf('Payment failed. %s', $message), 'woocommerce-gateway-centrobill'));
            }
        }

        /**
         * @param WC_Order $order
         */
        private function clear_cached_data(WC_Order $order)
        {
            wc_centrobill_remove_session_keys([SESSION_KEY_EMAIL]);
            delete_transient(WC_Centrobill::getPMTransientKey($order->get_billing_email()));
        }
    }
}

WC_Centrobill_Gateway_Abstract::init();
