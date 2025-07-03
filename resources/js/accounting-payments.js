import helper from './helper';
import accounting from './accounting';
import OrderDetails from './order-details';

const accountingPayments = {

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
    check_payment: 0,
    card_payment: 1,
    balance_payment: 2,
    invoice_type_single_order: 1,

    init() {
        accounting.init();
        // console.log("payments");
        this.datatablePayments();
        this.accountingPaymentsInput();
        this.reversePayment();
        this.exportPDF();
        window.viewDetails = this.viewDetails;
        this.removeAgentFromInvoice();
        this.initializeDatePickers();
        this.onOfficeChage();

        this.exportPayments();

        if ('paymentAmount' in sessionStorage) {
            sessionStorage.removeItem('paymentAmount');
        }
        window.viewOrderDetails = this.viewOrderDetails;
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
                                        <td>${order.date_completed ? helper.formatDateUsa(order.date_completed) : helper.formatDateUsa(order.updated_at)}</td>
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
                                    <td>${order.date_completed ? helper.formatDateUsa(order.date_completed) : helper.formatDateUsa(order.updated_at)}</td>
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
                                    <td>${order.date_completed ? helper.formatDateUsa(order.date_completed) : helper.formatDateUsa(order.updated_at)}</td>
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
                                    <td>${order.date_completed ? helper.formatDateUsa(order.date_completed) : helper.formatDateUsa(order.updated_at)}</td>
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

    accountingPaymentsInput() {
        let inputs = document.getAll(".accountingPaymentsInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                accountingPayments.table.fnFilter(input.value);
            });
        })
    },

    async datatablePayments() {
        let tableId = '#accountingPaymentsTable';
        if (helper.isMobilePhone()) {
            tableId = '#accountingPaymentsTableMobile';
        }
        /*if (helper.isTablet()) {
            tableId = '#accountingPaymentsTableTablet';
        }*/

        helper.showLoader();

        accountingPayments.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/payments"),
            serverSide: true,
            columnDefs: [
                // { className: "text-left", targets: [0, 3] },
                // { className: "width-px-100", targets: [4] }
            ],
            columns: [
                {
                    data: "invoice.invoice_number",
                    defaultContent: "404",
                    title: "INVOICE #",
                    name: "invoice.invoice_number",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <a href="#" class="text-primary text-uppercase" onclick="window.viewDetails(${r.invoice.id})">${r.invoice.invoice_number}</a>
                        `;
                    }
                },
                {
                    data: "invoice.office_name",
                    defaultContent: "404",
                    title: "OFFICE/AGENT",
                    name: "invoice.office_name",
                    searchable: false,
                    visible: 0,
                },
                {
                    data: "invoice.agent_name",
                    defaultContent: "404",
                    title: "OFFICE/AGENT",
                    name: "invoice.agent_name",
                    searchable: false,
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

                        if (!r.invoice.agent_name) return `${r.invoice.office_name}`;
                        return `
                            <p class="m-0">${r.invoice.office_name}</p>
                            <p class="m-0">${r.invoice.agent_name}</p>
                        `;
                    }

                },
                {
                    data: "invoice.created_at",
                    defaultContent: "...",
                    title: "INV DATE",
                    name: "invoice.created_at",
                    searchable: false,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return helper.formatDateUsa(r.invoice.created_at);
                    }
                },
                {
                    data: "created_at",
                    defaultContent: "...",
                    title: "DATE PAID",
                    name: "invoice.date_paid",
                    searchable: false,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return helper.formatDateTime(r.created_at);
                    }
                },
                {
                    data: "payment_method",
                    defaultContent: "...",
                    title: "PAYMENT METHOD",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        if (r.payment_method == 0) {
                            return `
                                <span class="text-primary font-weight-bold">CHECK</span>
                                <p># ${r.check_number}</p>
                            `;
                        }else if (r.payment_method == 1) {
                            return `
                                <span class="text-primary font-weight-bold">CC:${r.card_type}</span>
                                <p>${r.card_last_four}</p>
                            `;
                        }else if (r.payment_method == 2) {
                            return `
                                <span class="text-primary font-weight-bold">Balance</span>
                            `;
                        }
                    }
                },
                {
                    // data: "",
                    defaultContent: "...",
                    title: "REVERSE PAYMENT",
                    visible: this.userRole == 1 ? true : false,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        if (r.invoice.invoice_type == accountingPayments.invoice_type_single_order) {
                            return `
                            <div class="text-center ">
                                <button
                                    class="refund-payment btn btn-sm pl-4 pr-4 btn-orange rounded-pill font-weight-bold"
                                    data-payment-id="${r.id}" data-payment-method="${r.payment_method}"
                                    data-payment-amount="${r.total}"
                                >REFUND</button>
                            </div>
                            `;
                        } else {
                            return `
                                <div class="text-center ">
                                    <button
                                        class="reverse-payment btn btn-sm pl-4 pr-4 btn-orange rounded-pill font-weight-bold"
                                        data-payment-id="${r.id}" data-payment-method="${r.payment_method}"
                                    >REVERSE</button>
                                </div>
                            `;
                        }
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
                            <a href="${helper.getSiteUrl()}/accounting/invoice-view/${r.invoice.id}" class="text-primary export-invoice-pdf">PDF</a>
                        `;
                    }
                },
            ]
        })

        setTimeout(() => {
            helper.hideLoader('');
        }, 2000);
    },

    reversePayment() {
        $('body').on('click', '.reverse-payment', (e) => {
            const self = $(e.target);
            const paymentId = self.data('payment-id');
            const paymentMethod = self.data('payment-method');

            if (paymentId) {
                helper.confirm('Reverse Payment', 'Are you sure you wish to reverse this payment?',
                    () => {
                        if (paymentMethod == accountingPayments.balance_payment) {
                            const url = `${helper.getSiteUrl()}/accounting/payments/reverse/balance/${paymentId}`;
                            helper.redirectTo(url);
                        }

                        if (paymentMethod == accountingPayments.check_payment) {
                            const url = `${helper.getSiteUrl()}/accounting/payments/reverse/check/${paymentId}`;
                            helper.redirectTo(url);
                        }

                        if (paymentMethod == accountingPayments.card_payment) {
                            //Need to give option to either refund CC or credit balance
                            let url = `${helper.getSiteUrl()}/accounting/payments/reverse/balance/${paymentId}`;
                            $('#refundToBalance').prop('href', url);

                            url = `${helper.getSiteUrl()}/accounting/payments/reverse/card/${paymentId}`;
                            $('#refundToCard').prop('href', url);

                            helper.openModal('reverseCardPaymentModal');
                        }
                    },
                    () => {}
                );
            }
        });

        $('body').on('click', '.refund-payment', (e) => {
            const self = $(e.target);
            const paymentId = self.data('payment-id');
            const paymentAmount = self.data('payment-amount');

            $('#card_refund_amount').val(paymentAmount);

            sessionStorage.removeItem('paymentAmount');
            sessionStorage.setItem('paymentAmount', paymentAmount);


            let refundUrl = `${helper.getSiteUrl()}/accounting/payments/reverse/card/partial/${paymentId}`;
            $('#reversePaymentFormSingleOrder').prop('action', refundUrl);

            helper.openModal('reverseCardPaymentModalSingleOrder');
        });

        $('body').on('change', '#card_refund_amount', (e) => {
            const self = $(e.target);
            const paymentAmount =  parseFloat(sessionStorage.getItem('paymentAmount'));

            if (
                ! self.val()
                || parseFloat(self.val()) > paymentAmount
                || parseFloat(self.val()) <= 0
            ) {
                self.val(paymentAmount);
            }
        });

        $('#reversePaymentFormSingleOrder').on('submit', e => {
            const self = $(e.target);

            self.find('#submitRefundPaymentBtn').prop('disabled', true);

            helper.showLoader();
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

    initializeDatePickers() {
        $('#from_date').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#to_date').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#from_date_excel').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#to_date_excel').datepicker({
            dateFormat: 'm/d/yy',
        });
    },

    async getAgent(office) {
        return await $.get(helper.getSiteUrl(`/office/${office}/agents/order/by/name/json`));
    },

    onOfficeChage() {
        let input = $(`[name="export_to_csv_office"]`);
        if (input.length) {
            input.on("change", async (event) => {
                let value = event.target.value;
                let agents = await this.getAgent(value);
                if (!Array.isArray(agents)) {
                    agents = Object.values(agents);
                }
                let agentsInput = $(`[name="export_to_csv_agent"]`);
                agentsInput.html("");
                agentsInput.append(window.e('option', { value: "", htmlContent: "Select Agent" }))
                agents.forEach((agent) => {
                    agentsInput.append(window.e("option", { value: agent.id, htmlContent: agent.user.lastNameFirstName, }));
                });
            });
        }
    },

    exportPayments() {
        let modal = $("#exportToCsvModal");
        let modal2 =  $("#exportToExcelModal");

        $('body').on('click', '.export-to-csv', (event) => {
            helper.showLoader();

            setTimeout(() => {
                modal.modal('hide');

                modal.on('hidden.bs.modal', () => {
                    $(`[name="export_to_csv_agent"]`).html("");
                    modal.find('form').trigger("reset");
                });

                helper.hideLoader('');
            }, 2000);
        });

        $('body').on('click', '.export-to-excel', (event) => {
            helper.showLoader();

            setTimeout(() => {
                modal2.modal('hide');

                modal2.on('hidden.bs.modal', function () {
                    $(`[name="export_to_csv_agent"]`).html("");
                    modal2.find('form').trigger("reset");
                });

                helper.hideLoader('');
            }, 2000);
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

        if (status == accountingPayments.status_received) {
            statusDescription = "Received";
        }

        if (status == accountingPayments.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == accountingPayments.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == accountingPayments.status_scheduled) {
            statusDescription = "Scheduled";
        }

        if (status == accountingPayments.status_completed) {
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
    accountingPayments.init();
});

export default accountingPayments;
