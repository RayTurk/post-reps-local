import helper from './helper'
import InstallPost from './install_post';
import OrderDetails from './order-details';

const Order = {
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
    search: new URLSearchParams(window.location.search).get('search') ? new URLSearchParams(window.location.search).get('search') : '',

    init() {
        if (window.location.href.indexOf('/dashboard') != -1) {
            this.datatable()
        }
        if (window.location.href.indexOf('/order/status') != -1) {
            if (
                window.location.href.indexOf('history') == -1
                && window.location.href.indexOf('routes') == -1
            ) {
                this.datatableOrderStatus();
                $('#ordersHistory').removeClass('order-tab-active');
                $('#ordersActive').addClass('order-tab-active');
                $('#ordersRoutes').removeClass('order-tab-active');
                $('#ordersPullList').removeClass('order-tab-active');
            }
            this.filterOrderStatus();
            this.searchOrderStatus();
            this.showOrderStatusEntries();
            if (
                window.location.href.indexOf('history') != -1
                && window.location.href.indexOf('routes') == -1
            ) {
                $('#ordersHistory').addClass('order-tab-active');
                $('#ordersActive').removeClass('order-tab-active');
                $('#ordersRoutes').removeClass('order-tab-active');
                $('#ordersPullList').removeClass('order-tab-active');
                this.datatableOrderStatusHistory();
            }

            if (window.location.href.indexOf('routes') != -1) {
                $('#ordersHistory').removeClass('order-tab-active');
                $('#ordersActive').removeClass('order-tab-active');
                $('#ordersRoutes').addClass('order-tab-active');
                $('#ordersPullList').removeClass('order-tab-active');

                this.initRoutingMap();
                this.onInstallerChange();
                this.onRemoveStops();
                this.onRouteDateChange();
            }

            if ( helper.urlContains('pull-list') ) {
                $('#ordersHistory').removeClass('order-tab-active');
                $('#ordersActive').removeClass('order-tab-active');
                $('#ordersRoutes').removeClass('order-tab-active');
                $('#ordersPullList').addClass('order-tab-active');

                this.onPullListDateChange();
                this.onPullListInstallerChange();
                this.onPullListCheckbox();
            }
        }

        this.showOrderEntries();
        this.orderSearchInput();
        window.editOrder = this.editOrder;
        window.eremoveFile = this.eremoveFile;
        window.orderCancel = this.orderCancel
        window.markOrderCompleted = this.markOrderCompleted;
        this.deleteOrders();

        window.viewDetails = this.viewDetails;
        this.onHistoryClick();
        this.onActiveClick();
    },

    orderCancel(id) {
        $.get(`/get/order/` + id).done(order => {
            if (order.status == Order.status_scheduled) {
                helper.confirm2(
                    'REMOVE FROM ROUTE',
                    "Are you sure you wish to remove this order from its scheduled route?",
                    () => {
                        $.get(`/order/${id}/cancel`).done(res => {

                            window.location.reload();
                        });
                    },
                    () => {}
                );
            } else {
                helper.confirm(
                    'CANCEL ORDER',
                    "Are you sure you wish to CANCEL this order? This action is irreversible!",
                    () => {
                        $.get(`/order/${id}/cancel`).done(res => {

                            window.location.reload();
                        });
                    },
                    () => {}
                );
            }
        });
    },
    markOrderCompleted(id) {
        helper.showLoader();

        $.get(`/get/order/` + id).done(order => {
            //Display same modal as installer in iFrame
            const installerId = order.assigned_to;
            const url = `${helper.getSiteUrl()}/admin/installer/order/details/${id}/install/${installerId}`;
            $('#completeOrderIframe').prop('src', url);

            setTimeout(() => {
                helper.hideLoader();

                helper.openModal('markOrderCompletedModal');
            }, 1000)
        });
    },
    showOrderEntries() {
        let selects = document.getAll(".showOrderEntries");
        selects.forEach(select => {
            select = $(select);
            select.on("change", (event) => {
                let selected = parseInt(event.target.value);
                Order.tables.forEach(table => {
                    table.api().context[0]._iDisplayLength = selected;
                    table.api().draw();
                })
            });

        })
    },
    orderSearchInput() {
        let inputs = document.getAll(".orderSearchInput");
        inputs.forEach(input => {
            input = $(input)
            input.on("keyup", (event) => {
                let input = event.target;
                Order.tables.forEach(table => {
                    table.fnFilter(input.value);
                })
            });
        })
    },
    onHistoryClick() {
        $('#ordersHistory').on('click', (event) => {
            event.preventDefault();
            this.search ? helper.redirectTo(helper.getSiteUrl(`/order/status/history?search=${this.search}`)) : helper.redirectTo(helper.getSiteUrl(`/order/status/history`));
        });
    },
    onActiveClick() {
        $('#ordersActive').on('click', (event) => {
            event.preventDefault();
            this.search ? helper.redirectTo(helper.getSiteUrl(`/order/status?search=${this.search}`)) : helper.redirectTo(helper.getSiteUrl(`/order/status`));
        });
    },
    tables: [],
    datatable() {
        let tables = document.getAll(".ordersTable");
        tables.forEach((e, index) => {
            let table = $(e)
            if (table.length) {
                window['orderTable' + index] = table.dataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search...",
                    },
                    pageLength: 10,
                    dom: "rtip",
                    ajax: helper.getSiteUrl("/datatable/orders/status"),
                    serverSide: true,

                    columns: [
                        {
                            data: "address",
                            defaultContent: "404",
                            title: "Address",
                            name: "address",
                            visible: 1,
                            render(d, t, r) {
                                return `<a href="${helper.getSiteUrl()}/order/status"
                                    style="text-decoration: none;"
                                >
                                    ${d}
                                </a>`;
                            }
                        },
                        {
                            data: "status",
                            defaultContent: "...",
                            title: "Status",
                            name: "orders.status",
                            searchable: false,
                            orderable: false,
                            visible: 1,
                            render(d, t, r) {
                                if (d == Order.status_received) {
                                    return `<span class="badge badge-pill badge-primary">Received</span>`
                                } else if (d == Order.status_incomplete) {
                                    if (r.assigned_to > 0) {
                                        return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                                    } else {
                                        return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                                    }
                                } else if (d == Order.status_scheduled) {
                                    return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                                } else if (d == Order.status_completed) {
                                    return `<span class="badge badge-pill badge-success">Installed</span>`;
                                } else if (d == Order.status_cancelled) {
                                    return `<span class="badge badge-pill badge-danger">Cancelled</span>`;
                                }
                            }
                        }, {
                            data: "",
                            defaultContent: "...",
                            title: "Service Date",
                            name: "desired_date",
                            visible: 1,
                            orderable: false,
                            render(d, t, r) {
                                let s = ''
                                if (r.desired_date_type == Order.date.asap) {
                                    s = "Rush Order";
                                } else {
                                    return helper.formatDateUsa(r.desired_date);
                                }
                                return s;
                            }
                        },
                        {
                            data: "order_number",
                            defaultContent: "404",
                            title: "Order ID#",
                            name: "order_number",
                            visible: 1,
                        },
                    ]
                })
                Order.tables.push(table);
            }
        })
    },

    async datatableOrderStatus() {
        this.userRole = $('#userRole').val();

        let tableId = '#orderStatusTable';
        if (helper.isMobilePhone()) {
            tableId = '#orderStatusTableMobile';
        }
        /*if (helper.isTablet()) {
            tableId = '#orderStatusTableTablet';
        }*/
        //console.log(tableId)
        //$("#loader_image").modal('show');
        Order.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            search: {
                search: this.search
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/orders/status"),
            serverSide: true,
            columnDefs: [
                { className: "text-left", targets: [0, 3] },
                { className: "width-px-100", targets: [4] }
            ],
            columns: [
                {
                    data: "address",
                    defaultContent: "404",
                    title: "Address",
                    name: "address",
                    visible: 1,
                },
                {
                    data: "office_name",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "office_name",
                    visible: 0,
                },
                {
                    data: "agent_name",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "agent_name",
                    visible: 0,
                },
                {
                    data: "",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {

                        if (!r.agent_name) return `${r.office_name}`;
                        return `${r.office_name} - ${r.agent_name}`;
                    }

                },
                {
                    data: "order_type",
                    defaultContent: "...",
                    title: "Order Type",
                    name: "order_type",
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        if (d == 'install') {
                            return `<span class="text-success-dark font-weight-bold">Install</span>`;
                        }
                        if (d == 'repair') {
                            return `<span class="text-primary-dark font-weight-bold">Repair</span>`;
                        }
                        if (d == 'removal') {
                            return `<span class="text-danger font-weight-bold">Removal</span>`;
                        }
                        if (d == 'delivery') {
                            return `<span class="text-orange font-weight-bold">Delivery</span>`;
                        }
                    }
                },
                {
                    data: "status",
                    defaultContent: "...",
                    title: "Status",
                    name: "status",
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        if (d == Order.status_received) {
                            return `<span class="badge badge-pill badge-primary">Received</span>`
                        } else if (d == Order.status_incomplete) {
                            if (r.assigned_to > 0) {
                                return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                            } else {
                                return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                            }
                        } else if (d == Order.status_scheduled) {
                            return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                        } else if (d == Order.status_completed) {
                            if (r.order_type == 'install') {
                                return `<span class="badge badge-pill badge-success">Installed</span>`;
                            }
                            if (r.order_type == 'repair') {
                                return `<span class="badge badge-pill badge-success">Repaired</span>`;
                            }
                            if (r.order_type == 'removal') {
                                return `<span class="badge badge-pill badge-success">Removed</span>`;
                            }
                            if (r.order_type == 'delivery') {
                                return `<span class="badge badge-pill badge-success">Delivered</span>`;
                            }
                        } else if (d == Order.status_cancelled) {
                            return `<span class="badge badge-pill badge-danger">Cancelled</span>`;
                        }
                    }
                }, {
                    data: "",
                    defaultContent: "404",
                    title: "Service Date",
                    name: "desired_date",
                    visible: 1,
                    orderable: false,
                    render(d, t, r) {
                        let s = ''
                        if (r.desired_date_type == Order.date.asap) {
                            s = "Rush Order";
                        } else {
                            return helper.formatDateUsa(r.desired_date);
                        }
                        return s;
                    }
                },
                {
                    // data: "address",
                    defaultContent: "...",
                    title: "Action",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        let action = '<div class="d-flex justify-content-center">';

                        if (r.status == Order.status_completed) {
                            action += `<a class="link" onclick="window.viewDetails(${r.id}, '${r.order_type}')">
                                    <img src="${helper.getSiteUrl()}/images/ViewDetails_Icon.png" title="View Details" alt="View Details" class="width-px-40">
                                </a>`;

                            return action;
                        }

                        if (r.order_type == 'install') {
                            action += `<a class='link text-danger text-center' onclick="window.orderCancel(${r.id})">
                                <img src="${helper.getSiteUrl()}/images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                            </a>`;
                        }
                        if (r.order_type == 'repair') {
                            action += `<a class='link text-danger mx-1' onclick="window.repairOrderCancel(${r.id})">
                                <img src="${helper.getSiteUrl()}/images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                            </a>`;
                        }
                        if (r.order_type == 'removal') {
                            action += `<a class='link text-danger mx-1' onclick="window.removalOrderCancel(${r.id})">
                                <img src="${helper.getSiteUrl()}/images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                            </a>`;
                        }
                        if (r.order_type == 'delivery') {
                            action += `<a class='link text-danger mx-1' onclick="window.deliveryOrderCancel(${r.id})">
                                <img src="${helper.getSiteUrl()}/images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                            </a>`;
                        }

                        if (r.status != Order.status_scheduled) {
                            if (r.order_type == 'install') {
                                action += `<a class='link mx-1' onclick="window.editOrder(${r.id})">
                                    <img src="${helper.getSiteUrl()}/images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                                </a>`;
                            }
                            if (r.order_type == 'repair') {
                                action += `<a class='link mx-1' onclick="window.editRepairOrder(${r.id})">
                                    <img src="${helper.getSiteUrl()}/images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                                </a>`;
                            }
                            if (r.order_type == 'removal') {
                                action += `<a class='link mx-1' onclick="window.editRemovalOrder(${r.id})">
                                    <img src="${helper.getSiteUrl()}/images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                                </a>`;
                            }
                            if (r.order_type == 'delivery') {
                                action += `<a class='link mx-1' onclick="window.editDeliveryOrder(${r.id})">
                                    <img src="${helper.getSiteUrl()}/images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                                </a>`;
                            }
                        }

                        if (r.status == Order.status_scheduled) {
                            if (Order.userRole == 1) {
                                if (r.order_type == 'install') {
                                    action += `<br><a class='link text-success font-weight-bold mx-1' onclick="window.markOrderCompleted(${r.id})">
                                        <img src="${helper.getSiteUrl()}/images/Complete_Icon.png" title="Complete" alt="Complete" class="width-px-40">
                                    </a>`;
                                }
                                if (r.order_type == 'repair') {
                                    action += `<br><a class='link text-success font-weight-bold mx-1' onclick="window.markRepairOrderCompleted(${r.id})">
                                        <img src="${helper.getSiteUrl()}/images/Complete_Icon.png" title="Complete" alt="Complete" class="width-px-40">
                                    </a>`;
                                }
                                if (r.order_type == 'removal') {
                                    action += `<br><a class='link text-success font-weight-bold mx-1' onclick="window.markRemovalOrderCompleted(${r.id})">
                                        <img src="${helper.getSiteUrl()}/images/Complete_Icon.png" title="Complete" alt="Complete" class="width-px-40">
                                    </a>`;
                                }
                                if (r.order_type == 'delivery') {
                                    action += `<br><a class='link text-success font-weight-bold mx-1' onclick="window.markDeliveryOrderCompleted(${r.id})">
                                        <img src="${helper.getSiteUrl()}/images/Complete_Icon.png" title="Complete" alt="Complete" class="width-px-40">
                                    </a>`;
                                }
                            }
                        }

                        action += `<a class="link mx-1" onclick="window.viewDetails(${r.id}, '${r.order_type}')">
                            <img src="${helper.getSiteUrl()}/images/ViewDetails_Icon.png" title="View Details" alt="View Details" class="width-px-40">
                        </a>`;

                        action += '</div>';

                        return action;
                    }
                },
                {
                    data: "order_number",
                    defaultContent: "...",
                    title: "Order ID#",
                    name: "order_number",
                    visible: 1,
                },
            ]
        })

        //helper.hideLoader('');
    },

    datatableOrderStatusHistory() {
        //$("#loader_image").modal('show');
        let tableId = '#orderStatusTable';
        if (helper.isMobilePhone()) {
            tableId = '#orderStatusTableMobile';
        }
        /*if (helper.isTablet()) {
            tableId = '#orderStatusTableTablet';
        }*/

        Order.table = $(tableId).dataTable({
            retrieve: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            search: {
                search: this.search
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/order/status/history"),
            serverSide: true,
            columnDefs: [
                { className: "text-left", targets: [0, 3] },
                { className: "width-px-100", targets: [4] }
            ],
            columns: [
                {
                    data: "address",
                    defaultContent: "404",
                    title: "Address",
                    name: "address",
                    visible: 1,
                },
                {
                    data: "office_name",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "office_name",
                    visible: 0,
                },
                {
                    data: "agent_name",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "agent_name",
                    visible: 0,
                },
                {
                    data: "",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {

                        if (!r.agent_name) return `${r.office_name}`;
                        return `${r.office_name} - ${r.agent_name}`;
                    }

                },
                {
                    data: "order_type",
                    defaultContent: "...",
                    title: "Order Type",
                    name: "order_type",
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        if (d == 'install') {
                            return `<span class="text-success-dark font-weight-bold">Install</span>`;
                        }
                        if (d == 'repair') {
                            return `<span class="text-primary-dark font-weight-bold">Repair</span>`;
                        }
                        if (d == 'removal') {
                            return `<span class="text-danger font-weight-bold">Removal</span>`;
                        }
                        if (d == 'delivery') {
                            return `<span class="text-orange font-weight-bold">Delivery</span>`;
                        }
                    }
                },
                {
                    data: "status",
                    defaultContent: "...",
                    title: "Status",
                    name: "status",
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        if (d == Order.status_received) {
                            return `<span class="badge badge-pill badge-primary">Received</span>`
                        } else if (d == Order.status_incomplete) {
                            if (r.assigned_to > 0) {
                                return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                            } else {
                                return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                            }
                        } else if (d == Order.status_scheduled) {
                            return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                        } else if (d == Order.status_completed) {
                            if (r.order_type == 'install') {
                                return `<span class="badge badge-pill badge-success">Installed</span>`;
                            }
                            if (r.order_type == 'repair') {
                                return `<span class="badge badge-pill badge-success">Repaired</span>`;
                            }
                            if (r.order_type == 'removal') {
                                return `<span class="badge badge-pill badge-success">Removed</span>`;
                            }
                            if (r.order_type == 'delivery') {
                                return `<span class="badge badge-pill badge-success">Delivered</span>`;
                            }
                        } else if (d == Order.status_cancelled) {
                            return `<span class="badge badge-pill badge-danger">Cancelled</span>`;
                        }
                    }
                }, {
                    data: "",
                    defaultContent: "404",
                    title: "Service Date",
                    name: "desired_date",
                    visible: 1,
                    orderable: false,
                    render(d, t, r) {
                        let s = ''
                        if (r.desired_date_type == Order.date.asap) {
                            s = "Rush Order";
                        } else {
                            return helper.formatDateUsa(r.desired_date);
                        }
                        return s;
                    }
                },
                {
                    defaultContent: "...",
                    title: "Action",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {

                        return `<a class="link" onclick="window.viewDetails(${r.id}, '${r.order_type}')">
                                <img src="${helper.getSiteUrl()}/images/ViewDetails_Icon.png" title="View Details" alt="View Details" class="width-px-40">
                            </a>`;

                    }
                },
                {
                    data: "order_number",
                    defaultContent: "...",
                    title: "Order ID#",
                    name: "order_number",
                    visible: 1,
                },
            ]
        })

        //helper.hideLoader('');
    },

    filterOrderStatus() {
        const select = $("#filterOrders");
        select.on("change", (event) => {
            Order.table.fnFilter(event.target.value);
        });
    },

    searchOrderStatus() {
        this.search ? $("#searchOrders").val(this.search) : $("#searchOrders").val('');

        $('body').on("keyup", '#searchOrders', (event) => {
            Order.table.fnFilter(event.target.value);
        });
    },

    showOrderStatusEntries() {
        $('body').on("change", '#showOrderStatusEntries', (event) => {
            const selected = parseInt(event.target.value);
            Order.table.api().context[0]._iDisplayLength = selected;
            Order.table.api().draw();
        });
    },

    editOrder(id) {
        InstallPost.ignoreZoneFee = false;

        helper.showLoader();

        //Hide import order checkbox
        $('#import-order-div').hide();

        let modal = $("#install_post_modal");
        if (modal.length) {
            $.get(`/get/order/` + id).done(order => {
                InstallPost.resetInstallModalForm()
                InstallPost.createpost = false;
                InstallPost.order_id = order.id;
                InstallPost.upload_accessory_file = null;
                InstallPost._files = [];
                Order.setOfficeAndAgent(order)
                Order.setPropertyInfo(order)
                Order.setDate(order);
                Order.setComment(order)
                Order.setFiles(order);
                Order.setFooter();

                /*InstallPost.rowCount = 0;
                if (order.adjustments) {
                    Order.loadSavedAdjustments(order.adjustments);
                }*/
            })
        }
    },

    loadSavedAdjustments(adjustments) {
        const rowTmpl = $('#rowTmplInstallAdjustment').html();
        const rowContainer = $('#rowContainerInstallAdjustments');
        let totalAdjustments = 0;
        InstallPost.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };

        rowContainer.empty();
        $.each(adjustments, (i, row)=> {
            InstallPost.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, InstallPost.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="install_price_adjustment_description[${InstallPost.rowCount}]"]`).val(row.description);
            $(`[name="install_price_adjustment_charge[${InstallPost.rowCount}]"]`).val(row.charge);
            $(`[name="install_price_adjustment_discount[${InstallPost.rowCount}]"]`).val(row.discount);

            InstallPost.pricingAdjustments['description'][i] = row.description;
            InstallPost.pricingAdjustments['charge'][i] = row.charge;
            InstallPost.pricingAdjustments['discount'][i] = row.discount;

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            InstallPost.totalAdjusted = totalAdjustments;

            if (InstallPost.totalAdjusted < 0) {
                $('[install-post-adjustments]').html(`<span class="text-danger">- $${InstallPost.totalAdjusted*(-1)}</span>`);
            } else {
                $('[install-post-adjustments]').html(`$${InstallPost.totalAdjusted}`);
            }
        });

        InstallPost.calculateAdjustments();
    },

    //Need to come back to this and find a better way of doing it.
    setOfficeAndAgent(order) {
        let selectoffice = $(`[name="install_post_office"]`);
        selectoffice.find(`option[value="${order.office_id}"]`).prop('selected', true)
        selectoffice.trigger('change');
        //console.log('trigger office')
        setTimeout(() => {
            let selectagent = $(`[name="install_post_agent"]`);
            if (order.agent_id) {
                selectagent.find(`option[value="${order.agent_id}"]`).prop('selected', true)
                //console.log(order.agent_id)

                //Set agent change count to 0 to prevent form reset
                InstallPost.agentChangeCount = 0;

                selectagent.get(0).dispatchEvent(new Event('change'))
            }
            setTimeout(() => {
                $(`[name="install_post_post"][value="${order.post_id}"]`).trigger('click')
                $(`[name="install_post_panel"][value="${order.panel_id}"]`).trigger('click')
                order.accessories.forEach(accessory => {
                    $(`[name="install_post_accessories[]"][value="${accessory.accessory_id}"]`).trigger('click');
                })

                setTimeout(() => {
                    if (order.files.length) {
                        if (order.property_type == 2 || order.property_type == 3) {
                            $(".install-document-required-warning").addClass("d-block").removeClass('d-none');
                        } else {
                            //console.log(order);

                            $(".install-document-required-warning").addClass("d-none").removeClass('d-block');

                        }

                        $("#attachments .alert").addClass('d-none').removeClass('d-block');

                        let accessories = order.accessories.map(a => a.accessory);
                        let filesAcessories = [];

                        order.files.forEach(file => {
                            filesAcessories.push(file.accessory_id)

                        })

                        let platMap = order.files.filter(f => f.plat_map == 1);
                        if (order.property_type == 2 || order.property_type == 3) {
                            if (!platMap.length) {
                                $(".install-document-required-warning").addClass("d-block").removeClass('d-none');
                            }
                        }
                        accessories.forEach(accessory => {
                            if (filesAcessories.includes(accessory.id) == false) {
                                if (accessory.prompt == 1) {
                                    let s = `[install_accessory_name_id_${accessory.id}]`;
                                    $(s).removeClass(`d-none`).addClass(`d-block`);
                                }
                            }
                        });
                    }
                    if (order.agent_own_sign) {
                        $(`[name="install_post_panel_type"][value="-1"]`).trigger('click');
                    }
                    if (order.sign_at_property) {
                        $(`[name="install_post_panel_type"][value="-2"]`).trigger('click');
                    }

                    //Only open modal after everythins is loaded
                    $('#loader_image').modal('hide');

                    $(".modal").css({ "overflow-y": "scroll" });

                    InstallPost.rowCount = 0;
                    if (order.adjustments) {
                        Order.loadSavedAdjustments(order.adjustments);
                    }

                    $("#install_post_modal").modal();

                }, Math.floor(2000 / 3))
            }, 6000)
        }, 2000);

    },
    setPropertyInfo(order) {
        let addressInput = $("#address");
        let cityInput = $("#city");
        let stateInput = $("#state");
        if (addressInput.length && cityInput.length && stateInput.length) {
            addressInput.val(order.address.split(',')[0])
            cityInput.val(order.address.split(',')[1])

            //Set selected state
            $(`#state option[value=${order.address.split(',')[2]?.trimStart().substr(0, 2)}]`).attr('selected', 'selected');

            if (order.ignore_zone_fee == 1) {
                InstallPost.ignoreZoneFee = true;
            }

            InstallPost.findThePlace(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)}, true);
        }

        let propertyTypeSelect = $(`[name="install_post_property_type"]`);
        if (propertyTypeSelect.length) {
            propertyTypeSelect.find(`option[value="${order.property_type}"]`).prop('selected', true)
            if (order.property_type == 2 || order.property_type == 3) {
                $(".install-document-required-warning").addClass("d-block").removeClass('d-none');
            } else {
                $(".install-document-required-warning").addClass("d-none").removeClass('d-block');

            }
        }
    },

    setDate(order) {
        let datePicker = $("#selectdate_custom_date");
        if (order.desired_date_type == 1) {
            $(`[name="install_post_desired_date"][value="asap"]`).prop('checked', true);
            //$('[name="install_post_desired_date"]').val('asap');
            datePicker.addClass("d-none");

            InstallPost.setRushFee(order.rush_fee);
        } else {
            $(`[name="install_post_desired_date"][value="custom_date"]`).trigger('click')
            let d = helper.parseDate(order.desired_date);

            //Need to review this part. Why does it need setTimeout?
            setTimeout(()=>{
                datePicker.removeClass("d-none");
                //datePicker.datepicker("setDate", d);
                InstallPost.updateCalendar(d);
                $(`[name="install_post_custom_desired_date"]`).val(helper.formatDateUsa(order.desired_date))

                //Use this to prevent saved date from being cleared after updating map when editing order
                InstallPost.savedServiceDate = d;
            }, 6000);

            $('#rushFee').addClass('d-none');
        }
    },
    setComment(order) {
        $(`[name="install_post_comment"]`).text(order.comment)
    },
    setFiles(order) {
        $("#files_list").html(` `);
        order.files.forEach(file => {
            InstallPost._files.push(file);
            $("#files_list").append(`
                <li>
                    <span>
                    <a target="_blank" href="${helper.getSiteUrl(`/private/document/file/${file.name}`)}"><strong>${file.name}</strong></a> UPLOADED ${helper.formatDateTime(file.created_at)}
                    <a class="text-danger c-p" onclick="window.eremoveFile(event,${file.id})"><strong>REMOVE</strong></a>
                    </span>
                </li>
            `)
        })

        const propertyType = $('[name="install_post_property_type"]').val();
        if ( order.files.length == 0 && (propertyType == 2 || propertyType == 3)) {
            $(`.install-document-required-warning`).addClass('d-block').removeClass('d-none');
        }
    },
    setFooter() {
        $('#submitOrder').html('<strong class="text-white">UPDATE ORDER</strong>').prop('disabled', false);
    },
    eremoveFile(event, id) {
        $.get(`/order/delete/file/${id}`).done(res => {
            event.target.parentNode.parentNode.parentNode.remove();

            if (!$('ul#files_list li').length) {
                const propertyType = $('[name="install_post_property_type"]').val();
                if (propertyType == 2 || propertyType == 3) {
                    $(`.install-document-required-warning`).addClass('d-block').removeClass('d-none');
                }

                $(`.accessories-install-document-required-warning`).addClass('d-block').removeClass('d-none');
            }
            InstallPost._files = [];
        });
    },

    deleteOrders() {
        $('#deleteAllOrders').on('click', (e) => {
            const self = $(e.target);

            helper.confirm(
                '',
                "",
                () => {
                    const deletePath = helper.getSiteUrl("/order/delete/all");
                    const posting = $.post(deletePath)
                        .done((res) => {
                            if (res.type == "success") {
                                window.location.reload();
                            }
                        })
                        .fail((res) => {
                            console.error(res);
                        });
                },
                () => ""
            );

        });

        $('#deleteAllOrdersStatus').on('click', (e) => {
            const self = $(e.target);

            helper.confirm(
                '',
                "",
                () => {
                    const deletePath = helper.getSiteUrl("/order/status/delete/all");
                    const posting = $.post(deletePath)
                        .done((res) => {
                            if (res.type == "success") {
                                window.location.reload();
                            }
                        })
                        .fail((res) => {
                            console.error(res);
                        });
                },
                () => ""
            );

        });
    },

    initRoutingMap() {
        window.initRoutingMap = this.startRoutingMap;

        const src = `https://maps.googleapis.com/maps/api/js?key=${InstallPost.googleKey}&callback=window.initRoutingMap&libraries=drawing,geometry,places&v=weekly`;
        $("body").append(window.e("script", { src, googlescript: true }));
    },

    startRoutingMap() {
        // The location of defaultLocation
        const defaultLocation = {
            lat: 43.633994,
            lng: -116.433707,
        };

        // The map, centered at defaultLocation
        const map = new google.maps.Map(document.getElementById("routeMap"),
            {
                zoom: 10,
                center: defaultLocation,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            }
        );

        window.routeMap = map;

        $('#installerSelect').trigger('change');
    },

    previousMarkers: [],
    previousPolylines: [],
    routeOrders: {},
    installerId: {},
    totalAssigned: 0,
    totalUnassigned: 0,
    oms: {},
    assignedCounter: 0,
    loadRoutesOnMap(orders, installerId) {
        Order.assignedCounter = 0;

        //helper.showLoader();

        Order.routeOrders = orders;
        Order.installerId = installerId;

        let map = window.routeMap;

        //Create instance of OverlappingMarkerSpiderfier and associate with the map
        Order.oms = new OverlappingMarkerSpiderfier(map, {
            markersWontMove: true,
            markersWontHide: true,
            basicFormatEvents: true,
            circleSpiralSwitchover: 0,
            keepSpiderfied: true,
            nearbyDistance: 25,
            spiralFootSeparation: 26,
            spiralLengthStart: 11,
            spiralLengthFactor: 4
        });

        const previousMarkers = Order.previousMarkers;
        //console.log(previousMarkers)
        if (previousMarkers.length) {
            for (let i = 0; i < previousMarkers.length; i++) {
                previousMarkers[i].setMap(null);
            }
        }
        const previousPolylines = Order.previousPolylines;
        if (previousPolylines.length) {
            for (let i = 0; i < previousPolylines.length; i++) {
                previousPolylines[i].setMap(null);
            }
        }

        let infoWindow = new google.maps.InfoWindow();
        let directionData = [];

        let markers = [];
        if (orders.length) {
            $.each(orders, (i, order) => {
                //console.log(order)
                let markerData = {};

                markerData.order_type = order.order_type;

                markerData.latitude = order.latitude;
                markerData.longitude = order.longitude;

                //If job not assigned, label will be the service date
                //Otherwise populate the installer initials and stop number
                let label, stopNumber, orderType, installerInitials, orderDate;
                if (order.assigned_to > 0) {
                    if (order.order_type == 'install') {
                        orderType = '\xa0\xa0\xa0\xa0' + helper.initialUppercase(order.order_type);
                        installerInitials = helper.getInitialsFromName(order.installer_name);
                        if (order.stop_number >= 10) {
                            stopNumber = '\xa0\xa0\xa0\xa0\xa0\xa0' + order.stop_number;
                        } else {
                            stopNumber = '\xa0\xa0\xa0\xa0\xa0\xa0\xa0' + order.stop_number;
                        }
                        label = `${installerInitials} ${orderType} ${stopNumber}`;
                    }
                    if (order.order_type == 'delivery') {
                        orderType = '\xa0\xa0' + helper.initialUppercase(order.order_type);
                        installerInitials = helper.getInitialsFromName(order.installer_name);
                        if (order.stop_number >= 10) {
                            stopNumber = '\xa0\xa0\xa0\xa0' + order.stop_number;
                        } else {
                            stopNumber = '\xa0\xa0\xa0\xa0\xa0' + order.stop_number;
                        }
                        label = `${installerInitials} ${orderType} ${stopNumber}`;
                    }
                    if (order.order_type == 'repair') {
                        orderType = '\xa0\xa0\xa0\xa0' + helper.initialUppercase(order.order_type);
                        installerInitials = helper.getInitialsFromName(order.installer_name);
                        if (order.stop_number >= 10) {
                            stopNumber = '\xa0\xa0\xa0\xa0\xa0' + order.stop_number;
                        } else {
                            stopNumber = '\xa0\xa0\xa0\xa0\xa0\xa0' + order.stop_number;
                        }
                        label = `${installerInitials} ${orderType} ${stopNumber}`;
                    }
                    if (order.order_type == 'removal') {
                        orderType = '\xa0\xa0' + helper.initialUppercase(order.order_type);
                        installerInitials = helper.getInitialsFromName(order.installer_name);
                        if (order.stop_number >= 10) {
                            stopNumber = '\xa0\xa0\xa0' + order.stop_number;
                        } else {
                            stopNumber = '\xa0\xa0\xa0\xa0' + order.stop_number;
                        }
                        label = `${installerInitials} ${orderType} ${stopNumber}`;
                    }

                    markerData.label = label;

                    markerData.assigned = true;

                    markerData.routing_color = order.routing_color;
                    markerData.stop_number = order.stop_number;
                    markerData.installerId = order.installer_id
                } else {
                    if (order.desired_date_type == 2) {
                        if (order.order_type == 'install') {
                            orderType = '\xa0\xa0\xa0' + helper.initialUppercase(order.order_type);
                            orderDate = order.desired_date.substr(8, 2);
                            label = `${orderDate} ${orderType}`;
                        }
                        if (order.order_type == 'repair') {
                            orderType = '\xa0\xa0\xa0' + helper.initialUppercase(order.order_type);
                            orderDate = order.desired_date.substr(8, 2);
                            label = `${orderDate} ${orderType}`;
                        }
                        if (order.order_type == 'delivery') {
                            orderType = '\xa0' + helper.initialUppercase(order.order_type);
                            orderDate = order.desired_date.substr(8, 2);
                            label = `${orderDate} ${orderType}`;
                        }
                        if (order.order_type == 'removal') {
                            orderType = '\xa0' + helper.initialUppercase(order.order_type);
                            orderDate = order.desired_date.substr(8, 2);
                            label = `${orderDate} ${orderType}`;
                        }

                        markerData.label = label;
                        markerData.rushOrder = false;
                    } else {
                        //Rush Order
                        markerData.label = '\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0\xa0RUSH';
                        markerData.rushOrder = true;
                    }

                    markerData.assigned = false;
                    markerData.installerId = 0;
                }

                markerData.address = order.address;
                if (order.desired_date) {
                    markerData.desired_date = helper.formatDate(order.desired_date);
                }
                if (order.desired_date_type == 1) {
                    markerData.desired_date = 'Rush Order';
                }
                markerData.office_name = order.office_name;
                markerData.agent_name = order.agent_name || '';
                markerData.order_number = order.order_number;
                markerData.order_id = order.id;

                markers[i] = markerData;
            });

            //console.log(markers)
            Order.totalAssigned = 0;
            Order.totalUnassigned = 0;
            for (let i = 0; i < markers.length; i++) {
                let data = markers[i];
                //console.log(data)
                let myLatlng = new google.maps.LatLng(data.latitude, data.longitude);
                directionData.push(data);

                let icon;
                let label;
                if (data.installerId > 0) {
                    Order.totalAssigned++;

                    icon = {
                        url: helper.getSiteUrl(`/images/route_map_pin_template2.png`),
                        anchor: new google.maps.Point(0, 20),
                        labelOrigin: { x: 14, y: 10} //Align marker laber
                    };

                    if (data.rushOrder) {
                        icon = {
                            url: helper.getSiteUrl(`/images/route_map_pin_template_rush_order.png`),
                            anchor: new google.maps.Point(0, 20),
                            labelOrigin: { x: 14, y: 10} //Align marker laber
                        };
                    }

                    //Create route-map-label class
                    let pl = '0';
                    let pr = '0';
                    if (data.order_type == 'install') {
                        pl = '1px';
                    }
                    if (data.order_type == 'repair') {
                        pl = '2px';
                    }
                    if (data.order_type == 'delivery') {
                        pl = '1px';
                    }

                    /*const styleTag = document.querySelector('.routing-style');
                    if (styleTag) {
                        styleTag.remove();
                    }*/
                    document.head.insertAdjacentHTML("beforeend", `
                        <style class="routing-style">
                            .route-map-label${data.installerId}{
                                background-color: ${data.routing_color};
                                width: 16px;
                                height: 16px;
                                border-radius: 51%;
                                margin-left: -8px;
                                padding: 1px ${pr} 0 ${pl};
                                text-align: left;
                            }
                        </style>
                    `);

                    label = {
                        text: data.label,
                        fontSize: '11px',
                        fontWeight: '700',
                        color: '#FFFFFF',
                        className: `route-map-label${data.installerId}`
                    };
                } else {
                    Order.totalUnassigned++;

                    icon = {
                        url: helper.getSiteUrl(`/images/route_map_pin_template_unassigned.png`),
                        anchor: new google.maps.Point(0, 20),
                        labelOrigin: { x: 14, y: 10} //Align marker laber
                    };

                    if (data.rushOrder) {
                        icon = {
                            url: helper.getSiteUrl(`/images/route_map_pin_template_rush_order.png`),
                            anchor: new google.maps.Point(0, 20),
                            labelOrigin: { x: 14, y: 11} //Align marker laber
                        };

                        label = {
                            text: data.label,
                            fontSize: '12px',
                            fontWeight: '700',
                        };
                    } else {
                        let ml = '38px';
                        if (data.order_type == 'repair') {
                            ml = '39px';
                        }
                        if (data.order_type == 'removal') {
                            ml = '45px';
                        }
                        if (data.order_type == 'delivery') {
                            ml = '42px';
                        }

                        document.head.insertAdjacentHTML("beforeend", `
                            <style class="routing-unassigned-style">
                                .route-map-label-unassigned${data.order_type}{
                                    margin-left: ${ml};
                                    padding: 1px 0 0 0;
                                    text-align: left;
                                }
                            </style>
                        `);

                        label = {
                            text: data.label,
                            fontSize: '12px',
                            fontWeight: '700',
                            className: `route-map-label-unassigned${data.order_type}`
                        };
                    }
                }


                //https://codepen.io/studio-klik-hr/pen/VwWdpW
                //See about using custom marker HTML
                let marker = new google.maps.Marker({
                    position: myLatlng,
                    map: map,
                    icon: icon,
                    label: label
                });

                // console.log(i)
                Order.previousMarkers.push(marker);

                //latlngbounds.extend(marker.position);
                (function(marker, data) {
                    //google.maps.event.addListener(marker, "click", function(e) {
                    google.maps.event.addListener(marker, "spider_click", function(e) {
                        let content;
                        let installersHtml = '';
                        let stopNumberHtml = '';

                        const installerList = $('#installerSelect').data('installers');
                        if (installerId > 0) {
                            if ( ! data.assigned) {
                                //Automatically assign job to installer
                                const params = {
                                    installerId: installerId,
                                    orderType: data.order_type,
                                    orderId: data.order_id,
                                    route_date: $('#routeDateSelect').val()
                                };

                                Order.assignJob(params);

                                return false;
                            } else {
                                //Show edit window
                                $.each(installerList, (i, installer) => {
                                    let selected = installer.id == installerId ? 'selected' : '';
                                    installersHtml += `<option value="${installer.id}" ${selected}>${installer.name}</option>`;
                                });

                                let countAssigned = 0;
                                $.each(Order.routeOrders, (i, order) => {
                                    if (order.assigned_to > 0) {
                                        countAssigned++;
                                    }
                                });
                                //console.log(Order.routeOrders)
                                let selectedStop = 1;
                                for (let i=1; i < countAssigned + 1; i++) {
                                    let select = '';
                                    if (i == data.stop_number) {
                                        selectedStop = i;
                                        select = 'selected';
                                    }
                                    stopNumberHtml += `<option value="${i}" ${select}>${i}</option>`;
                                }

                                content = `
                                    <div style="width:330px; height:173px; text-align:left; padding:2px; line-height:20px;">
                                        <strong>Order Type:</strong> ${helper.initialUppercase(data.order_type)}
                                        <strong class="ml-4">Order #:</strong> ${data.order_number}<br>
                                        <strong>Address:</strong>  ${data.address}<br>
                                        <strong>Service Date:</strong>  ${data.desired_date}<br><br>
                                        <strong>Assign To:</strong>
                                        <select class="marker-select-installer width-px-170 border-solid-1" >
                                            <option value="0"></option>
                                            ${installersHtml}
                                        </select><br>
                                        <strong>Stop Number:</strong>
                                        <select class="marker-select-stop width-px-50 border-solid-1 mt-1" >
                                            ${stopNumberHtml}
                                        </select><br>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button
                                                type="button"
                                                data-order-id="${data.order_id}"
                                                data-order-type="${data.order_type}"
                                                data-installer="${installerId}"
                                                class="btn btn-danger btn-sm marker-unassign"
                                            >
                                                Unassign
                                            </button>
                                            <button
                                                type="button"
                                                data-order-id="${data.order_id}"
                                                data-order-type="${data.order_type}"
                                                data-installer="${installerId}"
                                                data-stop-number="${selectedStop}"
                                                class="btn btn-orange btn-sm marker-update-assignment"
                                            >
                                                Update
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }
                        } else {
                            if (data.installerId > 0) {
                                //Show edit window
                                $.each(installerList, (i, installer) => {
                                    let selected = installer.id == data.installerId ? 'selected' : '';
                                    installersHtml += `<option value="${installer.id}" ${selected}>${installer.name}</option>`;
                                });

                                let countAssigned = 0;
                                $.each(Order.routeOrders, (i, order) => {
                                    if (order.assigned_to > 0 && order.assigned_to == data.installerId) {
                                        countAssigned++;
                                    }
                                });
                                let selectedStop = 1;
                                for (let i=1; i <= countAssigned; i++) {
                                    let select = '';
                                    if (i == data.stop_number) {
                                        selectedStop = i;
                                        select = 'selected';
                                    }
                                    stopNumberHtml += `<option value="${i}" ${select}>${i}</option>`;
                                }

                                content = `
                                    <div class="col-12" style="width:330px; height:173px; text-align:left; padding:2px; line-height:20px;">
                                        <strong>Order Type:</strong> ${helper.initialUppercase(data.order_type)}
                                        <strong class="ml-4">Order #:</strong> ${data.order_number}<br>
                                        <strong>Address:</strong>  ${data.address}<br>
                                        <strong>Service Date:</strong>  ${data.desired_date}<br><br>
                                        <strong>Assign To:</strong>
                                        <select class="marker-select-installer width-px-170 border-solid-1" >
                                            <option value="0"></option>
                                            ${installersHtml}
                                        </select><br>
                                        <strong>Stop Number:</strong>
                                        <select class="marker-select-stop width-px-50 border-solid-1 mt-1" >
                                            ${stopNumberHtml}
                                        </select><br>
                                        <div class="d-flex justify-content-between mt-3">
                                            <button
                                                type="button"
                                                data-order-id="${data.order_id}"
                                                data-order-type="${data.order_type}"
                                                data-installer="${data.installerId}"
                                                class="btn btn-danger btn-sm marker-unassign"
                                            >
                                                Unassign
                                            </button>
                                            <button
                                                type="button"
                                                data-order-id="${data.order_id}"
                                                data-order-type="${data.order_type}"
                                                data-installer="${data.installerId}"
                                                data-stop-number="${selectedStop}"
                                                class="btn btn-orange btn-sm marker-update-assignment"
                                            >
                                                Update
                                            </button>
                                        </div>
                                    </div>
                                `;
                            } else {
                                $.each(installerList, (i, installer) => {
                                    installersHtml += `<option value="${installer.id}">${installer.name}</option>`;
                                });

                                content = `
                                    <div class="col-12" style="width:330px; height:145px; text-align:left; padding:2px; line-height:20px;">
                                        <strong>Order Type:</strong> ${helper.initialUppercase(data.order_type)}
                                        <strong class="ml-4">Order #:</strong> ${data.order_number}<br>
                                        <strong>Address:</strong>  ${data.address}<br>
                                        <strong>Service Date:</strong>  ${data.desired_date}<br><br>
                                        <strong>Assign To:</strong>
                                        <select class="marker-select-installer width-px-170 border-solid-1 col-12" >
                                            <option value="0"></option>
                                            ${installersHtml}
                                        </select><br>
                                        <div class="text-right mt-3">
                                            <button
                                                type="button"
                                                data-order-id="${data.order_id}"
                                                data-order-type="${data.order_type}"
                                                data-installer="0"
                                                class="btn btn-orange btn-sm marker-assign-installer"
                                            >
                                                Assign
                                            </button>
                                        </div>
                                    </div>
                                `;
                            }
                        }

                        infoWindow.setContent(content);
                        infoWindow.open(map, marker);
                    });
                })(marker, data);

                Order.oms.addMarker(marker);
            }

            //map.setCenter(latlngbounds.getCenter());
            //map.fitBounds(latlngbounds);

            //Set total assigned/unassigned
            $('#totalAssigned').html(` ${Order.totalAssigned}`);
            $('#totalUnassigned').html(` ${Order.totalUnassigned}`);

            if (Order.totalAssigned <= 1) {
                helper.hideLoader();
            }

            //***********ROUTING****************//
            //Initialize the Direction Service
            //let service = new google.maps.DirectionsService();
            Order.delay = 0;

            //Loop and Draw Path Route between the Points on MAP
            let arrayInstallerId = directionData.map(row => row.installerId);
            //console.log(arrayInstallerId)
            let processed = [];
            $.each(arrayInstallerId, (i, val) => {
                if (! processed.includes(val) && val > 0) {
                    let filteredOrders = directionData.filter(row => {return row.installerId == val});
                    //console.log(filteredOrders)
                    $.each(filteredOrders, (i, row) => {
                        if (filteredOrders[i+1]) {
                            const request = {
                                origin: `${row.latitude}, ${row.longitude}`,
                                destination: `${filteredOrders[i+1].latitude}, ${filteredOrders[i+1].longitude}`,
                            };

                            Order.processDirection(request, row);
                        }
                    });

                    processed.push(val);
                }
            });
        }

        //Event handler for infoWindow on map
        $('body').on('change', '.marker-select-installer', (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);
            //console.log(self.val())
            $('.marker-assign-installer').data('installer', self.val());
            $('.marker-update-assignment').data('installer', self.val());
        });

        $('body').on('change', '.marker-select-stop', (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);
            //console.log(self.val())
            $('.marker-update-assignment').data('stop-number', self.val());
        });

        $('body').on('click', '.marker-assign-installer', async (e) => {
            e.stopImmediatePropagation();
            //helper.showLoader();

            const self = $(e.target);
            const installerId = self.data('installer');
            const orderType = self.data('order-type');
            const orderId = self.data('order-id');

            if (installerId == 0) {
                helper.alertError('Please select installer.');
                return false;
            }

            //Assign and reload route Map
            const url = `${helper.getSiteUrl()}/order/assign`;
            const data = {
                installerId: installerId,
                orderType: orderType,
                orderId: orderId,
                route_date: $('#routeDateSelect').val()
            }

            $.post(url, data)
            .done (res => {
                if (res.type == 'error') {
                    helper.alertError(res.message);
                    return false;
                }

                $('#installerSelect').trigger('change');
            })
        });

        $('body').on('click', '.marker-update-assignment', async (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);
            const installerId = self.data('installer');
            const orderType = self.data('order-type');
            const orderId = self.data('order-id');
            const stopNumber = self.data('stop-number');

            if (installerId == 0) {
                helper.alertError('Please select installer.');
                return false;
            }

            //Assign and reload route Map
            const url = `${helper.getSiteUrl()}/order/assign/update`;
            const data = {
                installerId: installerId,
                orderType: orderType,
                stopNumber: stopNumber,
                orderId: orderId,
                route_date: $('#routeDateSelect').val()
            }
            const assigned = await $.post(url, data);

            if (assigned) {
                $('#installerSelect').trigger('change');
            }
        });

        $('body').on('click', '.marker-unassign', async (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);
            const installerId = self.data('installer');
            const orderType = self.data('order-type');
            const orderId = self.data('order-id');

            if (installerId == 0) {
                helper.alertError('Please select installer.');
                return false;
            }

            //Unassign and reload route Map
            const url = `${helper.getSiteUrl()}/order/unassign`;
            const data = {
                orderType: orderType,
                orderId: orderId,
                route_date: $('#routeDateSelect').val()
            }
            const assigned = await $.post(url, data);

            if (assigned) {
                $('#installerSelect').trigger('change');
            }
        });
    },

    delay: 0,
    async processDirection(request, row) {
        const map = window.routeMap;

        const data = {
            origin: request.origin,
            destination: request.destination
        }
        let response = await $.post(`${helper.getSiteUrl()}/order/get/direction`, data);
        //console.log(response)
        if (response) {
            response = JSON.parse(response);
            //console.log(InstallPost.googleKey)

            const overviewPolyline = response.routes[0].overview_polyline.points;
            //console.log(overviewPolyline)
            if (overviewPolyline) {
                let decodedPoints = google.maps.geometry.encoding.decodePath(overviewPolyline);

                let poly = new google.maps.Polyline({
                    map: map,
                    strokeColor: `${row.routing_color}`
                });
                poly.setPath(decodedPoints);

                Order.previousPolylines.push(poly);

                /*Order.assignedCounter++;
                console.log(Order.assignedCounter);
                if ((Order.assignedCounter + 3) >= Order.totalAssigned) {
                    helper.hideLoader();
                }*/
            }
        }
    },

    async assignJob( params) {
        //helper.showLoader();
        const url = `${helper.getSiteUrl()}/order/assign`;
        const assigned = await $.post(url, params);

        if (assigned) {
            $('#installerSelect').trigger('change');
            //helper.hideLoader('');
        }
    },

    onInstallerChange() {
        $('#installerSelect').on('change', async (e) => {
            try {
                Order.oms.unspiderfy();
            } catch(error) {

            }

            const self = $(e.target);
            const installerId = self.val();

            const installTmpl = $('#installCardTmpl').html();
            const repairTmpl = $('#repairCardTmpl').html();
            const removalTmpl = $('#removalCardTmpl').html();
            const deliveryTmpl = $('#deliveryCardTmpl').html();

            const container = $('#installerCardContainer');
            container.empty();

            //Make sure to get selected date
            const routeDate = $('#routeDateSelect').val();

            //Api call to pull installer routes
            const url = `${helper.getSiteUrl()}/get/installer/orders`;
            const orders = await $.post(url, {installerId: installerId, route_date: routeDate});

            window.markers = {};
            this.loadRoutesOnMap(orders, installerId);

            //if (orders.length) {
                let html = '';
                $.each(orders, (i, order) => {
                    //console.log(order.address)
                    let address = helper.initialUppercaseWord(order.address);
                    if (address.length > 32) {
                        address = `${address.substr(0, 30)}...`;
                    }

                    if (order.assigned_to > 0 || installerId == 0) {
                        if (order.order_type == 'install') {
                            let tmpl = installTmpl.replace(/replace_address/g, address);
                            let agentOffice = order.office_name;
                            if (order.agent_name) {
                                agentOffice = `${order.agent_name}, ${order.office_name}`
                            }
                            agentOffice = helper.initialUppercaseWord(agentOffice);
                            if (agentOffice.length > 32) {
                                agentOffice = `${agentOffice.substr(0, 30)}...`;
                            }

                            tmpl = tmpl.replace(/agent_office/g, agentOffice);
                            tmpl = tmpl.replace(/post_name/g, order.post_name.length > 32 ?  `${order.post_name.substr(0, 30)}...` : order.post_name);
                            tmpl

                            html += tmpl;
                        }

                        if (order.order_type == 'repair') {
                            let tmpl = repairTmpl.replace(/replace_address/g, address);
                            let agentOffice = order.office_name;
                            if (order.agent_name) {
                                agentOffice = `${order.agent_name}, ${order.office_name}`
                            }
                            agentOffice = helper.initialUppercaseWord(agentOffice);
                            if (agentOffice.length > 32) {
                                agentOffice = `${agentOffice.substr(0, 30)}...`;
                            }
                            tmpl = tmpl.replace(/agent_office/g, agentOffice);
                            tmpl = tmpl.replace(/post_name/g, order.post_name.length > 32 ?  `${order.post_name.substr(0, 30)}...` : order.post_name);

                            html += tmpl;
                        }

                        if (order.order_type == 'removal') {
                            let tmpl = removalTmpl.replace(/replace_address/g, address);
                            let agentOffice = order.office_name;
                            if (order.agent_name) {
                                agentOffice = `${order.agent_name}, ${order.office_name}`
                            }
                            agentOffice = helper.initialUppercaseWord(agentOffice);
                            if (agentOffice.length > 32) {
                                agentOffice = `${agentOffice.substr(0, 30)}...`;
                            }
                            tmpl = tmpl.replace(/agent_office/g, agentOffice);
                            tmpl = tmpl.replace(/post_name/g, order.post_name.length > 32 ?  `${order.post_name.substr(0, 30)}...` : order.post_name);

                            html += tmpl;
                        }

                        if (order.order_type == 'delivery') {
                            let tmpl = deliveryTmpl.replace(/replace_address/g, address);
                            let agentOffice = order.office_name;
                            if (order.agent_name) {
                                agentOffice = `${order.agent_name}, ${order.office_name}`
                            }
                            agentOffice = helper.initialUppercaseWord(agentOffice);
                            if (agentOffice.length > 32) {
                                agentOffice = `${agentOffice.substr(0, 30)}...`;
                            }
                            tmpl = tmpl.replace(/agent_office/g, agentOffice);

                            let deliveryType = 'Pickup';
                            if (order.post_name == 1) {
                                deliveryType = 'Dropoff';
                            }
                            tmpl = tmpl.replace(/post_name/g, deliveryType);

                            html += tmpl;
                        }
                    }
                });

                container.append(html);
            //}
        });
    },

    onRemoveStops() {
        $('#removeStops').on('click', (e) => {
            let installer = 'All Installers';
            const installerId = $('#installerSelect').val();
            if (installerId > 0) {
                installer = $('#installerSelect option:selected').text();
            }

            $('#removeFor').html(installer);
            $('#removeStopsModal').modal();
        });

        $('#confirmRemoveStopsBtn').on('click', (e) => {
            helper.showLoader();
            const installerId = $('#installerSelect').val();

            const url = `${helper.getSiteUrl()}/order/remove/stops`;
            $.post(url, {installerId: installerId})
            .done((res) => {
                $('#installerSelect').trigger('change');
                helper.closeModal('removeStopsModal');
            });
        });
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

    getStatus(status, orderType) {
        let statusDescription = '';

        if (status == Order.status_received) {
            statusDescription = "Received";
        }

        if (status == Order.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == Order.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == Order.status_scheduled) {
            statusDescription = "Scheduled";
        }

        if (status == Order.status_completed) {
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

    onRouteDateChange() {
        $('#routeDateSelect').on('change', async (e) => {
            $('#installerSelect').trigger('change');
        });

        $('.pull-list-date-select').on('change', async (e) => {
            const self = $(e.target);

            const routeDate = self.val();
            const url = `${helper.getSiteUrl()}/order/status/pull-list/${routeDate}`;

            helper.showLoader();

            helper.redirectTo(url);
        });
    },

    onPullListDateChange() {
        $('.pull-list-date-select').on('change', async (e) => {
            const self = $(e.target);

            const routeDate = self.val();
            const url = `${helper.getSiteUrl()}/order/status/pull-list/${routeDate}`;

            helper.showLoader();

            helper.redirectTo(url);
        });
    },

    onPullListInstallerChange() {
        $('.pull-list-installer-select').on('change', async (e) => {
            const self = $(e.target);

            const routeDate = $('.pull-list-date-select').val();
            const installerId = self.val();
            const url = `${helper.getSiteUrl()}/order/status/pull-list/${routeDate}/${installerId}`;

            helper.showLoader();

            helper.redirectTo(url);
        });
    },

    onPullListCheckbox() {
        $('.pull-list-item').each((i, el) => {
            const elem = $(el);
            const name = elem.data('name');

            let today = helper.getDateString(new Date());
            today = today.replace(/\-/g, '');

            const key = `${today}-${name}`;

            //Clear yesterdays items
            let yesterday = helper.getDateString(helper.previousDay(new Date()));
            yesterday = yesterday.replace(/\-/g, '');
            for (let i = 0; i < localStorage.length; i++){
                if (helper.stringContains(localStorage.key(i), yesterday)) {
                    localStorage.removeItem(localStorage.key(i));
                }
            }

            if (localStorage.getItem(key) === null) {
                elem.prop('checked', false);
            } else {
                elem.prop('checked', true);
            }
        });

        $('.pull-list-item').on('change', (e) => {
            const self = $(e.target);
            const name = self.data('name');

            let today = helper.getDateString(new Date());
            today = today.replace(/\-/g, '');

            const key = `${today}-${name}`;

            if (self.is(':checked')) {
                localStorage.setItem(key, name)
            } else {
                localStorage.removeItem(key)
            }
        });
    }
}

export default Order;
