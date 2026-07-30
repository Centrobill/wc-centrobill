<?php

/**
 * CartFlows Pro offer gateway adapter for Centrobill.
 *
 * Enables one-click upsell/downsell (MIT) charges against a stored consumer.
 * Registered with CartFlows Pro via the `cartflows_offer_supported_payment_gateways` filter.
 */
defined('ABSPATH') || exit;

if (!class_exists('Cartflows_Pro_Gateway_Centrobill')) {
    /**
     * Class Cartflows_Pro_Gateway_Centrobill
     */
    class Cartflows_Pro_Gateway_Centrobill
    {
        /**
         * @var Cartflows_Pro_Gateway_Centrobill
         */
        private static $instance;

        /**
         * Gateway slug (matches WC_Centrobill_Gateway_CC::$id).
         *
         * @var string
         */
        public $key = 'centrobill';

        /**
         * Offer refunds are not supported.
         *
         * @var bool
         */
        public $is_api_refund = false;

        /**
         * @return Cartflows_Pro_Gateway_Centrobill
         */
        public static function get_instance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Whether a stored payment method (consumer) is available for MIT.
         *
         * @param WC_Order $order
         *
         * @return bool
         */
        public function has_token($order)
        {
            if (!$order instanceof WC_Order) {
                return false;
            }

            if (!empty($order->get_meta(META_DATA_CB_USER))) {
                return true;
            }

            // Fallback for the IPN race (consumer meta not persisted yet):
            // the order was paid via a Centrobill gateway and we can resolve the consumer
            // by billing email at charge time.
            return $this->is_centrobill_order($order) && !empty($order->get_billing_email());
        }

        /**
         * Charge the offer product against the stored consumer.
         *
         * @param WC_Order $order Parent order created and passed by CartFlows.
         * @param array $product Offer product data (expects `step_id`, `total`).
         *
         * @return bool
         */
        public function process_offer_payment($order, $product)
        {
            if (!$order instanceof WC_Order) {
                return false;
            }

            $stepId = isset($product['step_id']) ? $product['step_id'] : 0;

            // Skip if this offer step was already charged.
            if (!empty($order->get_meta($this->get_txn_meta_key($stepId)))) {
                $this->log(sprintf('Offer step %s already charged, skipping.', $stepId));

                return true;
            }

            $amount = (float)(isset($product['total']) ? $product['total'] : 0);
            if ($amount <= 0) {
                $this->log('Offer amount is zero, nothing to charge.');

                return true;
            }

            if (!$this->has_token($order)) {
                $this->log('No stored consumer found, cannot process offer.');

                return false;
            }

            $metadata = [
                'source' => 'cartflows',
                'offer_type' => $this->get_offer_type($stepId),
                'offer_step_id' => (int)$stepId,
            ];

            try {
                $response = wc_centrobill()->api->processMitPayment($order, $amount, $metadata);
            } catch (Exception $e) {
                $note = sprintf('Centrobill offer payment failed. %s', $e->getMessage());
                $order->add_order_note($note);
                $this->log($note);

                return false;
            }

            if (!wc_centrobill_is_payment_successful($response)) {
                $note = sprintf(
                    'Centrobill offer payment failed. %s',
                    wc_centrobill_retrieve_response_text($response)
                );
                $order->add_order_note($note);
                $this->log($note);

                return false;
            }

            $transactionId = $response['payment']['transactionId'];
            try {
                $this->store_offer_transaction($order, ['id' => $transactionId], $product);
            } catch (Exception $e) {
                $this->log(sprintf(
                    'Offer charged (transaction %s) but persisting it failed: %s',
                    $transactionId,
                    $e->getMessage()
                ));
            }

            return true;
        }

        /**
         * Persist the offer transaction id.
         *
         * @param WC_Order $order
         * @param array $response Expects `id`.
         * @param array $product Expects `step_id`.
         *
         * @return void
         */
        public function store_offer_transaction($order, $response, $product)
        {
            $stepId = isset($product['step_id']) ? $product['step_id'] : 0;

            $order->update_meta_data($this->get_txn_meta_key($stepId), $response['id']);
            $order->save();

            $note = sprintf('Centrobill offer payment approved. Transaction ID: %s', $response['id']);
            $order->add_order_note($note);
            $this->log($note);
        }

        /**
         * @return bool
         */
        public function is_api_refund()
        {
            return $this->is_api_refund;
        }

        /**
         * @param int|string $stepId
         *
         * @return string
         */
        private function get_txn_meta_key($stepId)
        {
            return 'cartflows_offer_txn_resp_' . $stepId;
        }

        /**
         * CartFlows offer step type ('upsell' or 'downsell'); empty if unknown.
         *
         * @param int|string $stepId
         *
         * @return string
         */
        private function get_offer_type($stepId)
        {
            $type = $stepId ? (string)get_post_meta((int)$stepId, 'wcf-step-type', true) : '';

            return in_array($type, ['upsell', 'downsell'], true) ? $type : '';
        }

        /**
         * @param WC_Order $order
         *
         * @return bool
         */
        private function is_centrobill_order(WC_Order $order)
        {
            return strpos((string)$order->get_payment_method(), $this->key) === 0;
        }

        /**
         * @param string $message
         *
         * @return void
         */
        private function log($message)
        {
            wc_centrobill()->logger->info('[CartFlows] ' . $message);

            if (function_exists('wcf')) {
                wcf()->logger->log('Centrobill: ' . $message);
            }
        }
    }

    Cartflows_Pro_Gateway_Centrobill::get_instance();
}
