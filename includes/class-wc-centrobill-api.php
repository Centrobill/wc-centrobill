<?php
defined('ABSPATH') or exit();

class WC_Centrobill_Api
{
    const CENTROBILL_API_URL = 'https://epayment.centrobill.com/epayment/lib/paypage_api/pay.php';

    /**
     * @var string
     */
    protected $authKey;
    /**
     * @var integer
     */
    protected $siteId;

    /**
     * WC_Centrobill_Api constructor.
     *
     * @param string $auth_key
     * @param integer $site_id
     */
    public function __construct($auth_key, $site_id)
    {
        $this->authKey = $auth_key;
        $this->siteId = $site_id;
    }

    /**
     * @param WC_Order $order
     *
     * @return bool|string
     * @throws Exception
     */
    public function getPaymentUrl(WC_Order $order)
    {
        $result = $this->makeRequest($this->preparePaymentUrlRequestParams($order));
        if($result['result'] == 'OK') {
            return $result['url'];
        }

        throw new Exception('Payment gateway error: '.@$result['response_text']);
    }

    /**
     * @param float $amount
     * @param WC_Order $order
     *
     * @return mixed
     * @throws Exception
     */
    public function processRecurringPayment($amount, WC_Order $order)
    {
        $result = $this->makeRequest($this->prepareRecurringPaymentRequestParams($amount, $order));
        if ($result['result'] == 'OK') {
            return $result['transaction_id'];
        }

        throw new Exception('Payment gateway error: '.@$result['response_text']);
    }

    /**
     * @param WC_Order $order
     *
     * @return string
     * @throws Exception
     */
    public function createUstasIfNotExists(WC_Order $order)
    {
        $result = $this->makeRequest([
            'method' => 'get_ustas_or_create',
            'authentication_key' => $this->authKey,
            'fmt' => 'json',
            'username' => $order->get_billing_email(),
            'external_user_id' => $this->getExternalUserId($order),
        ]);

        if ($result['result'] == 'OK') {
            return $result['ustas'];
        }

        throw new Exception('Payment gateway error: '.@$result['response_text']);
    }

    /**
     * @param array $params
     *
     * @return bool|string
     * @throws Exception
     */
    protected function makeRequest(array $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_URL, self::CENTROBILL_API_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $output = curl_exec($ch);

        if ($this->isValidJson($output)) {
            return json_decode($output, true);
        }

        throw new Exception('Payment gateway error');
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public function isValidJson($string)
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    protected function preparePaymentUrlRequestParams(WC_Order $order)
    {
        $hasSubscriptionTrialPeriod = false;
        $price = $order->get_total();

        $subscription = $this->getSubscription($order);
        if ($subscription->get_trial_period() && ($subscription->get_time('trial_end') > time())) {
            $hasSubscriptionTrialPeriod = true;
            $price = $subscription->get_total();
        }

        $product_ids = [];
        $product_descriptions = [];
        foreach($order->get_items() as $item) {
            /**
             * @var WC_Product $product
             */
            $product                = $item->get_product();
            $product_ids[]          = $product->get_id();
            $product_descriptions[] = $product->get_name();
        }

        return [
            'method'              => 'get-paypage-url',
            'authentication_key'  => $this->authKey,
            'site_id'             => $this->siteId,
            'fmt'                 => 'json',
            'product_external_id' => implode(',', $product_ids),
            'price'               => $price,
            'currency'            => $order->get_currency(),
            'description'         => implode(', ', $product_descriptions),
            'email'               => $order->get_billing_email(),
            'external_user_id'    => $this->getExternalUserId($order),
            'custom_params'       => [
                'wp_order_id' => $order->get_id(),
                'trial' => (int) $hasSubscriptionTrialPeriod,
            ]
        ];
    }

    /**
     * @param float $amount
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    protected function prepareRecurringPaymentRequestParams($amount, WC_Order $order)
    {
        $subscription = $this->getSubscription($order);
        $isAuthOrder = ($order->get_meta('_subscription_renewal') && ($subscription->get_time('trial_end') + 3600 > time())) ?: false;

        $productNames = [];
        foreach ($order->get_items() as $item) {
            /** @var WC_Product $product */
            $product = $item->get_product();
            $productNames[] = $product->get_name();
        }

        $request = [
            'method' => $isAuthOrder ? 'quick_settle' : 'quick_sale',
            'authentication_key' => $this->authKey,
            'fmt' => 'json',
            'scode' => $this->calculateScodeByUstas($ustas = $this->getUstas($subscription->get_parent_id())),
            'ustas' => $ustas,
            'sku' => [
                [
                    'title' => implode(', ', $productNames),
                    'site_id' => $this->siteId,
                    'currency' => $order->get_currency(),
                    'price' => [
                        [
                            'offset' => '0d',
                            'price' => $amount,
                            'repeat' => false,
                        ]
                    ]
                ]
            ]
        ];

        if ($isAuthOrder) {
            $initialOrder = wc_get_order($subscription->get_parent_id());
            $request['auth_transaction_id'] = $initialOrder->get_meta(WC_Centrobill_Webhook_Handler::META_DATA_CB_TRANSACTION_ID);
        }

        return $request;
    }

    /**
     * @param int $orderId
     *
     * @return mixed
     * @throws Exception
     */
    public function getUstas($orderId)
    {
        $order = wc_get_order($orderId);
        if ($ustas = $order->get_meta(WC_Centrobill_Webhook_Handler::META_DATA_CB_USER)) {
            return $ustas;
        }

        return $this->createUstasIfNotExists($order);
    }

    /**
     * @param WC_Order $order
     *
     * @return int|string
     */
    private function getExternalUserId(WC_Order $order)
    {
        return !empty($order->get_customer_id())
            ? $order->get_customer_id() : $order->get_billing_email();
    }

    /**
     * @param int $ustas
     *
     * @return string
     */
    private function calculateScodeByUstas($ustas)
    {
        return md5($ustas.$this->authKey);
    }

    /**
     * @param WC_Order $order
     *
     * @return WC_Subscription
     * @throws Exception
     */
    private function getSubscription(WC_Order $order)
    {
        $orderTypes = ['parent', 'renewal', 'switch'];
        if (!$subscription = end(wcs_get_subscriptions_for_order($order, ['order_type' => $orderTypes]))) {
            throw new Exception('Subscription failed');
        }

        return $subscription;
    }
}
