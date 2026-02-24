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

    /**
     * Load cards from the unified charge API endpoint
     * @param {jQuery} selectInput - The card select dropdown
     * @param {object} params - Query params: { office_user_id, agent_user_id, source }
     * @param {function} callback - Optional callback after cards are loaded
     */
    loadCardsFromChargeAPI(selectInput, params, callback) {
        const queryString = $.param(params);
        const url = `${helper.getSiteUrl()}/charge/cards?${queryString}`;

        selectInput.empty();

        $.get(url)
        .done(res => {
            let html = '';
            if (res.length === 0) {
                html = '<option value="">No saved cards found</option>';
            }
            res.forEach(card => {
                html += `<option value="${card.value}">${card.cardType}: XXXX-XXXX-${card.cardNumber}  exp ${card.expDate}</option>`;
            });
            selectInput.html(html);

            if (typeof callback === 'function') {
                callback(res);
            }
        })
        .fail(() => {
            selectInput.html('<option value="">Error loading cards</option>');
        });
    },

    /**
     * Load offices for admin dropdown
     * @param {jQuery} selectInput - The office select dropdown
     * @param {function} callback - Optional callback after offices are loaded
     */
    loadOfficesForAdmin(selectInput, callback) {
        const url = `${helper.getSiteUrl()}/charge/offices`;

        $.get(url)
        .done(res => {
            let html = '<option value="">-- Select Office --</option>';
            res.forEach(office => {
                html += `<option value="${office.id}" data-user-id="${office.user_id}">${office.name}</option>`;
            });
            selectInput.html(html);

            if (typeof callback === 'function') {
                callback(res);
            }
        })
        .fail(() => {
            helper.alertError('Unable to load offices.');
        });
    },

    /**
     * Load agents for an office
     * @param {jQuery} selectInput - The agent select dropdown
     * @param {int} officeId - The office ID
     * @param {string} defaultLabel - Default option label
     * @param {function} callback - Optional callback after agents are loaded
     */
    loadAgentsForOffice(selectInput, officeId, defaultLabel, callback) {
        const url = `${helper.getSiteUrl()}/charge/offices/${officeId}/agents`;

        $.get(url)
        .done(res => {
            let html = `<option value="">${defaultLabel || '-- Office Card --'}</option>`;
            res.forEach(agent => {
                html += `<option value="${agent.id}" data-user-id="${agent.user_id}">${agent.name}</option>`;
            });
            selectInput.html(html);

            if (typeof callback === 'function') {
                callback(res);
            }
        })
        .fail(() => {
            helper.alertError('Unable to load agents.');
        });
    },

    /**
     * Initialize office charge source selector (office vs agent card)
     * Wires up the cascading dropdown logic for office payment modals.
     *
     * @param {object} config - Configuration object:
     *   - sourceSelect: ID of the charge source dropdown
     *   - agentSection: ID of the agent select section div
     *   - agentSelect: ID of the agent dropdown
     *   - cardSelect: ID of the card profile dropdown
     *   - useCardCheckbox: ID of the "use card" checkbox
     *   - useAnotherCheckbox: ID of the "new card" checkbox
     *   - officeId: The office ID for loading agents
     *   - officeUserId: The office user ID for loading cards
     */
    initOfficeChargeSource(config) {
        const sourceSelect = $(`#${config.sourceSelect}`);
        const agentSection = $(`#${config.agentSection}`);
        const agentSelect = $(`#${config.agentSelect}`);
        const cardSelect = $(`#${config.cardSelect}`);

        // Load agents for this office
        Payment.loadAgentsForOffice(agentSelect, config.officeId, '-- Select Agent --');

        // Source change handler
        sourceSelect.off('change').on('change', function () {
            const source = $(this).val();
            cardSelect.empty();

            if (source === 'agent') {
                agentSection.show();
                // Don't load cards yet, wait for agent selection
            } else {
                agentSection.hide();
                agentSelect.val('');
                // Load office's own cards
                Payment.loadCardsFromChargeAPI(cardSelect, {
                    source: 'office'
                }, function (cards) {
                    if (cards.length > 0) {
                        $(`#${config.useCardCheckbox}`).prop('checked', true);
                        cardSelect.prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $(`#${config.useAnotherCheckbox}`).prop('checked', false);
                    }
                });
            }
        });

        // Agent change handler
        agentSelect.off('change').on('change', function () {
            const selectedOption = $(this).find(':selected');
            const agentUserId = selectedOption.data('user-id');
            cardSelect.empty();

            if (agentUserId) {
                Payment.loadCardsFromChargeAPI(cardSelect, {
                    agent_user_id: agentUserId,
                    source: 'agent'
                }, function (cards) {
                    if (cards.length > 0) {
                        $(`#${config.useCardCheckbox}`).prop('checked', true);
                        cardSelect.prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $(`#${config.useAnotherCheckbox}`).prop('checked', false);
                    }
                });
            }
        });
    },

    /**
     * Initialize admin charge selectors (office + agent cascading dropdowns)
     * Wires up the cascading dropdown logic for admin payment modals.
     *
     * @param {object} config - Configuration object:
     *   - officeSelect: ID of the office dropdown
     *   - agentSelect: ID of the agent dropdown
     *   - cardSelect: ID of the card profile dropdown
     *   - useCardCheckbox: ID of the "use card" checkbox
     *   - useAnotherCheckbox: ID of the "new card" checkbox
     */
    initAdminChargeSelectors(config) {
        const officeSelect = $(`#${config.officeSelect}`);
        const agentSelect = $(`#${config.agentSelect}`);
        const cardSelect = $(`#${config.cardSelect}`);

        // Load offices
        Payment.loadOfficesForAdmin(officeSelect);

        // Office change handler
        officeSelect.off('change').on('change', function () {
            const selectedOption = $(this).find(':selected');
            const officeId = $(this).val();
            const officeUserId = selectedOption.data('user-id');
            cardSelect.empty();
            agentSelect.html('<option value="">-- Office Card --</option>');

            if (officeId) {
                // Load agents for the selected office
                Payment.loadAgentsForOffice(agentSelect, officeId, '-- Office Card --');

                // Load office cards by default
                Payment.loadCardsFromChargeAPI(cardSelect, {
                    office_user_id: officeUserId,
                    source: 'office'
                }, function (cards) {
                    if (cards.length > 0) {
                        $(`#${config.useCardCheckbox}`).prop('checked', true);
                        cardSelect.prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $(`#${config.useAnotherCheckbox}`).prop('checked', false);
                    }
                });
            }
        });

        // Agent change handler
        agentSelect.off('change').on('change', function () {
            const selectedOption = $(this).find(':selected');
            const agentUserId = selectedOption.data('user-id');
            const officeUserId = officeSelect.find(':selected').data('user-id');
            cardSelect.empty();

            if (agentUserId) {
                // Load agent's cards
                Payment.loadCardsFromChargeAPI(cardSelect, {
                    agent_user_id: agentUserId,
                    source: 'agent'
                }, function (cards) {
                    if (cards.length > 0) {
                        $(`#${config.useCardCheckbox}`).prop('checked', true);
                        cardSelect.prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $(`#${config.useAnotherCheckbox}`).prop('checked', false);
                    }
                });
            } else if (officeUserId) {
                // No agent selected - show office cards
                Payment.loadCardsFromChargeAPI(cardSelect, {
                    office_user_id: officeUserId,
                    source: 'office'
                }, function (cards) {
                    if (cards.length > 0) {
                        $(`#${config.useCardCheckbox}`).prop('checked', true);
                        cardSelect.prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $(`#${config.useAnotherCheckbox}`).prop('checked', false);
                    }
                });
            }
        });
    },
}

export default Payment;
