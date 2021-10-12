<?php

defined('ABSPATH') || exit();

const HTTP_STATUS_OK = 200;
const HTTP_STATUS_CREATED = 201;

const RESULT_CODE_SUCCESS = 0;
const RESULT_OK = 'OK';
const RESULT_NOK = 'NOK';

const API_URL = 'https://api.centrobill.com';
const EPAYMENT_URL = 'https://epayment.centrobill.com/epayment/lib/paypage_api/pay.php';

const API_ENDPOINT_PAYMENT = 'payment';
const API_ENDPOINT_TOKENIZE = 'tokenize';
const API_ENDPOINT_PAYMENT_PAGE = 'paymentPage';

const METHOD_GET_CREATE_USER = 'get_ustas_or_create';
const METHOD_GET_PM = 'get';

const PAYMENT_TYPE_TOKEN = 'token';
const PAYMENT_TYPE_SEPA = 'sepa';
const PAYMENT_TYPE_ONLINEBANKING = 'onlinebanking';
const PAYMENT_TYPE_SOFORTBANKING = 'sofortbanking';
const PAYMENT_TYPE_GIROPAY = 'giropay';
const PAYMENT_TYPE_IDEAL = 'ideal';
const PAYMENT_TYPE_PRZELEWY24 = 'przelewy24';
const PAYMENT_TYPE_BANCONTACT = 'bancontact';
const PAYMENT_TYPE_EPS = 'eps';
const PAYMENT_TYPE_MYBANK = 'mybank';
const PAYMENT_TYPE_CRYPTO = 'crypto';
const PAYMENT_TYPE_CONSUMER = 'consumer';

const MODE_SALE = 'sale';
const MODE_AUTH = 'auth';
const MODE_TEST = 'test';
const MODE_REFUND = 'refund';
const MODE_VOID = 'void';

const STATUS_SUCCESS = 'success';
const STATUS_FAIL = 'fail';
const STATUS_SHIPPED = 'shipped';
const STATUS_SUCCESSFUL = 'successful';
const STATUS_DECLINED = 'declined';
const STATUS_FAILED = 'failed';
const STATUS_REFUNDED = 'refunded';

const ACTION_CHARGE = 'charge';
const ACTION_REDIRECT = 'redirect';

const WC_STATUS_COMPLETED = 'completed';
const WC_STATUS_PROCESSING = 'processing';
const WC_STATUS_FAILED = 'failed';
const WC_STATUS_REFUNDED = 'refunded';

const META_DATA_CB_USER = '_cb_ustas';
const META_DATA_CB_TRANSACTION_ID = '_cb_transaction_id';

const SETTING_KEY_ALLOW_SUBSCRIPTIONS = 'allow_subscriptions';
const SETTING_KEY_AUTH_KEY = 'auth_key';
const SETTING_KEY_SITE_ID = 'site_id';
const SETTING_KEY_DEBUG = 'debug';
const SETTING_KEY_IPN_URL = 'ipn_url';
const SETTING_KEY_CC_CARDHOLDER = 'show_cardholder_name';
const SETTING_KEY_USE_PAYMENT_PAGE = 'use_payment_page';
const SETTING_KEY_CRYPTO_AVAILABILITY = 'crypto_availability';

const SETTING_OPTION_CRYPTO_ONETIME = 'crypto_availability_option_onetime_only';
const SETTING_OPTION_CRYPTO_ALL_WITH_EXCLUDING = 'crypto_availability_option_all_products_with_excluding';

const SETTING_VALUE_YES = 'yes';
const SETTING_VALUE_NO = 'no';

const SESSION_KEY_PM = 'centrobill_checkout_pm_%s';
const SESSION_KEY_EMAIL = 'centrobill_checkout_email';

const IPN_MESSAGE_UNPROCESSABLE_STATUS = 'Unprocessable status';
const IPN_ERROR_EMPTY_ORDER_ID = 'Empty order_id';
const IPN_ERROR_INVALID_SIGNATURE = 'Invalid signature';
