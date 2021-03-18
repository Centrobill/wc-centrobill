<?php

defined('ABSPATH') || exit();

if (!class_exists('WC_Centrobill_Constants')) {
    /**
     * Class WC_Centrobill_Constants
     */
    class WC_Centrobill_Constants
    {
        const MODE_SALE = 'sale';
        const MODE_AUTH = 'auth';
        const MODE_TEST = 'test';

        const STATUS_SHIPPED = 'shipped';
        const STATUS_SUCCESSFUL = 'successful';
        const STATUS_DECLINED = 'declined';
        const STATUS_FAILED = 'failed';

        const WC_STATUS_COMPLETED = 'completed';
        const WC_STATUS_PROCESSING = 'processing';
        const WC_STATUS_FAILED = 'failed';

        const META_DATA_CB_USER = '_cb_ustas';
        const META_DATA_CB_TRANSACTION_ID = '_cb_transaction_id';
    }
}
