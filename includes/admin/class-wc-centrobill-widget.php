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
            if ($isValidForUse) {
                wc_centrobill_load_partial_view('settings-nav');
                wc_centrobill_load_partial_view('settings-content-table', ['table' => $tableHtml]);
            } else {
                wc_centrobill_load_partial_view('settings-content-message');
            }
        }

        /**
         * @return array
         */
        public static function loadAdminFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill.php';
        }

        /**
         * @return array
         */
        public static function loadSepaFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-sepa.php';
        }

        /**
         * @return array
         */
        public static function loadGiropayFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-giropay.php';
        }

        /**
         * @return array
         */
        public static function loadSofortFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-sofort.php';
        }

        /**
         * @return array
         */
        public static function loadEpsFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-eps.php';
        }

        /**
         * @return array
         */
        public static function loadOnlinebankingFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-onlinebanking.php';
        }

        /**
         * @return array
         */
        public static function loadMybankFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-mybank.php';
        }

        /**
         * @return array
         */
        public static function loadPrzelewy24FormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-przelewy24.php';
        }

        /**
         * @return array
         */
        public static function loadIdealFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-ideal.php';
        }

        /**
         * @return array
         */
        public static function loadBancontactFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-bancontact.php';
        }

        /**
         * @return array
         */
        public static function loadCryptoFormFields()
        {
            return include WC_CENTROBILL_PLUGIN_PATH . '/includes/admin/settings-centrobill-crypto.php';
        }
    }
}
