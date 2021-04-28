<?php defined('ABSPATH') || exit; ?>

<style type="text/css">
    .wc-credit-card-form-cardholder-name {
        font-size: 1.2em;
        padding: 8px;
        background-repeat: no-repeat;
        background-position: right 0.618em center;
        background-size: 32px 20px;
    }
</style>

<?php include_once WC_CENTROBILL_PLUGIN_PATH . '/templates/centrobill-browser-form.php'; ?>

<fieldset id="wc-centrobill-cc-form" class="wc-credit-card-form wc-payment-form">
    <ul class="woocommerce-error" style="display:none"></ul>
    <div>
        <?php if ($show_cardholder_name): ?>
        <p class="form-row form-row-wide">
            <label for="centrobill_cardholder_name">
                <?php esc_html_e("Cardholder's name"); ?><span class="required">*</span>
            </label>
            <input id="centrobill_cardholder_name" name="centrobill_cardholder_name" class="input-text wc-credit-card-form-cardholder-name" type="text" value="" maxlength="50" autocomplete="on" placeholder="John Doe" />
        </p>
        <?php endif; ?>
        <p class="form-row form-row-wide">
            <label for="centrobill_card_number">
                <?php esc_html_e('Card Number'); ?><span class="required">*</span>
            </label>
            <input id="centrobill_card_number" name="centrobill_card_number" class="input-text wc-credit-card-form-card-number" type="text" value="" pattern="[0-9]*" maxlength="20" autocomplete="on" autocompletetype="cc-number" placeholder="•••• •••• •••• ••••" />
        </p>
        <p class="form-row form-row-first">
            <label for="centrobill_expiration_date">
                <?php esc_html_e('Expiry (MM/YY)'); ?><span class="required">*</span>
            </label>
            <input id="centrobill_expiration_date" name="centrobill_expiration_date" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="on" autocompletetype="cc-exp" placeholder="MM / YY" />
        </p>
        <p class="form-row form-row-last">
            <label for="centrobill_cvv">
                <?php esc_html_e('CVN/CVV'); ?><span class="required">*</span>
            </label>
            <input id="centrobill_cvv" name="centrobill_cvv" class="input-text wc-credit-card-form-card-cvc" type="password" maxlength="4" value="" pattern="[0-9]*" autocomplete="off" placeholder="CVV" autocompletetype="cc-csc" />
        </p>
    </div>
    <div class="clear"></div>
</fieldset>
