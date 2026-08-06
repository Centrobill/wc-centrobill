<?php

defined('ABSPATH') || exit;

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Enable CentroBill Credit Cards', 'woocommerce-gateway-centrobill'),
        'description' => __('Toggles the credit card payment method only. It does not disable the other CentroBill payment methods or the shared account settings on this tab.', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_YES,
    ],
    'title' => [
        'title' => __('Title', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('CentroBill', 'woocommerce-gateway-centrobill'),
        'desc_tip' => true,
    ],
    'description' => [
        'title' => __('Description', 'woocommerce-gateway-centrobill'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('Pay with your credit card via CentroBill', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_CC_CARDHOLDER => [
        'title' => __('Cardholder name', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __("Enable cardholder's name", 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_YES,
        'description' => __("Show cardholder's name field on the credit card form.", 'woocommerce-gateway-centrobill'),
    ],
    'account_settings' => [
        'title' => __('Account settings', 'woocommerce-gateway-centrobill'),
        'type' => 'title',
        'description' => __('Shared account settings used by all CentroBill payment methods.', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_AUTH_KEY => [
        'title' => __('Authentication key', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('Authentication key used for making requests and generating sign.', 'woocommerce-gateway-centrobill'),
        'default' => __('', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_SITE_ID => [
        'title' => __('Site ID', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('Site ID used for making requests.', 'woocommerce-gateway-centrobill'),
        'default' => __('', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_IPN_URL => [
        'title' => __('IPN url', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('URL where notifications will be send.', 'woocommerce-gateway-centrobill'),
        'default' => wc_centrobill_get_ipn_url(),
        'custom_attributes' => ['readonly' => 'readonly'],
    ],
    SETTING_KEY_DEBUG => [
        'title' => __('Log debug messages', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_NO,
        'description' => __('Log Centrobill events, such as IPN requests, responses, etc. <br>Note: using this for debugging purposes only and deleting the logs when finished.', 'woocommerce-gateway-centrobill'),
    ],
    'payment_page_settings' => [
        'title' => __('Centrobill payment page', 'woocommerce-gateway-centrobill'),
        'type' => 'title',
    ],
    SETTING_KEY_USE_PAYMENT_PAGE => [
        'title' => __('Enable/Disable', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Enable redirect to Centrobill payment page', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_NO,
        'description' => __('Redirect to Centrobill payment page instead of using the integrated payment form on the checkout page', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_PAYMENT_PAGE_URL => [
        'title' => __('Payment page base URL', 'woocommerce-gateway-centrobill'),
        'type' => 'select',
        'description' => __('Base URL for the Centrobill payment page. Default is https://pay.centrobill.com', 'woocommerce-gateway-centrobill'),
        'default' => 'https://pay.centrobill.com',
        'options' => [
            'https://pay.centrobill.com' => 'https://pay.centrobill.com',
            'https://checkout.centrobill.com' => 'https://checkout.centrobill.com',
        ],
    ],
    'subscription_settings' => [
        'type' => 'title',
        'title' => __('Subscription Settings', 'woocommerce-gateway-centrobill'),
        'description' => __('Additional settings for the recurring payments', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_ALLOW_SUBSCRIPTIONS => [
        'type' => 'checkbox',
        'title' => __('Enable/Disable', 'woocommerce-gateway-centrobill'),
        'label' => __('Enable subscription payments', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_YES,
    ]
];
