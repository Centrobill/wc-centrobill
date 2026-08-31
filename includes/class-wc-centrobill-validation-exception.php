<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Validation_Exception')) {
    /**
     * Thrown when the gateway rejects a request with a validation error (HTTP 400).
     */
    class WC_Centrobill_Validation_Exception extends WC_Centrobill_Exception
    {
        /**
         * @var array Raw field => message map as returned by the gateway.
         */
        private $errors;

        /**
         * @param string $message Flat message used for logs and order notes.
         * @param array $errors Raw field => message map as returned by the gateway.
         * @param int $code
         */
        public function __construct($message = 'Payment gateway error', array $errors = [], $code = 0)
        {
            parent::__construct($message, $code);
            $this->errors = $errors;
        }

        /**
         * Customer-friendly, per-field messages derived from the raw gateway errors.
         *
         * @return string[]
         */
        public function getValidationMessages()
        {
            return wc_centrobill_get_validation_messages(['errors' => $this->errors]);
        }

        /**
         * @return array Raw field => message map as returned by the gateway.
         */
        public function getErrors()
        {
            return $this->errors;
        }
    }
}
