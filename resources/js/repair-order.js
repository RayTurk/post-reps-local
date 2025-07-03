import helper from './helper';
import global from "./global";
import Payment from "./Payment";

const RepairOrder = {
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
    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    repair_order_id: 0,
    countClickAccessory: {},
    countClickPanel: {},
    installedAccessories: {},
    repairAccessories: {},
    repairAccessoryActions: {},

    init() {
        //Because repair order is being called along with install orders
        //causing google maps to load twice
        this.loadCompletedInstallOrders();

        this.loadDatatable()
        this.showRepairOrderEntries();
        this.repairOrderSearchInput();
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
            RepairOrder.holidays = holidays;
        });

        window.onAccessoryChange = this.onAccessoryChange;
        window.onSignPanelClick = this.onSignPanelClick;

        this.onCommentChange();
        this.onFileUploaded();
        window.repairRemoveFile = this.repairRemoveFile;

        this.onSubmitForm();

        Payment.init()
        helper.cardNumberInput('.cc-number-input');

        this.deleteOrders();

        window.eRepairRemoveFile = this.eRepairRemoveFile;

        this.pricingAdjustment();

        window.markRepairOrderCompleted = this.markRepairOrderCompleted;
    },

    loadCompletedInstallOrders() {
        $('.order-repair').on('click', () => {
            window.location.href = `${helper.getSiteUrl()}/repair`;
        });
    },

    loadDatatable() {
        let tableId = '#repairOrdersTable';
        if (helper.isMobilePhone()) {
            tableId = '#repairOrdersTableMobile';
        }
        if (helper.isTablet()) {
            tableId = '#repairOrdersTableTablet';
        }

        RepairOrder.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/repair/orders/datatable"),
            serverSide: true,
            columns: [
                {
                    data: "address",
                    defaultContent: "",
                    title: "Address",
                    name: "orders.address",
                    visible: 1,
                    searchable: false,
                },
                {
                    data: "repair_status",
                    defaultContent: "",
                    title: "Repair Status",
                    name: "repair.status",
                    searchable: false,
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
                            if (status == RepairOrder.status_received) {
                                html += `<span class="badge badge-pill badge-primary">Received</span>`
                            } else if (status == RepairOrder.status_incomplete) {
                                if (r.assigned_to > 0) {
                                    html += `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                                } else {
                                    html += `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                                }
                            } else if (status == RepairOrder.status_scheduled) {
                                html += `<span class="badge badge-pill badge-info">Scheduled</span>`;
                            } else if (status == RepairOrder.status_completed) {
                                html += `<span class="badge badge-pill badge-success">Completed</span>`;
                            } else if (status == RepairOrder.status_cancelled) {
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
                                RepairOrder.status_received,
                                RepairOrder.status_incomplete
                            ];

                            if (canEdit.includes(status)) {
                                action += `<a class='link mx-1' onclick="window.editRepairOrder(${r.id})">
                                    <img src="./images/Repair_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                                </a>`;
                            }

                            //Order can only be cancelled if status = received/scheduled/incomplete
                            const canCancel = [
                                RepairOrder.status_received,
                                RepairOrder.status_scheduled,
                                RepairOrder.status_incomplete
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
                    data: "order_number",
                    defaultContent: "404",
                    title: "Order ID#",
                    name: "order_number",
                    searchable: true,
                    visible: 1,
                    render(d, t, r) {
                        let orderNumber = r.order_number;

                        return orderNumber;
                    }
                },
            ]
        });
    },

    showRepairOrderEntries() {
        $('body').on("change", '#showRepairOrderEntries', (event) => {
            const selected = parseInt(event.target.value);
            RepairOrder.table.api().context[0]._iDisplayLength = selected;
            RepairOrder.table.api().draw();
        });
    },

    repairOrderSearchInput() {
        $('body').on("keyup", '#repairOrderSearchInput', (event) => {
            let inputVal = event.target.value;

            RepairOrder.table.fnFilter(inputVal);
        });
    },

    previousPanel: 0,
    onSignPanelClick(e){
        const self = $(e.target);
        const price = self.data('price');
        const id = self.val();

        RepairOrder.countClickPanel[id] = RepairOrder.countClickPanel[id] || 0;
        RepairOrder.countClickPanel[id] = parseInt(RepairOrder.countClickPanel[id]) + 1;

        if (RepairOrder.countClickPanel[id] == 1) {
            RepairOrder.totalPanel = price;
            RepairOrder.setRepairFee();

            //Display panel image
            $('#repair_sign_image_preview').removeClass('d-none').prop('src', self.data('image')).show();

            RepairOrder.previousPanel = id;
        } else {
            if (RepairOrder.previousPanel == id) {
                self.prop('checked', false);
                RepairOrder.countClickPanel[id] = 0;

                RepairOrder.totalPanel = 0;
                RepairOrder.setRepairFee();

                $('#repair_sign_image_preview').addClass('d-none').prop('src', '');

                if (RepairOrder.existingPanelImg) {
                    $('#repair_sign_image_preview').removeClass('d-none').prop('src', RepairOrder.existingPanelImg).show();
                }
            } else {
                RepairOrder.totalPanel = price;
                RepairOrder.setRepairFee();

                //Display panel image
                $('#repair_sign_image_preview').removeClass('d-none').prop('src', self.data('image')).show();

                RepairOrder.previousPanel = id;
            }
        }
    },

    onAccessoryChange(e) {
        const self = $(e.target);
        const id = self.val();

        //Only calculate accessories not included in install order
        let existingItem = false;
        $.each(RepairOrder.installedAccessories, (i, r) => {
            if (r.accessory_id == id) {
                existingItem = true;
            }
        });

        RepairOrder.countClickAccessory[id] = RepairOrder.countClickAccessory[id] || 0;

        RepairOrder.countClickAccessory[id] = parseInt(RepairOrder.countClickAccessory[id]) + 1;

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
            } else {
                if (RepairOrder.countClickAccessory[id] > 1) {
                    //Back to regular checkbox
                    $(`#accessory_icon_${id}`).hide();
                    self.removeClass('css-checkbox');
                    self.prop('checked', true);
                    RepairOrder.countClickAccessory[id] = 0;
                    imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${self.data('image')}" id="repairAccessoryImg${self.val()}">`);
                } else {
                    //Removing
                    console.log('removing')
                    $(`#accessory_icon_${id}`).removeClass('fa-plus-square').addClass('fa-minus-square').show();
                    self.addClass('repair-accessory remove');
                }
            }
        }

        let totalAccessories = 0;
        RepairOrder.accessories = [];
        $(`[name="repair_order_accessories[]"]`).each((i, el)=> {
            const elem = $(el);

            existingItem = false;
            $.each(RepairOrder.installedAccessories, (i, r) => {
                if (r.accessory_id == el.value) {
                    existingItem = true;
                }
            });
            if ( ! existingItem && elem.is(':checked')) {
                totalAccessories = totalAccessories + parseFloat(el.dataset.price);
            }

            RepairOrder.accessories[i] = {};
            if (elem.hasClass('repair-accessory add-replace')) {
                RepairOrder.accessories[i].accessory_id = el.value;
                RepairOrder.accessories[i].action = 0;
            }
            if (elem.hasClass('repair-accessory remove')) {
                RepairOrder.accessories[i].accessory_id = el.value;
                RepairOrder.accessories[i].action = 1;
            }
            RepairOrder.accessories = helper.removeEmptyObjectFromArray(RepairOrder.accessories);
        })

        RepairOrder.totalAccessories = totalAccessories;

        RepairOrder.setRepairFee();
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
                if (RepairOrder.create) {
                    panelId = order.panel_id;
                } else {
                    if (order.repair && order.repair.panel_id) {
                        panelId = order.repair.panel_id;
                    }
                }

                if (panel.id == panelId) {
                    isChecked = 'checked';

                    /*RepairOrder.totalPanel = panel.price;
                    RepairOrder.setRepairFee();*/
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

            RepairOrder.installedAccessories = order.accessories;

            let orderAccessories = order.accessories;
            if ( ! RepairOrder.create) {
                if (order.repair && ! $.isEmptyObject(order.repair.accessories)) {
                    orderAccessories = order.repair.accessories;
                    RepairOrder.repairAccessories = order.repair.accessories;
                }
            }

            let totalAccessories = 0;
            let itemExists = [];
            let action = -1;
            res.forEach(a => {
                let isChecked = '';

                //When editing repair order, make sure to check installed items
                if (! RepairOrder.create) {
                    $.each(RepairOrder.installedAccessories, (i, r) => {
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
                        $.each(RepairOrder.installedAccessories, (i, r) => {
                            if (r.accessory_id == a.id) {
                                itemExists[a.id] = true;
                            }
                        });

                        if (! RepairOrder.create && ! itemExists[a.id]) {
                            totalAccessories = totalAccessories + parseFloat(a.price);
                        }

                        //If accessory is included in repair order then ignore existing
                        //accessory in install order
                        if (! RepairOrder.create) {
                            itemExists[a.id] = false;

                            //get repair action for the accessory
                            action = row.action;

                            //Display accessory image if is to add
                            if (action == 0) {
                                imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}" id="repairAccessoryImg${a.id}">`)
                            }
                        }

                        //Load accessories images
                        if (RepairOrder.create || itemExists[a.id]) {
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
                if (! RepairOrder.create && isChecked && ! itemExists[a.id]) {
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
                /*if ( ! RepairOrder.create && ! itemExists[a.id]) {
                    $(`#repair_accessory_option_${a.id}`).trigger('change');
                }*/
            })

            if (! RepairOrder.create) {
                RepairOrder.totalAccessories = totalAccessories;

                RepairOrder.setRepairFee();
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
                if (RepairOrder.create) {
                    panelId = order.panel_id;
                } else {
                    if (order.repair && order.repair.panel_id) {
                        panelId = order.repair.panel_id;
                    }
                }

                if (panel.id == panelId) {
                    isChecked = 'checked';

                    /*RepairOrder.totalPanel = panel.price;
                    RepairOrder.setRepairFee();*/
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

            RepairOrder.installedAccessories = order.accessories;

            let orderAccessories = order.accessories;
            if ( ! RepairOrder.create) {
                if (order.repair && ! $.isEmptyObject(order.repair.accessories)) {
                    orderAccessories = order.repair.accessories;
                    RepairOrder.repairAccessories = order.repair.accessories;
                }
            }

            let totalAccessories = 0;
            let itemExists = [];
            let action = -1;
            res.forEach(a => {
                let isChecked = '';

                //When editing repair order, make sure to check installed items
                if (! RepairOrder.create) {
                    $.each(RepairOrder.installedAccessories, (i, r) => {
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
                        $.each(RepairOrder.installedAccessories, (i, r) => {
                            if (r.accessory_id == a.id) {
                                itemExists[a.id] = true;
                            }
                        });

                        if (! RepairOrder.create && ! itemExists[a.id]) {
                            totalAccessories = totalAccessories + parseFloat(a.price);
                        }

                        //If accessory is included in repair order then ignore existing
                        //accessory in install order
                        if (! RepairOrder.create) {
                            itemExists[a.id] = false;

                            //get repair action for the accessory
                            action = row.action;

                            //Display accessory image if is to add
                            if (action == 0) {
                                imagesContainer.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}" id="repairAccessoryImg${a.id}">`)
                            }
                        }

                        //Load accessories images
                        if (RepairOrder.create || itemExists[a.id]) {
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
                if (! RepairOrder.create && isChecked && ! itemExists[a.id]) {
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
                /*if ( ! RepairOrder.create && ! itemExists[a.id]) {
                    $(`#repair_accessory_option_${a.id}`).trigger('change');
                }*/
            })

            if (! RepairOrder.create) {
                RepairOrder.totalAccessories = totalAccessories;

                RepairOrder.setRepairFee();
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

        const src = `https://maps.googleapis.com/maps/api/js?key=${RepairOrder.googleKey}&callback=window.initRepairMap&libraries=drawing,geometry,places&v=weekly`;
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
        RepairOrder.loadAddressOnMap(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)});

        $('#repairOrderPropertyType').find(`option[value="${order.property_type}"]`).prop('selected', true);
    },

    async getRepairZone(orderId) {
        const zone = await $.get(helper.getSiteUrl(`/repair/get/zone/${orderId}`));

        return zone;
    },

    movedNextMonth: false,
    updateCalendar(savedDate, dateClicked = false) {
        $("#repairOrderDatePicker").datepicker("destroy");
        $("#repairOrderDatePicker").datepicker({
            onSelect: function (dateText) {
                $(`[name="repair_order_custom_desired_date"]`).val(dateText);
                return RepairOrder.updateCalendar(helper.parseUSDate(dateText), true);
            },
            beforeShowDay: function (date) {
                let dateString = helper.getDateStringUsa(date);

                if (RepairOrder.holidays.includes(dateString)) {
                    return [false];
                }

                //Disable past dates and current date
                let today = helper.getDateStringUsa(new Date());
                let daysdiff = helper.diffDays(dateString, today);
                let cdate = dateString;

                //Superadmin can always select today's date
                if (today == dateString) {
                    return [true];
                }

                let mSavedDate = '';
                if (savedDate) {
                    mSavedDate = helper.getDateStringUsa(savedDate);
                }

                if (daysdiff >= 1) {
                    if (mSavedDate == cdate) {
                        return [true, 'ui-state-highlight ui-state-active', ''];
                    }

                    return [false]; //disable past date
                }

                //Disable non working days from the selected zone
                if (RepairOrder.zone.data) {
                    let zone = RepairOrder.zone.data;
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
            if (! RepairOrder.create) {
                if (! dateClicked) {
                    setTimeout(() => {
                        const usDate = helper.getDateStringUsa(savedDate);
                        $("#repairOrderDatePicker").datepicker('setDate', usDate);
                    }, 2000);
                } else {
                    const usDate = helper.getDateStringUsa(savedDate);
                    $("#repairOrderDatePicker").datepicker('setDate', usDate);
                }
            } else {
                const usDate = helper.getDateStringUsa(savedDate);
                $("#repairOrderDatePicker").datepicker('setDate', usDate);
            }
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !RepairOrder.movedNextMonth && RepairOrder.create) {
                //Delay trigger to give enough ttime for the calendar to update
                //Ideally we would have a afterRender event for this but jQuery UI doesn't have it
                RepairOrder.movedNextMonth = true;
                setTimeout(() => {
                    $('#repairOrderDatePicker .ui-datepicker-next').trigger("click");
                }, 3000);
            }

            $("#repairOrderDatePicker").find('a.ui-state-active').removeClass('ui-state-active')
            .removeClass('ui-state-highlight').removeClass('ui-state-hover');
        }
    },

    setDate(repairOrder) {
        let datePicker = $("#repairOrderDatePicker");
        if (repairOrder.service_date_type == 1) {
            RepairOrder.updateCalendar(false);

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

                RepairOrder.updateCalendar(helper.parseDate(repairOrder.service_date));
            }, 1000);
        }
    },

    setPostSignAccessories(order) {
        $('label[for="repairOrderPost"]').html(order.post.post_name);

        if ( ! RepairOrder.create) {
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

            RepairOrder.totalPost = totalPost;

            RepairOrder.setRepairFee();
        } else {
            $('#repair_replace_post').prop('checked', false);
            $('#relocate_post').prop('checked', false);
        }

        const postImage = helper.getSiteUrl(`/private/image/post/${order.post.image_path}`);
        $('#repair_post_image_preview').removeClass('d-none').prop('src', postImage).show();

        let panel = {};
        if (order.repair && order.repair.panel) {
            panel = order.repair.panel;

            RepairOrder.totalPanel = panel.price;

            RepairOrder.setRepairFee();
        } else {
            panel = order.panel;
        }

        if (panel) {
            $('#selectedPanel').show();
            $('label[for="repairOrderPanel"]').html(panel.panel_name);
            const panelImage = helper.getSiteUrl(`/private/image/panel/${panel.image_path}`);
            $('#repair_sign_image_preview').removeClass('d-none').prop('src', panelImage).show();

            RepairOrder.existingPanelImg = panelImage;
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
        RepairOrder.totalAdjusted = 0;
        RepairOrder.rowCount = 0;
        RepairOrder.accessories = [];

        this.resetTotals();
        this.setRushFee(0);
        this.setRepairFee();

        $(`[name="repair_order_desired_date"][value="custom_date"]`).trigger('click');

        $(".list-container-accessories-repair").empty();

        RepairOrder.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
    },

    createRepairOrder(orderId) {
        RepairOrder.resetForm();

        helper.showLoader();
        $.get('/repair/get/install-order/' + orderId).done(async order => {
            RepairOrder.create = true;
            RepairOrder.order = order;
            RepairOrder.order_id = RepairOrder.order.id;

            RepairOrder.zone = await RepairOrder.getRepairZone(RepairOrder.order.id);
            RepairOrder.settings = await helper.getZoneSettings();

            RepairOrder.setOfficeAndAgent(order);
            RepairOrder.setPropertyInfo(order);
            RepairOrder.setPostSignAccessories(order);
            RepairOrder.movedNextMonth = false;
            RepairOrder.updateCalendar(false);
            RepairOrder.setFooter();

            if (RepairOrder.zone.data && RepairOrder.settings) {
                const zone = RepairOrder.zone.data;
                const settings = RepairOrder.settings;

                const zoneFee = parseFloat(zone.zone_fee) * settings.repair / 100;
                $(`[name="repair_order_zone_fee"]`).val(zoneFee);
                $('[repair-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                $(`[name="repair_order_zone_fee"]`).trigger('change');
            }

            helper.hideLoader('repairOrderModal');
        });
    },

    setFooter() {
        if (RepairOrder.create) {
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
        RepairOrder.resetForm();

        helper.showLoader()
        $.get('/repair/get/order/' + orderId).done(async repairOrder => {
            RepairOrder.create = false;

            //console.log(repairOrder.order);
            RepairOrder.repair_order_id = repairOrder.id;

            RepairOrder.order = repairOrder.order;
            RepairOrder.order_id = RepairOrder.order.id;

            RepairOrder.zone = await RepairOrder.getRepairZone(RepairOrder.order.id);
            RepairOrder.settings = await helper.getZoneSettings();

            RepairOrder.setOfficeAndAgent(repairOrder.order);
            RepairOrder.setPropertyInfo(repairOrder.order);
            RepairOrder.setPostSignAccessories(repairOrder.order);
            RepairOrder.setDate(repairOrder, false);
            RepairOrder.setComment(repairOrder);
            RepairOrder.setAttachments(repairOrder);
            RepairOrder.setFooter();
            RepairOrder.setRushFee(repairOrder.rush_fee);

            //Only calculate if repair order has fees
            if (repairOrder.total > 0) {
                if (RepairOrder.zone.data && RepairOrder.settings) {
                    const zone = RepairOrder.zone.data;
                    const settings = RepairOrder.settings;

                    const zoneFee = parseFloat(zone.zone_fee) * settings.repair / 100;
                    $(`[name="repair_order_zone_fee"]`).val(zoneFee);
                    $('[repair-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                    $(`[name="repair_order_zone_fee"]`).trigger('change');
                }
            } else {
                RepairOrder.totalPost = 0;
                RepairOrder.totalPanel = 0;
                RepairOrder.totalAccessories = 0;
                $(`[name="repair_order_zone_fee"]`).val(0);
                $(`[name="repair_trip_fee"]`).val(0);
                $(`[name="repair_order_fee"]`).val(0);
                $('[repair-fee]').html(`$0.00`);
                $('[repair-trip-fee]').html(`$0.00`);
                $(`[name="repair_order_zone_fee"]`).trigger('change');
            }

            RepairOrder.rowCount = 0;
            if (repairOrder.adjustments) {
                RepairOrder.loadSavedAdjustments(repairOrder.adjustments);
            }

            setTimeout(()=>{
                helper.hideLoader('repairOrderModal');
            }, 2500)
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
                    RepairOrder.setRushFee(0)
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
            const total = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories) + RepairOrder.getTotalFees() + parseFloat(RepairOrder.totalAdjusted);
            $(`[repair-total]`).html(`$${total.toFixed(2)}`);

            RepairOrder.total = total;
        });
        $(`[name="repair_order_fee"]`).on("change", (e) => {
            const total = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories) + RepairOrder.getTotalFees() + parseFloat(RepairOrder.totalAdjusted);
            $(`[repair-total]`).html(`$${total.toFixed(2)}`);

            RepairOrder.total = total;
        });
        $(`[name="repair_order_zone_fee"]`).on("change", (e) => {
            const total = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories) + RepairOrder.getTotalFees() + parseFloat(RepairOrder.totalAdjusted);
            $(`[repair-total]`).html(`$${total.toFixed(2)}`);

            RepairOrder.total = total;
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

                RepairOrder.totalPost = totalPost;

                const total = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories) + RepairOrder.getTotalFees() + parseFloat(RepairOrder.totalAdjusted);
                $(`[repair-total]`).html(`$${total.toFixed(2)}`);

                const repairFee = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories);
                $(`[repair-fee]`).html(`$${repairFee.toFixed(2)}`);

                RepairOrder.total = total;
            } else {
                RepairOrder.totalPost = RepairOrder.totalPost - parseFloat(self.val());

                const total = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories) + RepairOrder.getTotalFees() + parseFloat(RepairOrder.totalAdjusted);
                $(`[repair-total]`).html(`$${total.toFixed(2)}`);

                const repairFee = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories);
                $(`[repair-fee]`).html(`$${repairFee.toFixed(2)}`);

                RepairOrder.total = total;
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
                    RepairOrder._files.push(file);
                    RepairOrder.displayFiles(this._files);
                    RepairOrder.setFiles(this._files);
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
        let new_files = await RepairOrder._files.filter((file) => file._id != id);
        RepairOrder._files = new_files;
        RepairOrder.setFiles(RepairOrder._files);
        RepairOrder.displayFiles(RepairOrder._files);


        if (!RepairOrder._files.length) {
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
        const repairFee = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories);
        $(`[repair-fee]`).html(`$${repairFee.toFixed(2)}`);
        $(`[name="repair_order_fee"]`).val(repairFee);

        const total = parseFloat(RepairOrder.totalPost) + parseFloat(RepairOrder.totalPanel) + parseFloat(RepairOrder.totalAccessories) + RepairOrder.getTotalFees() + parseFloat(RepairOrder.totalAdjusted);
        $(`[repair-total]`).html(`$${total.toFixed(2)}`);

        RepairOrder.total = total;
    },

    resetTotals() {
        RepairOrder.totalPost = 0;
        RepairOrder.totalPanel = 0;
        RepairOrder.totalAccessories = 0;
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
            fd.append("repair_order_select_accessories", JSON.stringify(RepairOrder.accessories));
            //Comment
            fd.append("repair_order_comment", form.find(`[name="repair_order_comment"]`).val());
            //Create/Edit action
            fd.append("create_order", RepairOrder.create);
            //Order Id
            fd.append("order_id", RepairOrder.order_id);
            fd.append("repair_order_id", RepairOrder.repair_order_id);
            //Files
            RepairOrder._files.forEach((file, index) => fd.append(`file${index}`, file));
            //Fees and total
            fd.append("repair_trip_fee", form.find(`[name="repair_trip_fee"]`).val());
            fd.append("repair_order_rush_fee", form.find(`[name="repair_order_rush_fee"]`).val());
            fd.append("repair_order_fee", form.find(`[name="repair_order_fee"]`).val());
            fd.append("repair_order_zone_fee", form.find(`[name="repair_order_zone_fee"]`).val());
            fd.append('total', RepairOrder.total);

            fd.append("pricingAdjustments", JSON.stringify(RepairOrder.pricingAdjustments));

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

                    // let paymentModal = $("#repairPaymentModal");
                    $(`[repair-payment-total-amount]`).html(parseFloat(res.repairOrder.total).toFixed(2));

                    $(`[repair-billing-name]`).val(res.billing.name);
                    $(`[repair-billing-address]`).val(res.billing.address);
                    $(`[repair-billing-city]`).val(res.billing.city);
                    $(`[repair-billing-state]`).val(res.billing.state);
                    $(`[repair-billing-zip]`).val(res.billing.zipcode);

                    $('#repair_use_another_card').prop('checked', true);
                    $(`.form-another-card input`).prop('disabled', false);
                    $('#repair_card_profile_select').prop('disabled', true);

                    //If user has card on file then enable Use Cards on File. Otherwise enable Enter Another Card
                    if (res.repairOrder.office.user.authorizenet_profile_id) {
                        $('#repair_use_card_profile').prop('checked', true);
                        $('#repair_card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#repair_use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        if (!res.repairOrder.agent) {
                            Payment.loadCards($('#repair_card_profile_select'), res.repairOrder.office.user.id);
                        } else {
                            //Load any office card visible to agent
                            Payment.loadOfficeCardsVisibleToAgent(
                                $('#repair_card_profile_select'),
                                res.repairOrder.office.user.id
                            );
                        }
                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#repair_use_another_card').prop('checked', true);
                        $('#repair_use_card_profile').prop('checked', false);
                        $('#repair_card_profile_select').prop('disabled', true);
                    }

                    if (res.repairOrder.agent) {
                        if (res.repairOrder.agent.user.authorizenet_profile_id) {
                            $('#repair_use_card_profile').prop('checked', true);
                            $('#repair_card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#repair_use_another_card').prop('checked', false);

                            //Load cards in dropdown
                            Payment.loadCards($('#repair_card_profile_select'), res.repairOrder.agent.user.id);
                        }
                    }

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

    deleteOrders() {
        $('#deleteAllRepairOrders').on('click', (e) => {
            helper.confirm(
                "",
                "",
                () => {
                    const deletePath = helper.getSiteUrl("/repair/order/delete/all");
                    $.post(deletePath)
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

    repairOrderCancel(repairOrderId) {
        $.get(`/repair/get/order/${repairOrderId}/`).done(repairOrder => {
            if (repairOrder.status == RepairOrder.status_scheduled) {
                helper.confirm2(
                    'REMOVE FROM ROUTE',
                    "Are you sure you wish to remove this order from its scheduled route?",
                    () => {
                        $.get(`/repair/order/${repairOrderId}/cancel`).done(res => {

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
                        $.get(`/repair/order/${repairOrderId}/cancel`).done(res => {
                            //RepairOrder.table.api().draw();
                            window.location.reload();
                        })
                    },
                    () => {}
                );
            }
        });
    },

    pricingAdjustments: {
        description: [],
        charge: [],
        discount: []
    },
    totalAdjusted: 0,
    rowCount: 0,
    pricingAdjustment() {
        const rowTmpl = $('#rowTmplRepair').html();
        const rowContainer = $('#rowContainerRepair');

        $('#openRepairPriceAdjustmentModalBtn').on('click', ()=> {
            if (RepairOrder.rowCount == 0) {
                RepairOrder.rowCount++;
                let newTmpl = rowTmpl.replace(/rowCount/g, RepairOrder.rowCount);
                rowContainer.empty().append(newTmpl);
            }

            helper.openModal('repairPriceAdjustmentModal');
        });

        $('#closeRepairPriceAdjustmentModalBtn').on('click', ()=> {
            helper.closeModal('repairPriceAdjustmentModal');
        });

        $('#addAdjustmentBtnRepair').on('click', ()=> {
            RepairOrder.rowCount++;

            let newTmpl = rowTmpl.replace(/rowCount/g, RepairOrder.rowCount);
            rowContainer.append(newTmpl);
        });

        $('body').on('click', '.remove-price-adjustment-row', (e)=> {
            const self = $(e.target);

            self.closest('.row').remove();
            //RepairOrder.rowCount--;
        });

        $('#savePricingAdjustmentBtnRepair').on('click', ()=> {
            let hasError = false;
            let message = '';
            let totalRows = RepairOrder.rowCount;
            let description;
            let charge;
            let discount;
            RepairOrder.pricingAdjustments = {
                description: [],
                charge: [],
                discount: []
            };

            for (let i=1; i <= totalRows; i++) {
                if ($(`[name="repair_price_adjustment_description[${i}]"]`).length) {
                    description = $(`[name="repair_price_adjustment_description[${i}]"]`).val();
                    charge = parseFloat($(`[name="repair_price_adjustment_charge[${i}]"]`).val()) || 0;
                    discount = parseFloat($(`[name="repair_price_adjustment_discount[${i}]"]`).val()) || 0;

                    if ( ! description && (charge || discount)) {
                        message = 'Please provide description.';
                        hasError = true;
                    }

                    if ( ! description && ! charge && ! discount) {
                        message = 'Please fill out all fields.';
                        hasError = true;
                    }

                    RepairOrder.pricingAdjustments['description'][i] = description;
                    RepairOrder.pricingAdjustments['charge'][i] = charge;
                    RepairOrder.pricingAdjustments['discount'][i] = discount;
                }
            }

            if (hasError) {
                helper.alertError(message);
                return false;
            }

            RepairOrder.calculateAdjustments();
            helper.closeModal('repairPriceAdjustmentModal');
        });
    },

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = RepairOrder.rowCount;

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

        RepairOrder.totalAdjusted = totalAdjustments;

        if (RepairOrder.totalAdjusted < 0) {
            $('[repair-adjustments]').html(`<span class="text-danger">- $${RepairOrder.totalAdjusted*(-1)}</span>`);
        } else {
            $('[repair-adjustments]').html(`$${RepairOrder.totalAdjusted}`);
        }

        $(`[name="repair_order_zone_fee"]`).trigger('change');
    },

    loadSavedAdjustments(adjustments) {
        const rowTmpl = $('#rowTmplRepair').html();
        const rowContainer = $('#rowContainerRepair');
        let totalAdjustments = 0;
        RepairOrder.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };

        rowContainer.empty();
        $.each(adjustments, (i, row)=> {
            RepairOrder.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, RepairOrder.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="repair_price_adjustment_description[${RepairOrder.rowCount}]"]`).val(row.description);
            $(`[name="repair_price_adjustment_charge[${RepairOrder.rowCount}]"]`).val(row.charge);
            $(`[name="repair_price_adjustment_discount[${RepairOrder.rowCount}]"]`).val(row.discount);

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            RepairOrder.pricingAdjustments['description'][i] = row.description;
            RepairOrder.pricingAdjustments['charge'][i] = row.charge;
            RepairOrder.pricingAdjustments['discount'][i] = row.discount;

            RepairOrder.totalAdjusted = totalAdjustments;

            if (RepairOrder.totalAdjusted < 0) {
                $('[repair-adjustments]').html(`<span class="text-danger">- $${RepairOrder.totalAdjusted*(-1)}</span>`);
            } else {
                $('[repair-adjustments]').html(`$${RepairOrder.totalAdjusted}`);
            }
        });

        this.calculateAdjustments();
    },

    markRepairOrderCompleted(id) {
        helper.showLoader();

        $.get('/repair/get/order/' + id).done(order => {
            //Display same modal as installer in iFrame
            const installerId = order.assigned_to;
            const url = `${helper.getSiteUrl()}/admin/installer/order/details/${id}/repair/${installerId}`;
            $('#completeOrderIframe').prop('src', url);

            setTimeout(() => {
                helper.hideLoader();

                helper.openModal('markOrderCompletedModal');
            }, 1000)
        });
    },
}

$(() => {
    RepairOrder.init();
});
