jQuery(
    function($) {
        $('input[name="billing_email"]').change(function() {
            $('body').trigger('update_checkout');
        });

        $('input.wc-credit-card-form-card-number').payment('formatCardNumber');
        $('input.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
        $('input.wc-credit-card-form-card-cvc').payment('formatCardCVC');

        $(document.body)
            .on('updated_checkout wc-credit-card-form-init', function() {
                $('.wc-credit-card-form-card-number').payment( 'formatCardNumber' );
                $('.wc-credit-card-form-card-expiry').payment( 'formatCardExpiry' );
                $('.wc-credit-card-form-card-cvc').payment( 'formatCardCVC' );
            })
            .trigger( 'wc-credit-card-form-init' );
    }
);
