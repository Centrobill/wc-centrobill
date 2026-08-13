<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Validation_Exception')) {
    /**
     * Thrown when the gateway rejects a request with a validation error (HTTP 400).
     */
    class WC_Centrobill_Validation_Exception extends WC_Centrobill_Exception
    {
        /**
         * @var string[] Per-field, customer-friendly validation messages.
         */
        private $messages;

        /**
         * @param string $message Flat message used for logs and order notes.
         * @param string[] $messages Individual customer-friendly messages, one per invalid field.
         * @param int $code
         */
        public function __construct($message = 'Payment gateway error', array $messages = [], $code = 0)
        {
            parent::__construct($message, $code);
            $this->messages = $messages;
        }

        /**
         * @return string[]
         */
        public function getValidationMessages()
        {
            return $this->messages;
        }
    }
}
