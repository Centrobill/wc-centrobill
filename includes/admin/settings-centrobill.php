<?php

defined('ABSPATH') || exit;

$defaultIpnUrl = '';

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable Centrobill', 'woocommerce'),
        'default' => 'yes',
    ],
    'title' => [
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'default' => __('CentroBill', 'woocommerce'),
        'desc_tip' => true,
    ],
    'description' => [
        'title' => __('Description', 'woocommerce'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'default' => __('Pay with CentroBill', 'woocommerce'),
    ],
    'token' => [
        'title' => __('Token', 'woocommerce'),
        'type' => 'text',
        'description' => __('Key used for making requests and generating sign.', 'woocommerce'),
        'default' => __('', 'woocommerce'),
    ],

    'ipn_url' => [
        'title' => __('IPN url', 'woocommerce'),
        'type' => 'text',
        'description' => __('URL where notifications will be send.', 'woocommerce'),
        'default' => untrailingslashit(get_home_url()) . '/?wc-api=wc_gateway_centrobill',
    ],
    'debug' => [
        'title' => __('Log debug messages', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce'),
        'default' => __('no', 'woocommerce'),
        'description' => __('Log Centrobill events, such as IPN requests, responses, etc. <br>Note: using this for debugging purposes only and deleting the logs when finished.', 'woocommerce'),
    ],
    'subscription_settings' => [
        'type' => 'title',
        'title' => 'Subscription Settings',
        'description' => 'Additional settings for the recurring payments',
    ],
    WC_Centrobill_Subscription::SETTING_KEY_ALLOW_SUBSCRIPTIONS => [
        'type' => 'checkbox',
        'title' => __('Enable/Disable', 'woocommerce'),
        'label' => __('Enable Subscription Payments', 'woocommerce'),
        'default' => __(WC_Centrobill_Subscription::SETTING_VALUE_YES, 'woocommerce'),
    ]
];
