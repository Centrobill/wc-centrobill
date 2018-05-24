<?php
defined('ABSPATH') or exit();

class WC_Centrobill_Webhook_Handler
{
    const STATUS_DECLINED = 'declined';
    const STATUS_FAILED = 'failed';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_SUCCESSFUL = 'successful';
    const STATUS_REFUNDED = 'refunded';
    const MODE_AUTH = 'auth';
    const MODE_REFUND = 'refund';
    const MODE_SALE = 'sale';
    const MODE_TEST = 'test';
    const MODE_VOID = 'void';
    const MESSAGE_UNPROCESSABLE_STATUS = 'Unprocessable status';
    const ERROR_INVALID_SIGNATURE = 'Invalid signature';
    const ERROR_EMPTY_ORDER_ID = 'Empty order_id';
    const RESULT_NOK = 'NOK';
    const RESULT_OK = 'OK';

    /**
     * @var string
     */
    protected $authKey;

    /**
     * WC_Centrobill_Webhook_Handler constructor.
     *
     * @param string $auth_key
     */
    public function __construct($auth_key)
    {
        $this->authKey = $auth_key;
    }

    public function process()
    {
        $result = [
            'result' => self::RESULT_NOK
        ];
        try {
            $xml = simplexml_load_string(stripslashes($_REQUEST['ipn']));

            $ppgetdata   = (string) $xml->transaction->ppGetData;
            $status      = (string) $xml->transaction->status;
            $mode        = (string) $xml->transaction->mode;
            $cb_order_id = (string) $xml->transaction->orderId;

            $ppgetdata_params = unserialize(base64_decode($ppgetdata));
            $order_id         = $ppgetdata_params['wp_order_id'];

            if(!empty($order_id)) {
                $wc_order = new WC_Order($order_id);
                if($_REQUEST['sign'] == $this->generateSign($cb_order_id, $mode, $status)) {
                    $result['order_details'] = $wc_order->get_data();
                    if($this->isPaymentSuccessful($status, $mode)) {
                        $order_status = 'completed';
						foreach ($wc_order->get_items() as $product) {
							if(!$product->get_product()->is_downloadable() && !$product->get_product()->is_virtual()) {
								$order_status = 'processing';
								break;
							}
						}
                        $wc_order->update_status($order_status);
                    }
                    elseif($this->isRefundSuccessful($status, $mode)) {
                        // Mark order refunded
                        $wc_order->update_status('refunded');
                    }
                    elseif($this->isPaymentFailed($status, $mode)) {
                        $wc_order->update_status('failed', __('Payment failed', 'woocommerce'));
                    }
                    else {
                        $result['message'] = self::MESSAGE_UNPROCESSABLE_STATUS;
                    }
                    $result['result'] = self::RESULT_OK;
                }
                else {
                    $result['error'] = self::ERROR_INVALID_SIGNATURE;
                }
            }
            else {
                $result['error'] = self::ERROR_EMPTY_ORDER_ID;
            }
        }
        catch(Exception $e) {
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
    protected function isPaymentSuccessful($status, $mode)
    {
        return in_array($status, [self::STATUS_SUCCESSFUL, self::STATUS_SHIPPED]) && in_array($mode, [self::MODE_AUTH, self::MODE_SALE, self::MODE_TEST]);
    }

    /**
     * @param string $status
     * @param string $mode
     *
     * @return bool
     */
    protected function isRefundSuccessful($status, $mode)
    {
        return in_array($status, [self::STATUS_REFUNDED, self::STATUS_SUCCESSFUL]) && in_array($mode, [self::MODE_REFUND, self::MODE_VOID]);
    }

    /**
     * @param string $status
     * @param string $mode
     *
     * @return bool
     */
    protected function isPaymentFailed($status, $mode)
    {
        return in_array($status, [self::STATUS_DECLINED, self::STATUS_FAILED]) && in_array($mode, [self::MODE_AUTH, self::MODE_SALE, self::MODE_TEST]);
    }

    /**
     * @param string $cb_order_id
     * @param string $mode
     * @param string $status
     *
     * @return string
     */
    protected function generateSign($cb_order_id, $mode, $status)
    {
        return sha1(implode('_', [
            $this->authKey,
            $cb_order_id,
            $mode,
            $status,
            $this->authKey
        ]));
    }
}
