import helper from './helper';
import global from "./global";
import Payment from "./Payment";

const AgentRepairOrder = {

    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    repair_order_id: 0,
    countClickAccessory: {},
    countClickPanel: {},
    installedAccessories: {},
    order: {},
    zone: {},
    settings: {},
    total:0,
    totalPost:0,
    totalPanel:0,
    totalAccessories:0,
    accessories: [],
    create: true,
    payment_method_office_pay: 3,
    repairAccessories: {},
    repairAccessoryActions: {},

    dailyOrderCap: 0,
    countOrders: null,

    init() {
        this.loadDatatable();
        this.repairOrderSearchInput();
        this.showRepairOrderEntries();
        window.createRepairOrder = this.createRepairOrder;
        window.editRepairOrder = this.editRepairOrder;
        window.repairOrderCancel = this.repairOrderCancel;
        this. onDesiredDateChange();

        if (! helper.urlContains('/order/status')) {
            this.initRepairMap();
        }

        this.totalFee();

        //Disable holidays in calendar
        $.get(helper.getSiteUrl('/get/holidays')).done(holidays => {
            AgentRepairOrder.holidays = holidays;
        });

        $.get(helper.getSiteUrl('/get/zone/settings')).done(settings => {
            AgentRepairOrder.dailyOrderCap = settings.daily_order_cap;
        })

        $.get(helper.getSiteUrl('/agent/count-orders')).done(countorders => {
            AgentRepairOrder.countOrders = countorders;
        })

        window.onAccessoryChange = this.onAccessoryChange;
        window.onSignPanelClick = this.onSignPanelClick;

        this.onCommentChange();
        this.onFileUploaded();
        window.repairRemoveFile = this.repairRemoveFile;

        this.onSubmitForm();

        Payment.init()
        helper.cardNumberInput('.cc-number-input');

        window.eRepairRemoveFile = this.eRepairRemoveFile;
    },

    repairOrderCancel(repairOrderId) {
        helper.confirm("", "This action is irreversible!",
            () => {
                $.get(`/repair/order/${repairOrderId}/cancel`).done(res => {
                    //RepairOrder.table.api().draw();
                    window.location.reload();
                })
            },
            () => {}
        );
    },

    loadDatatable() {
        let tableId = '#repairOrdersTable';
        if (helper.isMobilePhone()) {
            tableId = '#repairOrdersTableMobile';
        }
        if (helper.isTablet()) {
            tableId = '#repairOrdersTableTablet';
        }

        AgentRepairOrder.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            infoCallback: function( settings, start, end, max, total, pre ) {
                return `Showing ${start} to ${end} of ${total} entries`;
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/agent/orders/repair"),
            serverSide: true,
            columns: [
                {
                    data: "address",
                    defaultContent: "",
                    title: "Address",
                    name: "orders.address",
                    visible: 1,
                },
                {
                    data: "repair_status",
                    defaultContent: "",
                    title: "Repair Status",
                    name: "status",
                    visible: 0
                },
                {
                    data: "repair_order_number",
                    defaultContent: "",
                    title: "Repair Order Id",
                    name: "order_number",
                    visible: 0
                },
                {
                    data: "order_number",
                    defaultContent: "",
                    title: "Order Id",
                    name: "orders.order_number",
                    visible: 0
                },
                {
                    data: "status",
                    defaultContent: "",
                    title: "Status",
                    name: "status",
                    searchable: false,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        let dateCompleted = r.desired_date;
                        if (r.desired_date_type == 1) { //Rush order
                            dateCompleted = r.updated_at
                        }

                        let html = `<span class="font-weight-bold" style="color: #267F00">
                            INSTALLED ${helper.formatDate(dateCompleted)}
                        </span>`;

                        if (r.order_type == 'repair') {
                            const status = parseInt(r.repair_status);

                            const serviceDate = r.desired_date_type == 1
                                ? 'Rush Order'
                                : helper.formatDate(r.desired_date);

                            html = `<span class="text-primary-dark font-weight-bold">
                                REPAIR ${serviceDate}
                            </span>`;

                            html += '<br>';
                            if (status == AgentRepairOrder.status_received) {
                                html += `<span class="badge badge-pill badge-primary">Received</span>`
                            } else if (status == AgentRepairOrder.status_incomplete) {
                                if (r.assigned_to > 0) {
                                    html += `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                                } else {
                                    html += `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                                }
                            } else if (status == AgentRepairOrder.status_scheduled) {
                                html += `<span class="badge badge-pill badge-info">Scheduled</span>`;
                            } else if (status == AgentRepairOrder.status_completed) {
                                html += `<span class="badge badge-pill badge-success">Completed</span>`;
                            } else if (status == AgentRepairOrder.status_cancelled) {
                                html += `<span class="badge badge-pill badge-danger">Cancelled</span>`;
                            }
                        }

                        return html;
                    }
                },
                {
                    defaultContent: "...",
                    title: "Action",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        let action = '<div class="d-flex justify-content-between">';

                        if (r.order_type == 'install') {
                            action += `<a class='link mx-1' onclick="window.createRepairOrder(${r.id})">
                                <img src="./images/Repair_Icon.png" title="Create" alt="Create" class="width-px-40">
                            </a>`;
                            action += '</div>';
                            return action;
                        }

                        //Open edit form if repair order exists
                        if (r.order_type == 'repair') {
                            const status = parseInt(r.repair_status);

                            //Order can only be edited if status = received
                            const canEdit = [
                                AgentRepairOrder.status_received,
                                AgentRepairOrder.status_incomplete
                            ];

                            if (canEdit.includes(status)) {
                                action += `<a class='link mx-1' onclick="window.editRepairOrder(${r.id})">
                                    <img src="./images/Repair_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                                </a>`;
                            }

                            //Order can only be cancelled if status = received/scheduled/incomplete
                            const canCancel = [
                                AgentRepairOrder.status_received,
                                AgentRepairOrder.status_scheduled,
                                AgentRepairOrder.status_incomplete
                            ];

                            if (canCancel.includes(status)) {
                                action += `<a class='link text-danger mx-1' onclick="window.repairOrderCancel(${r.id})">
                                    <img src="./images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                                </a>`;
                                action += '</div>';
                                return action;
                            }
                        }

                        action += '</div>';

                        return action;
                    }
                },
                {
                    data: "all_order_number",
                    defaultContent: "404",
                    title: "Order ID#",
                    name: "all_order_number",
                    searchable: false,
                    visible: 1,
                    render(d, t, r) {
                        let orderNumber = r.order_number;

                        return orderNumber;
                    }
                },
            ]
        });
    },

    repairOrderSearchInput() {
        $('body').on("keyup", '#repairOrderSearchInput', (event) => {
            let inputVal = event.target.value;

            AgentRepairOrder.table.fnFilter(inputVal);
        });
    },

    showRepairOrderEntries() {
        $('body').on("change", '#showRepairOrderEntries', (event) => {
            const selected = parseInt(event.target.value);
            AgentRepairOrder.table.api().context[0]._iDisplayLength = selected;
            AgentRepairOrder.table.api().draw();
        });
    },

    previousPanel: 0,
    onSignPanelClick(e){
        const self = $(e.target);
        const price = self.data('price');
        const id = self.val();

        AgentRepairOrder.countClickPanel[id] = AgentRepairOrder.countClickPanel[id] || 0;
        AgentRepairOrder.countClickPanel[id] = parseInt(AgentRepairOrder.countClickPanel[id]) + 1;

        if (AgentRepairOrder.countClickPanel[id] == 1) {
            AgentRepairOrder.totalPanel = price;
            AgentRepairOrder.setRepairFee();

            //Display panel image
            $('#repair_sign_image_preview').removeClass('d-none').prop('src', self.data('image')).show();

            AgentRepairOrder.previousPanel = id;
        } else {
            if (AgentRepairOrder.previousPanel == id) {
                self.prop('checked', false);
                AgentRepairOrder.countClickPanel[id] = 0;

                AgentRepairOrder.totalPanel = 0;
                AgentRepairOrder.setRepairFee();

                $('#repair_sign_image_preview').addClass('d-none').prop('src', '');

                if (AgentRepairOrder.existingPanelImg) {
                    $('#repair_sign_image_preview').removeClass('d-none').prop('src', AgentRepairOrder.existingPanelImg).show();
                }
            } else {
                AgentRepairOrder.totalPanel = price;
                AgentRepairOrder.setRepairFee();

                //Display panel image
                $('#repair_sign_image_preview').removeClass('d-none').prop('src', self.data('image')).show();

                AgentRepairOrder.previousPanel = id;
            }
        }
    },

    onAccessoryChange(e) {
        const self = $(e.target);
        const id = self.val();

        //Only calculate accessories not included in install order
        let existingItem = false;
        $.each(AgentRepairOrder.installedAccessories, (i, r) => {
            if (r.accessory_id == id) {
                existingItem = true;
            }
        });

        AgentRepairOrder.countClickAccessory[id] = AgentRepairOrder.countClickAccessory[id] || 0;

        AgentRepairOrder.countClickAccessory[id] = parseInt(AgentRepairOrder.countClickAccessory[id]) + 1;

        //Custom checkbox icons
        self.addClass('css-checkbox');

        let imagesContainer = $(".repair-order-preview-images");

        self.removeClass('repair-accessory add-replace');
        self.removeClass('repair-accessory remove');

        if (self.is(':checked')) {
            imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${self.data('image')}" id="repairAccessoryImg${self.val()}">`);

            //Replacing
            if ( ! $(`#accessory_icon_${id}`).hasClass('fa-plus-square')) {
                $(`#accessory_icon_${id}`).addClass('fa-plus-square');
            }
            $(`#accessory_icon_${id}`).removeClass('fa-minus-square').show();
            $(`#accessory_icon_${id}`).removeClass('fa-minus-square').show();

            self.addClass('repair-accessory add-replace');
        } else {
            $(`#repairAccessoryImg${self.val()}`).remove();

            if ( ! existingItem) {
                $(`#accessory_icon_${id}`).hide();
                self.removeClass('css-checkbox');
                self.removeClass('add-accessory');
            } else {
                if (AgentRepairOrder.countClickAccessory[id] > 1) {
                    //Back to regular checkbox
                    $(`#accessory_icon_${id}`).hide();
                    self.removeClass('css-checkbox');
                    self.prop('checked', true);
                    AgentRepairOrder.countClickAccessory[id] = 0;
                    imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${self.data('image')}" id="repairAccessoryImg${self.val()}">`);
                } else {
                    //Removing
                    $(`#accessory_icon_${id}`).removeClass('fa-plus-square').addClass('fa-minus-square').show();
                    self.addClass('repair-accessory remove');
                }
            }
        }

        let totalAccessories = 0;
        AgentRepairOrder.accessories = [];
        $(`[name="repair_order_accessories[]"]`).each((i, el)=> {
            const elem = $(el);

            existingItem = false;
            $.each(AgentRepairOrder.installedAccessories, (i, r) => {
                if (r.accessory_id == el.value) {
                    existingItem = true;
                }
            });

            if ( ! existingItem && elem.is(':checked')) {
                totalAccessories = totalAccessories + parseFloat(el.dataset.price);
            }

            AgentRepairOrder.accessories[i] = {};
            if (elem.hasClass('repair-accessory add-replace')) {
                AgentRepairOrder.accessories[i].accessory_id = el.value;
                AgentRepairOrder.accessories[i].action = 0;
            }
            if (elem.hasClass('repair-accessory remove')) {
                AgentRepairOrder.accessories[i].accessory_id = el.value;
                AgentRepairOrder.accessories[i].action = 1;
            }
            AgentRepairOrder.accessories = helper.removeEmptyObjectFromArray(AgentRepairOrder.accessories);
            console.log(AgentRepairOrder.accessories);
        })

        AgentRepairOrder.totalAccessories = totalAccessories;

        AgentRepairOrder.setRepairFee();
    },

    loadOfficePanels(order) {
        const officeId = order.office.id;
        $.get(helper.getSiteUrl(`/get/office/${officeId}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-signs");
            listContainer.empty();
            let html = '';
            res.forEach(panel => {
                let isChecked = '';

                let panelId = 0;
                if (AgentRepairOrder.create) {
                    panelId = order.panel_id;
                } else {
                    if (order.repair && order.repair.panel_id) {
                        panelId = order.repair.panel_id;
                    }
                }

                if (panel.id == panelId) {
                    isChecked = 'checked';

                    /*AgentRepairOrder.totalPanel = panel.price;
                    AgentRepairOrder.setRepairFee();

                    //Display panel image
                    const panelImage = helper.getSiteUrl(`/private/image/panel/${panel.image_path}`);
                    $('#repair_sign_image_preview').removeClass('d-none').prop('src', panelImage);*/
                }

                html += `
                    <div class="form-check d-flex justify-content-between">
                        <input type="radio" name="repair_order_panel" value="${panel.id}"
                            data-price="${panel.price}"
                            data-image="${helper.getSiteUrl(`/private/image/panel/${panel.image_path}`)}"
                            class="form-check-input" id="repair_panel_option_${panel.id}"
                            onclick="window.onSignPanelClick(event)"
                        >
                        <label class="form-check-label text-dark" for="repair_panel_option_${panel.id}">${panel.panel_name}</label>
                        <span>$${panel.price}</span>
                    </div>
                `;
            })

            listContainer.append(html);

            if (! AgentRepairOrder.create) {
                AgentRepairOrder.rowCount = 0;
                if (order.repair.adjustments) {
                    AgentRepairOrder.loadSavedAdjustments(order.repair.adjustments);
                }
            }
        });
    },

    loadOfficeAccessories(order) {
        const officeId = order.office.id;
        $.get(helper.getSiteUrl(`/get/office/${officeId}/accessories`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-accessories-repair");
            listContainer.empty();
            let html = '';
            let imagesContainer = $(".repair-order-preview-images");
            $('.accessory_image_preview').remove();

            AgentRepairOrder.installedAccessories = order.accessories;

            let orderAccessories = order.accessories;
            if ( ! AgentRepairOrder.create) {
                if (order.repair && ! $.isEmptyObject(order.repair.accessories)) {
                    orderAccessories = order.repair.accessories;
                    AgentRepairOrder.repairAccessories = order.repair.accessories;
                }
            }

            let totalAccessories = 0;
            let itemExists = [];
            let action = -1;
            res.forEach(a => {
                let isChecked = '';

                //When editing repair order, make sure to check installed items
                if (! AgentRepairOrder.create) {
                    $.each(AgentRepairOrder.installedAccessories, (i, r) => {
                        if (r.accessory_id == a.id) {
                            isChecked = 'checked';
                            itemExists[a.id] = true;
                        }
                    });
                }

                $.each(orderAccessories, (key, row) => {
                    //console.log(row.accessory_id, a.id)
                    if (row.accessory_id == a.id) {
                        isChecked = 'checked';

                        const image = `${helper.getSiteUrl('/private/image/accessory/' + a.image)}`;

                        //Only calculate accessories not included in install order
                        //or accessories includd in repair order
                        itemExists[a.id] = false;
                        $.each(AgentRepairOrder.installedAccessories, (i, r) => {
                            if (r.accessory_id == a.id) {
                                itemExists[a.id] = true;
                            }
                        });

                        if (! AgentRepairOrder.create && ! itemExists[a.id]) {
                            totalAccessories = totalAccessories + parseFloat(a.price);
                        }

                        //If accessory is included in repair order then ignore existing
                        //accessory in install order
                        if (! AgentRepairOrder.create) {
                            itemExists[a.id] = false;

                            //get repair action for the accessory
                            action = row.action;

                            //Display accessory image if is to add
                            if (action == 0) {
                                imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}" id="repairAccessoryImg${a.id}">`)
                            }
                        }

                        //Load accessories images
                        if (AgentRepairOrder.create || itemExists[a.id]) {
                            imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}" id="repairAccessoryImg${a.id}">`)
                        }
                    }
                });

                html =`
                <div class="form-check d-flex justify-content-between">
                    <input type="checkbox" name="repair_order_accessories[]" value="${a.id}"
                        data-price="${a.price}"
                        data-image="${helper.getSiteUrl('/private/image/accessory/' + a.image)}"
                        class="form-check-input"
                        id="repair_accessory_option_${a.id}"
                        ${isChecked}
                        ${isChecked ? 'ignore-in-total' : ''}
                        onchange="window.onAccessoryChange(event)"
                    >
                    <label class="form-check-label text-dark css-label" for="repair_accessory_option_${a.id}">
                        <span id="accessory_icon_${a.id}" class="fa fa-plus-square"></span>
                        <span class="pl-1">${a.accessory_name}<span>
                    </label>
                    <span>$${a.price}</span>
                </div>
                `;

                listContainer.append(html);

                //Manually change icons when editing repair order
                if (! AgentRepairOrder.create && isChecked && ! itemExists[a.id]) {
                      if (action == 0) {
                        $(`#repair_accessory_option_${a.id}`).prop('checked', true);
                        $(`#repair_accessory_option_${a.id}`).addClass('css-checkbox');
                        $(`#repair_accessory_option_${a.id}`).addClass('repair-accessory add-replace');
                        $(`#accessory_icon_${a.id}`).removeClass('fa-minus-square').addClass('fa-plus-square').show();
                    }

                    if (action == 1) {
                        $(`#repair_accessory_option_${a.id}`).prop('checked', false);
                        $(`#repair_accessory_option_${a.id}`).addClass('css-checkbox');
                        $(`#repair_accessory_option_${a.id}`).addClass('repair-accessory remove');
                        $(`#accessory_icon_${a.id}`).removeClass('fa-plus-square').addClass('fa-minus-square').show();
                    }
                }

                //trigger click to change icons
                /*if ( ! AgentRepairOrder.create && ! itemExists[a.id]) {
                    $(`#repair_accessory_option_${a.id}`).trigger('change');
                }*/
            })

            if (! AgentRepairOrder.create) {
                AgentRepairOrder.totalAccessories = totalAccessories;

                AgentRepairOrder.setRepairFee();
            }
        });
    },

    loadAgentPanels(order) {
        const agentId = order.agent.id;
        $.get(helper.getSiteUrl(`/get/agent/${agentId}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-signs");
            listContainer.empty();
            let html = '';
            res.forEach(panel => {
                let isChecked = '';

                let panelId = 0;
                if (AgentRepairOrder.create) {
                    panelId = order.panel_id;
                } else {
                    if (order.repair && order.repair.panel_id) {
                        panelId = order.repair.panel_id;
                    }
                }

                if (panel.id == panelId) {
                    isChecked = 'checked';

                    /*AgentRepairOrder.totalPanel = panel.price;
                    AgentRepairOrder.setRepairFee();*/
                }

                html += `
                    <div class="form-check d-flex justify-content-between">
                        <input type="radio" name="repair_order_panel" value="${panel.id}"
                            data-price="${panel.price}"
                            data-image="${helper.getSiteUrl(`/private/image/panel/${panel.image_path}`)}"
                            class="form-check-input" id="repair_panel_option_${panel.id}"
                            onclick="window.onSignPanelClick(event)"
                        >
                        <label class="form-check-label text-dark" for="repair_panel_option_${panel.id}">${panel.panel_name}</label>
                        <span>$${panel.price}</span>
                    </div>
                `;
            })

            listContainer.append(html);

            if (! AgentRepairOrder.create) {
                AgentRepairOrder.rowCount = 0;
                if (order.repair.adjustments) {
                    AgentRepairOrder.loadSavedAdjustments(order.repair.adjustments);
                }
            }
        });
    },

    loadAgentAccessories(order) {
        const agentId = order.agent.id;
        $.get(helper.getSiteUrl(`/get/agent/${agentId}/accessories`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-accessories-repair");
            listContainer.empty();
            let html = '';
            let imagesContainer = $(".repair-order-preview-images");
            $('.accessory_image_preview').remove();

            AgentRepairOrder.installedAccessories = order.accessories;

            let orderAccessories = order.accessories;
            if ( ! AgentRepairOrder.create) {
                if (order.repair && ! $.isEmptyObject(order.repair.accessories)) {
                    orderAccessories = order.repair.accessories;
                    AgentRepairOrder.repairAccessories = order.repair.accessories;
                }
            }

            let totalAccessories = 0;
            let itemExists = [];
            let action = -1;
            res.forEach(a => {
                let isChecked = '';

                //When editing repair order, make sure to check installed items
                if (! AgentRepairOrder.create) {
                    $.each(AgentRepairOrder.installedAccessories, (i, r) => {
                        if (r.accessory_id == a.id) {
                            isChecked = 'checked';
                            itemExists[a.id] = true;
                        }
                    });
                }

                $.each(orderAccessories, (key, row) => {
                    //console.log(row.accessory_id, a.id)
                    if (row.accessory_id == a.id) {
                        isChecked = 'checked';

                        const image = `${helper.getSiteUrl('/private/image/accessory/' + a.image)}`;

                        //Only calculate accessories not included in install order
                        //or accessories includd in repair order
                        itemExists[a.id] = false;
                        $.each(AgentRepairOrder.installedAccessories, (i, r) => {
                            if (r.accessory_id == a.id) {
                                itemExists[a.id] = true;
                            }
                        });

                        if (! AgentRepairOrder.create && ! itemExists[a.id]) {
                            totalAccessories = totalAccessories + parseFloat(a.price);
                        }

                        //If accessory is included in repair order then ignore existing
                        //accessory in install order
                        if (! AgentRepairOrder.create) {
                            itemExists[a.id] = false;

                            //get repair action for the accessory
                            action = row.action;

                            //Display accessory image if is to add
                            if (action == 0) {
                                imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}" id="repairAccessoryImg${a.id}">`)
                            }
                        }

                        //Load accessories images
                        if (AgentRepairOrder.create || itemExists[a.id]) {
                            imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}" id="repairAccessoryImg${a.id}">`)
                        }
                    }
                });

                html =`
                <div class="form-check d-flex justify-content-between">
                    <input type="checkbox" name="repair_order_accessories[]" value="${a.id}"
                        data-price="${a.price}"
                        data-image="${helper.getSiteUrl('/private/image/accessory/' + a.image)}"
                        class="form-check-input"
                        id="repair_accessory_option_${a.id}"
                        ${isChecked}
                        ${isChecked ? 'ignore-in-total' : ''}
                        onchange="window.onAccessoryChange(event)"
                    >
                    <label class="form-check-label text-dark css-label" for="repair_accessory_option_${a.id}">
                        <span id="accessory_icon_${a.id}" class="fa fa-plus-square"></span>
                        <span class="pl-1">${a.accessory_name}<span>
                    </label>
                    <span>$${a.price}</span>
                </div>
                `;

                listContainer.append(html);

                //Manually change icons when editing repair order
                if (! AgentRepairOrder.create && isChecked && ! itemExists[a.id]) {
                      if (action == 0) {
                        $(`#repair_accessory_option_${a.id}`).prop('checked', true);
                        $(`#repair_accessory_option_${a.id}`).addClass('css-checkbox');
                        $(`#repair_accessory_option_${a.id}`).addClass('repair-accessory add-replace');
                        $(`#accessory_icon_${a.id}`).removeClass('fa-minus-square').addClass('fa-plus-square').show();
                    }

                    if (action == 1) {
                        $(`#repair_accessory_option_${a.id}`).prop('checked', false);
                        $(`#repair_accessory_option_${a.id}`).addClass('css-checkbox');
                        $(`#repair_accessory_option_${a.id}`).addClass('repair-accessory remove');
                        $(`#accessory_icon_${a.id}`).removeClass('fa-plus-square').addClass('fa-minus-square').show();
                    }
                }

                //trigger click to change icons
                /*if ( ! AgentRepairOrder.create && ! itemExists[a.id]) {
                    $(`#repair_accessory_option_${a.id}`).trigger('change');
                }*/
            })

            if (! AgentRepairOrder.create) {
                AgentRepairOrder.totalAccessories = totalAccessories;

                AgentRepairOrder.setRepairFee();
            }
        });
    },

    setOfficeAndAgent(order) {
        $('#repairOrderOffice').val(order.office.user.name);
        if (order.agent) {
            $('#repairOrderAgent').val(order.agent.user.name);
            this.loadAgentPanels(order);
            this.loadAgentAccessories(order);
        } else {
            this.loadOfficePanels(order);
            this.loadOfficeAccessories(order);
        }
    },

    googleKey: global.googleKey,
    initRepairMap() {
        window.initRepairMap = this.startRepairMap;

        const src = `https://maps.googleapis.com/maps/api/js?key=${AgentRepairOrder.googleKey}&callback=window.initRepairMap&libraries=drawing,geometry,places&v=weekly`;
        $("body").append(window.e("script", { src, googlescript: true }));
    },

    startRepairMap() {
        const defaultLocation = {
            lat: 43.633994,
            lng: -116.433707,
        };
        const map = new google.maps.Map(document.getElementById("repairOrderMap"),
            {
                zoom: 11,
                center: defaultLocation,
            }
        );
        window.repairMap = map;
    },

    loadAddressOnMap(address, position) {
        window.repairMap.setCenter(position);
        const map = window.repairMap;

        let icon = {
            url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
            anchor: new google.maps.Point(0, 50)
        };
        new google.maps.Marker({
            position,
            map,
            title: address,
            icon,
            draggable: false,
        });

        window.repairMap.setZoom(17);
    },

    setPropertyInfo(order) {
        $('#repairOrderAddress').val(order.address.split(',')[0]);
        $('#repairOrderCity').val(order.address.split(',')[1]);
        //Set selected state
        $(`#repairOrderState option[value=${order.address.split(',')[2]?.trimStart().substr(0, 2)}]`).attr('selected', 'selected');

        //Map
        AgentRepairOrder.loadAddressOnMap(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)});

        $('#repairOrderPropertyType').find(`option[value="${order.property_type}"]`).prop('selected', true);
    },

    async getRepairZone(orderId) {
        const zone = await $.get(helper.getSiteUrl(`/repair/get/zone/${orderId}`));

        return zone;
    },

    movedNextMonth: false,
    updateCalendar(savedDate) {
        $("#repairOrderDatePicker").datepicker("destroy");
        $("#repairOrderDatePicker").datepicker({
            onSelect: function (dateText) {
                //console.log(dateText)
                $(`[name="repair_order_custom_desired_date"]`).val(dateText);
                return AgentRepairOrder.updateCalendar(helper.parseUSDate(dateText));
            },
            beforeShowDay: function (date) {
                let dateString = helper.getDateStringUsa(date);

                if (AgentRepairOrder.holidays.includes(dateString)) {
                    return [false];
                }

                let serviceDate = $.datepicker.formatDate('yy-mm-dd', new Date(date));
                let ordersCount = AgentRepairOrder.countOrders[serviceDate];
                if (typeof ordersCount !== 'undefined') {
                    if (ordersCount >= AgentRepairOrder.dailyOrderCap) {
                        return [false];
                    }
                }

                //Disable past dates and current date
                let today = helper.getDateStringUsa(new Date());
                let daysdiff = helper.diffDays(dateString, today);
                let cudate = today;
                let cdate = dateString;

                let mSavedDate = '';
                if (savedDate) {
                    mSavedDate = helper.getDateStringUsa(savedDate);
                }

                //If past cutoff time then disable next day
                if (helper.isNextDay(cudate, dateString)) {
                    //Check cuttof time
                    if (helper.isCutoffTime()) {
                        return [false];
                    }
                }

                if (cudate == cdate) {
                    if (cudate == mSavedDate) {
                        return [true, 'ui-state-highlight ui-state-active', ''];
                    }

                    return [false]; //disable current date
                }

                if (daysdiff >= 1) {
                    if (mSavedDate == cdate) {
                        return [true, 'ui-state-highlight ui-state-active', ''];
                    }

                    return [false]; //disable past date
                }

                //Disable non working days from the selected zone
                if (AgentRepairOrder.zone.data) {
                    let zone = AgentRepairOrder.zone.data;
                    let su = zone.su;
                    let mo = zone.m;
                    let tu = zone.tu;
                    let we = zone.w;
                    let th = zone.th;
                    let fr = zone.f;
                    let sa = zone.sa;
                    //order is important
                    let week = [su, mo, tu, we, th, fr, sa];

                    for (let index = 0; index < week.length; index++) {
                        const day = week[index];
                        if (date.getDay() === index) {
                            return [day];
                        }
                    }
                    return [true];
                } else {
                    return [true];
                }
            },
        });
        if (savedDate) {
            const usDate = helper.getDateStringUsa(savedDate);
            $("#repairOrderDatePicker").datepicker('setDate', usDate);
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !AgentRepairOrder.movedNextMonth && AgentRepairOrder.create) {
                //Delay trigger to give enough ttime for the calendar to update
                //Ideally we would have a afterRender event for this but jQuery UI doesn't have it
                AgentRepairOrder.movedNextMonth = true;
                setTimeout(() => {
                    $('#repairOrderDatePicker .ui-datepicker-next').trigger("click");
                }, 3000);
            }
        }
    },

    setDate(repairOrder) {
        let datePicker = $("#repairOrderDatePicker");
        if (repairOrder.service_date_type == 1) {
            AgentRepairOrder.updateCalendar(false);

            $(`[name="repair_order_desired_date"][value="asap"]`).prop('checked', true);
            datePicker.addClass("d-none");
        } else {
            $('#rushFeeRepair').addClass('d-none');
            $(`[name="repair_order_desired_date"][value="custom_date"]`).prop('checked', true);
            $(`[name="repair_order_desired_date"][value="custom_date"]`).trigger('change')
            setTimeout(()=>{
                datePicker.removeClass("d-none");
                //datePicker.datepicker("setDate", d);
                $(`[name="repair_order_custom_desired_date"]`).val(repairOrder.service_date);

                AgentRepairOrder.updateCalendar(helper.parseDate(repairOrder.service_date));
            }, 1000);
        }
    },

    setPostSignAccessories(order) {
        $('label[for="repairOrderPost"]').html(order.post.post_name);

        if ( ! AgentRepairOrder.create) {
            AgentRepairOrder.rowCount = 0;
            if (order.repair.adjustments) {
                AgentRepairOrder.loadSavedAdjustments(order.repair.adjustments);
            }

            let totalPost = 0;
            if (order.repair && order.repair.replace_repair_post) {
                $('#repair_replace_post').prop('checked', true);

                totalPost = parseFloat(totalPost) + parseFloat($('#repair_replace_post').val());
            } else {
                $('#repair_replace_post').prop('checked', false);
            }

            if (order.repair && order.repair.relocate_post) {
                $('#relocate_post').prop('checked', true);

                totalPost = totalPost + parseFloat($('#relocate_post').val());
            } else {
                $('#relocate_post').prop('checked', false);
            }

            AgentRepairOrder.totalPost = totalPost;

            AgentRepairOrder.setRepairFee();
        } else {
            $('#repair_replace_post').prop('checked', false);
            $('#relocate_post').prop('checked', false);
        }

        const postImage = helper.getSiteUrl(`/private/image/post/${order.post.image_path}`);
        $('#repair_post_image_preview').removeClass('d-none').prop('src', postImage).show();

        let panel = {};
        if (order.repair && order.repair.panel) {
            panel = order.repair.panel;

            AgentRepairOrder.totalPanel = panel.price;;

            AgentRepairOrder.setRepairFee();
        } else {
            panel = order.panel;
        }

        if (panel) {
            $('#selectedPanel').show();
            $('label[for="repairOrderPanel"]').html(panel.panel_name);
            const panelImage = helper.getSiteUrl(`/private/image/panel/${panel.image_path}`);
            $('#repair_sign_image_preview').removeClass('d-none').prop('src', panelImage).show();

            AgentRepairOrder.existingPanelImg = panelImage;
        } else {
            $('#selectedPanel').hide();
        }
    },

    resetForm() {
        this.startRepairMap();

        $('#repair_post_image_preview').addClass('d-none');

        $('#repairOrderAgent').val('');
        $(`[name="repair_order_comment"]`).val('');
        $("[repair-adjustments]").html("$0.00");
        AgentRepairOrder.totalAdjusted = 0;

        this.resetTotals();
        this.setRushFee(0);
        this.setRepairFee();

        $(`[name="repair_order_desired_date"][value="custom_date"]`).trigger('click');

        $(".list-container-accessories-repair").empty();
    },

    createRepairOrder(orderId) {
        AgentRepairOrder.resetForm();

        helper.showLoader();
        $.get('/repair/get/install-order/' + orderId).done(async order => {
            AgentRepairOrder.create = true;
            AgentRepairOrder.order = order;
            AgentRepairOrder.order_id = AgentRepairOrder.order.id;

            AgentRepairOrder.zone = await AgentRepairOrder.getRepairZone(AgentRepairOrder.order.id);
            AgentRepairOrder.settings = await helper.getZoneSettings();

            AgentRepairOrder.setOfficeAndAgent(order);
            AgentRepairOrder.setPropertyInfo(order);
            AgentRepairOrder.setPostSignAccessories(order);
            AgentRepairOrder.movedNextMonth = false;
            AgentRepairOrder.updateCalendar(false);
            AgentRepairOrder.setFooter();

            if (AgentRepairOrder.zone.data && AgentRepairOrder.settings) {
                const zone = AgentRepairOrder.zone.data;
                const settings = AgentRepairOrder.settings;

                const zoneFee = parseFloat(zone.zone_fee) * settings.repair / 100;
                $(`[name="repair_order_zone_fee"]`).val(zoneFee);
                $('[repair-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                $(`[name="repair_order_zone_fee"]`).trigger('change');
            }

            helper.hideLoader('repairOrderModal');
        });
    },

    setFooter() {
        if (AgentRepairOrder.create) {
            $('#submitRepairOrder').html('<strong class="text-white">SUBMIT REPAIR</strong>').prop('disabled', false);
        } else {
            $('#submitRepairOrder').html('<strong class="text-white">UPDATE ORDER</strong>').prop('disabled', false);
        }
    },

    setComment(repairOrder) {
        $(`[name="repair_order_comment"]`).val(repairOrder.comment);
    },

    setAttachments(repairOrder) {
        $("#files_list_repair").html(` `);
        repairOrder.attachments.forEach(file => {
            $("#files_list_repair").append(`
                <li>
                    <span>
                    <a target="_blank" href="${helper.getSiteUrl(`/private/document/file/${file.file_name}`)}"><strong>${file.file_name}</strong></a> UPLOADED ${helper.formatDateTime(file.created_at)}
                    <a class="text-danger c-p" onclick="window.eRepairRemoveFile(event, ${file.id})"><strong>REMOVE</strong></a>
                    </span>
                </li>
            `)
        })
    },

    editRepairOrder(orderId) {
        AgentRepairOrder.resetForm();

        helper.showLoader()
        $.get('/repair/get/order/' + orderId).done(async repairOrder => {
            AgentRepairOrder.create = false;

            //console.log(repairOrder.order);
            AgentRepairOrder.repair_order_id = repairOrder.id;

            AgentRepairOrder.order = repairOrder.order;
            AgentRepairOrder.order_id = AgentRepairOrder.order.id;

            AgentRepairOrder.zone = await AgentRepairOrder.getRepairZone(AgentRepairOrder.order.id);
            AgentRepairOrder.settings = await helper.getZoneSettings();

            AgentRepairOrder.setOfficeAndAgent(repairOrder.order);
            AgentRepairOrder.setPropertyInfo(repairOrder.order);
            AgentRepairOrder.setPostSignAccessories(repairOrder.order);
            AgentRepairOrder.setDate(repairOrder, false);
            AgentRepairOrder.setComment(repairOrder);
            AgentRepairOrder.setAttachments(repairOrder);
            AgentRepairOrder.setFooter();
            AgentRepairOrder.setRushFee(repairOrder.rush_fee);

            //Only calculate if repair order has fees
            if (repairOrder.total > 0) {
                if (AgentRepairOrder.zone.data && AgentRepairOrder.settings) {
                    const zone = AgentRepairOrder.zone.data;
                    const settings = AgentRepairOrder.settings;

                    const zoneFee = parseFloat(zone.zone_fee) * settings.repair / 100;
                    $(`[name="repair_order_zone_fee"]`).val(zoneFee);
                    $('[repair-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                    $(`[name="repair_order_zone_fee"]`).trigger('change');
                }
            } else {
                AgentRepairOrder.totalPost = 0;
                AgentRepairOrder.totalPanel = 0;
                AgentRepairOrder.totalAccessories = 0;
                $(`[name="repair_order_zone_fee"]`).val(0);
                $(`[name="repair_trip_fee"]`).val(0);
                $(`[name="repair_order_fee"]`).val(0);
                $('[repair-fee]').html(`$0.00`);
                $('[repair-trip-fee]').html(`$0.00`);
                $(`[name="repair_order_zone_fee"]`).trigger('change');
            }

            /* AgentRepairOrder.rowCount = 0;
            if (repairOrder.adjustments) {
                AgentRepairOrder.loadSavedAdjustments(repairOrder.adjustments);
            } */

            helper.hideLoader('repairOrderModal');
        });
    },

    onDesiredDateChange() {
        let dates_input = document.getAll(`[name="repair_order_desired_date"]`);
        let datepicker = $("#repairOrderDatePicker");
        dates_input.forEach((d) => {
            d.onchange = (e) => {
                let type = e.target.value;
                $(`[name="repair_order_desired_date"]`).removeAttr('checked')
                $(e.target).prop('checked', true);
                if (type === "custom_date") {
                    AgentRepairOrder.setRushFee(0)
                    datepicker.removeClass("d-none");
                    $('#rushFeeRepair').addClass('d-none');
                } else {
                    $("#repairRushOrderModal").modal();
                    datepicker.addClass("d-none");
                }
            };
        });

        $("[repair-rush-fee-decline-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            this.setRushFee(0)
            $(`[name="repair_order_desired_date"][value="custom_date"]`).trigger('click');
        });

        $("[repair-rush-fee-accept-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            const rush_fee =  $('[name="repair_order_desired_date"]').attr('repair-rush-order-fee');
            this.setRushFee(rush_fee)
        });
    },

    setRushFee(value) {
        let rush_fee_input = $(`input[name="repair_order_rush_fee"]`);
        rush_fee_input.val(value)
        rush_fee_input.trigger('change');

        if (value > 0) {
            $('#rushFeeRepair').removeClass('d-none');
        }
    },

    totalFee() {
        $(`[name="repair_order_rush_fee"]`).on("change", (e) => {
            const total = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories) + AgentRepairOrder.getTotalFees() + parseFloat(AgentRepairOrder.totalAdjusted);
            $(`[repair-total]`).html(`$${total.toFixed(2)}`);

            AgentRepairOrder.total = total;
        });
        $(`[name="repair_order_fee"]`).on("change", (e) => {
            const total = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories) + AgentRepairOrder.getTotalFees() + parseFloat(AgentRepairOrder.totalAdjusted);
            $(`[repair-total]`).html(`$${total.toFixed(2)}`);

            AgentRepairOrder.total = total;
        });
        $(`[name="repair_order_zone_fee"]`).on("change", (e) => {
            const total = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories) + AgentRepairOrder.getTotalFees() + parseFloat(AgentRepairOrder.totalAdjusted);
            $(`[repair-total]`).html(`$${total.toFixed(2)}`);

            AgentRepairOrder.total = total;
        });
        $(`[name="repair_options_post[]"]`).on("change", (e) => {
            const self = $(e.target);

            if (self.is(':checked')) {

                let totalPost = 0;
                $(`[name="repair_options_post[]"]`).each((i, el)=> {
                    if (el.checked) {
                        totalPost = totalPost + parseFloat(el.value);
                    }
                })

                AgentRepairOrder.totalPost = totalPost;

                const total = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories) + AgentRepairOrder.getTotalFees() + parseFloat(AgentRepairOrder.totalAdjusted);
                $(`[repair-total]`).html(`$${total.toFixed(2)}`);

                const repairFee = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories);
                $(`[repair-fee]`).html(`$${repairFee.toFixed(2)}`);

                AgentRepairOrder.total = total;
            } else {
                AgentRepairOrder.totalPost = AgentRepairOrder.totalPost - parseFloat(self.val());

                const total = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories) + AgentRepairOrder.getTotalFees() + parseFloat(AgentRepairOrder.totalAdjusted);
                $(`[repair-total]`).html(`$${total.toFixed(2)}`);

                const repairFee = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories);
                $(`[repair-fee]`).html(`$${repairFee.toFixed(2)}`);

                AgentRepairOrder.total = total;
            }
        });
    },

    getTotalFees() {
        const rush_fee = $(`[name="repair_order_rush_fee"]`).val();
        const zone_fee = $(`[name="repair_order_zone_fee"]`).val();
        const repairTripFee = $(`[name="repair_trip_fee"]`).val();

        return parseFloat(repairTripFee) + parseFloat(zone_fee) + parseFloat(rush_fee);
    },

    onCommentChange() {
        let textarea = $(`[name="repair_order_comment"]`);
        if (textarea.length) {
            textarea.on("keyup", (e) => {
                let len = (textarea.val() || "").trim().length;
                $(".char-used").html(len);
                if (len > 500) {
                    textarea.addClass("is-invalid");
                } else {
                    textarea.removeClass("is-invalid");
                }
            });
        }
    },

    _files: [],
    onFileUploaded() {
        let files = $(`input[name="repair_order_files[]"]`);
        if (files.length) {
            files.on("change", (e) => {
                let file_input = e.target;
                let files = file_input.files;
                for (let file of files) {
                    file._id = helper.genId();
                    AgentRepairOrder._files.push(file);
                    AgentRepairOrder.displayFiles(this._files);
                    AgentRepairOrder.setFiles(this._files);
                }
            });
        }
    },

    setFiles(files) {
        let input = $(`input[name="repair_order_files[]"]`);
        if (input.length) {
            input.files = files;
        }
    },

    displayFiles(files) {
        let files_list = $("#files_list_repair");
        if (files_list.length) {
            files_list.html('');
            files.forEach((file) => {
                files_list.append(`
                <li>
                    <span>
                        <a href="#"><strong>${file.name}</strong></a>
                        UPLOADED ${helper.formatDateTime((new Date).toISOString())}
                        <a class='text-danger c-p' onclick="window.repairRemoveFile('${file._id}')">
                        <strong>REMOVE</strong></a>
                    </span>
                </li>`);
            });
        }
    },

    async repairRemoveFile(id) {
        let new_files = await AgentRepairOrder._files.filter((file) => file._id != id);
        AgentRepairOrder._files = new_files;
        AgentRepairOrder.setFiles(AgentRepairOrder._files);
        AgentRepairOrder.displayFiles(AgentRepairOrder._files);


        if (!AgentRepairOrder._files.length) {
            // If all files were removed, reset files input to allow adding a new one that was already assigned
            $(`input[name="repair_order_files[]"]`).val('');
        }
    },

    eRepairRemoveFile(event, id) {
        $.get(`/repair/order/delete/file/${id}`).done(res => {
            event.target.parentNode.parentNode.parentNode.remove();
        });
    },

    setRepairFee() {
        const repairFee = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories);
        $(`[repair-fee]`).html(`$${repairFee.toFixed(2)}`);
        $(`[name="repair_order_fee"]`).val(repairFee);

        const total = parseFloat(AgentRepairOrder.totalPost) + parseFloat(AgentRepairOrder.totalPanel) + parseFloat(AgentRepairOrder.totalAccessories) + AgentRepairOrder.getTotalFees() + parseFloat(AgentRepairOrder.totalAdjusted);
        $(`[repair-total]`).html(`$${total.toFixed(2)}`);

        AgentRepairOrder.total = total;
    },

    resetTotals() {
        AgentRepairOrder.totalPost = 0;
        AgentRepairOrder.totalPanel = 0;
        AgentRepairOrder.totalAccessories = 0;
    },

    onSubmitForm() {
        $("#repairOrderForm").on("submit", (e) => {
            helper.showLoader();

            e.preventDefault();
            let form = $(e.target);

            let fd = new FormData();
            //Date
            fd.append("repair_order_desired_date", form.find(`[name="repair_order_desired_date"]:checked`).val());
            fd.append("repair_order_custom_desired_date", form.find(`[name="repair_order_custom_desired_date"]`).val());
            //Post
            if ($('#repair_replace_post').is(':checked')) {
                fd.append("repair_replace_post", 1);
            } else {
                fd.append("repair_replace_post", 0);
            }
            if ($('#relocate_post').is(':checked')) {
                fd.append("relocate_post", 1);
            } else {
                fd.append("relocate_post", 0);
            }
            //Panel
            fd.append("panel_id", form.find(`[name="repair_order_panel"]:checked`).val());
            //Accessories
            fd.append("repair_order_select_accessories", JSON.stringify(AgentRepairOrder.accessories));
            //Comment
            fd.append("repair_order_comment", form.find(`[name="repair_order_comment"]`).val());
            //Create/Edit action
            fd.append("create_order", AgentRepairOrder.create);
            //Order Id
            fd.append("order_id", AgentRepairOrder.order_id);
            fd.append("repair_order_id", AgentRepairOrder.repair_order_id);
            //Files
            AgentRepairOrder._files.forEach((file, index) => fd.append(`file${index}`, file));
            //Fees and total
            fd.append("repair_trip_fee", form.find(`[name="repair_trip_fee"]`).val());
            fd.append("repair_order_rush_fee", form.find(`[name="repair_order_rush_fee"]`).val());
            fd.append("repair_order_fee", form.find(`[name="repair_order_fee"]`).val());
            fd.append("repair_order_zone_fee", form.find(`[name="repair_order_zone_fee"]`).val());
            fd.append('total', AgentRepairOrder.total);

            //Button
            form.find(`[type="submit"]`).prop('disabled', true);
            form.find(`[type="submit"]`).html(`<strong class="text-white">SENDING...</strong>`);

            $.ajax({
                url: form.prop('action'),
                data: fd,
                type: "POST",
                contentType: false,
                processData: false,
                cache: false,
            }).done(res => {
                if (res.type) {
                    if (res.type == 'error') {
                        helper.alertError(res.message);
                        form.find(`[type="submit"]`).prop('disabled', false);
                        form.find(`[type="submit"]`).html(`<strong class="text-white">TRY AGAIN</strong>`);

                        helper.hideLoader('');

                        return false;
                    }
                }

                //console.log(res); return false;

                if (res.repairOrder.editOrder && !res.repairOrder.needPayment) {
                    window.location.reload();
                }

                if (res.repairOrder.needPayment) {
                    $(`[repair-payment-total-amount]`).html(parseFloat(res.repairOrder.total).toFixed(2));
                    $(`[repair-payment-card-name]`).val(res.billing.name);

                    $(`[repair-billing-name]`).val(res.billing.name);
                    $(`[repair-billing-address]`).val(res.billing.address);
                    $(`[repair-billing-city]`).val(res.billing.city);
                    $(`[repair-billing-state]`).val(res.billing.state);
                    $(`[repair-billing-zip]`).val(res.billing.zipcode);

                    if (res.repairOrder.agent.user.authorizenet_profile_id) {
                        $('#repair_use_card_profile').prop('checked', true);
                        $('#repair_card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#repair_use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        Payment.loadCards($('#repair_card_profile_select'), res.repairOrder.agent.user.id);
                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#repair_use_another_card').prop('checked', true);
                        $('#repair_use_card_profile').prop('checked', false);
                        $('#repair_card_profile_select').prop('disabled', true);
                    }

                    //Load any office card visible to agent
                    Payment.loadOfficeCardsVisibleToAgent(
                        $('#repair_card_profile_select'),
                        res.repairOrder.office.user.id,
                        () => {
                            $('#repair_use_card_profile').prop('checked', true);
                            $('#repair_card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#repair_use_another_card').prop('checked', false);
                        }
                    );

                    let repairOrderModal = $("#repairOrderModal");
                    if (repairOrderModal.length) repairOrderModal.modal('hide')

                    let paymentModal = $("#repairPaymentModal");
                    if (paymentModal.length) {
                        paymentModal.find(`[name="repair_order_id"]`).val(res.repairOrder.id);

                        helper.hideLoader('repairPaymentModal');
                    }

                } else {
                    window.location.reload();
                }
            }).fail(res => {
                let f = res.responseJSON;
                let msgs = `<ul>`;
                //main message
                // msgs += "<li class='text-danger'><b>" + f.message + "</b></li>"
                for (const property in f.errors) {
                    $(`[name^="${property}"]`).addClass('is-invalid');
                    msgs += "<li class='text-danger'>" + f.errors[property] + "</li>"
                }
                msgs += '</ul>';


                helper.alertError(msgs);

                form.find(`[type="submit"]`).prop('disabled', false);
                form.find(`[type="submit"]`).html(`<strong class="text-white">TRY AGAIN</strong>`);

                helper.hideLoader('');
            });
        });
    },

    loadSavedAdjustments(adjustments) {
        const rowTmpl = $('#rowTmplRepair').html();
        const rowContainer = $('#rowContainerRepair');
        let totalAdjustments = 0;
        AgentRepairOrder.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };

        rowContainer.empty();
        $.each(adjustments, (i, row)=> {
            AgentRepairOrder.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, AgentRepairOrder.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="repair_price_adjustment_description[${AgentRepairOrder.rowCount}]"]`).val(row.description);
            $(`[name="repair_price_adjustment_charge[${AgentRepairOrder.rowCount}]"]`).val(row.charge);
            $(`[name="repair_price_adjustment_discount[${AgentRepairOrder.rowCount}]"]`).val(row.discount);

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            AgentRepairOrder.pricingAdjustments['description'][i] = row.description;
            AgentRepairOrder.pricingAdjustments['charge'][i] = row.charge;
            AgentRepairOrder.pricingAdjustments['discount'][i] = row.discount;

            AgentRepairOrder.totalAdjusted = totalAdjustments;

            if (AgentRepairOrder.totalAdjusted < 0) {
                $('[repair-adjustments]').html(`<span class="text-danger">- $${AgentRepairOrder.totalAdjusted*(-1)}</span>`);
            } else {
                $('[repair-adjustments]').html(`$${AgentRepairOrder.totalAdjusted}`);
            }
        });

        this.calculateAdjustments();
    },

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = AgentRepairOrder.rowCount;

        for (let i=1; i <= totalRows; i++) {
            charge = $(`[name="repair_price_adjustment_charge[${i}]"]`).val();
            discount = $(`[name="repair_price_adjustment_discount[${i}]"]`).val();

            if (charge > 0) {
                totalAdjustments = parseFloat(totalAdjustments) + parseFloat(charge);
            }

            if (discount > 0) {
                totalAdjustments = parseFloat(totalAdjustments) - parseFloat(discount);
            }
        }

        AgentRepairOrder.totalAdjusted = totalAdjustments;

        if (AgentRepairOrder.totalAdjusted < 0) {
            $('[repair-adjustments]').html(`<span class="text-danger">- $${AgentRepairOrder.totalAdjusted*(-1)}</span>`);
        } else {
            $('[repair-adjustments]').html(`$${AgentRepairOrder.totalAdjusted}`);
        }

        $(`[name="repair_order_zone_fee"]`).trigger('change');
    }

}

$(() => {
    AgentRepairOrder.init();
});
