<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Exception')) {
    /**
     * Class WC_Centrobill_Exception
     */
    class WC_Centrobill_Exception extends Exception
    {
        /**
         * @param string $message
         * @param int $code
         */
        public function __construct($message = 'Payment gateway error', $code = 0)
        {
            parent::__construct($message, $code);
        }
    }
}
