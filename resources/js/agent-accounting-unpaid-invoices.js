import helper from './helper';
import accounting from './accounting';
import OrderDetails from './order-details';
import Payment from "./Payment";

const agentAccountingUnpaidInvoices = {

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
        window.viewOrderDetails = this.viewOrderDetails;

        this.processPayment();
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

    exportPDF() {
        $('body').on('click', '.export-invoice-pdf', () => {
            helper.showLoader();

            setTimeout(() => {
                helper.hideLoader('');
            }, 2000);
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
        agentAccountingUnpaidInvoices.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            infoCallback: function( settings, start, end, max, total, pre ) {
                return `Showing ${start} to ${end} of ${total} entries`;
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/agent/accounting/unpaid/invoices"),
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
                            ${agentAccountingUnpaidInvoices.userRole == 1 ? '<a href="#" class="text-primary edit-invoice" data-invoice-id="'+r.id+'">EDIT</a>' : ''}
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
                                <button onclick="window.sendUnpaidInvoiceEmail(${r.id})" class="width-px-125 btn btn-sm pl-4 pr-4 btn-primary rounded-pill text-white font-weight-bold">
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
                    visible: true,
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

    unpaidInvoicesInput() {
        let inputs = document.getAll(".unpaidInvoicesInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                agentAccountingUnpaidInvoices.table.fnFilter(input.value);
            });
        })
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

        if (status == agentAccountingUnpaidInvoices.status_received) {
            statusDescription = "Received";
        }

        if (status == agentAccountingUnpaidInvoices.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == agentAccountingUnpaidInvoices.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == agentAccountingUnpaidInvoices.status_scheduled) {
            statusDescription = "Scheduled";
        }

        if (status == agentAccountingUnpaidInvoices.status_completed) {
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

    form: $('#invoicePaymentForm'),
    agentUser: null,
    originalInvoiceAmount: 0,
    convenienceFeeAmount: 0,
    processPayment() {
        $('body').on('click', '.process-payment', async (e) => {
            const self = $(e.target);
            const invoiceId = self.data('invoice-id');
            let invoiceAmount = parseFloat(self.data('invoice-amount'));
            agentAccountingUnpaidInvoices.originalInvoiceAmount = invoiceAmount;

            if (invoiceId) {
                helper.showLoader();

                //Update invoice id in form
                agentAccountingUnpaidInvoices.form.find('[name="invoice_id"]').val(invoiceId);

                //Amount due
                agentAccountingUnpaidInvoices.form.find('[invoice-amount-due]').html(invoiceAmount);

                $.get(`${helper.getSiteUrl()}/accounting/unpaid/invoice/payer/${invoiceId}`)
                .done(agentUser => {
                    //console.log(user)
                    agentAccountingUnpaidInvoices.agentUser = agentUser;

                    //If agent is invoiced then apply convenience fee
                    //Also add the fee to amount due
                    if (agentUser.payment_method == 2) {
                        agentAccountingUnpaidInvoices.form.find('#convenienceFeeMessageDiv').removeClass('d-none');
                    }

                    $(`[billing-name]`).val(agentUser.name);
                    $(`[billing-address]`).val(agentUser.address);
                    $(`[billing-city]`).val(agentUser.city);
                    $(`[billing-state]`).val(agentUser.state);
                    $(`[billing-zip]`).val(agentUser.zipcode);

                    if (agentUser.authorizenet_profile_id) {
                        agentAccountingUnpaidInvoices.form.find('#use_card_profile').prop('checked', true);
                        agentAccountingUnpaidInvoices.form.find('#card_profile_select').prop('disabled', false);
                        agentAccountingUnpaidInvoices.form.find(`.form-another-card input`).prop('disabled', true);
                        agentAccountingUnpaidInvoices.form.find('#use_another_card').prop('checked', false);

                        Payment.loadCards(agentAccountingUnpaidInvoices.form.find('#card_profile_select'), agentUser.id);
                    } else {
                        agentAccountingUnpaidInvoices.form.find('#use_another_card').prop('checked', true);
                        agentAccountingUnpaidInvoices.form.find('.form-another-card input').prop('disabled', false);
                        agentAccountingUnpaidInvoices.form.find('#card_profile_select').prop('disabled', true).empty();
                        agentAccountingUnpaidInvoices.form.find('#use_card_profile').prop('checked', false);
                    }

                    agentAccountingUnpaidInvoices.form.find('[name="card_payment_amount"]').val('');

                    setTimeout(() => {
                        helper.hideLoader();

                        //Show payment modal/form modal
                        helper.openModal('invoicePaymentModal');
                    }, 1500)
                }).fail(res => {
                    helper.alertError(helper.serverErrorMessage());
                });

                //Remove required attributes to prevent error
                //An invalid form control with name='' is not focusable.
                agentAccountingUnpaidInvoices.form.find('[name="card_payment_amount"]').prop('required', true);
                agentAccountingUnpaidInvoices.form.find('[name="check_number"]').removeAttr('required');
                agentAccountingUnpaidInvoices.form.find('[name="amount"]').removeAttr('required');
            }
        });

        agentAccountingUnpaidInvoices.form.find('#card_payment_amount').on('keyup', (e) => {
            const self = $(e.target);

            if (
                agentAccountingUnpaidInvoices.agentUser.payment_method == 2
                && self.val() > 0
            ) {

                let invoiceAmount = parseFloat(self.val());

                const convenienceFeeMessageDiv = agentAccountingUnpaidInvoices.form.find('#convenienceFeeMessageDiv');
                convenienceFeeMessageDiv.removeClass('d-none');

                const convenienceFee = parseFloat(convenienceFeeMessageDiv.data('convenience-fee'));
                agentAccountingUnpaidInvoices.convenienceFeeAmount = invoiceAmount * convenienceFee / 100;
                invoiceAmount = invoiceAmount + agentAccountingUnpaidInvoices.convenienceFeeAmount;
                invoiceAmount = helper.roundToDecimal(invoiceAmount, 2);

                //agentAccountingUnpaidInvoices.form.find('[invoice-amount-due]').html(invoiceAmount);
            } /*else {
                agentAccountingUnpaidInvoices.form.find('[invoice-amount-due]')
                    .html(agentAccountingUnpaidInvoices.originalInvoiceAmount)
            }*/
        });

        agentAccountingUnpaidInvoices.form.on('submit', e => {
            //Get card info
            const cardInfo = agentAccountingUnpaidInvoices.form.find('#card_profile_select option:selected').text();
            agentAccountingUnpaidInvoices.form.find('[name="card_info"]').val(cardInfo);

            agentAccountingUnpaidInvoices.form.find('#convenience_fee_amount').val(0);

            agentAccountingUnpaidInvoices.form.find('#convenience_fee_amount').val(agentAccountingUnpaidInvoices.convenienceFeeAmount);

            helper.showLoader();
        });
    },

}

$(() => {
    agentAccountingUnpaidInvoices.init();
});

export default agentAccountingUnpaidInvoices;
