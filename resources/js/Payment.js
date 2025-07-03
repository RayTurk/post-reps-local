import helper from "./helper";


const Payment = {
    init() {
        this.onPaymentFormSubmit()
        let paymentTypeSelector = `[name="payment_type"]`;
        let inputsSelector = `.form-another-card input`;
        let cardProfileSelector = `[name="card_profile"]`;
        let selectsSelector = `.form-another-card select`;
        $(inputsSelector).prop('disabled', true);
        $(paymentTypeSelector).on('change', e => {
            let { target } = e;
            $(paymentTypeSelector).prop(`checked`, false);
            target.checked = true;
            if (target.getAttribute('id') == "use_another_card") {
                $(inputsSelector).prop('disabled', false);
                $(cardProfileSelector).prop('disabled', true);
                $(selectsSelector).prop('disabled', false);
            } else {
                $(cardProfileSelector).prop('disabled', false);
                $(inputsSelector).prop('disabled', true);
                $(selectsSelector).prop('disabled', true);
            }
            console.log(target);
        })

        $(`[name="repair_payment_type"]`).on('change', e => {
            let { target } = e;
            $(`[name="repair_payment_type"]`).prop(`checked`, false);
            target.checked = true;
            if (target.getAttribute('id') == "repair_use_another_card") {
                $(inputsSelector).prop('disabled', false);
                $(selectsSelector).prop('disabled', false);
                $(`[name="repair_card_profile"]`).prop('disabled', true);
            } else {
                $(`[name="repair_card_profile"]`).prop('disabled', false);
                $(inputsSelector).prop('disabled', true);
                $(selectsSelector).prop('disabled', true);
            }
        })

        $(`[name="removal_payment_type"]`).on('change', e => {
            let { target } = e;
            $(`[name="removal_payment_type"]`).prop(`checked`, false);
            target.checked = true;
            if (target.getAttribute('id') == "removal_use_another_card") {
                $(inputsSelector).prop('disabled', false);
                $(`[name="removal_card_profile"]`).prop('disabled', true);
                $(selectsSelector).prop('disabled', false);
            } else {
                $(`[name="removal_card_profile"]`).prop('disabled', false);
                $(inputsSelector).prop('disabled', true);
                $(selectsSelector).prop('disabled', true);
            }
        })

        $(`[name="delivery_payment_type"]`).on('change', e => {
            let { target } = e;
            $(`[name="delivery_payment_type"]`).prop(`checked`, false);
            target.checked = true;
            if (target.getAttribute('id') == "delivery_use_another_card") {
                $(inputsSelector).prop('disabled', false);
                $(`[name="delivery_card_profile"]`).prop('disabled', true);
                $(selectsSelector).prop('disabled', false);
            } else {
                $(`[name="delivery_card_profile"]`).prop('disabled', false);
                $(inputsSelector).prop('disabled', true);
                $(selectsSelector).prop('disabled', true);
            }
        })

        this.orderHold();
        this.repairOrderHold();
        this.removalOrderHold();
        this.deliveryOrderHold();

        this.onRepairPaymentFormSubmit();
        this.onRemovalPaymentFormSubmit();
        this.onDeliveryPaymentFormSubmit();
    },

    onPaymentFormSubmit() {
        let form = $("#paymentForm");

        if (form.length) {
            form.on('submit', e => {
                e.stopImmediatePropagation();
                e.preventDefault()

                helper.showLoader();

                let data = {};
                let formData = $(e.target).serializeArray()
                formData.forEach(f => {
                    if (f.name == "_token") return null;
                    data[f.name] = f.value;
                })
                $.post(helper.getSiteUrl(`/payment/pay`), data)
                    .done(res => {
                        console.log(res);
                        if (res.messages.resultCode == 'Error') {

                            if (res.messages.message[1]?.text == 'card declined') {
                                $(`[name="payment_type"]`).trigger('change');
                                $('#card_profile_select').empty();
                            }

                            let msgs = `<ul class="px-2">`;

                            if (res.transactionResponse && res.transactionResponse.errors) {
                                res.transactionResponse.errors.forEach(e => {
                                    msgs += `<li class='text-danger'>${e.errorText}</li>`;
                                })
                            } else {
                                res.messages.message.forEach(m => {
                                    msgs += `<li class='text-danger'>${m.text}</li>`;
                                })
                            }

                            msgs += '</ul>';

                            helper.alertError(msgs);

                            helper.hideLoader('');
                        } else {
                            window.location.reload();
                        }
                    })
                    .fail(res => {
                        let f = res.responseJSON;
                        let msgs = `<ul>`;
                        //main message
                        msgs += "<li class='text-danger'><b>" + f.message + "</b></li>"
                        for (const property in f.errors) {
                            $(`[name^="${property}"]`).addClass('is-invalid');
                            msgs += "<li class='text-danger'>" + f.errors[property] + "</li>"
                        }
                        msgs += '</ul>';

                        helper.alertError(msgs);

                        helper.hideLoader('');
                    })
            })
        }
    },

    loadCards(selectInput, userId) {
        const url = `${helper.getSiteUrl()}/payment/get-saved-cards/${userId}`;

        $.get(url)
        .done( res => {
            console.log(res);

            let html = '';

            $.each(res, (paymentProfileId, cardInfo) => {
                //console.log(paymentProfileId)
                if (selectInput.find(`option[value="${cardInfo.customerProfileId}::${paymentProfileId}"]`).length == 0) {
                    html += `<option value="${cardInfo.customerProfileId}::${paymentProfileId}">${cardInfo.cardType}: XXXX-XXXX-${cardInfo.cardNumber}  exp ${cardInfo.expDate}</option>`;
                }
            });

            selectInput.append(html);
        })
        .fail(res => {
            helper.alertError('Unable to load cards due to server error.');
        })
    },

    loadAgentCardsVisibleToOffice(selectInput, agentUserId, OfficeUserId) {
        const url = `${helper.getSiteUrl()}/payment/get-agent-cards-visible-to-office/${agentUserId}/${OfficeUserId}`;

        $.get(url)
        .done( res => {
            console.log(res);

            let html = '';

            $.each(res, (paymentProfileId, cardInfo) => {
                //console.log(paymentProfileId)
                //Append only if card not available yet.
                if (selectInput.find(`option[value="${cardInfo.customerProfileId}::${paymentProfileId}"]`).length == 0) {
                    html += `<option value="${cardInfo.customerProfileId}::${paymentProfileId}">${cardInfo.cardType}: XXXX-XXXX-${cardInfo.cardNumber}  exp ${cardInfo.expDate}</option>`;
                }
            });
            console.log(html)
            selectInput.append(html);
        })
        .fail(res => {
            helper.alertError('Unable to load cards due to server error.');
        })
    },

    loadOfficeCardsVisibleToAgent(selectInput, OfficeUserId, callback) {
        const url = `${helper.getSiteUrl()}/payment/get-office-cards-visible-to-agents/${OfficeUserId}`;

        $.get(url)
        .done( res => {
            console.log(res);

            let html = '';

            $.each(res, (paymentProfileId, cardInfo) => {
                //console.log(paymentProfileId)
                //Append only if card not available yet.
                if (selectInput.find(`option[value="${cardInfo.customerProfileId}::${paymentProfileId}"]`).length == 0) {
                    html += `<option value="${cardInfo.customerProfileId}::${paymentProfileId}">${cardInfo.cardType}: XXXX-XXXX-${cardInfo.cardNumber}  exp ${cardInfo.expDate}</option>`;
                }
            });
            //console.log(html)
            selectInput.append(html);

            if (typeof callback == 'function') {
                callback();
            }
        })
        .fail(res => {
            helper.alertError('Unable to load cards due to server error.');
        })
    },

    orderHold() {
        $('.order-hold').on('click', (e) => {
            e.stopImmediatePropagation();

            helper.showLoader();

            //Send email for order
            const orderId = $('[name="order_id"]').val();

            const url = `${helper.getSiteUrl()}/order/email/${orderId}`;
            $.get(url)
            .done( res => {
                window.location.reload();
            });
        });
    },

    repairOrderHold() {
        $('.repair-order-hold').on('click', (e) => {
            e.stopImmediatePropagation();

            helper.showLoader();

            //Send email for repair order
            const repairOrderId = $('[name="repair_order_id"]').val();

            const url = `${helper.getSiteUrl()}/repair/order/email/${repairOrderId}`;
            $.get(url)
            .done( res => {
                window.location.reload();
            });
        });
    },

    removalOrderHold() {
        $('.removal-order-hold').on('click', (e) => {
            e.stopImmediatePropagation();

            helper.showLoader();

            //Send email for removal order
            const RemovalOrderId = $('[name="removal_order_id"]').val();

            const url = `${helper.getSiteUrl()}/removal/order/email/${RemovalOrderId}`;
            $.get(url)
            .done( res => {
                window.location.reload();
            });
        });
    },

    deliveryOrderHold() {
        $('.delivery-order-hold').on('click', (e) => {
            e.stopImmediatePropagation();

            helper.showLoader();

            //Send email for delivery order
            const DeliveryOrderId = $('[name="delivery_order_id"]').val();

            const url = `${helper.getSiteUrl()}/delivery/order/email/${DeliveryOrderId}`;
            $.get(url)
            .done( res => {
                window.location.reload();
            });
        });
    },

    onRepairPaymentFormSubmit() {
        let form = $("#repairPaymentForm");

        if (form.length) {
            form.on('submit', e => {
                e.stopImmediatePropagation();
                e.preventDefault()

                helper.showLoader();

                let data = {};
                let formData = $(e.target).serializeArray()
                formData.forEach(f => {
                    if (f.name == "_token") return null;
                    data[f.name] = f.value;
                })
                $.post(`${helper.getSiteUrl()}/payment/repair/pay`, data)
                    .done(res => {
                        console.log(res);
                        if (res.messages.resultCode == 'Error') {

                            if (res.messages.message[1]?.text == 'card declined') {
                                $(`[name="repair_payment_type"]`).trigger('change');
                                $('#repair_card_profile_select').empty();
                            }

                            let msgs = `<ul class="px-2">`;

                            if (res.transactionResponse && res.transactionResponse.errors) {
                                res.transactionResponse.errors.forEach(e => {
                                    msgs += `<li class='text-danger'>${e.errorText}</li>`;
                                })
                            } else {
                                res.messages.message.forEach(m => {
                                    msgs += `<li class='text-danger'>${m.text}</li>`;
                                })
                            }

                            msgs += '</ul>';

                            helper.alertError(msgs);

                            helper.hideLoader('');
                        } else {
                            window.location.reload();
                        }
                    })
                    .fail(res => {
                        console.log(res)
                        let f = res.responseJSON;
                        let msgs = `<ul>`;
                        //main message
                        msgs += "<li class='text-danger'><b>" + f.message + "</b></li>"
                        for (const property in f.errors) {
                            $(`[name^="${property}"]`).addClass('is-invalid');
                            msgs += "<li class='text-danger'>" + f.errors[property] + "</li>"
                        }
                        msgs += '</ul>';

                        helper.alertError(msgs);

                        helper.hideLoader('');
                    })
            })
        }
    },

    multiplePosts: false,
    onRemovalPaymentFormSubmit() {
        let form = $("#removalPaymentForm");

        if (form.length) {
            form.on('submit', e => {
                e.stopImmediatePropagation();
                e.preventDefault()

                helper.showLoader();

                let data = {};
                let formData = $(e.target).serializeArray()
                formData.forEach(f => {
                    if (f.name == "_token") return null;
                    data[f.name] = f.value;
                })

                data['multiplePosts'] = Payment.multiplePosts;

                $.post(`${helper.getSiteUrl()}/payment/removal/pay`, data)
                    .done(res => {
                        console.log(res);
                        if (res.messages.resultCode == 'Error') {

                            if (res.messages.message[1]?.text == 'card declined') {
                                $(`[name="removal_payment_type"]`).trigger('change');
                                $('#removal_card_profile_select').empty();
                            }

                            let msgs = `<ul class="px-2">`;

                            if (res.transactionResponse && res.transactionResponse.errors) {
                                res.transactionResponse.errors.forEach(e => {
                                    msgs += `<li class='text-danger'>${e.errorText}</li>`;
                                })
                            } else {
                                res.messages.message.forEach(m => {
                                    msgs += `<li class='text-danger'>${m.text}</li>`;
                                })
                            }

                            msgs += '</ul>';

                            helper.alertError(msgs);

                            helper.hideLoader('');
                        } else {
                            window.location.reload();
                        }
                    })
                    .fail(res => {
                        console.log(res)
                        let f = res.responseJSON;
                        let msgs = `<ul>`;
                        //main message
                        msgs += "<li class='text-danger'><b>" + f.message + "</b></li>"
                        for (const property in f.errors) {
                            $(`[name^="${property}"]`).addClass('is-invalid');
                            msgs += "<li class='text-danger'>" + f.errors[property] + "</li>"
                        }
                        msgs += '</ul>';

                        helper.alertError(msgs);

                        helper.hideLoader('');
                    })
            })
        }
    },

    onDeliveryPaymentFormSubmit() {
        let form = $("#deliveryPaymentForm");

        if (form.length) {
            form.on('submit', e => {
                e.stopImmediatePropagation();
                e.preventDefault()

                helper.showLoader();

                let data = {};
                let formData = $(e.target).serializeArray()
                formData.forEach(f => {
                    if (f.name == "_token") return null;
                    data[f.name] = f.value;
                })

                $.post(`${helper.getSiteUrl()}/payment/delivery/pay`, data)
                    .done(res => {
                        console.log(res);
                        if (res.messages.resultCode == 'Error') {

                            if (res.messages.message[1]?.text == 'card declined') {
                                $(`[name="delivery_payment_type"]`).trigger('change');
                                $('#delivery_card_profile_select').empty();
                            }

                            let msgs = `<ul class="px-2">`;

                            if (res.transactionResponse && res.transactionResponse.errors) {
                                res.transactionResponse.errors.forEach(e => {
                                    msgs += `<li class='text-danger'>${e.errorText}</li>`;
                                })
                            } else {
                                res.messages.message.forEach(m => {
                                    msgs += `<li class='text-danger'>${m.text}</li>`;
                                })
                            }

                            msgs += '</ul>';

                            helper.alertError(msgs);

                            helper.hideLoader('');
                        } else {
                            window.location.reload();
                        }
                    })
                    .fail(res => {
                        console.log(res)
                        let f = res.responseJSON;
                        let msgs = `<ul>`;
                        //main message
                        msgs += "<li class='text-danger'><b>" + f.message + "</b></li>"
                        for (const property in f.errors) {
                            $(`[name^="${property}"]`).addClass('is-invalid');
                            msgs += "<li class='text-danger'>" + f.errors[property] + "</li>"
                        }
                        msgs += '</ul>';

                        helper.alertError(msgs);

                        helper.hideLoader('');
                    })
            })
        }
    },
}

export default Payment;
