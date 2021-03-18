<?php

defined('ABSPATH') || exit;

if (!class_exists('WC_Centrobill_Admin_Widget')) {
    /**
     * Class WC_Centrobill_Admin_Widget
     */
    class WC_Centrobill_Admin_Widget
    {
        /**
         * @param false $isValidForUse
         * @param string $tableHtml
         */
        public static function showAdminOptions($isValidForUse, $tableHtml)
        {
            $content = sprintf('<h3>%s</h3>', translate('CentroBill Payment Gateway ', 'woocommerce'));
            if ($isValidForUse) {
                $content .= sprintf('<table class="form-table">%s</table>', $tableHtml);
            } else {
                $content .= sprintf(
                    '<div class="inline error"><p><strong>%s</strong></p><p>%s</p></div>',
                    translate('Gateway disabled', 'woocommerce'),
                    translate('CentroBill does not support your store currency.', 'woocommerce')
                );
            }

            echo $content;
        }

        /**
         * @return array
         */
        public static function loadAdminFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill.php';
        }
    }
}
