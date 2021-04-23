jQuery(
    function($) {
        $('input[name="billing_email"]').change(function() {
            $('body').trigger('update_checkout');
        });

        $('input.wc-credit-card-form-card-number').payment('formatCardNumber');
        $('input.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
        $('input.wc-credit-card-form-card-cvc').payment('formatCardCVC');

        $('#centrobill_browser_java_enabled').val(navigator.javaEnabled());
        $('#centrobill_browser_screen_height').val(screen.height);
        $('#centrobill_browser_screen_width').val(screen.width);
        $('#centrobill_browser_color_depth').val(screen.colorDepth ? screen.colorDepth : '32');
        $('#centrobill_browser_timezone').val(new Date().getTimezoneOffset() / 60);

        $(document.body)
            .on('updated_checkout wc-credit-card-form-init', function () {
                $('.wc-credit-card-form-card-number').payment('formatCardNumber');
                $('.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
                $('.wc-credit-card-form-card-cvc').payment('formatCardCVC');
            })
            .trigger('wc-credit-card-form-init');
    }
);
