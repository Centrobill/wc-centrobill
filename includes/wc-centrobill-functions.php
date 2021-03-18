<?php

defined('ABSPATH') || exit();

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
 * @param string $name
 * @param array $args
 */
function wc_centrobill_get_template($name, array $args = []) {
    wc_get_template($name, $args, '', WC_CENTROBILL_PLUGIN_PATH . '/templates/');
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
    return WC_Geolocation::get_external_ip_address();
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
 * @param array $data
 * @return bool
 */
function wc_centrobill_is_3ds_redirect($data) {
    return !empty($data['payment']['url']) &&
        (!empty($data['payment']['action']) && $data['payment']['action'] === 'redirect');
}

/**
 * @param string $data
 * @return bool
 */
function wc_centrobill_is_api_ipn($data) {
    $data = json_decode($data, true);

    return !empty($data['payment']['code']);
}

/**
 * @param array $data
 * @return string
 */
function wc_centrobill_get_ipn_url(array $data) {
    return !empty($data['ipn_url']) ?
        $data['ipn_url'] : untrailingslashit(get_home_url()) . '?wc-api=wc_gateway_centrobill';
}

/**
 * @param array $response
 * @return string
 */
function wc_centrobill_retrieve_response_text(array $response) {
    return !empty($response['response_text']) ? $response['response_text'] : '';
}
