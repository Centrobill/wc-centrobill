<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Logger')) {
    /**
     * Class WC_Centrobill_Logger
     */
    class WC_Centrobill_Logger
    {
        const LOG_SOURCE = 'wc-centrobill';

        /**
         * @var bool
         */
        private static $isEnabled;

        /**
         * @var null|WC_Logger
         */
        private static $logger = null;

        /**
         * @var array
         */
        private static $excludedParams = [
            'authentication_key',
            'expirationYear',
            'expirationMonth',
            'number',
            'cvv',
        ];

        /**
         * @param array $settings
         */
        public function __construct(array $settings)
        {
            self::$isEnabled = !empty($settings[SETTING_KEY_DEBUG]) && $settings[SETTING_KEY_DEBUG] === 'yes';
        }

        /**
         * @param string $message
         * @param mixed $context
         * @param string $level possible values (emergency|alert|critical|error|warning|notice|info|debug)
         * @param bool $prettifyOutput
         */
        public static function log($message, $context = null, $level = 'info', $prettifyOutput = false)
        {
            if (!self::$isEnabled) {
                return;
            }

            if (empty(self::$logger)) {
                self::$logger = wc_get_logger();
            }

            self::$logger->log(
                $level,
                self::sanitizeAndFormatData($message, $context, $prettifyOutput),
                ['source' => self::LOG_SOURCE]);
        }

        /**
         * @param string $message
         * @param mixed $context
         */
        public function debug($message, $context = null)
        {
            self::log($message, $context, 'debug');
        }

        /**
         * @param string $message
         * @param mixed $context
         */
        public function info($message, $context = null)
        {
            self::log($message, $context, 'info');
        }

        /**
         * @param string $message
         * @param mixed $context
         */
        public function error($message, $context = null)
        {
            self::log($message, $context, 'error');
        }

        /**
         * @param string $message
         * @param mixed $context
         * @param bool $prettifyOutput
         *
         * @return string
         */
        private static function sanitizeAndFormatData($message, $context, $prettifyOutput = false)
        {
            if (isset($context['body']) && is_string($context['body'])) {
                $context['body'] = json_decode($context['body'], true);
            }

            foreach (self::$excludedParams as $excludedParam) {
                if (isset($context[$excludedParam])) {
                    $context[$excludedParam] = '';
                }

                if (isset($context['body'][$excludedParam])) {
                    $context['body'][$excludedParam] = '';
                }
            }

            if ((is_array($context) || is_object($context)) && !$prettifyOutput) {
                $context = json_encode($context);
            }

            return $message . (!empty($context) ? ' | Context: ' . wc_print_r($context, true) : '');
        }
    }
}
