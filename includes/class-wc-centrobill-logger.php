<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Logger')) {
    /**
     * Class WC_Centrobill_Logger
     */
    class WC_Centrobill_Logger
    {
        const LOG_SOURCE = 'wc-centrobill';

        const CONTEXT_IN_FILE_SINCE = '8.6.0';

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
         */
        public static function log($message, $context = null, $level = 'info')
        {
            if (!self::$isEnabled) {
                return;
            }

            if (empty(self::$logger)) {
                self::$logger = wc_get_logger();
            }

            $data = self::sanitizeContext($context);
            if (is_array($data)) {
                $logContext = array_merge($data, ['source' => self::LOG_SOURCE]);
            } elseif ($data !== null) {
                $logContext = ['source' => self::LOG_SOURCE, 'context' => $data];
            } else {
                $logContext = ['source' => self::LOG_SOURCE];
            }

            $message = (string)$message;

            // WooCommerce (< self::CONTEXT_IN_FILE_SINCE) does not write the context
            // to the log file, so append it to the message.
            if ($data !== null && self::shouldInlineContext()) {
                $message .= ' CONTEXT: ' . (is_scalar($data)
                    ? (string)$data
                    : wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            self::$logger->log($level, $message, $logContext);
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
         * @return bool
         */
        private static function shouldInlineContext()
        {
            return !defined('WC_VERSION')
                || version_compare(WC_VERSION, self::CONTEXT_IN_FILE_SINCE, '<');
        }

        /**
         * Sanitize a log context value: redact sensitive fields and decode the
         * request body into structured data. Returns null when there is nothing
         * to log, otherwise the value ready to hand to WC_Logger as context.
         *
         * @param mixed $context
         *
         * @return mixed|null
         */
        private static function sanitizeContext($context)
        {
            if ($context === null || $context === '' || $context === []) {
                return null;
            }

            if (is_object($context)) {
                $context = json_decode(json_encode($context), true);
            }

            if (!is_array($context)) {
                return $context; // scalar (e.g. an exception message)
            }

            if (isset($context['body']) && is_string($context['body'])) {
                $decoded = json_decode($context['body'], true);
                if (is_array($decoded)) {
                    $context['body'] = $decoded;
                }
            }

            foreach (self::$excludedParams as $excludedParam) {
                if (array_key_exists($excludedParam, $context)) {
                    $context[$excludedParam] = '';
                }

                if (isset($context['body']) && is_array($context['body'])
                    && array_key_exists($excludedParam, $context['body'])) {
                    $context['body'][$excludedParam] = '';
                }
            }

            return $context;
        }
    }
}
