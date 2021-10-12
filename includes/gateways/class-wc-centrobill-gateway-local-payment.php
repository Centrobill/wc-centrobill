<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Gateway_Local_payment')) {
    /**
     * Class WC_Centrobill_Gateway_Local_payment
     */
    abstract class WC_Centrobill_Gateway_Local_payment extends WC_Centrobill_Gateway_Abstract
    {
        /**
         * {@inheritDoc}
         */
        public function payment_form()
        {
        }

        /**
         * {@inheritDoc}
         */
        public function before_process_payment(array $data, $errors)
        {
        }

        /**
         * {@inheritDoc}
         */
        public function gateway_process_payment($orderId)
        {
            $paymentSource = [
                'type' => str_replace('centrobill_', '', $this->id),
            ];

            if ($bic = wc_centrobill_retrieve_post_param('centrobill_bic')) {
                $paymentSource['bic'] = $bic;
            }

            return wc_centrobill()->api->pay($paymentSource, $orderId);
        }
    }
}
