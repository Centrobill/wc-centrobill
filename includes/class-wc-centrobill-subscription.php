<?php

defined('ABSPATH') or exit();

/**
 * Class WC_Centrobill_Subscription
 *
 * @class   WC_Centrobill_Subscription
 * @version 1.0.0
 * @author 	CentroBill
 */
class WC_Centrobill_Subscription extends WC_Centrobill_Gateway_Plugin
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
    protected function addSubscriptionSupport()
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
        add_action(self::WC_ACTION_SCHEDULED_SUBSCRIPTION_PAYMENT . "_{$this->id}", [
            $this, 'processScheduledSubscriptionPayment'
        ], 10, 2);
    }

    /**
     * Handles recurring transactions
     *
     * @param $amount
     * @param $renewalOrder
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
     * @return mixed|WP_Error
     */
    protected function processSubscriptionPayment($amount, $order)
    {
        try {
            return (new WC_Centrobill_Api($this->authKey, $this->siteId))
                ->processRecurringPayment($amount, $order);
        } catch (Exception $e) {
            return new \WP_Error(
                $e->getCode(),
                $e->getMessage()
            );
        }
    }

    /**
     * @param WC_Order $renewalOrder
     * @param stdClass|WP_Error $response
     *
     * @throws Exception
     * @return void
     */
    protected function updateOrderStatus($renewalOrder, $response)
    {
        if (!$renewalOrder instanceof WC_Order) {
            throw new \Exception('Invalid WooCommerce Order!');
        }

        if (is_wp_error($response)) {
            $renewalOrder->add_order_note('Payment transaction failed');
            $renewalOrder->update_status(WC_Centrobill_Webhook_Handler::STATUS_FAILED, $response->get_error_message());

            return;
        }

        $renewalOrder->payment_complete($response);
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
            $order->payment_complete();
            $order->add_order_note('This subscription has a free trial');

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        } else {
            return parent::process_payment($orderId);
        }
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
     * {@inheritdoc}
     */
    public function init_form_fields()
    {
        parent::init_form_fields();

        $this->form_fields += [
            'subscription_settings' => [
                'type' => 'title',
                'title' => 'Subscription Settings',
                'description' => 'Additional settings for the recurring payments',
            ],
            self::SETTING_KEY_ALLOW_SUBSCRIPTIONS => [
                'type' => 'checkbox',
                'title' => 'Enable/Disable',
                'label' => 'Enable/Disable Subscription Payments',
                'default' => self::SETTING_VALUE_YES,
            ]
        ];
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
    protected function isSubscriptionEnabled()
    {
        return $this->get_option(self::SETTING_KEY_ALLOW_SUBSCRIPTIONS) == self::SETTING_VALUE_YES;
    }
}
