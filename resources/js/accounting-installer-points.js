import helper from './helper';
import accounting from './accounting';
import OrderDetails from './order-details';

const accountingInstallerPoints = {

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
    paymentsTable: {},
    status: {
        cancelled: 1,
    },
    url: helper.getSiteUrl(`/datatable/installer-points?installer_name=${$(`[name=installer_select] option:selected`).text()}`),
    urlPayments: helper.getSiteUrl(`/datatable/installer-payments?installer_name=${$(`[name=installer_payment_select] option:selected`).text()}`),
    payment: null,

    init() {
        accounting.init();
        this.datatable();
        this.paymentsDatatable();
        this.onInstallerChange();
        this.onInstallerChangeMobile();
        this.installerPointsInput();
        this.installerPaymentsInput();
        this.addPaymentModal();
        this.onInstallerPaymentChange();
        this.onTableLoad();
        this.onPaymentsTableLoad();

        window.editPointsModal = this.editPointsModal;
        window.onEditPayment = this.onEditPayment;
        window.onCancelPayment = this.onCancelPayment;
        window.viewOrderDetails = this.viewOrderDetails;
    },

    onInstallerChange() {
        let select = $(`[name=installer_select]`);
        if (select.length) {
            select.on("change", () => {

                helper.showLoader();

                let inputs = document.getAll(".installerPointsInput");
                inputs.forEach(input => {
                    input = $(input)
                    input.val("");
                })

                let name = $(`[name=installer_select] option:selected`).text();

                accountingInstallerPoints.url = helper.getSiteUrl(`/datatable/installer-points?installer_name=${encodeURIComponent(name)}`);

                accountingInstallerPoints.table.ajax.url(accountingInstallerPoints.url).load();

                helper.hideLoader();
            });
        }
    },

    onInstallerChangeMobile() {
        let select = $(`[name=installer_select_mobile]`);
        if (select.length) {
            select.on("change", () => {

                helper.showLoader();

                let inputs = document.getAll(".installerPointsInput");
                inputs.forEach(input => {
                    input = $(input)
                    input.val("");
                })

                let name = $(`[name=installer_select_mobile] option:selected`).text();

                accountingInstallerPoints.url = helper.getSiteUrl(`/datatable/installer-points?installer_name=${encodeURIComponent(name)}`);

                accountingInstallerPoints.table.ajax.url(accountingInstallerPoints.url).load();

                helper.hideLoader();
            });
        }
    },

    installerPointsInput() {
        let inputs = document.getAll(".installerPointsInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                accountingInstallerPoints.table.search(input.value).draw();
            });
        })
    },

    installerPaymentsInput() {
        let inputs = document.getAll(".installerPaymentsInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                accountingInstallerPoints.paymentsTable.search(input.value).draw();
            });
        })
    },

    editPointsModal(type, id, points) {
        let adjustmentForm = $("#pointsAdjustmentForm");

        adjustmentForm.prop('action', `${helper.getSiteUrl()}/accounting/installer-points/edit/${type}/${id}`);

        $("#pointsAdjustment").val(points);

        helper.openModal('editPointsModal');
    },

    addPaymentModal() {
        let button = $("#submitPaymentButton");
        let form = $("#addPaymentForm");

        button.on('click', (event) => {

            event.preventDefault();

            helper.showLoader();
            $("#userId").val($("#installerPaymentSelect").val());

            $.ajax({
                method: "POST",
                url: `${helper.getSiteUrl()}/accounting/installer-payments/create`,
                data: form.serialize(),
            }).done((response) => {
                form.trigger("reset");
                accountingInstallerPoints.paymentsTable.ajax.reload();
                helper.closeModal('addPaymentModal');
                return helper.alertMsg("Success", response);
            }).fail((error) => {
                if(error.responseJSON) {
                    let errorDiv = $('<div></div>');
                    $.each(error.responseJSON.errors, function(key,value) {
                        errorDiv.append('<div class="text-danger">'+value+'</div');
                    });
                    return helper.alertError(errorDiv);
                }

                return helper.alertError(error.responseText);
            });

            helper.hideLoader();
        });

    },

    onInstallerPaymentChange() {
        let select = $(`[name=installer_payment_select]`);
        $("#addPaymentButton").hide();

        if (select.length) {
            select.on("change", () => {

                helper.showLoader();

                if(select.val() != ""){
                    $("#addPaymentButton").show();
                }else {
                    $("#addPaymentButton").hide();
                }

                let inputs = document.getAll(".installerPaymentsInput");
                inputs.forEach(input => {
                    input = $(input)
                    input.val("");
                })

                let name = $(`[name=installer_payment_select] option:selected`).text();

                accountingInstallerPoints.urlPayments = helper.getSiteUrl(`/datatable/installer-payments?installer_name=${encodeURIComponent(name)}`);

                accountingInstallerPoints.paymentsTable.ajax.url(accountingInstallerPoints.urlPayments).load();

                helper.hideLoader();
            });
        }
    },

    onEditPayment(id) {

        let modal = $("#editPaymentModal");
        let form = $("#editPaymentForm");
        let button = $("#submitEditPaymentButton");

        $.ajax({
            method: "GET",
            url: `${helper.getSiteUrl()}/accounting/installer-payments/${id}`,
        }).done((response) => {
            this.payment = response;
            modal.find("#paymentAmountEdit").val(response.amount);
            modal.find("#paymentCheckNumberEdit").val(response.check_number);
            modal.find("#paymentCommentEdit").val(response.comments);
        }).fail((error) => {

        });

        helper.openModal("editPaymentModal");

        modal.on('hide.bs.modal', (event) => {
            $("#editPaymentForm").trigger("reset");
        });

        button.on('click', (event) => {

            event.preventDefault();

            $.ajax({
                method: "POST",
                url: `${helper.getSiteUrl()}/accounting/installer-payments/edit/${this.payment.id}`,
                data: {
                    user_id: this.payment.user_id,
                    payment_amount: $("#paymentAmountEdit").val(),
                    payment_check_number: $("#paymentCheckNumberEdit").val(),
                    payment_comments: $("#paymentCommentEdit").val()
                },
            }).done((response) => {
                form.trigger("reset");
                helper.closeModal("editPaymentModal");
                accountingInstallerPoints.paymentsTable.ajax.reload();
                return helper.alertMsg("Success", response);
            }).fail((error) => {
                if(error.responseJSON) {
                    let errorDiv = $('<div></div>');
                    $.each(error.responseJSON.errors, function(key,value) {
                        errorDiv.append('<div class="text-danger">'+value+'</div');
                    });
                    return helper.alertError(errorDiv);
                }

                return helper.alertError(error.responseText);
            });

        });

    },

    onCancelPayment(id) {
        helper.confirm("Cancel Payment", "Are you sure you want to cancel this payment?", () => {
            $.ajax({
                method: "POST",
                url: `${helper.getSiteUrl()}/accounting/installer-payments/cancel/${id}`,
            }).done((response) => {
                accountingInstallerPoints.paymentsTable.ajax.reload();
                return helper.alertMsg("Success", response);
            }).fail((error) => {
                return helper.alertError(error);
            });
        }, () => {});
    },

    datatable() {

        //helper.showLoader();

        let tableId = '#installerPointsTable';
        if (helper.isMobilePhone()) {
            tableId = '#installerPointsTableMobile';
        }
        /*if (helper.isTablet()) {
            tableId = '#installerPointsTableTablet';
        }*/

        accountingInstallerPoints.table = $(tableId).DataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            },
            pageLength: 10,
            dom: "rtip",
            ajax: accountingInstallerPoints.url,
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
                            <p class="m-0"><a href="#" class="text-primary text-uppercase" onclick="window.viewOrderDetails(${r.id}, '${r.order_type}')">${r.order_number}</a></a>
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
                    data: "date_completed",
                    defaultContent: "404",
                    title: "DATE COMPLETED",
                    name: "date_completed",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        if (r.date_completed) return helper.formatDateTime(r.date_completed);
                        return ``;
                    }

                },
                {
                    data: "address",
                    defaultContent: "404",
                    title: "ADDRESS",
                    name: "address",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                },
                {
                    data: "order_type",
                    defaultContent: "404",
                    title: "TYPE",
                    name: "order_type",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return `
                            <p class="text-capitalize">${r.order_type}</p>
                        `;
                    }
                },
                {
                    data: "installer_points",
                    defaultContent: "...",
                    title: "POINTS",
                    name: "installer_points",
                    searchable: false,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return `
                            <p class="m-0">${r.installer_points}</p>
                            <a href="" role="button" class="text-info font-weight-bold text-decoration-none" onclick="event.preventDefault(); window.editPointsModal('${r.order_type}', ${r.id}, ${r.installer_points})">Edit</a>
                        `;
                    }
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
                    data: "installer_name",
                    defaultContent: "404",
                    title: "INSTALLER",
                    name: "installer_name",
                    visible: 1,
                    searchable: true,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <p class="m-0">${r.installer_name ? r.installer_name : ""}</p>
                        `;
                    }
                },
                {
                    data: "installer_comments",
                    defaultContent: "404",
                    title: "COMMENT",
                    name: "installer_comments",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        return `
                            <p class="m-0">${r.installer_comments ? r.installer_comments : ""}</p>
                        `;
                    }
                }
            ]
        })

        //helper.hideLoader();
    },

    paymentsDatatable() {

        //helper.showLoader();

        let tableId = '#paymentsTable';
        accountingInstallerPoints.paymentsTable = $(tableId).DataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            pageLength: 5,
            dom: "rtip",
            ajax: accountingInstallerPoints.urlPayments,
            serverSide: true,
            columnDefs: [
                // { className: "text-left", targets: [0, 3] },
                // { className: "width-px-100", targets: [4] }
            ],
            columns: [
                {
                    data: "created_at",
                    defaultContent: "404",
                    title: "Date",
                    name: "created_at",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        return helper.formatDateUsa(r.created_at)
                    }
                },
                {
                    data: "amount",
                    defaultContent: "404",
                    title: "Amount",
                    name: "amount",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                },
                {
                    data: "check_number",
                    defaultContent: "404",
                    title: "Note",
                    name: "check_number",
                    searchable: true,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        return `
                            <p class="m-0">Check # ${r.check_number.substr(-4)}</p>
                        `;
                    }
                },
                {
                    data: "",
                    defaultContent: "",
                    title: "",
                    name: "",
                    searchable: false,
                    visible: 1,
                    render(d, t, r) {
                        if(r.canceled == accountingInstallerPoints.status.cancelled) {
                            return `<span class="badge badge-pill badge-danger">Cancelled</span>`;
                        }else {
                            return `
                                <a href="#" onclick="window.onEditPayment(${r.id})"><img src="../images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-25"></a>
                                <a href="#" onclick="window.onCancelPayment(${r.id})"><img src="../images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-30"></a>
                            `;
                        }
                    }
                },
                {
                    data: "user.name",
                    defaultContent: "404",
                    title: "Installer Name",
                    name: "user.name",
                    searchable: true,
                    visible: 0,
                },
            ]
        })

        //helper.hideLoader();
    },

    onTableLoad() {
        this.table.on('draw.dt', (event, settings, json) => {
            $("#due_points").text(parseFloat(settings.json.due).toFixed(2));
            $("#paid_points").text(parseFloat(settings.json.paid).toFixed(2));
            $("#total_points").text(parseFloat(settings.json.total).toFixed(2));

            $("#due_points_mobile").text(parseFloat(settings.json.due).toFixed(2));
            $("#paid_points_mobile").text(parseFloat(settings.json.paid).toFixed(2));
            $("#total_points_mobile").text(parseFloat(settings.json.total).toFixed(2));
        });
    },

    onPaymentsTableLoad() {
        this.paymentsTable.on('draw.dt', (event, settings, json) => {
            $("#total_due_points").text(parseFloat(settings.json.total_due).toFixed(2));
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

        if (status == accountingInstallerPoints.status_received) {
            statusDescription = "Received";
        }

        if (status == accountingInstallerPoints.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == accountingInstallerPoints.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == accountingInstallerPoints.status_scheduled) {
            statusDescription = "Scheduled";
        }

        if (status == accountingInstallerPoints.status_completed) {
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

}

$(() => {
    accountingInstallerPoints.init();
});

// export default accountingInstallerPoints;
