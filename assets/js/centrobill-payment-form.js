jQuery(
    function($) {

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

        let WCCentrobillForm = {
            form: $('form.checkout'),
            isSubmit: false,
            init: function () {
                WCCentrobillForm.form.on('submit', function (e) {
                    if (WCCentrobillForm.isSubmit === false) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        WCCentrobillForm.retrieveCardToken();
                    }
                });
            },
            retrieveCardToken: function () {
                WCCentrobillForm.blockCheckoutForm();

                if (!WCCentrobillForm.validateForm()) {
                    WCCentrobillForm.unblockCheckoutForm();
                    return false;
                }

                $.ajax(
                    {
                        type: 'POST',
                        crossOrigin: true,
                        url: centrobill_params.tokenize_url,
                        dataType: 'json',
                        data: {
                            'number': WCCentrobillForm.getCardNumber(),
                            'expirationYear': WCCentrobillForm.getExpirationYear(),
                            'expirationMonth': WCCentrobillForm.getExpirationMonth(),
                            'cvv': WCCentrobillForm.getCardCvv(),
                            'cardHolder': WCCentrobillForm.getCardHolder(),
                            'zip': WCCentrobillForm.getZip(),
                        },
                        success: function (result) {
                            try {
                                let data = $.parseJSON(result);
                                WCCentrobillForm.setCardTokenData(data.token, data.expireAt);
                            } catch (err) {
                                WCCentrobillForm.showError();
                            }
                        },
                        error: function () {
                            WCCentrobillForm.showError();
                        },
                        complete: function () {
                            WCCentrobillForm.submit();
                        }
                    }
                );
            },
            /**
             * @param {string} token
             * @param {number} expireAt
             */
            setCardTokenData: function (token, expireAt) {
                $('#centrobill_card_token').val(token);
                $('#centrobill_card_token_expire').val(expireAt);
            },
            /**
             * @returns {string}
             */
            getCardHolder: function () {
                return $('#centrobill_cardholder_name').val();
            },
            /**
             * @returns {number}
             */
            getCardNumber: function () {
                let value = $('#centrobill_card_number').val();

                return parseInt(value.replace(/\s/g, ''));
            },
            /**
             * @returns {{month: string, year: string}}
             */
            getExpirationDate: function () {
                let expDate = $('#centrobill_expiration_date').val();
                let date = expDate.split('/');

                return {
                    "month": (date.length === 2) ? date[0].trim() : '',
                    "year": (date.length === 2) ? date[1].trim() : ''
                };
            },
            /**
             * @returns {string}
             */
            getExpirationYear: function () {
                return this.getExpirationDate().year;
            },
            /**
             * @returns {string}
             */
            getExpirationMonth: function () {
                return this.getExpirationDate().month;
            },
            /**
             * @returns {number}
             */
            getCardCvv: function () {
                return $('#centrobill_cvv').val();
            },
            /**
             * @returns {string}
             */
            getZip: function () {
                return $('#billing_postcode').val();
            },
            validateForm: function () {
                WCCentrobillForm.isSubmit = false;

                return true;
            },
            showError: function () {
                $('.woocommerce-error').html("Gateway error. <br> Payment token was not received.").show();
            },
            /**
             * @param form
             */
            blockCheckoutForm: function (form) {
                form = (form) ? form : $('form.checkout');
                form.addClass('processing').block({
                    message: null,
                    overlayCSS: {
                        background: '#333',
                        opacity: 0.6
                    }
                });
            },
            /**
             * @param form
             */
            unblockCheckoutForm: function (form) {
                form = (form) ? form : $('form.checkout');
                form.removeClass('processing').unblock();
            },
            submit: function () {
                WCCentrobillForm.isSubmit = true;
                WCCentrobillForm.unblockCheckoutForm();
                WCCentrobillForm.form.submit();
            }
        };

        // WCCentrobillForm.init(); // js submit disabled
    }
);
