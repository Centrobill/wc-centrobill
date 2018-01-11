<?php
/*
  Plugin Name: CentroBill Payment Gateway
  Plugin URI:
  Description: Allows you to use CentroBill payment method with the WooCommerce plugin.
  Version: 1.0.0
  Author: CentroBill
  Author URI: https://centrobill.com/
 */

if(!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/* Add a custom payment class to WC */
add_action('plugins_loaded', 'woocommerce_centrobill', 0);

function woocommerce_centrobill()
{
    if(!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_TechCentro extends WC_Payment_Gateway
    {
        const STATUS_DECLINED = 'declined';
        const STATUS_FAILED = 'failed';
        const STATUS_SHIPPED = 'shipped';
        const STATUS_SUCCESSFUL = 'successful';
        const STATUS_REFUNDED = 'refunded';
        const MODE_AUTH = 'auth';
        const MODE_REFUND = 'refund';
        const MODE_SALE = 'sale';
        const MODE_TEST = 'test';
        const MODE_VOID = 'void';

        protected $secretKey;

        public function __construct()
        {
            $plugin_dir = plugin_dir_url(__FILE__);

            global $woocommerce;

            $this->id         = 'centrobill';
            $this->icon       = apply_filters('woocommerce_centrobill_icon', ''.$plugin_dir.'centrobill.png');
            $this->has_fields = true;

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->secretKey   = $this->get_option('secret');
            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Actions
            add_action('woocommerce_receipt_'.$this->id, array($this, 'receipt_page'));

            // Save options
            add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_centrobill', array($this, 'check_ipn_response'));

            if(!$this->is_valid_for_use()) {
                $this->enabled = false;
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's country
         *
         * @access public
         * @return bool
         */
        function is_valid_for_use()
        {
            if(!in_array(get_woocommerce_currency(), apply_filters('woocommerce_centrobill_supported_currencies', array('RUB', 'USD', 'EUR', 'UAH')))) {
                return false;
            }

            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         *
         * @since 1.0.0
         */
        public function admin_options()
        {
            ?>
            <h3><?php _e('CentroBill', 'woocommerce'); ?></h3>

            <?php if($this->is_valid_for_use()) : ?>
            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table>
        <?php else : ?>
            <div class="inline error"><p>
                    <strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('CentroBill does not support your store currency.', 'woocommerce'); ?>
                </p></div>
            <?php
        endif;
        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled'     => array(
                    'title'   => __('Enable/Disable', 'woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title'       => array(
                    'title'       => __('Title', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default'     => __('CentroBill', 'woocommerce'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Description', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                    'default'     => __('Pay with CentroBill', 'woocommerce')
                ),
                'secret'      => array(
                    'title'       => __('Secret key', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Secret key used for generating sign.', 'woocommerce'),
                    'default'     => __('', 'woocommerce')
                ),
            );
        }

        /**
         * Get Сentrobill Args
         *
         * @access public
         *
         * @param mixed $order
         *
         * @return array
         */
        function get_centrobill_args($order)
        {
            global $woocommerce;

            $order_id = $order->get_id();

            $centrobill_args = array();

            $items     = $order->get_items();
            $sku_names = [];
            foreach($items as $item_id => $item_data) {
                foreach($item_data->get_product()->get_attributes() as $attribute) {
                    if($attribute['name'] == 'cb_sku_id') {
                        $sku_names = $attribute['value'];
                    }
                }
            }
            $centrobill_args['sku_name']       = implode(',', $sku_names);
            $centrobill_args['customer_email'] = $order->billing_email;
            $centrobill_args['wp_order_id']    = $order_id;

            $centrobill_args = apply_filters('woocommerce_centrobill_args', $centrobill_args);

            return $centrobill_args;
        }

        /**
         * Generate the Centrobill button link
         *
         * @access public
         *
         * @param mixed $order_id
         *
         * @return string
         */
        function generate_centrobill_form($order_id)
        {
            global $woocommerce;

            $order = new WC_Order($order_id);

            $centrobill_args = $this->get_centrobill_args($order);

            $centrobill_args_array = array();

            foreach($centrobill_args as $key => $value) {
                $centrobill_args_array[] = '<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'" />';
            }

            wc_enqueue_js('
				jQuery("body").block({
						message: "'.esc_js(__('Thank you for your order. We are now redirecting you to CentroBill to make payment.', 'woocommerce')).'",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        "20px",
							zindex:         "9999999",
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:     "24px",
						}
					});
				jQuery("#submit_centrobill_payment_form").click();
			');

            return '<form action="http://purchase.centrobill.com/ustas/" method="get" id="centrobill_payment_form" target="_top">'
                   .implode('', $centrobill_args_array).'<input type="submit" class="button alt" id="submit_centrobill_payment_form" value="'
                   .__('Pay', 'woocommerce').'" /> <a class="button cancel" href="'.esc_url($order->get_cancel_order_url()).'">'
                   .__('Cancel order &amp; restore cart', 'woocommerce').'</a></form>';
        }

        /**
         * Process the payment and return the result
         *
         * @access public
         *
         * @param int $order_id
         *
         * @return array
         */
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);

            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        /**
         * Output for the order received page.
         *
         * @access public
         * @return void
         */
        function receipt_page($order)
        {
            global $woocommerce;

            echo '<p>'.__('Thank you for your order, please click the button below to pay with CentroBill.', 'woocommerce').'</p>';

            echo $this->generate_centrobill_form($order);
            // Empty cart and clear session
            // $woocommerce->cart->empty_cart();
        }

        /**
         * Check for CentroBill Response
         *
         * @access public
         * @return void
         */
        function check_ipn_response()
        {
            global $woocommerce;

            $xml = simplexml_load_string($_REQUEST['ipn']);

            $ppgetdata   = (string) $xml->transaction->ppGetData;
            $status      = (string) $xml->transaction->status;
            $mode        = (string) $xml->transaction->mode;
            $cb_order_id = (string) $xml->transaction->orderId;

            $ppgetdata_params = unserialize(base64_decode($ppgetdata));
            $order_id         = $ppgetdata_params['wp_order_id'];

            if(!empty($order_id)) {
                $wc_order = new WC_Order($order_id);
                if($_REQUEST['sign'] == $this->generateSign($cb_order_id, $mode, $status)) {
                    if($this->isPaymentSuccessful($status, $mode)) {
                        // Mark order complete
                        $wc_order->update_status('processing');
                    }
                    elseif($this->isRefundSuccessful($status, $mode)) {
                        // Mark order refunded
                        $wc_order->update_status('refunded');
                    }
                    elseif($this->isPaymentFailed($status, $mode)) {
                        $wc_order->update_status('failed', __('Payment failed', 'woocommerce'));
                    }
                    echo 'OK';
                    exit;
                }
            }
            echo 'NOK';
            exit;
        }

        /**
         * @param $status
         * @param $mode
         *
         * @return bool
         */
        protected function isPaymentSuccessful($status, $mode)
        {
            return in_array($status, [self::STATUS_SUCCESSFUL, self::STATUS_SHIPPED]) && in_array($mode, [self::MODE_AUTH, self::MODE_SALE, self::MODE_TEST]);
        }

        /**
         * @param $status
         * @param $mode
         *
         * @return bool
         */
        protected function isRefundSuccessful($status, $mode)
        {
            return in_array($status, [self::STATUS_REFUNDED, self::STATUS_SUCCESSFUL]) && in_array($mode, [self::MODE_REFUND, self::MODE_VOID]);
        }

        protected function isPaymentFailed($status, $mode)
        {
            return in_array($status, [self::STATUS_DECLINED, self::STATUS_FAILED]) && in_array($mode, [self::MODE_AUTH, self::MODE_SALE, self::MODE_TEST]);
        }

        protected function generateSign($cb_order_id, $mode, $status)
        {
            return sha1(implode('_', [
                $this->secretKey,
                $cb_order_id,
                $mode,
                $status,
                $this->secretKey
            ]));
        }
    }

    /**
     * Add the gateway to WooCommerce
     **/
    function add_centrobill_gateway($methods)
    {
        $methods[] = 'WC_Gateway_TechCentro';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_centrobill_gateway');
}
