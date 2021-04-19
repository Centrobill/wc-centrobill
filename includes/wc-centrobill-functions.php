<?php

defined('ABSPATH') || exit();

/**
 * Check if the gateway is enabled and available in the user's country
 *
 * @access public
 * @return bool
 */
function wc_centrobill_is_valid_for_use() {
    $currencies = apply_filters('woocommerce_centrobill_supported_currencies', ['RUB', 'USD', 'EUR', 'UAH']);

    if (!in_array(get_woocommerce_currency(), $currencies, true)) {
        return false;
    }

    return true;
}

/**
 * @param string $param
 * @param mixed $default
 *
 * @return mixed
 */
function wc_centrobill_retrieve_post_param($param = null, $default = null) {
    if (!empty($_POST[$param])) {
        return wp_unslash(wc_clean($_POST[$param]));
    }

    if ($param === null) {
        return (array) wp_unslash(wc_clean($_POST));
    }

    return $default;
}

/**
 * @param string $param
 * @param mixed $value
 *
 * @return void
 */
function wc_centrobill_set_post_param($param, $value) {
    $_POST[$param] = $value;
}

/**
 * @param string $string
 * @return bool
 */
function wc_centrobill_is_valid_json($string) {
    json_decode($string);
    return json_last_error() == JSON_ERROR_NONE;
}

/**
 * @return string
 */
function wc_centrobill_get_ip_address() {
    return WC_Geolocation::get_ip_address() ?: WC_Geolocation::get_external_ip_address();
}

/**
 * Format: MM/YY
 *
 * @param string $date
 * @return bool
 */
function wc_centrobill_check_expiration_date($date) {
    return (bool)preg_match('/(0[1-9]|1[0-2]).?\/.?([2-4][0-9])$/', $date);
}

/**
 * @param string $data
 * @return bool
 */
function wc_centrobill_is_api_ipn($data) {
    $data = json_decode($data, true);

    return isset($data['payment']['code']);
}

/**
 * @param array $data
 * @return string
 */
function wc_centrobill_get_ipn_url(array $data = []) {
    return !empty($data['ipn_url']) ?
        $data['ipn_url'] : trailingslashit(get_home_url()) . '?wc-api=wc_gateway_centrobill';
}

/**
 * @param array $response
 * @return string
 */
function wc_centrobill_retrieve_response_text(array $response) {

    $message = '';
    if (!empty($response['response_text'])) {
        $message = $response['response_text'];
    } elseif (!empty($response['error_message'])) {
        $message = $response['error_message'];
    } elseif (!empty($response['payment']['description'])) {
        $message = $response['payment']['description'];
    }

    return $message;
}

/**
 * @param array $response
 * @return bool
 */
function wc_centrobill_is_subscription_payment_successful(array $response) {
    return isset($response['payment']['transactionId']) &&
        isset($response['payment']['code']) &&
        (int)$response['payment']['code'] === 0;
}

/**
 * Checks if Woocommerce Subscriptions is enabled or not
 *
 * @return bool
 */
function wc_centrobill_is_subscriptions_plugin_active() {
    return class_exists('WC_Subscriptions') && class_exists('WC_Subscriptions_Order');
}

/**
 * @return bool
 */
function wc_centrobill_is_subscriptions_enabled() {
    $settings = get_option('woocommerce_centrobill_cc_settings', []);

    return isset($settings[SETTING_KEY_ALLOW_SUBSCRIPTIONS])
        && $settings[SETTING_KEY_ALLOW_SUBSCRIPTIONS] === SETTING_VALUE_YES;
}

/**
 * @param WC_Order $order
 * @return bool
 */
function wc_centrobill_is_order_contains_subscription(WC_Order $order) {
    return function_exists('wcs_order_contains_subscription') &&
        (wcs_order_contains_subscription($order) || wcs_order_contains_renewal($order));
}

/**
 * @param string $name
 * @param array $args
 */
function wc_centrobill_get_template($name, array $args = []) {
    wc_get_template($name, $args, '', WC_CENTROBILL_PLUGIN_PATH . '/templates/');
}

/**
 * @param string $name
 * @param array $args
 * @param bool $includeOnce
 */
function wc_centrobill_load_partial_view($name, array $args = [], $includeOnce = true) {
    foreach ($args as $variable => $value) {
        $$variable = $value;
    }

    $path = WC_CENTROBILL_PLUGIN_PATH . "/includes/admin/views/%s.php";
    if ($includeOnce) {
        include_once sprintf($path, $name);
    } else {
        include sprintf($path, $name);
    }
}

/**
 * @param string $path
 * @return string
 */
function wc_centrobill_assets_url($path) {
    return apply_filters('woocommerce_centrobill_icon', sprintf('%s/assets/%s', WC_CENTROBILL_PLUGIN_URL, $path));
}

/**
 * @param string $img
 * @return string
 */
function wc_centrobill_image_url($img) {
    return wc_centrobill_assets_url('images/' . $img);
}

/**
 * @param array $keys
 */
function wc_centrobill_remove_session_keys(array $keys = []) {
    foreach ($keys as $key) {
        WC()->session->__unset($key);
    }
}
