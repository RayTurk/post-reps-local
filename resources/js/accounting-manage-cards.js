import helper from './helper';
import accounting from './accounting';

const accountingManageCards = {
    init() {
        accounting.init();
        this.removeCardButton();
        this.addCard();
        this.officeToggleCardVisibility();
    },

    removeCardButton() {
        $('body').on('click', '.remove-card', (e)=> {
            const self = $(e.target);
            const paymentProfileId = self.data('payment_profile_id');
            // const invoiceId = self.data('invoice-id');

            helper.confirm('Remove Card', 'Are you sure you want to remove this card?',
                () => {
                    helper.showLoader;

                    helper.redirectTo(`${helper.getSiteUrl()}/accounting/manage-cards/remove/${paymentProfileId}`);
                },
                () => {}
            )
        });
    },

    addCard() {
        const form = $("#addCardForm");

        form.on('submit', e => {
            e.stopImmediatePropagation();
            e.preventDefault()

            form.find('#submitCardBtn').text('PROCESSING...');

            helper.showLoader();

            let data = {};
            let formData = $(e.target).serializeArray()
            formData.forEach(f => {
                if (f.name == "_token") return null;
                data[f.name] = f.value;
            })
            $.post(helper.getSiteUrl(`/accounting/manage-cards/add-card`), data)
                .done(res => {
                    console.log(res);
                    if (res.messages.resultCode == 'Error') {
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

                        form.find('#submitCardBtn').text('SUBMIT');

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

                    form.find('#submitCardBtn').text('SUBMIT');

                    helper.hideLoader('');
                })
        })
    },

    officeToggleCardVisibility() {
        $('body').on('click', '.card-visible-to-agents', (e) => {
            const self = $(e.target);

            const visibility = self.is(':checked') ? 1 : 0;
            const data = {
                visibility: visibility,
                payment_profile_id: self.data('payment-profile-id')
            };
            $.post(helper.getSiteUrl(`/accounting/manage-cards/toggle-visibility`), data)
            .done (res => {
                if (res.type == 'error') {
                    helper.alertError(res.message);
                    return false;
                }
            })
            .fail((res) => {
                helper.alertError(helper.serverErrorMessage());
            });
        });
    }
}

$(() => {
    accountingManageCards.init();
});

export default accountingManageCards;
