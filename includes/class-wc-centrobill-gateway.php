<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Abstract')) {
    /**
     * Class WC_Centrobill_Gateway_Abstract
     */
    abstract class WC_Centrobill_Gateway_Abstract extends WC_Payment_Gateway
    {
        /**
         * @var array
         */
        public static $availableGateways = [];

        public function __construct()
        {
            $this->method_description = __('Take payments online with CentroBill', 'woocommerce-gateway-centrobill');

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Actions
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
            add_action('woocommerce_after_checkout_validation', [$this, 'before_process_payment'], 10, 2);

            // Save options
            if (is_admin()) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            }

            add_filter('wp_enqueue_scripts', [$this, 'payment_scripts']);
            add_filter('wc_centrobill_settings_nav_tabs', [$this, 'admin_navigation_tabs']);

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_centrobill', [new WC_Centrobill_Webhook_Handler(), 'process']);

            if (!$this->is_valid_for_use()) {
                $this->enabled = SETTING_VALUE_NO;
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
         * @return mixed
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
         * Check if the gateway is available for use
         *
         * @return bool
         */
        public function is_available()
        {
            if (
                empty($this->get_option(SETTING_KEY_AUTH_KEY)) &&
                empty($this->get_option(SETTING_KEY_SITE_ID))
            ) {
                return false;
            }

            return parent::is_available();
        }

        /**
         * Check if the gateway is enabled and available in the user's country
         *
         * @access public
         * @return bool
         */
        public function is_valid_for_use()
        {
            $currencies = apply_filters('woocommerce_centrobill_supported_currencies', ['RUB', 'USD', 'EUR', 'UAH']);

            if (!in_array(get_woocommerce_currency(), $currencies, true)) {
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
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }

            wp_enqueue_script('centrobill-payment-form');
            $this->payment_form();
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
         * Process the payment and return the result
         *
         * @param int $orderId
         *
         * @return array
         */
        public function process_payment($orderId)
        {
            wc_centrobill_remove_session_keys([SESSION_KEY_EMAIL, SESSION_KEY_PM]);

            try {
                $order = wc_get_order($orderId);
                $result = $this->gateway_process_payment($orderId);

                return [
                    'result' => 'success',
                    'redirect' => $this->receive_order_redirect_url($order, $result),
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
            if (!is_checkout() || is_order_received_page() || $this->enabled === SETTING_VALUE_NO) {
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
            if (is_user_logged_in()) {
                $this->update_consumer_id($order, $data);
            }

            if (
                !empty($data['payment']['url']) &&
                (!empty($data['payment']['action']) && $data['payment']['action'] === 'redirect')
            ) {
                return $data['payment']['url'];
            }

            return $this->get_return_url($order);
        }

        /**
         * @param WC_Order $order
         * @param array|null $data
         */
        private function update_consumer_id(WC_Order $order, $data)
        {
            if (!empty($data['consumer']['id']) && get_current_user_id()) {
                update_user_meta($order->get_customer_id(), META_DATA_CB_USER, $data['consumer']['id']);
            }
        }
    }
}

WC_Centrobill_Gateway_Abstract::init();
