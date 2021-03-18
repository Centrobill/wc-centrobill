<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Webhook_Handler')) {
    /**
     * Class WC_Centrobill_Webhook_Handler
     */
    class WC_Centrobill_Webhook_Handler
    {
        const RESULT_CODE_SUCCESS = 0;
        const RESULT_OK = 'OK';
        const RESULT_NOK = 'NOK';
        const ERROR_EMPTY_ORDER_ID = 'Empty order_id';

        public function process()
        {
            if (!wc_centrobill_is_api_ipn($callback = file_get_contents('php://input'))) {
                return;
            }

            wc_centrobill()->logger->info('[IPN] Callback received', $callback);
            $result = ['result' => self::RESULT_NOK];

            try {
                $data = json_decode($callback, true);
                if (!empty($data['metadata']['wp_order_id'])) {
                    $order = wc_get_order($data['metadata']['wp_order_id']);
                    if ((int)$data['payment']['code'] === self::RESULT_CODE_SUCCESS) {
                        $status = WC_Centrobill_Constants::WC_STATUS_COMPLETED;
                        foreach ($order->get_items() as $product) {
                            if (!$product->get_product()->is_downloadable() && !$product->get_product()->is_virtual()) {
                                $status = WC_Centrobill_Constants::WC_STATUS_PROCESSING;
                                break;
                            }
                        }
                        $order->update_status($status);
                        wc_empty_cart();
                    } else {
                        $order->update_status(WC_Centrobill_Constants::WC_STATUS_FAILED, __($data['payment']['description'], 'woocommerce'));
                    }
                    $order->update_meta_data(WC_Centrobill_Constants::META_DATA_CB_USER, $data['consumer']['id']);
                    $order->update_meta_data(WC_Centrobill_Constants::META_DATA_CB_TRANSACTION_ID, $data['payment']['transactionId']);
                    $order->save_meta_data();

                    $result['result'] = self::RESULT_OK;
                    $result['order_details'] = $order->get_data();
                } else {
                    $result['error'] = self::ERROR_EMPTY_ORDER_ID;
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

            wc_centrobill()->logger->info('[IPN] Response', $result);
            echo json_encode($result);
            exit;
        }
    }
}
