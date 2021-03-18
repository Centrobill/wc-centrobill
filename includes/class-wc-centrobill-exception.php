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
         */
        public function __construct($message = 'Payment gateway error')
        {
            parent::__construct($message);
        }
    }
}
