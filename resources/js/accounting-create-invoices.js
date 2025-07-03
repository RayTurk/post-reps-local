import helper from './helper';
import accounting from './accounting';
import OrderDetails from './order-details';

const accountingCreateInvoices = {

    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    date: {
        asap: 1,
        desired_date_type: 2,
    },
    userRole: 0,
    table: {},

    init() {
        accounting.init();
        this.initializeDatePickers();
        this.onRadiosChange();
        this.onOfficeChage();
        this.datatableUpaidInvoices();
        this.createInvoicesInput();
        this.onFormSubmit();

        window.viewDetails = this.viewDetails;
    },

    onRadiosChange() {
        //desktop view
        if($('#allAccountsRadio').is(':checked')) {
            $("[name=create_invoice_office]").attr("disabled", "disabled");
            $("[name=create_invoice_agent]").attr("disabled", "disabled");
        }

        $('#allAccountsRadio').on("click", function() {
            if($('#allAccountsRadio').is(':checked')) {
                $("[name=create_invoice_office]").attr("disabled", "disabled");
                $("[name=create_invoice_agent]").attr("disabled", "disabled");
            }
        });

        $('#individualAccountRadio').on("click", function() {
            if($('#individualAccountRadio').is(':checked')) {
                $("[name=create_invoice_office]").removeAttr("disabled", "disabled");
                $("[name=create_invoice_agent]").removeAttr("disabled", "disabled");
            }
        });

        //mobile view
        if($('#allAccountsRadioMobile').is(':checked')) {
            $("[name=create_invoice_office]").attr("disabled", "disabled");
            $("[name=create_invoice_agent]").attr("disabled", "disabled");
        }

        $('#allAccountsRadioMobile').on("click", function() {
            if($('#allAccountsRadioMobile').is(':checked')) {
                $("[name=create_invoice_office]").attr("disabled", "disabled");
                $("[name=create_invoice_agent]").attr("disabled", "disabled");
            }
        });

        $('#individualAccountRadioMobile').on("click", function() {
            if($('#individualAccountRadioMobile').is(':checked')) {
                $("[name=create_invoice_office]").removeAttr("disabled", "disabled");
                $("[name=create_invoice_agent]").removeAttr("disabled", "disabled");
            }
        });

        //tablet view
        if($('#allAccountsRadioTablet').is(':checked')) {
            $("[name=create_invoice_office]").attr("disabled", "disabled");
            $("[name=create_invoice_agent]").attr("disabled", "disabled");
        }

        $('#allAccountsRadioTablet').on("click", function() {
            if($('#allAccountsRadioTablet').is(':checked')) {
                $("[name=create_invoice_office]").attr("disabled", "disabled");
                $("[name=create_invoice_agent]").attr("disabled", "disabled");
            }
        });

        $('#individualAccountRadioTablet').on("click", function() {
            if($('#individualAccountRadioTablet').is(':checked')) {
                $("[name=create_invoice_office]").removeAttr("disabled", "disabled");
                $("[name=create_invoice_agent]").removeAttr("disabled", "disabled");
            }
        });
    },

    initializeDatePickers() {
        $('#from_date').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#to_date').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#from_date_mobile').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#to_date_mobile').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#from_date_tablet').datepicker({
            dateFormat: 'm/d/yy',
        });
        $('#to_date_tablet').datepicker({
            dateFormat: 'm/d/yy',
        });
    },

    async getAgent(office) {
        return await $.get(helper.getSiteUrl(`/office/${office}/agents/order/by/name/json`));
    },

    onOfficeChage() {
        let input = $(`[name="create_invoice_office"]`);
        if (input.length) {
            input.on("change", async (event) => {
                let value = event.target.value;
                let agents = await this.getAgent(value);
                if (!Array.isArray(agents)) {
                    agents = Object.values(agents);
                }
                let agentsInput = $(`[name="create_invoice_agent"]`);
                agentsInput.html("");
                agentsInput.append(window.e('option', { value: "", htmlContent: "Select Agent" }))
                agents.forEach((agent) => {
                    agentsInput.append(window.e("option", { value: agent.id, htmlContent: agent.user.lastNameFirstName, }));
                });
            });
        }
    },

    createInvoicesInput() {
        let inputs = document.getAll(".createInvoicesInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                accountingCreateInvoices.table.fnFilter(input.value);
            });
        })
    },

    async datatableUpaidInvoices() {

        let tableId = '#createInvoicesTable';
        if (helper.isMobilePhone()) {
            tableId = '#createInvoicesTableMobile';
        }
        /*if (helper.isTablet()) {
            tableId = '#createInvoicesTableTablet';
        }*/

        accountingCreateInvoices.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/create/invoices"),
            serverSide: true,
            columnDefs: [
                // { className: "text-left", targets: [0, 3] },
                // { className: "width-px-100", targets: [4] }
            ],
            columns: [
                {
                    data: "order_number",
                    defaultContent: "404",
                    title: "ORDER ID",
                    name: "order_number",
                    visible: 1,
                    searchable: true,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <a href="#" class="text-primary text-uppercase" onclick="window.viewDetails(${r.order_id}, '${r.order_type}')">${r.order_number}</a>
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
                            <p class="m-0">${r.office_name} - ${r.agent_name}</p>
                        `;
                    }

                },
                {
                    data: "created_at",
                    defaultContent: "...",
                    title: "ORDER DATE",
                    name: "created_at",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return helper.formatDateUsa(r.created_at);
                    }
                },
                {
                    data: "order_total",
                    defaultContent: "404",
                    title: "ORDER TOTAL",
                    name: "order_total",
                    visible: 1,
                    searchable: true,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <div class="text-center d-flex flex-column">
                                $${r.order_total}
                            </div>
                        `;
                    }
                }
            ]
        })

    },

    onFormSubmit() {
        $('.create-invoice-form').on('submit', (e) => {
            helper.showLoader();
        });
    },

    getStatus(status, orderType) {
        let statusDescription = '';

        if (status == accountingCreateInvoices.status_received) {
            statusDescription = "Received";
        }

        if (status == accountingCreateInvoices.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == accountingCreateInvoices.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == accountingCreateInvoices.status_scheduled) {
            statusDescription = "Schedulled";
        }

        if (status == accountingCreateInvoices.status_completed) {
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

    viewDetails(orderId, orderType) {
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

};

$(() => {
    accountingCreateInvoices.init();
});

export default accountingCreateInvoices;
