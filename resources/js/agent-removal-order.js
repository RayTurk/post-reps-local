import helper from './helper';
import Payment from './Payment';

const AgentRemovalOrder = {

    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    order: {},
    zone: {},
    settings: {},
    total:0,
    accessories: [],
    create: true,
    payment_method_office_pay: 3,
    removal_order_id: 0,

    dailyOrderCap: 0,
    countOrders: null,

    init() {
        $("[googlescript]").remove();

        this.loadPage();
        this.loadDatatable();
        this.showRemovalOrderEntries();
        this.removalOrderSearchInput();
        window.createRemovalOrder = this.createRemovalOrder;
        window.editRemovalOrder = this.editRemovalOrder;
        window.removalOrderCancel = this.removalOrderCancel;
        this. onDesiredDateChange();
        //this.initMap();

        this.totalFee();

        //Disable holidays in calendar
        $.get(helper.getSiteUrl('/get/holidays')).done(holidays => {
            AgentRemovalOrder.holidays = holidays;
        });

        $.get(helper.getSiteUrl('/get/zone/settings')).done(settings => {
            AgentRemovalOrder.dailyOrderCap = settings.daily_order_cap;
        })

        $.get(helper.getSiteUrl('/agent/count-orders')).done(countorders => {
            AgentRemovalOrder.countOrders = countorders;
        })

        window.onSignPanelChange = this.onSignPanelChange;

        this.onCommentChange();

        this.onSubmitForm();

        Payment.init()
        helper.cardNumberInput('.cc-number-input');

        this.deleteOrders();

        this.processMultiplePosts();
    },

    removalOrderCancel(RemovalOrderId) {
        helper.confirm("", "This action is irreversible!",
            () => {
                $.get(`/removal/order/${RemovalOrderId}/cancel`).done(res => {
                    //RemovalOrder.table.api().draw();
                    window.location.reload();
                })
            },
            () => {}
        );
    },

    loadPage() {
        $('.order-removal').on('click', () => {
            helper.redirectTo(`${helper.getSiteUrl()}/removal`);
        });
    },

    loadDatatable() {
        let tableId = '#removalOrdersTable';
        if (helper.isMobilePhone()) {
            tableId = '#removalOrdersTableMobile';
        }
        if (helper.isTablet()) {
            tableId = '#removalOrdersTableTablet';
        }

        AgentRemovalOrder.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            infoCallback: function( settings, start, end, max, total, pre ) {
                return `Showing ${start} to ${end} of ${total} entries`;
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/agent/orders/removal"),
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
                    data: "removal_status",
                    defaultContent: "",
                    title: "Removal Status",
                    name: "status",
                    visible: 0
                },
                {
                    data: "removal_order_number",
                    defaultContent: "",
                    title: "Removal Order Id",
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

                        if (r.order_type == 'removal') {
                            const status = parseInt(r.removal_status);

                            const serviceDate = r.desired_date_type == 1
                                ? 'Rush Order'
                                : helper.formatDate(r.desired_date);

                            html = `<span class="text-danger font-weight-bold">
                                REMOVAL ${serviceDate}
                            </span>`;

                            html += '<br>';
                            if (status == AgentRemovalOrder.status_received) {
                                html += `<span class="badge badge-pill badge-primary">Received</span>`
                            } else if (status == AgentRemovalOrder.status_incomplete) {
                                if (r.assigned_to > 0) {
                                    html += `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                                } else {
                                    html += `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                                }
                            } else if (status == AgentRemovalOrder.status_scheduled) {
                                html += `<span class="badge badge-pill badge-info">Scheduled</span>`;
                            } else if (status == AgentRemovalOrder.status_completed) {
                                html += `<span class="badge badge-pill badge-success">Completed</span>`;
                            } else if (status == AgentRemovalOrder.status_cancelled) {
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
                            action +=`<a class='link mx-1' onclick="window.createRemovalOrder(${r.id})">
                                <img src="./images/Removal_Icon.png" alt="Create" class="width-px-40">
                            </a>`;
                            action += '</div>';
                            return action;
                        }

                        //Open edit form if removal order exists
                        if (r.order_type == 'removal') {
                            const status = parseInt(r.removal_status);

                            //Order can only be edited if status = received
                            const canEdit = [
                                AgentRemovalOrder.status_received,
                                AgentRemovalOrder.status_incomplete
                            ];

                            if (canEdit.includes(status)) {
                                action += `<a class='link mx-1' onclick="window.editRemovalOrder(${r.id})">
                                    <img src="./images/Removal_Icon.png" alt="Edit" class="width-px-40">
                                </a>`;
                            }

                            //Order can only be cancelled if status = received/scheduled/incomplete
                            const canCancel = [
                                AgentRemovalOrder.status_received,
                                AgentRemovalOrder.status_scheduled,
                                AgentRemovalOrder.status_incomplete
                            ];

                            if (canCancel.includes(status)) {
                                action += `<a class='link text-danger mx-1' onclick="window.removalOrderCancel(${r.id})">
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
                    visible: 1,
                    render(d, t, r) {
                        let orderNumber = r.order_number;

                        return orderNumber;
                    }
                },
            ]
        });
    },

    showRemovalOrderEntries() {
        $('body').on("change", '#showRemovalOrderEntries', (event) => {
            const selected = parseInt(event.target.value);
            AgentRemovalOrder.table.api().context[0]._iDisplayLength = selected;
            AgentRemovalOrder.table.api().draw();
        });
    },

    removalOrderSearchInput() {
        $('body').on("keyup", '#removalOrderSearchInput', (event) => {
            AgentRemovalOrder.table.fnFilter(event.target.value);
        });
    },

    onSignPanelChange(e){
        AgentRemovalOrder.setRemovalFee();
    },

    setOfficeAndAgent(order) {
        $('#removalOrderOffice').val(order.office.user.name);
        if (order.agent) {
            $('#removalOrderAgent').val(order.agent.user.name);
        }
    },

    setPropertyInfo(order) {
        $('#removalOrderAddress').val(order.address.split(',')[0]);
        $('#removalOrderCity').val(order.address.split(',')[1]);
        //Set selected state
        $(`#removalOrderState option[value=${order.address.split(',')[2]?.trimStart().substr(0, 2)}]`).attr('selected', 'selected');

        //Map
        //AgentRemovalOrder.loadAddressOnMap(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)});

        //$('#removalOrderPropertyType').find(`option[value="${order.property_type}"]`).prop('selected', true);
    },

    async getRemovalZone(orderId) {
        const zone = await $.get(helper.getSiteUrl(`/removal/get/zone/${orderId}`));

        return zone;
    },

    movedNextMonth:false,
    updateCalendar(savedDate) {
        $("#removalOrderDatePicker").datepicker("destroy");
        $("#removalOrderDatePicker").datepicker({
            onSelect: function (dateText) {
                //console.log(dateText)
                $(`[name="removal_order_custom_desired_date"]`).val(dateText);
                return AgentRemovalOrder.updateCalendar(helper.parseUSDate(dateText));
            },
            beforeShowDay: function (date) {
                let dateString = helper.getDateStringUsa(date);

                if (AgentRemovalOrder.holidays.includes(dateString)) {
                    return [false];
                }

                let serviceDate = $.datepicker.formatDate('yy-mm-dd', new Date(date));
                let ordersCount = AgentRemovalOrder.countOrders[serviceDate];
                if (typeof ordersCount !== 'undefined') {
                    if (ordersCount >= AgentRemovalOrder.dailyOrderCap) {
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
                if (AgentRemovalOrder.zone.data) {
                    let zone = AgentRemovalOrder.zone.data;
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
            $("#removalOrderDatePicker").datepicker('setDate', usDate);
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !AgentRemovalOrder.movedNextMonth && AgentRemovalOrder.create) {
                //Delay trigger to give enough ttime for the calendar to update
                //Ideally we would have a afterRender event for this but jQuery UI doesn't have it
                AgentRemovalOrder.movedNextMonth = true;
                setTimeout(() => {
                    $('#removalOrderDatePicker .ui-datepicker-next').trigger("click");
                }, 3000);
            }
        }
    },

    setDate(removalOrder) {
        let datePicker = $("#removalOrderDatePicker");
        if (removalOrder.service_date_type == 1) {
            AgentRemovalOrder.updateCalendar();

            $(`[name="removal_order_desired_date"][value="asap"]`).prop('checked', true);
            datePicker.addClass("d-none");
        } else {
            $(`[name="removal_order_desired_date"][value="custom_date"]`).prop('checked', true);
            $(`[name="removal_order_desired_date"][value="custom_date"]`).trigger('change')
            //Need to review this part. Why does it need setTimeout?
            setTimeout(()=>{
                datePicker.removeClass("d-none");
                //datePicker.datepicker("setDate", d);
                $(`[name="removal_order_custom_desired_date"]`).val(removalOrder.service_date);

                AgentRemovalOrder.updateCalendar(helper.parseDate(removalOrder.service_date));
            }, 1000);
        }
    },

    loadAccessories(order) {
        let listContainer = $(".list-container-accessories-removal");
        listContainer.empty();

        //We need to make sure it includes repair accessories in case they were swaped after install
        let orderAccessories;
        if (order.repair) {
            orderAccessories = order.repair.accessories;
        } else {
            orderAccessories = order.accessories;
        }

        let html = '';
        $.each(orderAccessories, (key, row) => {
            html +=`
                <div class="form-check d-flex justify-content-between">
                    <label class="form-check-label text-dark" style="margin-left: -1rem;">
                        <span>${row.accessory.accessory_name}<span>
                    </label>
                </div>
            `;
        });

        listContainer.append(html);
    },

    setPostSignAccessories(order) {
        $('#removalOrderPost').val(order.post.post_name);

        AgentRemovalOrder.loadAccessories(order);

        if ( ! AgentRemovalOrder.create) {
            AgentRemovalOrder.rowCount = 0;
            if (order.removal.adjustments) {
                AgentRemovalOrder.loadSavedAdjustments(order.removal.adjustments);
            }

            $(`[name="removal_order_panel"][value="${order.removal.sign_panel}"]`).prop('checked', true);
        }
    },

    resetForm() {
        $('#removalOrderAgent').val('');
        $(`[name="removal_order_comment"]`).val('');

        $(`[name="removal_order_panel"][value="0"]`).prop('checked', true);

        $("[removal-adjustments]").html("$0.00");
        AgentRemovalOrder.totalAdjusted = 0;
        AgentRemovalOrder.rowCount = 0;

        AgentRemovalOrder.setRushFee(0)
        $(`[name="removal_order_desired_date"][value="custom_date"]`).trigger('click');

        $('[name="removal_order_fee"]').val(0);

        AgentRemovalOrder.setRemovalFee();
    },

    createRemovalOrder(orderId) {
        AgentRemovalOrder.resetForm();

        $("#loader_image").modal('show');
        $.get('/removal/get/install-order/' + orderId).done(async order => {
            AgentRemovalOrder.create = true;
            AgentRemovalOrder.order = order;
            AgentRemovalOrder.order_id = order.id;

            AgentRemovalOrder.zone = await AgentRemovalOrder.getRemovalZone(order.id);
            AgentRemovalOrder.settings = await helper.getZoneSettings();

            AgentRemovalOrder.setOfficeAndAgent(order);
            AgentRemovalOrder.setPropertyInfo(order);
            AgentRemovalOrder.setPostSignAccessories(order);
            AgentRemovalOrder.movedNextMonth = false;
            AgentRemovalOrder.updateCalendar(false);
            AgentRemovalOrder.setFooter();

            if (AgentRemovalOrder.zone.data && AgentRemovalOrder.settings) {
                const zone = AgentRemovalOrder.zone.data;
                const settings = AgentRemovalOrder.settings;

                const zoneFee = parseFloat(zone.zone_fee) * settings.removal / 100;
                $(`[name="removal_order_zone_fee"]`).val(zoneFee);
                $('[removal-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                $(`[name="removal_order_zone_fee"]`).trigger('change');

                $('[name="removal_order_fee"]').val(settings.removal_fee);
                AgentRemovalOrder.setRemovalFee();
            }

            setTimeout(async ()=>{
                $("#loader_image").modal('hide');

                $(".modal").css({ "overflow-y": "scroll" });
                $('#removalOrderModal').modal();

                //Check if there are multiple posts atthe property and show warning
                const totalPosts = await AgentRemovalOrder.countPostsAtProperty(
                    order.address, order.latitude, order.longitude, order.office_id, order.agent_id
                );
                if (totalPosts) {
                    if (totalPosts > 1) {
                        $('#multiplePostsModal').modal();
                    }
                }
            }, 1000)
        });
    },

    setFooter() {
        if (AgentRemovalOrder.create) {
            $('#submitRemovalOrder').html('<strong class="text-white">SUBMIT REMOVAL</strong>').prop('disabled', false);
        } else {
            $('#submitRemovalOrder').html('<strong class="text-white">UPDATE ORDER</strong>').prop('disabled', false);
        }
    },

    setComment(removalOrder) {
        $(`[name="removal_order_comment"]`).val(removalOrder.comment);
    },

    editRemovalOrder(orderId) {
        AgentRemovalOrder.resetForm();

        $("#loader_image").modal('show');
        $.get('/removal/get/order/' + orderId).done(async removalOrder => {
            AgentRemovalOrder.create = false;

            //console.log(AgentRemovalOrder.order);
            AgentRemovalOrder.removal_order_id = removalOrder.id;

            AgentRemovalOrder.order = removalOrder.order;
            AgentRemovalOrder.order_id = removalOrder.order.id;

            AgentRemovalOrder.zone = await AgentRemovalOrder.getRemovalZone(removalOrder.order.id);
            AgentRemovalOrder.settings = await helper.getZoneSettings();

            AgentRemovalOrder.setOfficeAndAgent(removalOrder.order);
            AgentRemovalOrder.setPropertyInfo(removalOrder.order);
            AgentRemovalOrder.setPostSignAccessories(removalOrder.order);
            AgentRemovalOrder.setDate(removalOrder, false);
            AgentRemovalOrder.setComment(removalOrder);
            AgentRemovalOrder.setFooter();
            AgentRemovalOrder.setRushFee(removalOrder.rush_fee);

            $('[name="removal_order_fee"]').val(removalOrder.removal_fee);
            AgentRemovalOrder.setRemovalFee();

            if (AgentRemovalOrder.zone.data && AgentRemovalOrder.settings) {
                const zone = AgentRemovalOrder.zone.data;
                const settings = AgentRemovalOrder.settings;

                const zoneFee = parseFloat(zone.zone_fee) * settings.removal / 100;
                $(`[name="removal_order_zone_fee"]`).val(zoneFee);
                $('[removal-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                $(`[name="removal_order_zone_fee"]`).trigger('change');
            }

            /*AgentRemovalOrder.rowCount = 0;
            if (removalOrder.adjustments) {
                AgentRemovalOrder.loadSavedAdjustments(removalOrder.adjustments);
            }*/

            setTimeout(()=>{
                $("#loader_image").modal('hide');

                $(".modal").css({ "overflow-y": "scroll" });
                $('#removalOrderModal').modal();
            }, 1000)
        });
    },

    onDesiredDateChange() {
        let dates_input = document.getAll(`[name="removal_order_desired_date"]`);
        let datepicker = $("#removalOrderDatePicker");
        dates_input.forEach((d) => {
            d.onchange = (e) => {
                let type = e.target.value;
                $(`[name="removal_order_desired_date"]`).removeAttr('checked')
                $(e.target).prop('checked', true);
                if (type === "custom_date") {
                    AgentRemovalOrder.setRushFee(0)
                    datepicker.removeClass('d-none');
                    $('#rushFeeRemoval').addClass('d-none');
                } else {
                    $("#removalRushOrderModal").modal();
                    datepicker.addClass("d-none");
                }
            };
        });

        $("[removal-rush-fee-decline-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            this.setRushFee(0)
            $(`[name="removal_order_desired_date"][value="custom_date"]`).trigger('click');
        });

        $("[removal-rush-fee-accept-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            const rush_fee =  $('[name="removal_order_desired_date"]').attr('removal-rush-order-fee');
            this.setRushFee(rush_fee)
        });
    },

    setRushFee(value) {
        let rush_fee_input = $(`input[name="removal_order_rush_fee"]`);
        rush_fee_input.val(value)
        rush_fee_input.trigger('change');

        if (value > 0) {
            $('#rushFeeRemoval').removeClass('d-none');
        }
    },

    totalFee() {
        $(`[name="removal_order_rush_fee"]`).on("change", (e) => {
            this.setRemovalFee();
        });
        $(`[name="removal_order_fee"]`).on("change", (e) => {
            this.setRemovalFee();
        });
        $(`[name="removal_order_zone_fee"]`).on("change", (e) => {
            this.setRemovalFee();
        });
    },

    getTotalFees() {
        const rush_fee = $(`[name="removal_order_rush_fee"]`).val();
        const zone_fee = $(`[name="removal_order_zone_fee"]`).val();

        return parseFloat(zone_fee) + parseFloat(rush_fee);
    },

    onCommentChange() {
        let textarea = $(`[name="removal_order_comment"]`);
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

    setRemovalFee() {
        const removalFee = parseFloat($(`[name="removal_order_fee"]`).val());
        $(`[removal-fee]`).html(`$${removalFee.toFixed(2)}`);

        const total = removalFee + AgentRemovalOrder.getTotalFees() + parseFloat(AgentRemovalOrder.totalAdjusted);
        $(`[removal-total]`).html(`$${total.toFixed(2)}`);

        AgentRemovalOrder.total = total;
    },

    onSubmitForm() {
        $("#removalOrderForm").on("submit", (e) => {
            helper.showLoader();

            e.preventDefault();
            let form = $(e.target);

            let fd = new FormData();
            //Date
            fd.append("removal_order_desired_date", form.find(`[name="removal_order_desired_date"]:checked`).val());
            fd.append("removal_order_custom_desired_date", form.find(`[name="removal_order_custom_desired_date"]`).val());
            //Panel
            fd.append("sign_panel", form.find(`[name="removal_order_panel"]:checked`).val());
            //Comment
            fd.append("removal_order_comment", form.find(`[name="removal_order_comment"]`).val());
            //Create/Edit action
            fd.append("create_order", AgentRemovalOrder.create);
            //Order Id
            fd.append("order_id", AgentRemovalOrder.order_id);
            fd.append("removal_order_id", AgentRemovalOrder.removal_order_id);
            //Fees and total
            fd.append("removal_order_rush_fee", form.find(`[name="removal_order_rush_fee"]`).val());
            fd.append("removal_order_fee", form.find(`[name="removal_order_fee"]`).val());
            fd.append("removal_order_zone_fee", form.find(`[name="removal_order_zone_fee"]`).val());
            fd.append('total', AgentRemovalOrder.total);
            //Button
            form.find(`[type="submit"]`).prop('disabled', true);
            form.find(`[type="submit"]`).html(`<strong class="text-white">SENDING...</strong>`);
            //Multiple posts
            fd.append("multiplePosts", AgentRemovalOrder.multiplePosts);

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

                if (res.removalOrder.editOrder && !res.removalOrder.needPayment) {
                    window.location.reload();
                }

                if (res.removalOrder.needPayment) {
                    $(`[removal-payment-total-amount]`).html(parseFloat(res.removalOrder.total).toFixed(2));
                    $(`[removal-payment-card-name]`).val(res.billing.name);

                    $(`[removal-billing-name]`).val(res.billing.name);
                    $(`[removal-billing-address]`).val(res.billing.address);
                    $(`[removal-billing-city]`).val(res.billing.city);
                    $(`[removal-billing-state]`).val(res.billing.state);
                    $(`[removal-billing-zip]`).val(res.billing.zipcode);

                    if (res.removalOrder.agent.user.authorizenet_profile_id) {
                        $('#removal_use_card_profile').prop('checked', true);
                        $('#removal_card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#removal_use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        Payment.loadCards($('#removal_card_profile_select'), res.removalOrder.agent.user.id);
                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#removal_use_another_card').prop('checked', true);
                        $('#removal_use_card_profile').prop('checked', false);
                        $('#removal_card_profile_select').prop('disabled', true);
                    }

                    //Load any office card visible to agent
                    Payment.loadOfficeCardsVisibleToAgent(
                        $('#removal_card_profile_select'),
                        res.removalOrder.office.user.id,
                        () => {
                            $('#removal_use_card_profile').prop('checked', true);
                            $('#removal_card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#removal_use_another_card').prop('checked', false);
                        }
                    );

                    let removalOrderModal = $("#removalOrderModal");
                    if (removalOrderModal.length) removalOrderModal.modal('hide')

                    let paymentModal = $("#removalPaymentModal");
                    if (paymentModal.length) {
                        paymentModal.find(`[name="removal_order_id"]`).val(res.removalOrder.id);

                        helper.hideLoader('removalPaymentModal');
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
        $('#deleteAllRemovalOrders').on('click', (e) => {
            helper.confirm(
                "",
                "",
                () => {
                    const deletePath = helper.getSiteUrl("/removal/order/delete/all");
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

    async countPostsAtProperty(address, lat, lng, officeId, agentId) {
        const checkPosts = await $.get(`
            ${helper.getSiteUrl()}/removal/order/count/posts/address/${address}/lat/${lat}/lng/${lng}/office/${officeId}/agent/${agentId}
        `);

        return checkPosts;
    },

    async getOthersOrdersSameProperty() {
        const getOrders = await $.get(`${helper.getSiteUrl()}/removal/orders/same/property/${AgentRemovalOrder.order_id}`);

        return getOrders;
    },

    multiplePosts: false,
    processMultiplePosts() {
        $("[multiple-posts-yes-button]").on('click', async (e) => {
            AgentRemovalOrder.multiplePosts = true;
            Payment.multiplePosts = true;

            const baseFee = parseFloat($('[name="removal_order_fee"]').val());
            let removalFees = parseFloat($('[name="removal_order_fee"]').val());
            const discount = parseFloat($('[name="discount_extra_post_removal"]').val());

            //Get all others posts except current one
            const orders = await AgentRemovalOrder.getOthersOrdersSameProperty();
            if (orders) {
                //Apply discount to each additional order/post
                $.each(orders, (i, order) => {
                    removalFees = removalFees + baseFee * discount / 100;
                });

                $('[name="removal_order_fee"]').val(removalFees);

                AgentRemovalOrder.setRemovalFee();
            }
        });

        $("[multiple-posts-no-button]").on('click', e => {
            AgentRemovalOrder.multiplePosts = false;
            Payment.multiplePosts = false;
        });
    },

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = AgentRemovalOrder.rowCount;

        for (let i=1; i <= totalRows; i++) {
            charge = $(`[name="removal_price_adjustment_charge[${i}]"]`).val();
            discount = $(`[name="removal_price_adjustment_discount[${i}]"]`).val();

            if (charge > 0) {
                totalAdjustments = parseFloat(totalAdjustments) + parseFloat(charge);
            }

            if (discount > 0) {
                totalAdjustments = parseFloat(totalAdjustments) - parseFloat(discount);
            }
        }

        AgentRemovalOrder.totalAdjusted = totalAdjustments;

        if (AgentRemovalOrder.totalAdjusted < 0) {
            $('[removal-adjustments]').html(`<span class="text-danger">- $${AgentRemovalOrder.totalAdjusted*(-1)}</span>`);
        } else {
            $('[removal-adjustments]').html(`$${AgentRemovalOrder.totalAdjusted}`);
        }

        $(`[name="removal_order_zone_fee"]`).trigger('change');
    },

    loadSavedAdjustments(adjustments) {
        const rowTmpl = $('#rowTmplRemoval').html();
        const rowContainer = $('#rowContainerRemoval');
        let totalAdjustments = 0;
        AgentRemovalOrder.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };

        rowContainer.empty();
        $.each(adjustments, (i, row)=> {
            AgentRemovalOrder.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, AgentRemovalOrder.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="removal_price_adjustment_description[${AgentRemovalOrder.rowCount}]"]`).val(row.description);
            $(`[name="removal_price_adjustment_charge[${AgentRemovalOrder.rowCount}]"]`).val(row.charge);
            $(`[name="removal_price_adjustment_discount[${AgentRemovalOrder.rowCount}]"]`).val(row.discount);

            AgentRemovalOrder.pricingAdjustments['description'][i] = row.description;
            AgentRemovalOrder.pricingAdjustments['charge'][i] = row.charge;
            AgentRemovalOrder.pricingAdjustments['discount'][i] = row.discount;

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            AgentRemovalOrder.totalAdjusted = totalAdjustments;

            if (AgentRemovalOrder.totalAdjusted < 0) {
                $('[removal-adjustments]').html(`<span class="text-danger">- $${AgentRemovalOrder.totalAdjusted*(-1)}</span>`);
            } else {
                $('[removal-adjustments]').html(`$${AgentRemovalOrder.totalAdjusted}`);
            }
        });

        this.calculateAdjustments();
    },

}

$(() => {
    AgentRemovalOrder.init();
});
