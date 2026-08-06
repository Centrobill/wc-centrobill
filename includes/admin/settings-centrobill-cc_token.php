<?php

defined('ABSPATH') || exit;

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Enable Centrobill Credit Card (Hosted)', 'woocommerce-gateway-centrobill'),
        'description' => __('Redirects the customer to the card payment provider\'s hosted page to complete the payment.', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_NO,
    ],
    'title' => [
        'title' => __('Title', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('Credit Card', 'woocommerce-gateway-centrobill'),
        'desc_tip' => true,
    ],
    'description' => [
        'title' => __('Description', 'woocommerce-gateway-centrobill'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('You will be redirected to complete your card payment', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_SHOW_ICON => [
        'title' => __('Icon', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Show the card icon at checkout', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_YES,
    ],
];
