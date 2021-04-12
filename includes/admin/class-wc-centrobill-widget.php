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
                $tabs = apply_filters('wc_centrobill_settings_nav_tabs', []);
?>

<style type="text/css">
    .wc-centrobill-settings-nav {
        margin: 1em 0;
        font-size: 1.1em;
    }
    .wc-centrobill-settings-nav .nav-link:nth-child(n+2) {
        margin-left: 0.5em;
    }
    .wc-centrobill-settings-nav .nav-link-active {
        color: #555;
        font-weight: 700;
    }
</style>

<div class="wc-centrobill-settings-logo">
    <img src="<?php echo wc_centrobill_image_url('centrobill_logo.png'); ?>" />
</div>

<div class="wc-centrobill-settings-nav">
    <?php foreach ($tabs as $id => $tab) : ?>
        <?php if ($_GET['section'] === $id) { $active = 'nav-link-active'; } else { $active = ''; } ?>
        <a class="nav-link <?php echo $active; ?>" href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $id); ?>">
            <?php echo esc_attr($tab); ?>
        </a>
    <?php endforeach; ?>
</div>
<div class="clear"></div>

<?php

                $content = "<div class='wc-centrobill-settings-container'><table class='form-table'>{$tableHtml}</table></div>";
            } else {
                $content = sprintf('<h3>%s</h3>', translate('CentroBill Payment Gateway ', 'woocommerce-gateway-centrobill'));
                $content .= sprintf(
                    '<div class="inline error"><p><strong>%s</strong></p><p>%s</p></div>',
                    translate('Gateway disabled', 'woocommerce-gateway-centrobill'),
                    translate('CentroBill does not support your store currency.', 'woocommerce-gateway-centrobill')
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
    }
}
