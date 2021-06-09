<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Webhook_Handler')) {
    /**
     * Class WC_Centrobill_Webhook_Handler
     */
    class WC_Centrobill_Webhook_Handler
    {
        /**
         * @throws WC_Centrobill_Exception
         */
        public function process()
        {
            if (!$this->isValidIPN()) {
                return;
            }

            if (!empty($_REQUEST['ipn'])) {
                $this->processXml($_REQUEST['ipn']);
            } else {
                $this->processJson(file_get_contents('php://input'));
            }
        }

        /**
         * @return bool
         */
        private function isValidIPN()
        {
            return !empty($_REQUEST['ipn']) || wc_centrobill_is_api_ipn(file_get_contents('php://input'));
        }

        /**
         * @param string $callback
         */
        private function processJson($callback)
        {
            wc_centrobill()->logger->info('[IPN] Callback received', $callback);
            $result = ['result' => RESULT_NOK];

            try {
                $data = json_decode($callback, true);
                if (!empty($data['metadata']['wp_order_id']) && ($order = wc_get_order($data['metadata']['wp_order_id']))) {
                    if ((int)$data['payment']['code'] === RESULT_CODE_SUCCESS && $data['payment']['status'] === STATUS_SUCCESS) {
                        $status = WC_STATUS_COMPLETED;
                        foreach ($order->get_items() as $product) {
                            if (!$product->get_product()->is_downloadable() && !$product->get_product()->is_virtual()) {
                                $status = WC_STATUS_PROCESSING;
                                break;
                            }
                        }
                        $order->update_status($status);
                        wc_empty_cart();
                    } else {
                        $order->update_status(WC_STATUS_FAILED, __(sprintf('Payment failed. %s', $data['payment']['description']), 'woocommerce-gateway-centrobill'));
                    }

                    update_user_meta($order->get_customer_id(), META_DATA_CB_USER, $data['consumer']['id']);
                    $order->update_meta_data(META_DATA_CB_USER, $data['consumer']['id']);
                    $order->update_meta_data(META_DATA_CB_TRANSACTION_ID, $data['payment']['transactionId']);
                    $order->save_meta_data();

                    $result['result'] = RESULT_OK;
                    $result['order_details'] = $order->get_data();
                } else {
                    $result['error'] = IPN_ERROR_EMPTY_ORDER_ID;
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

            wc_centrobill()->logger->info('[IPN] Response', $result);

            http_response_code(200);
            echo json_encode($result);
            exit;
        }

        /**
         * @param string $callback
         *
         * @throws WC_Centrobill_Exception
         */
        private function processXml($callback)
        {
            $settings = get_option('woocommerce_centrobill_settings', []);
            if (empty($authKey = $settings[SETTING_KEY_AUTH_KEY])) {
                throw new WC_Centrobill_Exception('Authentication key is missing.');
            }

            wc_centrobill()->logger->info('[IPN] Callback received', $callback);
            $result = ['result' => RESULT_NOK];

            try {
                $xml = simplexml_load_string(stripslashes($_REQUEST['ipn']));

                $ppgetdata = (string)$xml->transaction->ppGetData;
                $status = (string)$xml->transaction->status;
                $mode = (string)$xml->transaction->mode;
                $cbOrderId = (string)$xml->transaction->orderId;

                $ppgetdataParams = unserialize(base64_decode($ppgetdata));
                $orderId = isset($ppgetdataParams['wp_order_id']) ? $ppgetdataParams['wp_order_id'] : null;

                if (!empty($orderId)) {
                    $order = new WC_Order($orderId);
                    if ($_REQUEST['sign'] === $this->generateSign($cbOrderId, $mode, $status, $authKey)) {
                        $result['order_details'] = $order->get_data();
                        if ($this->isPaymentSuccessful($status, $mode)) {
                            $status = WC_STATUS_COMPLETED;
                            foreach ($order->get_items() as $product) {
                                if (!$product->get_product()->is_downloadable() && !$product->get_product()->is_virtual()) {
                                    $status = WC_STATUS_PROCESSING;
                                    break;
                                }
                            }
                            $order->update_status($status);
                        } elseif ($this->isRefundSuccessful($status, $mode)) {
                            $order->update_status(WC_STATUS_REFUNDED);
                        } elseif ($this->isPaymentFailed($status, $mode)) {
                            $response_text = (string)$xml->transaction->responseText;
                            $order->update_status(WC_STATUS_FAILED, __(sprintf('Payment failed. %s', $response_text), 'woocommerce-gateway-centrobill'));
                        } else {
                            $result['message'] = IPN_MESSAGE_UNPROCESSABLE_STATUS;
                        }
                        $result['result'] = RESULT_OK;

                        update_user_meta($order->get_customer_id(), META_DATA_CB_USER, (string)$xml->transaction->customer->ustas);
                        $order->update_meta_data(META_DATA_CB_USER, (string)$xml->transaction->customer->ustas);
                        $order->update_meta_data(META_DATA_CB_TRANSACTION_ID, (string)$xml->transaction->attributes()->id);
                        $order->save_meta_data();
                    } else {
                        $result['error'] = IPN_ERROR_INVALID_SIGNATURE;
                    }
                } else {
                    $result['error'] = IPN_ERROR_EMPTY_ORDER_ID;
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

            echo json_encode($result);
            exit;
        }

        /**
         * @param $status
         * @param $mode
         *
         * @return bool
         */
        private function isPaymentSuccessful($status, $mode)
        {
            return in_array($status, [STATUS_SUCCESSFUL, STATUS_SHIPPED], true) &&
                in_array($mode, [MODE_AUTH, MODE_SALE, MODE_TEST], true);
        }

        /**
         * @param string $status
         * @param string $mode
         *
         * @return bool
         */
        private function isRefundSuccessful($status, $mode)
        {
            return in_array($status, [STATUS_REFUNDED, STATUS_SUCCESSFUL], true) &&
                in_array($mode, [MODE_REFUND, MODE_VOID], true);
        }

        /**
         * @param string $status
         * @param string $mode
         *
         * @return bool
         */
        private function isPaymentFailed($status, $mode)
        {
            return in_array($status, [STATUS_DECLINED, STATUS_FAILED], true) &&
                in_array($mode, [MODE_AUTH, MODE_SALE, MODE_TEST], true);
        }

        /**
         * @param string $cbOrderId
         * @param string $mode
         * @param string $status
         * @param string $authKey
         *
         * @return string
         */
        private function generateSign($cbOrderId, $mode, $status, $authKey)
        {
            return sha1(implode('_', [$authKey, $cbOrderId, $mode, $status, $authKey]));
        }
    }
}
