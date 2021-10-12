<?php

defined('ABSPATH') || exit;

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'woocommerce-gateway-centrobill'),
        'type' => 'checkbox',
        'label' => __('Enable Centrobill Crypto', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_VALUE_YES,
    ],
    'title' => [
        'title' => __('Title', 'woocommerce-gateway-centrobill'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('Crypto', 'woocommerce-gateway-centrobill'),
        'desc_tip' => true,
    ],
    'description' => [
        'title' => __('Description', 'woocommerce-gateway-centrobill'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-centrobill'),
        'default' => __('You will be redirected to Coincentro', 'woocommerce-gateway-centrobill'),
    ],
    SETTING_KEY_CRYPTO_AVAILABILITY => [
        'title' => __('Use payment method', 'woocommerce-gateway-centrobill'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('This controls the availability of the crypto payment method on the checkout page.', 'woocommerce-gateway-centrobill'),
        'default' => SETTING_OPTION_CRYPTO_ONETIME,
        'desc_tip' => true,
        'options' => [
            SETTING_OPTION_CRYPTO_ONETIME => 'Non-subscription products only',
            SETTING_OPTION_CRYPTO_ALL_WITH_EXCLUDING => 'All products excluding subscriptions with price less than $19.95',
        ],
    ],
];
