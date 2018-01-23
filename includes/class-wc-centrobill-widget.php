<?php
defined('ABSPATH') or exit();

class WC_Centrobill_Widget
{
    public function showPaymentForm($payment_url, $cancel_url)
    {
        echo '<p>'.__('Thank you for your order, please click the button below to pay with CentroBill.', 'woocommerce').'</p>';

        echo '<a class="button alt" href="'.esc_url($payment_url).'">'
             .__('Pay', 'woocommerce').'</a>
                   <a class="button cancel" href="'.esc_url($cancel_url).'">'
             .__('Cancel order &amp; restore cart', 'woocommerce').'</a>';
    }
}
