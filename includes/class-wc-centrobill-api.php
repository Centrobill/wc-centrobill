<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Api')) {
    /**
     * Class WC_Centrobill_Api
     */
    class WC_Centrobill_Api
    {
        const CENTROBILL_API_URL = 'https://api.centrobill.com';
        const CENTROBILL_EPAYMENT_URL = 'https://epayment.centrobill.com/epayment/lib/paypage_api/pay.php';

        const HTTP_STATUS_OK = 200;
        const HTTP_STATUS_CREATED = 201;

        /**
         * @var string
         */
        private $authKey;

        /**
         * @var int
         */
        private $siteId;

        /**
         * @var array
         */
        private $settings;

        /**
         * @param array $settings
         */
        public function __construct(array $settings)
        {
            $this->settings = $settings;

            list($this->authKey, $this->siteId) = explode(':', $settings['token']);
        }

        /**
         * @param array $data
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function getToken(array $data)
        {
            $response = $this->makeRequest($this->prepareTokenRequestParams($data), 'tokenize', true);
            wc_centrobill()->logger->info('[API] Get token response', $response);

            if (empty($response['token'])) {
                throw new WC_Centrobill_Exception('Token is missing');
            }

            return $response;
        }

        /**
         * @param string $token
         * @param WC_Order $order
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function pay($token, WC_Order $order)
        {
            $response = $this->makeRequest($this->preparePaymentRequestParams($token, $order), 'payment');
            wc_centrobill()->logger->info('[API] Payment response', $response);

            return $response;
        }

        /**
         * @param float $amount
         * @param WC_Order $order
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function processRecurringPayment($amount, WC_Order $order)
        {
            $response = $this->makeRequest($this->prepareRecurringPaymentRequestParams($amount, $order));
            wc_centrobill()->logger->info('[API] Recurring payment response', $response);

            return $response;
        }

        /**
         * @param WC_Order $order
         *
         * @return string
         * @throws WC_Centrobill_Exception
         */
        public function createConsumerIfNotExists(WC_Order $order)
        {
            $response = $this->makeRequest([
                'method' => 'get_ustas_or_create',
                'authentication_key' => $this->authKey,
                'fmt' => 'json',
                'username' => $order->get_billing_email(),
                'external_user_id' => $this->getExternalUserId($order),
            ]);
            wc_centrobill()->logger->info('[API] Create consumer response', $response);

            return $response['ustas'];
        }

        /**
         * @param WC_Order $order
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function getPaymentMethods(WC_Order $order)
        {
            $response = $this->makeRequest([
                'method' => 'get',
                'authentication_key' => $this->authKey,
                'fmt' => 'json',
                'ustas' => $this->getConsumer($order),
                'sku_name' => $this->getTechSku(),
                'customer_remote_addr' => wc_centrobill_get_ip_address(),
                'customer_country' => $order->get_billing_country(),
                'load_cc_mid_info' => true,
            ]);

            $result = [];
            if (!empty($response['short_payment_methods'])) {
                foreach ($response['short_payment_methods'] as $paymentMethod => $data) {
                    $result[] = $paymentMethod;
                }
            }

            wc_centrobill()->logger->info('[API] Payment methods response', $response);

            return $result;
        }

        /**
         * @param array $params
         * @param null|string $endpoint
         * @param bool $xhr
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        private function makeRequest(array $params, $endpoint = null, $xhr = false)
        {
            if (!is_null($endpoint)) {
                $url = sprintf('%s/%s', self::CENTROBILL_API_URL, untrailingslashit($endpoint));
                $headers = [
                    'Authorization' => $this->authKey,
                    'Content-Type' => 'application/json',
                ];
                $data = [
                    'headers' => !$xhr ? $headers : array_merge($headers, ['X-Requested-With' => 'XMLHttpRequest']),
                    'body' => json_encode($params),
                ];
            } else {
                $url = self::CENTROBILL_EPAYMENT_URL;
                $data = [
                    'headers' => ['Content-Type: application/json'],
                    'body' => $params
                ];
            }

            wc_centrobill()->logger->info('[API] Request', ['url' => $url, 'body' => $params]);
            $response = wp_remote_post($url, $data);

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if (is_wp_error($response) || !wc_centrobill_is_valid_json($body)) {
                wc_centrobill()->logger->error('[API] Payment gateway error', [
                    'message' => !empty($body) ? $body : wp_remote_retrieve_response_message($response)
                ]);
                throw new WC_Centrobill_Exception(
                    sprintf('Payment gateway error. %s', wp_remote_retrieve_response_message($response))
                );
            }

            $response = json_decode($body, true);

            if (empty($endpoint) && !empty($response['result']) && $response['result'] !== 'OK') {
                throw new WC_Centrobill_Exception(
                    sprintf('Payment gateway error. %s', wc_centrobill_retrieve_response_text($response))
                );
            }

            if (!empty($endpoint) && !in_array($code, [self::HTTP_STATUS_OK, self::HTTP_STATUS_CREATED], true)) {
                wc_centrobill()->logger->error('[API] Payment gateway error', [
                    'response_code' => $code,
                    'message' => $response['message'],
                    'errors' => $response['errors'],
                ]);
                throw new WC_Centrobill_Exception(sprintf('Payment gateway error. %s', $response['message']));
            }

            return $response;
        }

        /**
         * @param array $data
         *
         * @return array
         */
        private function prepareTokenRequestParams(array $data)
        {
            if (!empty($data['centrobill_cardholder_name'])) {
                $cardHolder = $data['centrobill_cardholder_name'];
            } else {
                $cardHolder = sprintf(
                    '%s %s',
                    !empty($data['billing_first_name']) ? $data['billing_first_name'] : '',
                    !empty($data['billing_last_name']) ? $data['billing_last_name'] : ''
                );
            }

            $expirationMonth = $expirationYear = '';
            if (!empty($data['centrobill_expiration_date'])) {
                list($expirationMonth, $expirationYear) = explode('/', $data['centrobill_expiration_date']);
            }

            return [
                'cardHolder' => $cardHolder,
                'expirationMonth' => trim($expirationMonth),
                'expirationYear' => trim($expirationYear),
                'number' => str_replace(' ', '', !empty($data['centrobill_card_number']) ? $data['centrobill_card_number'] : ''),
                'cvv' => !empty($data['centrobill_cvv']) ? $data['centrobill_cvv'] : '',
                'zip' => !empty($data['billing_postcode']) ? $data['billing_postcode'] : '',
            ];
        }

        /**
         * @param string $token
         * @param WC_Order $order
         *
         * @return array
         */
        private function preparePaymentRequestParams($token, WC_Order $order)
        {
            $hasSubscriptionTrialPeriod = false;
            $amount = $order->get_total();

            if ($subscription = $this->getSubscription($order)) {
                if ($subscription->get_trial_period() && ($subscription->get_time('trial_end') > time())) {
                    $hasSubscriptionTrialPeriod = true;
                    $amount = $subscription->get_total();
                }
            }

            return [
                'paymentSource' => [
                    'type' => 'token',
                    'value' => $token,
                ],
                'sku' => [
                    'title' => join(', ', $this->getProductNames($order)),
                    'siteId' => $this->siteId,
                    'price' => [
                        [
                            'offset' => '0d',
                            'amount' => $amount,
                            'currency' => $order->get_currency(),
                            'repeat' => false,
                        ],
                    ],
                ],
                'consumer' => [
                    'firstName' => $order->get_billing_first_name(),
                    'lastName' => $order->get_billing_last_name(),
                    'externalId' => $this->getExternalUserId($order),
                    'email' => $order->get_billing_email(),
                    'ip' => wc_centrobill_get_ip_address(),
                ],
                'url' => [
                    'ipnUrl' => wc_centrobill_get_ipn_url($this->settings),
                    'redirectUrl' => $order->get_checkout_order_received_url(),
                ],
                'metadata' => [
                    'wp_order_id' => $order->get_id(),
                    'trial' => (int)$hasSubscriptionTrialPeriod,
                    'invoice_external_id' => ($subscription instanceof WC_Subscription) ?
                        (string)$subscription->get_id() : '',
                ],
            ];
        }

        /**
         * @param float $amount
         * @param WC_Order $order
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        private function prepareRecurringPaymentRequestParams($amount, WC_Order $order)
        {
            if (!$subscription = $this->getSubscription($order)) {
                throw new WC_Centrobill_Exception('Subscription failed');
            }

            $isAuthOrder = $order->get_meta('_subscription_renewal') &&
                ($subscription->get_time('trial_end') - 3600 > time());

            $request = [
                'method' => $isAuthOrder ? 'quick_settle' : 'quick_sale',
                'authentication_key' => $this->authKey,
                'fmt' => 'json',
                'ustas' => $consumer = $this->getConsumer($subscription->get_parent_id()),
                'scode' => $this->calculateScode($consumer),
                'sku' => [
                    [
                        'title' => implode(', ', $this->getProductNames($order)),
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
                ],
                'is_vat_included' => 0,
            ];

            if ($isAuthOrder) {
                $initialOrder = wc_get_order($subscription->get_parent_id());
                $request['auth_transaction_id'] = $initialOrder->get_meta(WC_Centrobill_Constants::META_DATA_CB_TRANSACTION_ID);
            }

            return $request;
        }

        /**
         * @param WC_Order|int $order
         *
         * @return string
         * @throws WC_Centrobill_Exception
         */
        private function getConsumer($order)
        {
            if (!$order instanceof WC_Order) {
                $order = wc_get_order($order);
            }

            if ($ustas = $order->get_meta(WC_Centrobill_Constants::META_DATA_CB_USER)) {
                return $ustas;
            }

            return $this->createConsumerIfNotExists($order);
        }

        /**
         * @param WC_Order $order
         *
         * @return string
         */
        private function getExternalUserId(WC_Order $order)
        {
            return !empty($order->get_customer_id()) ?
                'wp__' . $order->get_customer_id() : $order->get_billing_email();
        }

        /**
         * @param WC_Order $order
         *
         * @return array
         */
        private function getProductNames(WC_Order $order)
        {
            $productNames = [];
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $productNames[] = $product->get_name();
            }

            return $productNames;
        }

        /**
         * @param int $consumer
         *
         * @return string
         */
        private function calculateScode($consumer)
        {
            return md5($consumer . $this->authKey);
        }

        /**
         * @return string
         */
        private function getTechSku()
        {
            return 'TECH_' . $this->siteId;
        }

        /**
         * @param WC_Order $order
         *
         * @return WC_Subscription|null
         */
        private function getSubscription(WC_Order $order)
        {
            if (!WC_Centrobill_Subscription::isWCSubscriptionsPluginActive()) {
                return null;
            }

            $subscriptions = wcs_get_subscriptions_for_order($order, ['order_type' => ['parent', 'renewal', 'switch']]);
            if ($subscription = end($subscriptions)) {
                return $subscription;
            }

            return null;
        }
    }
}
