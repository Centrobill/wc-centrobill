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
    return !empty($response['response_text']) ? $response['response_text'] : '';
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
