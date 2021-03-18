<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Subscription')) {
    /**
     * Class WC_Centrobill_Subscription
     *
     * @class  WC_Centrobill_Subscription
     * @author CentroBill
     */
    class WC_Centrobill_Subscription extends WC_Centrobill_Gateway
    {
        const SETTING_KEY_ALLOW_SUBSCRIPTIONS = 'allow_subscriptions';
        const SETTING_VALUE_YES = 'yes';

        const FEATURE_SUBSCRIPTIONS = 'subscriptions';
        const FEATURE_SUBSCRIPTION_CANCELLATION = 'subscription_cancellation';
        const FEATURE_SUBSCRIPTION_SUSPENSION = 'subscription_suspension';
        const FEATURE_SUBSCRIPTION_REACTIVATION = 'subscription_reactivation';
        const FEATURE_SUBSCRIPTION_AMOUNT_CHANGES = 'subscription_amount_changes';
        const FEATURE_SUBSCRIPTION_DATE_CHANGES = 'subscription_date_changes';
        const FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE = 'subscription_payment_method_change';

        const WC_ACTION_SCHEDULED_SUBSCRIPTION_PAYMENT = 'woocommerce_scheduled_subscription_payment';

        /**
         * {@inheritdoc}
         */
        public function __construct()
        {
            parent::__construct();

            if ($this->isSubscriptionEnabled()) {
                $this->addSubscriptionSupport();
            }
        }

        /**
         * Supports subscription payments
         *
         * @return void
         */
        private function addSubscriptionSupport()
        {
            $this->supports = array_merge(
                $this->supports,
                [
                    self::FEATURE_SUBSCRIPTIONS,
                    self::FEATURE_SUBSCRIPTION_CANCELLATION,
                    self::FEATURE_SUBSCRIPTION_SUSPENSION,
                    self::FEATURE_SUBSCRIPTION_REACTIVATION,
                    self::FEATURE_SUBSCRIPTION_AMOUNT_CHANGES,
                    self::FEATURE_SUBSCRIPTION_DATE_CHANGES,
                    self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE,
                ]
            );

            // Add handler for recurring sale transactions
            add_action(sprintf('%s_%s', self::WC_ACTION_SCHEDULED_SUBSCRIPTION_PAYMENT, $this->id), [
                $this, 'processScheduledSubscriptionPayment'
            ], 10, 2);
        }

        /**
         * Handles recurring transactions
         *
         * @param float $amount
         * @param WC_Order $renewalOrder
         *
         * @throws Exception
         */
        public function processScheduledSubscriptionPayment($amount, $renewalOrder)
        {
            $this->updateOrderStatus(
                $renewalOrder,
                $this->processSubscriptionPayment($amount, $renewalOrder)
            );
        }

        /**
         * @param int $amount
         * @param WC_Order $order
         *
         * @return array|WP_Error
         */
        private function processSubscriptionPayment($amount, $order)
        {
            try {
                return wc_centrobill()->api->processRecurringPayment($amount, $order);
            } catch (Exception $e) {
                return new WP_Error($e->getCode(), $e->getMessage());
            }
        }

        /**
         * @param WC_Order $renewalOrder
         * @param array|WP_Error $response
         *
         * @return void
         * @throws WC_Centrobill_Exception
         */
        private function updateOrderStatus($renewalOrder, $response)
        {
            if (!$renewalOrder instanceof WC_Order) {
                throw new WC_Centrobill_Exception('Invalid WooCommerce Order!');
            }

            if (is_wp_error($response)) {
                $renewalOrder->add_order_note('Payment transaction failed');
                $renewalOrder->update_status(WC_Centrobill_Constants::WC_STATUS_FAILED, $response->get_error_message());

                return;
            }

            if ($this->isPaymentSuccessful($response)) {
                $renewalOrder->payment_complete($response['transaction_id']);
            } else {
                $renewalOrder->update_status(WC_Centrobill_Constants::WC_STATUS_FAILED, $this->getResponseText($response));
            }
        }

        /**
         * Process a trial subscription order with 0 total
         *
         * @param int $orderId
         * @return array
         */
        public function process_payment($orderId)
        {
            $order = wc_get_order($orderId);
            if ($this->isOrderContainsSubscription($order) && $order->get_total() == 0) {
                $order->add_order_note('This subscription has a free trial');
            }

            return parent::process_payment($orderId);
        }

        /**
         * @param WC_Order $order
         *
         * @return bool
         */
        private function isOrderContainsSubscription(WC_Order $order)
        {
            return function_exists('wcs_order_contains_subscription')
                && (wcs_order_contains_subscription($order) || wcs_order_contains_renewal($order));
        }

        /**
         * Checks if Woocommerce Subscriptions is enabled or not
         *
         * @return string
         */
        public static function isWCSubscriptionsPluginActive()
        {
            return class_exists('WC_Subscriptions') && class_exists('WC_Subscriptions_Order');
        }

        /**
         * Checks if recurring payments are enabled or not
         *
         * @return bool
         */
        private function isSubscriptionEnabled()
        {
            return $this->get_option(self::SETTING_KEY_ALLOW_SUBSCRIPTIONS) === self::SETTING_VALUE_YES;
        }

        /**
         * @param array $response
         *
         * @return bool
         */
        private function isPaymentSuccessful(array $response)
        {
            return array_key_exists('transaction_id', $response) &&
                array_key_exists('status', $response) &&
                in_array(
                    $response['status'],
                    [
                        WC_Centrobill_Constants::STATUS_SUCCESSFUL,
                        WC_Centrobill_Constants::STATUS_SHIPPED
                    ]
                );
        }

        /**
         * @param array $response
         *
         * @return string
         */
        private function getResponseText(array $response)
        {
            $message = '';
            if (!empty($response['response_text'])) {
                $message = $response['response_text'];
            } elseif (!empty($response['error_message'])) {
                $message = $response['error_message'];
            }

            return $message;
        }
    }
}
