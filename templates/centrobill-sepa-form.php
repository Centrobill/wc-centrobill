<?php defined('ABSPATH') || exit; ?>

<fieldset id="wc-centrobill-sepa-form" class="wc-credit-card-form wc-payment-form">
    <ul class="woocommerce-error" style="display:none"></ul>
    <div>
        <p class="form-row form-row-wide">
            <label for="centrobill_iban">
                <?php esc_html_e('IBAN'); ?><span class="required">*</span>
            </label>
            <input id="centrobill_iban" name="centrobill_iban" class="input-text wc-sepa-form-bin" type="text" value="" autocomplete="on" placeholder="" />
        </p>
        <?php if (false) : ?>
        <p class="form-row form-row-first">
            <label for="centrobill_bic"><?php esc_html_e('BIC'); ?></label>
            <input id="centrobill_bic" name="centrobill_bic" class="input-text wc-sepa-form-bic" type="text" autocomplete="on" />
        </p>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</fieldset>
