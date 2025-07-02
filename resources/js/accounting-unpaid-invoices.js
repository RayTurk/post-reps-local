import helper from './helper';
import accounting from './accounting';
import Payment from "./Payment";
import OrderDetails from './order-details';

const accountingUnpaidInvoices = {

    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    date: {
        asap: 1,
        desired_date_type: 2,
    },
    table: {},
    userRole: $('#userRole').val(),

    init() {
        accounting.init();
        this.datatableUpaidInvoices();
        this.unpaidInvoicesInput();
        window.viewDetails = this.viewDetails;
        this.exportPDF();
        this.processPayment();
        this.editInvoice();
        window.sendUnpaidInvoiceEmail = this.sendUnpaidInvoiceEmail;
        this.removeAgentFromInvoice();
        window.viewOrderDetails = this.viewOrderDetails;
    },

    unpaidInvoicesInput() {
        let inputs = document.getAll(".unpaidInvoicesInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                accountingUnpaidInvoices.table.fnFilter(input.value);
            });
        })
    },

    sendUnpaidInvoiceEmail(id, invoiceNumber) {
        helper.showLoader();

        $.get(`${helper.getSiteUrl()}/accounting/unpaid/invoice/${id}/email`)
        .done(res => {
            //Refresh Email history
            let title = '';
            $.each(res, (i, history) => {
                title += history.sent;
            });
            $(`.email-history[data-invoice-id="${id}"]`).prop('title', title);

            helper.hideLoader();
            helper.alertMsg('Unpaid Invoice Email', `Invoice ${invoiceNumber} emailed successfully.`);
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    viewDetails(id) {
        helper.showLoader();
        let modal = $('#invoiceDetails');
        let tableBody = $('#invoiceDetailsTable>tbody');
        let officeAgentName;
        let officeAgentAddress;
        let officeAgentState;
        let officeAgentPhone;
        let charges;
        let adjustments;
        let paid;
        let amountDue;
        let adjustmentsTotal = 0;
        let paymentsTotal = 0;
        let chargesTotal = 0;
        let processedAgents = [];
        let agentTotal = 0;
        let savedOrder;


        $.get(`${helper.getSiteUrl()}/accounting/unpaid/invoice/${id}`).done(invoice => {
            modal.find('#invoiceNumber').text(invoice.invoice_number);
            modal.find('#invoiceDate').text(helper.formatDate(invoice.created_at));
            modal.find('#invoiceDueDate').text(helper.formatDate(invoice.due_date));

            officeAgentName = modal.find("#officeAgentName");
            officeAgentAddress = modal.find("#officeAgentAddress");
            officeAgentState = modal.find("#officeAgentState");
            officeAgentPhone = modal.find("#officeAgentPhone");
            charges = modal.find("#charges");
            adjustments = modal.find("#adjustments");
            paid = modal.find("#paid");
            amountDue = modal.find("#amountDue");

            if (invoice.agent_name) {
                officeAgentName.text(invoice.agent_name);
                officeAgentAddress.text(invoice.agent_address);
                officeAgentState.text(`${invoice.agent_city}, ${invoice.agent_state}, ${invoice.agent_zipcode}`);
                officeAgentPhone.text(invoice.agent_phone);
            }else if (invoice.office_name) {
                officeAgentName.text(invoice.office_name);
                officeAgentAddress.text(invoice.office_address);
                officeAgentState.text(`${invoice.office_city}, ${invoice.office_state}, ${invoice.office_zipcode}`);
                officeAgentPhone.text(invoice.office_phone);
            }

            invoice.adjustments.forEach(adjustment => {
                adjustmentsTotal += parseFloat(adjustment.amount);
                $('#invoiceAdjustmentsDetailsTable>tbody').append(`
                    <tr>
                        <td class="">${adjustment.description}</td>
                        <td style="text-align: end">${helper.formatDateUsa(adjustment.created_at)}</td>
                        <td style="text-align: end">$ ${adjustment.amount}</td>
                    </tr>
                `);
            });

            invoice.payments.forEach(payment => {
                paymentsTotal += parseFloat(payment.total);
                $('#invoicePaymentsDetailsTable>tbody').append(`
                    <tr>
                        <td class="">${payment.payment_method == 0 ? "CHECK" : payment.payment_method == 1 ? "CC" : "BALANCE"}</td>
                        <td class="">${invoice.fully_paid == 1 ? 'Payment Received': 'Partial Payment Received'}</td>
                        <td style="text-align: end">${helper.formatDateUsa(payment.created_at)}</td>
                        <td style="text-align: end">$ ${payment.total.toFixed(2)}</td>
                    </tr>
                `);
            });

            invoice.invoice_lines.forEach(invoiceLine => {
                chargesTotal += parseFloat(invoiceLine.amount);
            });

            charges.text(`$ ${chargesTotal.toFixed(2)}`);
            adjustments.text(`$ ${adjustmentsTotal.toFixed(2)}`);
            paid.text(`$ ${paymentsTotal.toFixed(2)}`);
            amountDue.text(`$ ${Number(invoice.amount).toFixed(2)}`);

            let previousAgent = null;
            let currentAgent;
            const hasPayment = ! $.isEmptyObject(invoice.payments);
            invoice.invoice_lines.forEach((invoiceLine, index, array) => {
                switch (invoiceLine.order_type) {
                    case 1:
                        $.ajax({
                                url:`${helper.getSiteUrl()}/order/${invoiceLine.order_id}/install`,
                                type: "GET",
                                async: false,
                                success: function (order) {
                                    currentAgent = order.agent_id;

                                    if (previousAgent != null && !processedAgents.includes(order.agent_id) && !index==0) {
                                        tableBody.append(`
                                            <tr class="agent-totals">
                                                <td class=""></td>
                                                <td class=""></td>
                                                <td class=""><a href="#" class="${hasPayment ? 'd-none' : ''} btn btn-danger text-white text-decoration-none btn-sm remove-agent" data-agent-id="${order.agent_id}" data-invoice-id="${invoice.id}">Remove Agent</a></td>
                                                <td colspan="" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                                <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ ${agentTotal.toFixed(2)}</td>
                                            </tr>
                                        `);
                                    }

                                    if (!processedAgents.includes(order.agent_id)) {
                                        processedAgents.push(order.agent_id);
                                        agentTotal = 0;
                                    }

                                    let chargeDetails = order.address;
                                    let lineTotal = parseFloat(order.total).toFixed(2);
                                    if (invoiceLine.missing_items == 1) {
                                        //Add description if missing items
                                        chargeDetails = `${order.address}: ${invoiceLine.description}`;

                                        //Use invoice line total if missing items
                                        lineTotal = parseFloat(invoiceLine.amount).toFixed(2);
                                    }

                                    tableBody.append(`
                                    <tr>
                                        <td><a href="#" class="text-primary text-uppercase" onclick="window.viewOrderDetails(${order.id}, 'install')">${order.order_number}</a></td>
                                        <td><span class=""><u>INSTALL: </u></span> ${chargeDetails}</td>
                                        <td>
                                            ${order.agent ? order.agent.user.name : ''}<br>
                                            ${order.agent ? order.agent.user.phone : ''}
                                        </td>
                                        <td>${helper.formatDateUsa(order.date_completed)}</td>
                                        <td class="text-right" >$ ${lineTotal}</td>
                                    </tr>
                                    `);

                                    savedOrder = order;

                                    previousAgent = order.agent_id;
                                },
                            });

                        break;

                    case 2:
                        $.ajax({
                            url:`${helper.getSiteUrl()}/order/${invoiceLine.order_id}/repair`,
                            type: "GET",
                            async: false,
                            success: function (order) {
                                currentAgent = order.order.agent_id;

                                if (previousAgent != null && !processedAgents.includes(order.order.agent_id) && !index==0) {
                                    tableBody.append(`
                                        <tr class="agent-totals">
                                            <td class=""></td>
                                            <td class=""></td>
                                            <td class=""><a href="#" class="${hasPayment ? 'd-none' : ''} btn btn-danger text-white text-decoration-none btn-sm remove-agent" data-agent-id="${order.order.agent_id}" data-invoice-id="${invoice.id}">Remove Agent</a></td>
                                            <td colspan="" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                            <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ ${agentTotal.toFixed(2)}</td>
                                        </tr>
                                    `);
                                }

                                if (!processedAgents.includes(order.order.agent_id)) {
                                    processedAgents.push(order.order.agent_id);
                                    agentTotal = 0;
                                }

                                let chargeDetails = order.order.address;
                                let lineTotal = parseFloat(order.total).toFixed(2);
                                if (invoiceLine.missing_items == 1) {
                                    //Add description if missing items
                                    chargeDetails = `${order.order.address}: ${invoiceLine.description}`;

                                    //Use invoice line total if missing items
                                    lineTotal = parseFloat(invoiceLine.amount).toFixed(2);
                                }

                                tableBody.append(`
                                <tr>
                                    <td><a href="#" class="text-primary text-uppercase" onclick="window.viewOrderDetails(${order.id}, 'repair')">${order.order_number}</a></td>
                                    <td><span class=""><u>REPAIR: </u></span> ${chargeDetails}</td>
                                    <td>
                                        ${order.order.agent ? order.order.agent.user.name : ''}<br>
                                        ${order.order.agent ? order.order.agent.user.phone : ''}
                                    </td>
                                    <td>${helper.formatDateUsa(order.date_completed)}</td>
                                    <td class="text-right" >$ ${lineTotal}</td>
                                </tr>
                                `);

                                savedOrder = order;
                                previousAgent = order.order.agent_id;
                            },
                        });

                        break;

                    case 3:
                        $.ajax({
                            url:`${helper.getSiteUrl()}/order/${invoiceLine.order_id}/removal`,
                            type: "GET",
                            async: false,
                            success: function (order) {
                                currentAgent = order.order.agent_id;

                                if (previousAgent != null && !processedAgents.includes(order.order.agent_id) && !index==0) {
                                    tableBody.append(`
                                        <tr class="agent-totals">
                                            <td class=""></td>
                                            <td class=""></td>
                                            <td class=""><a href="#" class="${hasPayment ? 'd-none' : ''} btn btn-danger text-white text-decoration-none btn-sm remove-agent" data-agent-id="${order.order.agent_id}" data-invoice-id="${invoice.id}">Remove Agent</a></td>
                                            <td colspan="" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                            <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ ${agentTotal.toFixed(2)}</td>
                                        </tr>
                                    `);
                                }

                                if (!processedAgents.includes(order.order.agent_id)) {
                                    processedAgents.push(order.order.agent_id);
                                    agentTotal = 0;
                                }

                                let chargeDetails = order.order.address;
                                let lineTotal = parseFloat(order.total).toFixed(2);
                                if (invoiceLine.missing_items == 1) {
                                    //Add description if missing items
                                    chargeDetails = `${order.order.address}: ${invoiceLine.description}`;

                                    //Use invoice line total if missing items
                                    lineTotal = parseFloat(invoiceLine.amount).toFixed(2);
                                }

                                tableBody.append(`
                                <tr>
                                    <td><a href="#" class="text-primary text-uppercase" onclick="window.viewOrderDetails(${order.id}, 'removal')">${order.order_number}</a></td>
                                    <td><span class=""><u>REMOVAL: </u></span> ${chargeDetails}</td>
                                    <td>
                                        ${order.order.agent ? order.order.agent.user.name : ''}<br>
                                        ${order.order.agent ? order.order.agent.user.phone : ''}
                                    </td>
                                    <td>${helper.formatDateUsa(order.date_completed)}</td>
                                    <td class="text-right" >$ ${lineTotal}</td>
                                </tr>
                                `);

                                savedOrder = order;
                                previousAgent = order.order.agent_id;
                            },
                        });
                        break;

                    case 4:
                        $.ajax({
                            url:`${helper.getSiteUrl()}/order/${invoiceLine.order_id}/delivery`,
                            type: "GET",
                            async: false,
                            success: function (order) {
                                currentAgent = order.agent_id;

                                if (previousAgent != null && !processedAgents.includes(order.agent_id) && !index==0) {
                                    tableBody.append(`
                                        <tr class="agent-totals">
                                            <td class=""></td>
                                            <td class=""></td>
                                            <td class=""><a href="#" class="${hasPayment ? 'd-none' : ''} btn btn-danger text-white text-decoration-none btn-sm remove-agent" data-agent-id="${order.agent_id}" data-invoice-id="${invoice.id}">Remove Agent</a></td>
                                            <td colspan="" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                            <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ ${agentTotal.toFixed(2)}</td>
                                        </tr>
                                    `);
                                }

                                if (!processedAgents.includes(order.agent_id)) {
                                    processedAgents.push(order.agent_id);
                                    agentTotal = 0;
                                }

                                let chargeDetails = order.address;
                                let lineTotal = parseFloat(order.total).toFixed(2);
                                if (invoiceLine.missing_items == 1) {
                                    //Add description if missing items
                                    chargeDetails = `${order.address}: ${invoiceLine.description}`;

                                    //Use invoice line total if missing items
                                    lineTotal = parseFloat(invoiceLine.amount).toFixed(2);
                                }

                                tableBody.append(`
                                <tr>
                                    <td><a href="#" class="text-primary text-uppercase" onclick="window.viewOrderDetails(${order.id}, 'delivery')">${order.order_number}</a></td>
                                    <td><span class=""><u>DELIVERY: </u></span> ${chargeDetails}</td>
                                    <td>
                                        ${order.agent ? order.agent.user.name : ''}<br>
                                        ${order.agent ? order.agent.user.phone : ''}
                                    </td>
                                    <td>${helper.formatDateUsa(order.date_completed)}</td>
                                    <td class="text-right" >$ ${lineTotal}</td>
                                </tr>
                                `);

                                savedOrder = order;
                                previousAgent = order.agent_id;
                            },
                        });
                        break;

                    default:
                        break;
                }


                if (invoiceLine.missing_items == 1) {
                    agentTotal += parseFloat(invoiceLine.amount);
                } else {
                    agentTotal += parseFloat(savedOrder.total);
                }

                if (previousAgent != null && index == array.length - 1) {
                    $('#invoiceDetailsTable > tbody:last-child').append(`
                        <tr class="agent-totals-last">
                            <td class=""></td>
                            <td class=""></td>
                            <td class=""><a href="#" class="${hasPayment ? 'd-none' : ''} btn btn-danger text-white text-decoration-none btn-sm remove-agent" data-agent-id="${currentAgent}" data-invoice-id="${invoice.id}">Remove Agent</a></td>
                            <td colspan="" class="text-right" >AGENT TOTALS:</td>
                            <td class="text-right">$ ${agentTotal.toFixed(2)}</td>
                        </tr>
                    `);
                }

                if (index == array.length - 1) {
                    helper.hideLoader('invoiceDetails');
                }
            });
        });

        modal.on('hidden.bs.modal', function (event) {
            $(this).removeData();
            tableBody.html("");
            $('#invoiceAdjustmentsDetailsTable>tbody').html("");
            $('#invoicePaymentsDetailsTable>tbody').html("");
        });
    },

    async datatableUpaidInvoices() {
        let tableId = '#unpaidInvoicesTable';
        if (helper.isMobilePhone()) {
            tableId = '#unpaidInvoicesTableMobile';
        }
        /*if (helper.isTablet()) {
            tableId = '#unpaidInvoicesTableTablet';
        }*/

        //$("#loader_image").modal('show');
        accountingUnpaidInvoices.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/unpaid/invoices"),
            serverSide: true,
            columnDefs: [
                // { className: "text-left", targets: [0, 3] },
                // { className: "width-px-100", targets: [4] }
            ],
            columns: [
                {
                    data: "invoice_number",
                    defaultContent: "404",
                    title: "INVOICE #",
                    name: "invoice_number",
                    visible: 1,
                    searchable: true,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <a href="#" class="text-primary text-uppercase" onclick="window.viewDetails(${r.id})">${r.invoice_number}</a>
                        `;
                    }
                },
                {
                    data: "office_name",
                    defaultContent: "404",
                    title: "OFFICE/AGENT",
                    name: "office_name",
                    searchable: true,
                    visible: 0,
                },
                {
                    data: "agent_name",
                    defaultContent: "404",
                    title: "OFFICE/AGENT",
                    name: "agent_name",
                    searchable: true,
                    visible: 0,
                },
                {
                    data: "",
                    defaultContent: "404",
                    title: "OFFICE/AGENT",
                    name: "",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {

                        if (!r.agent_name) return `${r.office_name}`;
                        return `
                            <p class="m-0">${r.office_name}</p>
                            <p class="m-0">${r.agent_name}</p>
                        `;
                    }

                },
                {
                    data: "created_at",
                    defaultContent: "...",
                    title: "DATE",
                    name: "created_at",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return helper.formatDateUsa(r.created_at);
                    }
                },
                {
                    data: "due_date",
                    defaultContent: "...",
                    title: "DUE",
                    name: "due_date",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return helper.formatDateUsa(r.due_date);
                    }
                },
                {
                    data: "total",
                    defaultContent: "404",
                    title: "AMOUNT",
                    name: "amount",
                    visible: 1,
                    searchable: true,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <div class="text-center d-flex flex-column">
                                $ ${r.amount}
                            </div>
                            ${accountingUnpaidInvoices.userRole == 1 ? '<a href="#" class="text-primary edit-invoice" data-invoice-id="'+r.id+'">EDIT</a>' : ''}
                        `;
                    }
                },
                {
                    // data: "address",
                    defaultContent: "...",
                    title: "EMAIL NOTICE",
                    visible: this.userRole == 1 ? true : false,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <div class="text-center">
                                <button onclick="window.sendUnpaidInvoiceEmail(${r.id}, '${r.invoice_number}')" class="width-px-125 btn btn-sm pl-4 pr-4 btn-primary rounded-pill text-white font-weight-bold">
                                    SEND EMAIL
                                </button>
                            </div>
                            <a href="#" class="text-primary email-history" title="${r.history}" data-invoice-id="${r.id}">
                                HISTORY
                            </a>
                        `;
                    }
                },
                {
                    // data: "",
                    defaultContent: "...",
                    title: "PROCESS PAYMENT",
                    visible: this.userRole == 1 ? true : false,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <div class="text-center ">
                                <button
                                    class="process-payment btn btn-sm pl-4 pr-4 btn-orange rounded-pill font-weight-bold"
                                    data-invoice-id="${r.id}" data-invoice-amount="${r.amount}"
                                >PROCESS PAYMENT</button>
                            </div>
                        `;
                    }
                },
                {
                    // data: "",
                    defaultContent: "...",
                    title: "EXPORT",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <a href="${helper.getSiteUrl()}/accounting/invoice-view/${r.id}" class="text-primary export-invoice-pdf">PDF</a>
                        `;
                    }
                },
            ]
        })

        //helper.hideLoader('');
    },

    exportPDF() {
        $('body').on('click', '.export-invoice-pdf', () => {
            helper.showLoader();

            setTimeout(() => {
                helper.hideLoader('');
            }, 2000);
        });
    },

    form: $('#invoicePaymentForm'),
    processPayment() {
        $('body').on('click', '.process-payment', async (e) => {
            const self = $(e.target);
            const invoiceId = self.data('invoice-id');
            const invoiceAmount = self.data('invoice-amount');

            if (invoiceId) {
                const user =  await $.get(`${helper.getSiteUrl()}/accounting/unpaid/invoice/payer/${invoiceId}`);

                if (user) {
                    if (user.balance > 0) { //Pay with user balance if any
                        accountingUnpaidInvoices.form.find('[name="payment_method"]').val('balance');
                        accountingUnpaidInvoices.form.find('[name="invoice_id"]').val(invoiceId);
                        accountingUnpaidInvoices.form.trigger('submit');
                    } else {
                        //Update invoice id in form
                        accountingUnpaidInvoices.form.find('[name="invoice_id"]').val(invoiceId);

                        //Activate chekc tab by default
                        accountingUnpaidInvoices.form.find('#check-tab').trigger('click');

                        //Amount due
                        accountingUnpaidInvoices.form.find('[invoice-amount-due]').html(invoiceAmount);

                        //Remove required attributes to prevent error
                        //An invalid form control with name='' is not focusable.
                        accountingUnpaidInvoices.form.find('[name="card_payment_amount"]').removeAttr('required');
                        accountingUnpaidInvoices.form.find('[name="check_number"]').prop('required', true);
                        accountingUnpaidInvoices.form.find('[name="amount"]').prop('required', true);

                        //Show payment modal/form modal
                        helper.openModal('invoicePaymentModal');
                    }
                }
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', (e) => {
            const self = $(e.target);
            const tab = self.attr('pay-method');

            accountingUnpaidInvoices.form.find('[name="payment_method"]').val(tab);

            //Load card details if card selected
            if (tab == 'card') {
                helper.showLoader();
                //Need to get office or agent who needs to pay for the invoice
                const invoiceId = accountingUnpaidInvoices.form.find('[name="invoice_id"]').val();

                $.get(`${helper.getSiteUrl()}/accounting/unpaid/invoice/payer/${invoiceId}`)
                .done(user => {
                    // console.log(user)

                    $(`[billing-name]`).val(user.name);
                    $(`[billing-address]`).val(user.address);
                    $(`[billing-city]`).val(user.city);
                    $(`[billing-state]`).val(user.state);
                    $(`[billing-zip]`).val(user.zipcode);

                    accountingUnpaidInvoices.form.find('#use_another_card').prop('checked', true);
                    accountingUnpaidInvoices.form.find('.form-another-card input').prop('disabled', false);
                    accountingUnpaidInvoices.form.find('#card_profile_select').prop('disabled', true);

                    if (user.authorizenet_profile_id) {
                        accountingUnpaidInvoices.form.find('#use_card_profile').prop('checked', true);
                        accountingUnpaidInvoices.form.find('#card_profile_select').prop('disabled', false);
                        accountingUnpaidInvoices.form.find(`.form-another-card input`).prop('disabled', true);
                        accountingUnpaidInvoices.form.find('#use_another_card').prop('checked', false);

                        Payment.loadCards(accountingUnpaidInvoices.form.find('#card_profile_select'), user.id);
                    }
                    setTimeout(() => {
                        helper.hideLoader();
                    }, 1500)
                }).fail(res => {
                    helper.alertError(helper.serverErrorMessage());
                });

                //Remove required attributes to prevent error
                //An invalid form control with name='' is not focusable.
                accountingUnpaidInvoices.form.find('[name="card_payment_amount"]').prop('required', true);
                accountingUnpaidInvoices.form.find('[name="check_number"]').removeAttr('required');
                accountingUnpaidInvoices.form.find('[name="amount"]').removeAttr('required');
            }

            if (tab == 'check') {
                accountingUnpaidInvoices.form.find('[name="card_payment_amount"]').removeAttr('required');
                accountingUnpaidInvoices.form.find('[name="check_number"]').prop('required', true);
                accountingUnpaidInvoices.form.find('[name="amount"]').prop('required', true);
            }
        });

        accountingUnpaidInvoices.form.on('submit', e => {
            const tab = accountingUnpaidInvoices.form.find('[name="payment_method"]').val();

            const amountDue = parseFloat(accountingUnpaidInvoices.form.find('[invoice-amount-due]').html());
            //console.log(amountDue); return false;

            /*if (tab == 'card') {
                const paymentAmount = accountingUnpaidInvoices.form.find('[name="card_payment_amount"]').val();

                if (paymentAmount <= 0 || paymentAmount > amountDue) {
                    helper.alertError('Invalid payment amount!');
                    return false;
                }
            }

            if (tab == 'check') {
                const paymentAmount = accountingUnpaidInvoices.form.find('[name="amount"]').val();

                if (paymentAmount <= 0 || paymentAmount > amountDue) {
                    helper.alertError('Invalid payment amount!');
                    return false;
                }
            }*/

            //Get card info
            const cardInfo = accountingUnpaidInvoices.form.find('#card_profile_select option:selected').text();
            accountingUnpaidInvoices.form.find('[name="card_info"]').val(cardInfo);

            helper.showLoader();
        });
    },

    invoiceAdjustments: {
        description: [],
        charge: [],
        discount: []
    },
    rowCount: 0,
    adjustmentForm: $('#adjustmentForm'),
    editInvoice() {
        const rowTmpl = $('#rowTmplInvoiceAdjustment').html();
        const rowContainer = $('#rowContainerInvoiceAdjustments');

        $('body').on('click', '.edit-invoice', (e) => {
            const self = $(e.target);
            const invoiceId = self.data('invoice-id');

            if (invoiceId) {
                accountingUnpaidInvoices.adjustmentForm.find('[name="invoice_id"]').val(invoiceId);

                if (accountingUnpaidInvoices.rowCount == 0) {
                    accountingUnpaidInvoices.rowCount++;
                    let newTmpl = rowTmpl.replace(/rowCount/g, accountingUnpaidInvoices.rowCount);
                    rowContainer.empty().append(newTmpl);
                }

                helper.openModal('invoiceAdjustmentModal');
            }
        });

        $('#closeInvoiceAdjustmentModalBtn').on('click', ()=> {
            helper.closeModal('invoiceAdjustmentModal');
        });

        $('#addInvoiceAdjustmentBtn').on('click', ()=> {
            accountingUnpaidInvoices.rowCount++;

            let newTmpl = rowTmpl.replace(/rowCount/g, accountingUnpaidInvoices.rowCount);
            rowContainer.append(newTmpl);
        });

        $('body').on('click', '.remove-invoice-adjustment-row', (e)=> {
            const self = $(e.target);

            self.closest('.row').remove();
            accountingUnpaidInvoices.rowCount--;
        });

        accountingUnpaidInvoices.adjustmentForm.on('submit', (e) => {
            const self = $(e.target);

            helper.showLoader();
        });
    },

    resetPricingAdjustment() {
        DeliveryOrder.invoiceAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
    },

    removeAgentFromInvoice() {
        $('body').on('click', '.remove-agent', (e)=> {
            const self = $(e.target);
            const agentId = self.data('agent-id');
            const invoiceId = self.data('invoice-id');

            helper.confirm('Remove Agent', 'Are you sure you wish to remove this agent?',
                () => {
                    helper.showLoader;

                    helper.redirectTo(`${helper.getSiteUrl()}/accounting/unpaid/invoice/remove/agent/${agentId}/${invoiceId}`);
                },
                () => {}
            )
        });
    },

    viewOrderDetails(orderId, orderType) {
        if (!orderId || !orderType) {
            return false;
        }

        if (orderType == 'delivery') {
            OrderDetails.viewDeliveryDetails(orderId, orderType);
        }

        if (orderType == 'install') {
            OrderDetails.viewInstallDetails(orderId, orderType);
        }

        if (orderType == 'repair') {
            OrderDetails.viewRepairDetails(orderId, orderType);
        }

        if (orderType == 'removal') {
            OrderDetails.viewRemovalDetails(orderId, orderType);
        }
    },

    getStatus(status, orderType) {
        let statusDescription = '';

        if (status == accountingUnpaidInvoices.status_received) {
            statusDescription = "Received";
        }

        if (status == accountingUnpaidInvoices.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == accountingUnpaidInvoices.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == accountingUnpaidInvoices.status_scheduled) {
            statusDescription = "Scheduled";
        }

        if (status == accountingUnpaidInvoices.status_completed) {
            if (orderType == 'install') {
                statusDescription = "Installed";
            }
            if (orderType == 'repair') {
                statusDescription = "Repaired";
            }
            if (orderType == 'removal') {
                statusDescription = "Removed";
            }
            if (orderType == 'delivery') {
                statusDescription = "Delivered";
            }
        }

        return statusDescription;
    },
};

$(() => {
    accountingUnpaidInvoices.init();
});

export default accountingUnpaidInvoices;
