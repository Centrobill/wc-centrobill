<?php

defined('ABSPATH') || exit;

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Enable Centrobill Onlinebanking', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_YES,
    ],
    'title' => [
        'title' => __('Title', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('Onlinebanking', 'woocommerce-gateway-centrobill'),
        'desc_tip' => true,
    ],
    'description' => [
        'title' => __('Description', 'woocommerce-gateway-centrobill'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('You will be redirected to Onlinebanking', 'woocommerce-gateway-centrobill'),
    ],
];
