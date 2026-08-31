<?php

defined('ABSPATH') || exit();

/**
 * Check if the gateway is available
 *
 * @access public
 * @return bool
 */
function wc_centrobill_is_valid_for_use() {
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
 * @return string
 */
function wc_centrobill_get_useragent() {
    return (string) wp_unslash(wc_clean($_SERVER['HTTP_USER_AGENT']));
}

/**
 * @param string $default
 * @return string
 */
function wc_centrobill_get_browser_language($default = 'en') {
    return !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])
        ? substr(wp_unslash(wc_clean($_SERVER['HTTP_ACCEPT_LANGUAGE'])), 0, 2) : $default;
}

/**
 * @return string
 */
function wc_centrobill_get_browser_accept_header() {
    return (string) wp_unslash(wc_clean($_SERVER['HTTP_ACCEPT']));
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
 * @param string $name
 * @return array
 */
function wc_centrobill_parse_cardholder_name($name) {
    $names = explode(' ', $name);

    $firstName = empty($names[0]) ? '' : array_shift($names);
    $lastName = trim(implode(' ', $names));

    return [$firstName, $lastName];
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
 * @param array $data
 * @return string
 */
function wc_centrobill_get_payment_page_url(array $data = []) {
    return !empty($data[SETTING_KEY_PAYMENT_PAGE_URL]) ?
        $data[SETTING_KEY_PAYMENT_PAGE_URL] : 'https://pay.centrobill.com';
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
 * Build customer-friendly messages from a gateway validation error (HTTP 400) response.
 *
 * @param array $response
 * @return string[] One message per invalid field.
 */
function wc_centrobill_get_validation_messages(array $response) {
    if (empty($response['errors']) || !is_array($response['errors'])) {
        return [];
    }

    $labels = [
        'email' => __('Email address', 'woocommerce-gateway-centrobill'),
        'firstName' => __('First name', 'woocommerce-gateway-centrobill'),
        'lastName' => __('Last name', 'woocommerce-gateway-centrobill'),
        'number' => __('Card number', 'woocommerce-gateway-centrobill'),
        'cardHolder' => __('Cardholder name', 'woocommerce-gateway-centrobill'),
        'cvv' => __('Card security code', 'woocommerce-gateway-centrobill'),
        'expirationMonth' => __('Card expiration month', 'woocommerce-gateway-centrobill'),
        'expirationYear' => __('Card expiration year', 'woocommerce-gateway-centrobill'),
        'zip' => __('Postal code', 'woocommerce-gateway-centrobill'),
        'country' => __('Country', 'woocommerce-gateway-centrobill'),
        'state' => __('State', 'woocommerce-gateway-centrobill'),
        'city' => __('City', 'woocommerce-gateway-centrobill'),
        'address' => __('Address', 'woocommerce-gateway-centrobill'),
        'phone' => __('Phone number', 'woocommerce-gateway-centrobill'),
    ];

    $messages = [];
    foreach (array_keys($response['errors']) as $field) {
        $segment = (($pos = strrpos($field, '.')) !== false) ? substr($field, $pos + 1) : $field;

        if (isset($labels[$segment])) {
            $label = $labels[$segment];
        } else {
            $label = ucfirst(strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', str_replace('_', ' ', $segment)))));
        }

        $messages[$label] = sprintf(
            __('%s is not valid', 'woocommerce-gateway-centrobill'),
            $label
        );
    }

    return array_values($messages);
}

/**
 * Render an HTML checkout notice for a failed payment with validation messages.
 *
 * @param string $title Payment method title.
 * @param string[] $messages Customer-friendly validation messages.
 * @return string
 */
function wc_centrobill_format_validation_notice($title, array $messages) {
    $heading = '<strong>' . sprintf(
        esc_html__('%s payment failed:', 'woocommerce-gateway-centrobill'),
        esc_html($title)
    ) . '</strong>';

    $messages = array_values(array_filter($messages));

    $items = '';
    foreach ($messages as $message) {
        $items .= '<li>' . esc_html($message) . '</li>';
    }

    return $heading . '<ul>' . $items . '</ul>';
}

/**
 * @param array $response
 * @return bool
 */
function wc_centrobill_is_payment_successful(array $response) {
    return isset($response['payment']['transactionId']) &&
        isset($response['payment']['code']) && (int)$response['payment']['code'] === 0 &&
        isset($response['payment']['action']) && $response['payment']['action'] === ACTION_CHARGE &&
        isset($response['payment']['status']) && in_array($response['payment']['status'], [STATUS_SUCCESS, STATUS_CONFIRMED], true);
}

/**
 * @param array $response
 * @return bool
 */
function wc_centrobill_is_subscription_payment_successful(array $response) {
    return wc_centrobill_is_payment_successful($response);
}

/**
 * @param array $response
 * @return bool
 */
function wc_centrobill_is_refund_successful(array $response) {
    return isset($response['payment']['transactionId']) &&
        isset($response['payment']['code']) && (int)$response['payment']['code'] === RESULT_CODE_SUCCESS &&
        isset($response['payment']['action']) && $response['payment']['action'] === ACTION_CREDIT &&
        isset($response['payment']['status']) && $response['payment']['status'] === STATUS_SUCCESS;
}

/**
 * Human-readable gateway error message from a thrown exception.
 *
 * @param Exception $e
 * @return string
 */
function wc_centrobill_get_gateway_error_message(Exception $e) {
    if ($e instanceof WC_Centrobill_Validation_Exception && $e->getErrors()) {
        $messages = array_filter(array_map('strval', array_values($e->getErrors())));
        if (!empty($messages)) {
            return implode('. ', $messages);
        }
    }

    return $e->getMessage();
}

/**
 * Build a refund reason accepted by the gateway (required, 3-64 chars).
 *
 * @param string $reason
 * @param WC_Order $order
 * @return string
 */
function wc_centrobill_prepare_refund_reason($reason, WC_Order $order) {
    $reason = trim((string)$reason);

    if (mb_strlen($reason) < 3) {
        $reason = sprintf('Refund for order #%s', $order->get_order_number());
    }

    if (mb_strlen($reason) > 64) {
        $reason = mb_substr($reason, 0, 64);
    }

    return $reason;
}

/**
 * Email of the currently logged-in user.
 *
 * @return string
 */
function wc_centrobill_get_current_user_email() {
    $user = wp_get_current_user();

    return ($user instanceof WP_User && $user->exists()) ? $user->user_email : '';
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
    $settings = get_option('woocommerce_centrobill_settings', []);

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
 * @return bool
 */
function wc_centrobill_is_payment_page_enabled() {
    $settings = get_option('woocommerce_centrobill_settings', []);

    return isset($settings[SETTING_KEY_USE_PAYMENT_PAGE])
        && $settings[SETTING_KEY_USE_PAYMENT_PAGE] === SETTING_VALUE_YES;
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

/**
 * @param string $country
 * @return string
 */
function wc_centrobill_country_convert_to_iso3($country)
{
    $countries = [
        'AF' => 'AFG',
        'AX' => 'ALA',
        'AL' => 'ALB',
        'DZ' => 'DZA',
        'AS' => 'ASM',
        'AD' => 'AND',
        'AO' => 'AGO',
        'AI' => 'AIA',
        'AQ' => 'ATA',
        'AG' => 'ATG',
        'AR' => 'ARG',
        'AM' => 'ARM',
        'AW' => 'ABW',
        'AU' => 'AUS',
        'AT' => 'AUT',
        'AZ' => 'AZE',
        'BS' => 'BHS',
        'BH' => 'BHR',
        'BD' => 'BGD',
        'BB' => 'BRB',
        'BY' => 'BLR',
        'BE' => 'BEL',
        'BZ' => 'BLZ',
        'BJ' => 'BEN',
        'BM' => 'BMU',
        'BT' => 'BTN',
        'BO' => 'BOL',
        'BQ' => 'BES',
        'BA' => 'BIH',
        'BW' => 'BWA',
        'BV' => 'BVT',
        'BR' => 'BRA',
        'IO' => 'IOT',
        'BN' => 'BRN',
        'BG' => 'BGR',
        'BF' => 'BFA',
        'BI' => 'BDI',
        'KH' => 'KHM',
        'CM' => 'CMR',
        'CA' => 'CAN',
        'CV' => 'CPV',
        'KY' => 'CYM',
        'CF' => 'CAF',
        'TD' => 'TCD',
        'CL' => 'CHL',
        'CN' => 'CHN',
        'CX' => 'CXR',
        'CC' => 'CCK',
        'CO' => 'COL',
        'KM' => 'COM',
        'CG' => 'COG',
        'CD' => 'COD',
        'CK' => 'COK',
        'CR' => 'CRI',
        'CI' => 'CIV',
        'HR' => 'HRV',
        'CU' => 'CUB',
        'CW' => 'CUW',
        'CY' => 'CYP',
        'CZ' => 'CZE',
        'DK' => 'DNK',
        'DJ' => 'DJI',
        'DM' => 'DMA',
        'DO' => 'DOM',
        'EC' => 'ECU',
        'EG' => 'EGY',
        'SV' => 'SLV',
        'GQ' => 'GNQ',
        'ER' => 'ERI',
        'EE' => 'EST',
        'ET' => 'ETH',
        'FK' => 'FLK',
        'FO' => 'FRO',
        'FJ' => 'FIJ',
        'FI' => 'FIN',
        'FR' => 'FRA',
        'GF' => 'GUF',
        'PF' => 'PYF',
        'TF' => 'ATF',
        'GA' => 'GAB',
        'GM' => 'GMB',
        'GE' => 'GEO',
        'DE' => 'DEU',
        'GH' => 'GHA',
        'GI' => 'GIB',
        'GR' => 'GRC',
        'GL' => 'GRL',
        'GD' => 'GRD',
        'GP' => 'GLP',
        'GU' => 'GUM',
        'GT' => 'GTM',
        'GG' => 'GGY',
        'GN' => 'GIN',
        'GW' => 'GNB',
        'GY' => 'GUY',
        'HT' => 'HTI',
        'HM' => 'HMD',
        'VA' => 'VAT',
        'HN' => 'HND',
        'HK' => 'HKG',
        'HU' => 'HUN',
        'IS' => 'ISL',
        'IN' => 'IND',
        'ID' => 'IDN',
        'IR' => 'IRN',
        'IQ' => 'IRQ',
        'IE' => 'IRL',
        'IM' => 'IMN',
        'IL' => 'ISR',
        'IT' => 'ITA',
        'JM' => 'JAM',
        'JP' => 'JPN',
        'JE' => 'JEY',
        'JO' => 'JOR',
        'KZ' => 'KAZ',
        'KE' => 'KEN',
        'KI' => 'KIR',
        'KP' => 'PRK',
        'KR' => 'KOR',
        'KW' => 'KWT',
        'KG' => 'KGZ',
        'LA' => 'LAO',
        'LV' => 'LVA',
        'LB' => 'LBN',
        'LS' => 'LSO',
        'LR' => 'LBR',
        'LY' => 'LBY',
        'LI' => 'LIE',
        'LT' => 'LTU',
        'LU' => 'LUX',
        'MO' => 'MAC',
        'MK' => 'MKD',
        'MG' => 'MDG',
        'MW' => 'MWI',
        'MY' => 'MYS',
        'MV' => 'MDV',
        'ML' => 'MLI',
        'MT' => 'MLT',
        'MH' => 'MHL',
        'MQ' => 'MTQ',
        'MR' => 'MRT',
        'MU' => 'MUS',
        'YT' => 'MYT',
        'MX' => 'MEX',
        'FM' => 'FSM',
        'MD' => 'MDA',
        'MC' => 'MCO',
        'MN' => 'MNG',
        'ME' => 'MNE',
        'MS' => 'MSR',
        'MA' => 'MAR',
        'MZ' => 'MOZ',
        'MM' => 'MMR',
        'NA' => 'NAM',
        'NR' => 'NRU',
        'NP' => 'NPL',
        'NL' => 'NLD',
        'AN' => 'ANT',
        'NC' => 'NCL',
        'NZ' => 'NZL',
        'NI' => 'NIC',
        'NE' => 'NER',
        'NG' => 'NGA',
        'NU' => 'NIU',
        'NF' => 'NFK',
        'MP' => 'MNP',
        'NO' => 'NOR',
        'OM' => 'OMN',
        'PK' => 'PAK',
        'PW' => 'PLW',
        'PS' => 'PSE',
        'PA' => 'PAN',
        'PG' => 'PNG',
        'PY' => 'PRY',
        'PE' => 'PER',
        'PH' => 'PHL',
        'PN' => 'PCN',
        'PL' => 'POL',
        'PT' => 'PRT',
        'PR' => 'PRI',
        'QA' => 'QAT',
        'RE' => 'REU',
        'RO' => 'ROU',
        'RU' => 'RUS',
        'RW' => 'RWA',
        'BL' => 'BLM',
        'SH' => 'SHN',
        'KN' => 'KNA',
        'LC' => 'LCA',
        'MF' => 'MAF',
        'SX' => 'SXM',
        'PM' => 'SPM',
        'VC' => 'VCT',
        'WS' => 'WSM',
        'SM' => 'SMR',
        'ST' => 'STP',
        'SA' => 'SAU',
        'SN' => 'SEN',
        'RS' => 'SRB',
        'SC' => 'SYC',
        'SL' => 'SLE',
        'SG' => 'SGP',
        'SK' => 'SVK',
        'SI' => 'SVN',
        'SB' => 'SLB',
        'SO' => 'SOM',
        'ZA' => 'ZAF',
        'GS' => 'SGS',
        'SS' => 'SSD',
        'ES' => 'ESP',
        'LK' => 'LKA',
        'SD' => 'SDN',
        'SR' => 'SUR',
        'SJ' => 'SJM',
        'SZ' => 'SWZ',
        'SE' => 'SWE',
        'CH' => 'CHE',
        'SY' => 'SYR',
        'TW' => 'TWN',
        'TJ' => 'TJK',
        'TZ' => 'TZA',
        'TH' => 'THA',
        'TL' => 'TLS',
        'TG' => 'TGO',
        'TK' => 'TKL',
        'TO' => 'TON',
        'TT' => 'TTO',
        'TN' => 'TUN',
        'TR' => 'TUR',
        'TM' => 'TKM',
        'TC' => 'TCA',
        'TV' => 'TUV',
        'UG' => 'UGA',
        'UA' => 'UKR',
        'AE' => 'ARE',
        'GB' => 'GBR',
        'US' => 'USA',
        'UM' => 'UMI',
        'UY' => 'URY',
        'UZ' => 'UZB',
        'VU' => 'VUT',
        'VE' => 'VEN',
        'VN' => 'VNM',
        'VG' => 'VGB',
        'VI' => 'VIR',
        'WF' => 'WLF',
        'EH' => 'ESH',
        'YE' => 'YEM',
        'ZM' => 'ZMB',
        'ZW' => 'ZWE',
    ];

    return !empty($countries[$country]) ? $countries[$country] : null;
}

/**
 * Build user agent string for API requests.
 *
 * @return string
 */
function wc_centrobill_build_useragent() {
    return sprintf('Centrobill WP Plugin/%s (WP/%s; WC/%s; PHP/%s) %s',
        WC_CENTROBILL_VERSION,
        get_bloginfo('version'),
        function_exists('WC') ? WC()->version : '0',
        phpversion(),
        get_bloginfo('url')
    );
}

/**
 * Extract a POST redirect instruction from a payment response, if present.
 *
 * @param mixed $data
 * @return array|null
 */
function wc_centrobill_extract_redirect_form($data) {
    if (!is_array($data)) {
        return null;
    }

    $sources = [$data];
    if (!empty($data['payment']) && is_array($data['payment'])) {
        $sources[] = $data['payment'];
    }

    foreach ($sources as $source) {
        if (
            !empty($source['redirectForm']['url'])
            && !empty($source['redirectForm']['fields'])
            && is_array($source['redirectForm']['fields'])
        ) {
            return $source['redirectForm'];
        }
    }

    return null;
}
