<?php
defined('ABSPATH') or exit();

class WC_Centrobill_Gateway_Plugin extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    protected $authKey;
    /**
     * @var integer
     */
    protected $siteId;

    public function __construct()
    {
        $plugin_dir = plugin_dir_url(__FILE__);

        $this->id         = 'centrobill';
        $this->icon       = apply_filters('woocommerce_centrobill_icon', $plugin_dir.'../assets/images/centrobill_logo.png');
        $this->has_fields = true;

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        list($this->authKey, $this->siteId) = explode(':', $this->get_option('token'));
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Actions
        add_action('woocommerce_receipt_'.$this->id, [$this, 'receipt_page']);

        // Save options
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, [$this, 'process_admin_options']);

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_centrobill', [new WC_Centrobill_Webhook_Handler($this->authKey), 'process']);

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
        if(!in_array(get_woocommerce_currency(), apply_filters('woocommerce_centrobill_supported_currencies', ['RUB', 'USD', 'EUR', 'UAH']))) {
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
     * Initialize Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields()
    {
        $this->form_fields = [
            'enabled'     => [
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable', 'woocommerce'),
                'default' => 'yes'
            ],
            'title'       => [
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default'     => __('CentroBill', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default'     => __('Pay with CentroBill', 'woocommerce')
            ],
            'token'    => [
                'title'       => __('Token', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Key used for making requests and generating sign.', 'woocommerce'),
                'default'     => __('', 'woocommerce')
            ],
        ];
    }

    /**
     * @param int $order_id
     */
    public function receipt_page($order_id)
    {
        global $woocommerce;

        $order = new WC_Order($order_id);
        try {
            $centrobill_api = new WC_Centrobill_Api($this->authKey, $this->siteId);
            $payment_url    = $centrobill_api->getPaymentUrl($order);
            $widget         = new WC_Centrobill_Widget;
            $widget->showPaymentForm(
                $payment_url,
                $order->get_cancel_order_url()
            );

            // Empty cart and clear session
            $woocommerce->cart->empty_cart();
        }
        catch(Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
        }
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

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        ];
    }
}