<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Api')) {
    /**
     * Class WC_Centrobill_Api
     */
    class WC_Centrobill_Api
    {
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
        }

        /**
         * @param array $data
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function getToken(array $data)
        {
            $response = $this->request($this->prepareTokenRequestParams($data), API_ENDPOINT_TOKENIZE, true);
            wc_centrobill()->logger->info('[API] Get token response', $response);

            if (empty($response['token'])) {
                throw new WC_Centrobill_Exception('Token is missing.');
            }

            return $response;
        }

        /**
         * @param array $paymentSource
         * @param int $orderId
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function pay(array $paymentSource, $orderId)
        {
            $response = $this->request(
                $this->preparePaymentRequestParams($paymentSource, $orderId),
                API_ENDPOINT_PAYMENT
            );
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
            $response = $this->request(
                $this->prepareRecurringPaymentRequestParams($amount, $order),
                API_ENDPOINT_PAYMENT
            );
            wc_centrobill()->logger->info('[API] Recurring payment response', $response);

            return $response;
        }

        /**
         * @param int|WC_Order $orderId
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function getPaymentPage($orderId)
        {
            $response = $this->request(
                $this->preparePaymentPageRequestParams($orderId),
                API_ENDPOINT_PAYMENT_PAGE
            );
            wc_centrobill()->logger->info('[API] Get payment page', $response);

            if (empty($response['url'])) {
                throw new WC_Centrobill_Exception('Payment page URL is missing.');
            }

            return $response;
        }

        /**
         * @param string $email
         *
         * @return string
         * @throws WC_Centrobill_Exception
         */
        public function createConsumerIfNotExists($email)
        {
            $response = $this->request([
                'method' => METHOD_GET_CREATE_USER,
                'username' => $email,
                'external_user_id' => $this->getExternalUserId($email),
            ]);
            wc_centrobill()->logger->info('[API] Create consumer response', $response);

            return $response['ustas'];
        }

        /**
         * @param string $email
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        public function getPaymentMethods($email)
        {
            $response = $this->request([
                'method' => METHOD_GET_PM,
                'ustas' => $this->getConsumer(null, $email),
                'sku_name' => $this->getTechSku(),
                'customer_remote_addr' => wc_centrobill_get_ip_address(),
                'load_cc_mid_info' => true,
            ]);

            wc_centrobill()->logger->info('[API] Payment methods response', $response);

            $result = [];
            if (!empty($response['short_payment_methods'])) {
                $result = array_keys($response['short_payment_methods']);
            }

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
        private function request(array $params, $endpoint = null, $xhr = false)
        {
            if (!is_null($endpoint)) {
                $url = sprintf('%s/%s', API_URL, untrailingslashit($endpoint));
                $headers = [
                    'Authorization' => $this->getAuthKey(),
                    'Content-Type' => 'application/json',
                ];
                $data = [
                    'headers' => !$xhr ? $headers : array_merge($headers, ['X-Requested-With' => 'XMLHttpRequest']),
                    'body' => json_encode($params),
                ];
            } else {
                $url = EPAYMENT_URL;
                $data = [
                    'headers' => ['Content-Type: application/json'],
                    'body' => array_merge(
                        [
                            'authentication_key' => $this->getAuthKey(),
                            'fmt' => 'json',
                        ],
                        $params
                    ),
                ];
            }
            $data['timeout'] = 60;

            wc_centrobill()->logger->info('[API] Request', ['url' => $url, 'body' => $data['body']]);
            $response = wp_remote_post($url, $data);

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if (is_wp_error($response) || !wc_centrobill_is_valid_json($body)) {
                $message = ($response instanceof WP_Error)
                    ? $response->get_error_message() : wp_remote_retrieve_response_message($response);
                wc_centrobill()->logger->error('[API] Payment gateway error', ['message' => $message]);

                throw new WC_Centrobill_Exception(sprintf('Payment gateway error. %s', $message));
            }

            $response = json_decode($body, true);

            if (empty($endpoint) && !empty($response['result']) && $response['result'] !== 'OK') {
                throw new WC_Centrobill_Exception(
                    sprintf('Payment gateway error. %s', wc_centrobill_retrieve_response_text($response))
                );
            }

            if (!empty($endpoint) && !in_array($code, [HTTP_STATUS_OK, HTTP_STATUS_CREATED], true)) {
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
         * @param array $paymentSource
         * @param int|WC_Order $orderId
         * @param float|null $renewalAmount
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        private function preparePaymentRequestParams(array $paymentSource, $orderId, $renewalAmount = null)
        {
            $order = wc_get_order($orderId);
            $hasSubscriptionTrialPeriod = false;

            $amount = $order->get_total();
            if ($subscription = $this->getSubscription($order)) {
                if ($subscription->get_trial_period() && ($subscription->get_time('trial_end') > time())) {
                    $hasSubscriptionTrialPeriod = true;
                    $amount = $subscription->get_total();
                }
            }

            $request = [
                'paymentSource' => $paymentSource,
                'sku' => [
                    'title' => mb_substr(join(', ', $this->getProductNames($order)), 0, 64),
                    'siteId' => $this->getSiteId(),
                    'price' => [
                        [
                            'offset' => $hasSubscriptionTrialPeriod ? $this->getOffsetParam($order) : '0d',
                            'amount' => ($renewalAmount !== null) ? floatval($renewalAmount) : floatval($amount),
                            'currency' => $order->get_currency(),
                            'repeat' => false,
                        ],
                    ],
                ],
                'consumer' => $this->prepareConsumerData($order),
                'url' => [
                    'ipnUrl' => wc_centrobill_get_ipn_url($this->settings),
                    'redirectUrl' => $order->get_checkout_order_received_url(),
                ],
                'metadata' => [
                    'version' => WC_CENTROBILL_VERSION,
                    'wp_order_id' => $order->get_id(),
                    'trial' => (int)$hasSubscriptionTrialPeriod,
                    'invoice_external_id' => ($subscription instanceof WC_Subscription) ?
                        (string)$subscription->get_id() : '',
                ],
            ];

            if ($renewalAmount) {
                $request['metadata']['is_vat_included'] = 0;
            }

            return $request;
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

            return $this->preparePaymentRequestParams(
                [
                    'type' => PAYMENT_TYPE_CONSUMER,
                    'value' => $this->getConsumer($subscription->get_parent_id()),
                ],
                $order,
                $amount
            );
        }

        /**
         * @param int|WC_Order $orderId
         *
         * @return array
         * @throws WC_Centrobill_Exception
         */
        private function preparePaymentPageRequestParams($orderId)
        {
            $order = wc_get_order($orderId);
            $amount = $order->get_total();
            $hasSubscriptionTrialPeriod = false;

            if ($subscription = $this->getSubscription($order)) {
                if ($subscription->get_trial_period() && ($subscription->get_time('trial_end') > time())) {
                    $hasSubscriptionTrialPeriod = true;
                    $amount = $subscription->get_total();
                }
            }

            return [
                'sku' => [
                    'title' => mb_substr(join(', ', $this->getProductNames($order)), 0, 64),
                    'siteId' => $this->getSiteId(),
                    'price' => [
                        [
                            'offset' => $hasSubscriptionTrialPeriod ? $this->getOffsetParam($order) : '0d',
                            'amount' => floatval($amount),
                            'currency' => $order->get_currency(),
                            'repeat' => false,
                        ],
                    ],
                    'url' => [
                        'ipnUrl' => wc_centrobill_get_ipn_url($this->settings),
                        'redirectUrl' => $order->get_checkout_order_received_url(),
                    ],
                ],
                'consumer' => $this->prepareConsumerData($order),
                'metadata' => [
                    'version' => WC_CENTROBILL_VERSION,
                    'wp_order_id' => $order->get_id(),
                    'trial' => (int)$hasSubscriptionTrialPeriod,
                    'invoice_external_id' => ($subscription instanceof WC_Subscription) ?
                        (string)$subscription->get_id() : '',
                ],
            ];
        }

        /**
         * @param WC_Order $order
         *
         * @return string
         */
        private function getOffsetParam(WC_Order $order)
        {
            $offset = 1;
            foreach ($order->get_items() as $item) {
                $offset = WC_Subscriptions_Product::get_trial_length($item->get_product_id());
            }

            return sprintf('%ud', $offset);
        }

        /**
         * @param WC_Order $order
         *
         * @return array
         */
        private function prepareConsumerData(WC_Order $order)
        {
            $data = [
                'email' => $order->get_billing_email(),
                'firstName' => $order->get_billing_first_name(),
                'lastName' => $order->get_billing_last_name(),
                'externalId' => $this->getExternalUserId($order->get_billing_email()),
                'ip' => $order->get_customer_ip_address() ?: wc_centrobill_get_ip_address(),
                'browserLanguage' => wc_centrobill_get_browser_language(),
            ];

            if (!empty($userAgent = wc_centrobill_get_useragent())) {
                $data['userAgent'] = $userAgent;
            }

            if (!empty($browserAcceptHeader = wc_centrobill_get_browser_accept_header())) {
                $data['browserAcceptHeader'] = $browserAcceptHeader;
            }

            if (!empty($phone = $order->get_billing_phone())) {
                $data['phone'] = $phone;
            }

            if (!empty($country = wc_centrobill_country_convert_to_iso3($order->get_billing_country()))) {
                $data['country'] = $country;
            }

            if (!empty($state = $order->get_billing_state())) {
                $data['state'] = $state;
            }

            if (!empty($city = $order->get_billing_city())) {
                $data['city'] = $city;
            }

            if (!empty($zip = $order->get_billing_postcode())) {
                $data['zip'] = $zip;
            }

            if (!empty($javaEnabled = wc_centrobill_retrieve_post_param('centrobill_browser_java_enabled'))) {
                $data['browserJavaEnabled'] = (bool)$javaEnabled;
            }

            if (!empty($colorDepth = wc_centrobill_retrieve_post_param('centrobill_browser_color_depth'))) {
                $data['browserColorDepth'] = $colorDepth;
            }

            if (!empty($screenHeight = wc_centrobill_retrieve_post_param('centrobill_browser_screen_height'))) {
                $data['browserScreenHeight'] = $screenHeight;
            }

            if (!empty($screenWidth = wc_centrobill_retrieve_post_param('centrobill_browser_screen_width'))) {
                $data['browserScreenWidth'] = $screenWidth;
            }

            if (!empty($timezone = wc_centrobill_retrieve_post_param('centrobill_browser_timezone'))) {
                $data['browserTimezone'] = $timezone;
            }

            return $data;
        }

        /**
         * @param int $orderId
         * @param string|null $email
         *
         * @return string
         * @throws WC_Centrobill_Exception
         */
        private function getConsumer($orderId, $email = null)
        {
            $currentUser = wp_get_current_user();

            if ($currentUser->exists() && !$orderId) {
                if ($user = get_user_meta(get_current_user_id(), META_DATA_CB_USER, true)) {
                    return $user;
                }
            }

            if ($email === null) {
                $order = wc_get_order($orderId);
                if ($order && ($user = $order->get_meta(META_DATA_CB_USER))) {
                    return $user;
                }

                if ($order instanceof WC_Order) {
                    $email = $order->get_billing_email();
                } elseif ($currentUser->exists()) {
                    $email = $currentUser->user_email;
                } else {
                    throw new WC_Centrobill_Exception('Customer email is missing.');
                }
            }

            return $this->createConsumerIfNotExists($email);
        }

        /**
         * @param string $email
         *
         * @return string
         */
        private function getExternalUserId($email)
        {
            return !empty(get_current_user_id()) ? sprintf('wp__%s', get_current_user_id()) : $email;
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
         * @return string
         * @throws WC_Centrobill_Exception
         */
        private function getTechSku()
        {
            return 'TECH_' . $this->getSiteId();
        }

        /**
         * @param WC_Order $order
         *
         * @return WC_Subscription|null
         */
        private function getSubscription(WC_Order $order)
        {
            if (!wc_centrobill_is_subscriptions_plugin_active()) {
                return null;
            }

            $subscriptions = wcs_get_subscriptions_for_order($order, ['order_type' => ['parent', 'renewal', 'switch']]);
            if ($subscription = end($subscriptions)) {
                return $subscription;
            }

            return null;
        }

        /**
         * @return string
         * @throws WC_Centrobill_Exception
         */
        private function getAuthKey()
        {
            if (empty($this->settings[SETTING_KEY_AUTH_KEY])) {
                throw new WC_Centrobill_Exception('Authentication key is missing.');
            }

            return $this->settings[SETTING_KEY_AUTH_KEY];
        }

        /**
         * @return int
         * @throws WC_Centrobill_Exception
         */
        private function getSiteId()
        {
            if (empty($this->settings[SETTING_KEY_SITE_ID])) {
                throw new WC_Centrobill_Exception('Site ID is missing.');
            }

            return $this->settings[SETTING_KEY_SITE_ID];
        }
    }
}
