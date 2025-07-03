import helper from "./helper";
import Agent from "./agent";
import Office from "./office";
import global from "./global";
import agentOfficesSearch from "./agent-offices-search";
import _ from "lodash";

let User = {
    userRole: $('#userRole').val(),
    agent: null,
    office: null,
    role: {
        superAdmin: 1,
        office: 2,
        agent: 3,
        installer: 4
    },

    init() {

        if (helper.urlContains('settings') && User.userRole == User.role.agent) {

            //get the current agent
            $.get(`${helper.getSiteUrl()}/settings/agent`)
            .done((agent) => {
                User.agent = agent.agent;
                Agent.agentId = agent.agent.id,
                Agent.agentEmail = agent.email
            })
            .fail((error) => {
                helper.alertError(helper.serverErrorMessage());
            });

            Agent.onAdditionalSettingsClick();
            window.onAgentNotificationClick = Agent.onAgentNotificationClick;
            Agent.onNewEmailSubmit();
            window.removeAgentEmail = Agent.removeAgentEmail;
            Agent.onClosePasswordSettingsModal();

            window.changeOffice = this.changeOffice;
            window.agentOfficesSearch = agentOfficesSearch.list({
                table: "#changeOfficeTable",
                search_element: "#changeOfficeTableSearch",
                tableName: "changeOfficeTable",
            });
        } else if (helper.urlContains('settings') && User.userRole == User.role.office) {
            //get the current office
            $.get(`${helper.getSiteUrl()}/settings/office`)
            .done((office) => {
                Office.officeId = office.office.id,
                Office.officeEmail = office.email
            }).fail((error) => {
                helper.alertError(helper.serverErrorMessage());
            });
            Office.onAdditionalSettingsClick();
            window.onNotificationClick = Office.onNotificationClick;
            Office.onNewEmailSubmit();
            window.removeEmail = Office.removeEmail;
            Office.onClosePasswordSettingsModal();
        }

        if (helper.urlContains('users')) {
            Office.init();
            Agent.init();
            Office.officesDatatable();
            if (this.userRole == User.role.office) {
                Office.getOfficeAgent()
                Office.officeAgentsDatatable();
            }
            Agent.agentsDatatable();
            this.installerDatatable();
            this.activeTab();

            window.editInstallerModal = this.editInstallerModal;
            this.installerSearchInput();
            this.showInstallersEntries();
            this.activeInactive();
        } else {
            const isOrderDetailsPage = window.location.href.indexOf('installer/order/details/') != -1;

            if ( ! isOrderDetailsPage) {
                this.installerSettings();
                this.onOrderCardClick();
                this.onRouteDateChange();

                if (helper.urlContains('dashboard')) {
                    //this.loadInstallerOrders();

                    $('#installerMapView').removeClass('order-tab-active');
                    $('#installerRoute').addClass('order-tab-active');
                    $('#installerPullList').removeClass('order-tab-active');
                }

                if (helper.urlContains('/installer/map/view')) {
                    $('#installerMapView').addClass('order-tab-active');
                    $('#installerRoute').removeClass('order-tab-active');
                    $('#installerPullList').removeClass('order-tab-active');

                    this.initRoutingMap();
                    this.acceptJob();
                }

                if (helper.urlContains('/installer/pull/list')) {
                    this.pullList();
                    $('#installerMapView').removeClass('order-tab-active');
                    $('#installerRoute').removeClass('order-tab-active');
                    $('#installerPullList').addClass('order-tab-active');
                }
            } else {
                this.orderDetails();
                this.onOrderCommentChange();
            }
        }
    },

    changeOffice() {
        localStorage.selected_agent_for_change_office = User.agent.id;
        localStorage.current_office = User.agent.agent_office;

        let modal = $("#changeOfficeFormModal");
        if (modal.length) {
            window.agentOfficesSearch.api().draw();
            modal.modal();
            modal.on("hidden.bs.modal", () => {
                localStorage.removeItem("selected_agent_for_change_office");
            });
        }
    },

    googleKey: global.googleKey,
    initRoutingMap() {
        window.initRoutingMap = this.startRoutingMap;

        const src = `https://maps.googleapis.com/maps/api/js?key=${User.googleKey}&callback=window.initRoutingMap&libraries=drawing,geometry,places&v=weekly`;
        $("body").append(window.e("script", { src, googlescript: true }));
    },

    startRoutingMap() {
        // The location of defaultLocation
        const defaultLocation = {
            lat: 43.633994,
            lng: -116.433707,
        };

        // The map, centered at defaultLocation
        const map = new google.maps.Map(document.getElementById("installerMap"),
            {
                zoom: 9,
                center: defaultLocation,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                disableDefaultUI: true
            }
        );

        window.routeMap = map;

        //Get orders and load on map
        window.markers = {};
        User.loadRoutesOnMap($('#installerId').val());
    },

    previousMarkers: [],
    previousPolylines: [],
    routeOrders: {},
    installerId: {},
    totalAssigned: 0,
    totalUnassigned: 0,
    oms: {},
    assignedCounter: 0,
    async loadRoutesOnMap(installerId) {
        User.assignedCounter = 0;

        //Get orders
        User.installerId = installerId;

        let map = window.routeMap;
        const url = `${helper.getSiteUrl()}/get/installer/orders`;
        const orders = await $.post(url, {installerId: installerId, route_date: $('#installerRouteDateSelect').val()});
        User.routeOrders = orders;

        //Create instance of OverlappingMarkerSpiderfier and associate with the map
        User.oms = new OverlappingMarkerSpiderfier(map, {
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

        const previousMarkers = User.previousMarkers;
        //console.log(previousMarkers)
        if (previousMarkers.length) {
            for (let i = 0; i < previousMarkers.length; i++) {
                previousMarkers[i].setMap(null);
            }
        }
        const previousPolylines = User.previousPolylines;
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
            User.totalAssigned = 0;
            User.totalUnassigned = 0;
            for (let i = 0; i < markers.length; i++) {
                let data = markers[i];
                //console.log(data)
                let myLatlng = new google.maps.LatLng(data.latitude, data.longitude);
                directionData.push(data);

                let icon;
                let label;
                if (data.installerId > 0) {
                    User.totalAssigned++;

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
                    User.totalUnassigned++;

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
                User.previousMarkers.push(marker);

                //latlngbounds.extend(marker.position);
                (function(marker, data) {
                    //google.maps.event.addListener(marker, "click", function(e) {
                    google.maps.event.addListener(marker, "spider_click", async (e) => {
                        if (! data.assigned) {
                            helper.showLoader();

                            //Get order inventory items
                            User.getOrderItems(data.order_id, data.order_type);
                            setTimeout(() => {
                                //Display modal to accept job
                                $('#acceptJobModal').find('#acceptJobBtn').data('order-type', data.order_type);
                                $('#acceptJobModal').find('#acceptJobBtn').data('order-id', data.order_id);
                                $('#acceptJobModal').find('#jobAddress').html(data.address);
                                $('#acceptJobModal').find('#jobOfficeAgent').html(`${data.office_name} - ${data.agent_name}`);

                                helper.hideLoader('acceptJobModal');
                            }, 500);
                        } else {
                            helper.showLoader();

                            //Display order below Map
                            const routeDate = $('#installerRouteDateSelect').val();
                            const url = `${helper.getSiteUrl()}/installer/map/view/${data.order_type}/${data.order_id}/${routeDate}`;
                            helper.redirectTo(url);
                        }
                    });
                })(marker, data);

                User.oms.addMarker(marker);
            }

            //map.setCenter(latlngbounds.getCenter());
            //map.fitBounds(latlngbounds);

            //Set total assigned/unassigned
            $('#totalAssigned').html(` ${User.totalAssigned}`);
            $('#totalUnassigned').html(` ${User.totalUnassigned}`);

            if (User.totalAssigned <= 1) {
                helper.hideLoader();
            }

            //***********ROUTING****************//
            //Initialize the Direction Service
            //let service = new google.maps.DirectionsService();
            User.delay = 0;

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

                            User.processDirection(request, row);
                        }
                    });

                    processed.push(val);
                }
            });
        }
    },

    delay: 0,
    async processDirection(request, row) {
        const map = window.routeMap;

        const data = {
            origin: request.origin,
            destination: request.destination
        }
        let response = await $.post(`${helper.getSiteUrl()}/order/get/direction`, data);
        if (response) {
            response = JSON.parse(response);

            const overviewPolyline = response.routes[0].overview_polyline.points;
            //console.log(overviewPolyline)
            if (overviewPolyline) {
                let decodedPoints = google.maps.geometry.encoding.decodePath(overviewPolyline);

                let poly = new google.maps.Polyline({
                    map: map,
                    strokeColor: `${row.routing_color}`
                });
                poly.setPath(decodedPoints);

                User.previousPolylines.push(poly);
            }
        }
    },

    async assignJob( params) {
        //helper.showLoader();
        const url = `${helper.getSiteUrl()}/order/assign`;
        const assigned = await $.post(url, params);

        if (assigned) {

            //Display order below Map
            const routeDate = $('#installerRouteDateSelect').val();
            const url = `${helper.getSiteUrl()}/installer/map/view/${params.orderType}/${params.orderId}/${routeDate}`;
            helper.redirectTo(url);
        }
    },

    onOrderCardClick() {
        $('body').on('click', '.installer-order-card' , (e) => {
            const self = $(e.target);
            const orderId = self.data('order-id');
            const orderType = self.data('order-type');

            const url = `${helper.getSiteUrl()}/installer/order/details/${orderId}/${orderType}`;

           helper.redirectTo(url);
        });

        $('body').on('change', '.installer-stop-number' , async (e) => {
            const self = $(e.target);
            const orderId = self.data('order-id');
            const orderType = self.data('order-type');

            //Assign and reload page
            const url = `${helper.getSiteUrl()}/order/assign/update`;
            const data = {
                installerId: User.installerId,
                orderType: orderType,
                stopNumber: self.val(),
                orderId: orderId,
                route_date: $('#installerRouteDateSelect').val()
            }
            const assigned = await $.post(url, data);

            if (assigned) {
                helper.reloadPage();
            }
        });
    },

    photoIndex: 1,
    officeId: $('#officeId').val(),
    agentId: $('#agentId').val(),
    rowCountPickup: 1,
    orderDetails() {
        $('.close-order').on('click', () => {
            const url = `${helper.getSiteUrl()}/dashboard`;
            window.location.href =  url;
        });

        $('#installPhotoDiv1').on('click', (e) => {

            $('#fileUpload1').trigger('click');
        });

        $('#installPhotoDiv2').on('click', () => {
            $('#fileUpload2').trigger('click');
        });

        $('#installPhotoDiv3').on('click', () => {
            $('#fileUpload3').trigger('click');
        });

        $('[name="installation_photos[]"]').on('change', (event) => {
            const self = $(event.target);
            const index = self.data('index');

            helper.loadImagePreview(event, `photo${index}`);
            $(`#photo${index}`).removeClass('d-none');
            $(`#photoIcon${index}`).addClass('d-none');
        });

        //Double click in item will display X
        $('.installation-items').on('change', (e) => {
            const self = $(e.target);

            if ( ! self.is(':checked')) {
                self.addClass('d-none');
                self.next('.out-of-inventory').removeClass('d-none');
            }
        });

        //Change icon to out of inventory if unchecked
        $('.repair-items').on('change', (e) => {
            const self = $(e.target);

            if ( ! self.is(':checked') && ! self.hasClass('not-item')) {
                self.addClass('d-none');
                self.next('.repair-out-of-inventory').removeClass('d-none');
            }
        });

        $('body').on('click', '.out-of-inventory', (e) => {
            const self = $(e.target);

            if (e.currentTarget != e.target) {
                self.parent().prev().removeClass('d-none');
                self.parent().addClass('d-none');
            } else {
                self.prev().removeClass('d-none');
                self.addClass('d-none');
            }
        });

        $('body').on('click', '.repair-out-of-inventory', (e) => {
            const self = $(e.target);

            if (e.currentTarget != e.target) {
                self.parent().prev().removeClass('d-none');
                self.parent().addClass('d-none');
            } else {
                self.prev().removeClass('d-none');
                self.addClass('d-none');
            }
        });

        $('.delivery-items').on('change', (e) => {
            const self = $(e.target);

            if ( ! self.is(':checked')) {
                self.addClass('d-none');
                self.next('.delivery-out-of-inventory').removeClass('d-none');
            } else {
                self.removeClass('d-none').prop('checked', true);
                self.next('.delivery-out-of-inventory').addClass('d-none');
            }
        });

        $('body').on('click', '.delivery-out-of-inventory', (e) => {
            const self = $(e.target);

            if (e.currentTarget != e.target) {
                self.parent().prev().removeClass('d-none');
                self.parent().addClass('d-none');
                self.parent().prev().prop('checked', true);
            } else {
                self.prev().removeClass('d-none');
                self.addClass('d-none');
                self.prev().prop('checked', true);
            }
        });

        $('#installSearchAccessory').on('keyup', (e) => {
            const self = $(e.target);
            const searchValue = self.val().toLowerCase();

            $('.modal-accessories').each( (i, el) => {
                let elem = $(el);
                let accessoryName = elem.data('accessory-name').toLowerCase();
                if (accessoryName.indexOf(searchValue) == -1) {
                    elem.addClass('d-none');
                } else {
                    elem.removeClass('d-none');
                }
            });
        });

        $('#repairSearchAccessory').on('keyup', (e) => {
            const self = $(e.target);
            const searchValue = self.val().toLowerCase();

            $('.modal-accessories').each( (i, el) => {
                let elem = $(el);
                let accessoryName = elem.data('accessory-name').toLowerCase();
                if (accessoryName.indexOf(searchValue) == -1) {
                    elem.addClass('d-none');
                } else {
                    elem.removeClass('d-none');
                }
            });
        });

        $('#installOpenAccessoriesModal').on('click', (e) => {
            $('.modal-accessories-checkbox').each( (i, el) => {
                $(el).prop('checked', false);
            });

            helper.openModal('installAccessoriesModal');
        });

        $('#repairOpenAccessoriesModal').on('click', (e) => {
            $('.modal-accessories-checkbox').each( (i, el) => {
                $(el).prop('checked', false);
            });

            helper.openModal('repairAccessoriesModal');
        });

        helper.inputNumber('.qty-box');
        $('#installAddPanelsBtn').on('click', (e) => {
            if ($('#orderHasAgent').val() == 'yes') {
                this.loadAgentPanels();
            } else {
                this.loadOfficePanels();
            }

            $(`[name="pickup_panel_style[1]"]`)
            .html('<option value="0">--Select Panel Style--</option>')
            .prop('disabled', false);

            $(`[name="pickup_panel_qty[1]"]`)
            .val('')
            .prop('disabled', false);

            $(`#addNewPickup1`).show();

            for (let i = 2; i <= User.rowCountPickup; i++) {
                $(`#pickupDiv${i}`).remove();
            }

            helper.openModal('installAddPanelsModal');
        });

        $('#repairAddPanelsBtn').on('click', (e) => {
            if ($('#orderHasAgent').val() == 'yes') {
                this.loadAgentPanels();
            } else {
                this.loadOfficePanels();
            }

            $(`[name="pickup_panel_style[1]"]`)
            .html('<option value="0">--Select Panel Style--</option>')
            .prop('disabled', false);

            $(`[name="pickup_panel_qty[1]"]`)
            .val('')
            .prop('disabled', false);

            $(`#addNewPickup1`).show();

            for (let i = 2; i <= User.rowCountPickup; i++) {
                $(`#pickupDiv${i}`).remove();
            }

            helper.openModal('repairAddPanelsModal');
        });

        $('#removalAddPanelsBtn').on('click', (e) => {
            if ($('#orderHasAgent').val() == 'yes') {
                this.loadAgentPanels();
            } else {
                this.loadOfficePanels();
            }

            $(`[name="pickup_panel_style[1]"]`)
            .html('<option value="0">--Select Panel Style--</option>')
            .prop('disabled', false);

            $(`[name="pickup_panel_qty[1]"]`)
            .val('')
            .prop('disabled', false);

            $(`#addNewPickup1`).show();

            for (let i = 2; i <= User.rowCountPickup; i++) {
                $(`#pickupDiv${i}`).remove();
            }

            helper.openModal('removalAddPanelsModal');
        });

        $('body').on('click', '.add-new-pickup', (e)=> {
            e.stopImmediatePropagation();
            const self = $(e.target);
            let rowNumber = self.data('row');
            sessionStorage.setItem('rowNumber', rowNumber);

            //Generate panel as inactive until installer confirm
            helper.confirm(
                'New Sign Panel',
                "Is the panel design that is DIFFERENT from those already stored with us?",
                () => {
                    //Change dropdown style to New Panel
                    rowNumber = sessionStorage.getItem('rowNumber');

                    $(`[name="pickup_panel_style[${rowNumber}]"]`)
                        .append(`<option value="-1">New Panel</option>`)
                        .val('-1')
                        .prop('disabled', true);

                    $(`[name="pickup_panel_qty[${rowNumber}]"]`)
                        .val(1)
                        .prop('disabled', true);

                    $(`#addNewPickup${rowNumber}`).hide();
                },
                () => {}
            );
        });

        $('#addAnotherPickup').on('click', ()=> {
            const pickupTmpl = $('#pickupTmpl').html();
            const pickupContainer = $('#pickupContainer');

            User.rowCountPickup++;

            let newTmpl = pickupTmpl.replace(/rowCount/g, User.rowCountPickup);
            pickupContainer.append(newTmpl);

            const panelHtml = $(`[name="pickup_panel_style[1]"]`).html();
            $(`[name="pickup_panel_style[${User.rowCountPickup}]"]`).html(panelHtml);
            $(`[name="pickup_panel_style[${User.rowCountPickup}]"]`)
                .find('option[value="-1"]')
                .remove();
        });

        $('body').on('click', '.remove-pickup', (e)=> {
            const self = $(e.target);

            self.closest('.to-append').remove();
            User.rowCountPickup--;
        });

        $('#installSavePanelsBtn').on('click', (e) => {
            User.signPanels = {
                panel: [],
                qty: []
            };

            $('.panel-list').each( (i, el) => {
                const elem = $(el);

                if (elem.val() != 0) {
                    User.signPanels['panel'][i] = elem.val();
                    User.signPanels['qty'][i] = elem.next('.qty-box').val();
                }
            });

            helper.closeModal('installAddPanelsModal');
        });

        $('#repairSavePanelsBtn').on('click', (e) => {
            User.signPanels = {
                panel: [],
                qty: []
            };

            $('.panel-list').each( (i, el) => {
                const elem = $(el);

                if (elem.val() != 0) {
                    User.signPanels['panel'][i] = elem.val();
                    User.signPanels['qty'][i] = elem.next('.qty-box').val();
                }
            });

            helper.closeModal('repairAddPanelsModal');
        });

        $('#removalSavePanelsBtn').on('click', (e) => {
            User.signPanels = {
                panel: [],
                qty: []
            };

            $('.panel-list').each( (i, el) => {
                const elem = $(el);

                if (elem.val() != 0) {
                    User.signPanels['panel'][i] = elem.val();
                    User.signPanels['qty'][i] = elem.next('.qty-box').val();
                }
            });

            helper.closeModal('removalAddPanelsModal');
        });

        $('#installAddAccessoryBtn').on('click', (e) => {
            $('.modal-accessories-checkbox').each( (i, el) => {
                const elem = $(el);
                if (elem.is(':checked')) {
                    const accessoryId = elem.data('accessory-id');
                    $('.hidden-accessories').each( (i, el2) => {
                        const div = $(el2);
                        const id = div.data('accessory-id');
                        if (id == accessoryId) {
                            div.removeClass('d-none');
                        }
                    });
                }
            });

            $('#installSearchAccessory').val('').trigger('keyup');

            helper.closeModal('installAccessoriesModal');
        });

        $('#repairAddAccessoryBtn').on('click', (e) => {
            $('.modal-accessories-checkbox').each( (i, el) => {
                const elem = $(el);
                if (elem.is(':checked')) {
                    const accessoryId = elem.data('accessory-id');
                    $('.hidden-accessories').each( (i, el2) => {
                        const div = $(el2);
                        const id = div.data('accessory-id');
                        if (id == accessoryId) {
                            div.removeClass('d-none');
                        }
                    });
                }
            });

            $('#repairSearchAccessory').val('').trigger('keyup');

            helper.closeModal('repairAccessoriesModal');
        });

        $('body').on('click', '.remove-accessory', (e) => {
            const self = $(e.target);

            let accessoryId = 0;
            if (e.currentTarget != e.target) {
                accessoryId = self.parent().data('accessory-id');
            } else {
                accessoryId = self.data('accessory-id');
            }

            $('.hidden-accessories').each( (i, el2) => {
                const div = $(el2);
                const id = div.data('accessory-id');
                if (id == accessoryId) {
                    div.addClass('d-none');
                }
            });
        });

        $('#markInstallCompleteBtn').on('click', (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);

            self.prop('disabled', true);

            let orderStatus = 'complete';

            let missingPostId = '';
            let missingPanelId = '';
            let missingAccessoriesIds = '';
            let postOutOfInventoryId = '';
            let panelOutOfInventoryId = '';
            let accessoriesOutOfInventoryIds = '';
            let installedPostId;
            let installedPanelId;

            //Make sure at least post is installed
            if ( ! $('#postCheckbox').is(':checked')) {
                /*helper.alertError('Post must be installed.');
                return false;*/

                //If post not selected then order is incomplete if not out of inventory
                const postOut = ! $('#postCheckbox').next('.out-of-inventory').hasClass('d-none');
                if ( ! postOut) {
                    orderStatus = 'incomplete';

                    //Get id of the missing post
                    missingPostId = $('#postCheckbox').data('item-id');
                } else {
                    postOutOfInventoryId = $('#postCheckbox').data('item-id');
                }
            } else {
                installedPostId = $('#postCheckbox').data('item-id');
            }

            //Make sure comment field is not empty or less than 3 characters
            if ($(`#installerComments`).val() == "" || ($(`#installerComments`).val() || "").trim().length < 3) {
                helper.alertError("Comments are required and at least 3 characters long.");
                self.prop('disabled', false);
                return false;
            }

            //Make sure address is verified
            if ( ! $('#addressVerified').is(':checked')) {
                helper.alertError('Address must be verified.');
                self.prop('disabled', false);
                return false;
            }

            //Must upload at least one photo of the installation
            //Except for removal order

            if (!$('#fileUpload1').val() && !$('#fileUpload2').val() && !$('#fileUpload3').val()) {
                helper.alertError('Photos must be uploaded.');
                self.prop('disabled', false);
                return false;
            }

            //If panel not selected then order is incomplete
            if ( $('#panelCheckbox').length && ! $('#panelCheckbox').is(':checked')) {
                const panelOut = ! $('#panelCheckbox').next('.out-of-inventory').hasClass('d-none');
                if ( ! panelOut) {
                    orderStatus = 'incomplete';

                    missingPanelId = $('#panelCheckbox').next('.out-of-inventory').data('item-id');
                } else {
                    panelOutOfInventoryId = $('#panelCheckbox').next('.out-of-inventory').data('item-id');
                }
            } else {
                installedPanelId = $('#panelCheckbox').data('item-id');
            }

            //Loop through accessories
            let comments = $('#installerComments').val();
            let installedAccessoriesIds = '';
            $('.accessory-div').each((i, el) => {
                const elem = $(el);
                if ( ! elem.hasClass('d-none')) {
                    const checkbox = elem.find('.accessories-checkbox');
                    //If not checked then it means order will be incomplete
                    if ( ! checkbox.is(':checked')) {
                        const accessoryOut = ! checkbox.next('.out-of-inventory').hasClass('d-none');
                        const accessoryId = checkbox.next('.out-of-inventory').data('item-id');
                        if ( ! accessoryOut) {
                            orderStatus = 'incomplete';

                            missingAccessoriesIds += `${accessoryId},`;
                        } else {
                            accessoriesOutOfInventoryIds += `${accessoryId},`;
                        }
                    }

                    //Need to attach any accessory item to the end of comments
                    if (checkbox.is(':checked')) {
                        const accessoryItem = elem.find('.accessories-item');
                        comments += `
                        ${accessoryItem.data('accessory-name')} ${accessoryItem.find('option:selected').text()}`;

                        installedAccessoriesIds += `${checkbox.data('item-id')},`;
                    }
                }
            });
            accessoriesOutOfInventoryIds = accessoriesOutOfInventoryIds.replace(/\,+$/, '');
            missingAccessoriesIds = missingAccessoriesIds.replace(/\,+$/, '');
            installedAccessoriesIds = installedAccessoriesIds.replace(/\,+$/, '');

            //Any items out of inventory?
            let outOfInventory = '';;
            $('.out-of-inventory').each((i, el) => {
                const elem = $(el);
                if ( ! elem.hasClass('d-none')) {
                    outOfInventory += `${elem.data('item-name')},`;

                    /*comments += `
                    Item out of inventory: ${elem.data('item-name')}`;*/
                }
            });
            outOfInventory = outOfInventory.replace(/\,+$/, '');

            //console.log(comments)
            let params = [];
            params['comments'] = comments.replace(/undefined/g, '');
            params['outOfInventory'] = outOfInventory;
            params['missingPostId'] = missingPostId;
            params['missingPanelId'] = missingPanelId;
            params['missingAccessoriesIds'] = missingAccessoriesIds;
            params['orderStatus'] = orderStatus;
            params['orderId'] = $('#orderId').val();
            params['orderType'] = $('#orderType').val();
            params['postOutOfInventoryId'] = postOutOfInventoryId;
            params['panelOutOfInventoryId'] = panelOutOfInventoryId;
            params['accessoriesOutOfInventoryIds'] = accessoriesOutOfInventoryIds;
            params['installedAccessoriesIds'] = installedAccessoriesIds;
            params['installedPostId'] = installedPostId;
            params['installedPanelId'] = installedPanelId;

            //If any item unmarked then ask for confirmation
            if (orderStatus == 'incomplete') {
                helper.confirm(
                    'Submit Order',
                    'There are unchecked items. Proceed?',
                    () => {
                        helper.showLoader();
                        User.installOrderComplete(params);
                    },
                    () => {
                        self.prop('disabled', false);
                    }
                );
            } else {
                helper.showLoader();
                User.installOrderComplete(params);
            }
        });

        $('#markRepairCompleteBtn').on('click', (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);

            self.prop('disabled', true);

            let orderStatus = 'complete';

            let missingPanelId = '';
            let missingAccessoriesIds = '';
            let panelOutOfInventoryId = '';
            let accessoriesOutOfInventoryIds = '';
            let repairedPanelId = 0;

            //Replace post
            let missingReplaceRepairPost;
            let postReplaceRepair;
            let missingRelocatePost;
            let postRelocate;
            if ($('#replaceRepairPostCheckbox').length && ! $('#replaceRepairPostCheckbox').is(':checked')) {
                orderStatus = 'incomplete';
                missingReplaceRepairPost = true;
            }
            if ($('#replaceRepairPostCheckbox').length && $('#replaceRepairPostCheckbox').is(':checked')) {
                postReplaceRepair = true;
            }

            //Relocate post
            if ($('#relocatePostCheckbox').length && ! $('#relocatePostCheckbox').is(':checked')) {
                orderStatus = 'incomplete';
                missingRelocatePost = true;
            }
            if ($('#relocatePostCheckbox').length && $('#relocatePostCheckbox').is(':checked')) {
                postRelocate = true;
            }

            //Make sure comment field is not empty or less than 3 characters
            if ($(`#installerComments`).val() == "" || ($(`#installerComments`).val() || "").trim().length < 3) {
                helper.alertError("Comments are required and at least 3 characters long.");
                self.prop('disabled', false);
                return false;
            }

            //Make sure address is verified
            if ( ! $('#addressVerified').is(':checked')) {
                helper.alertError('Address must be verified.');
                self.prop('disabled', false);
                return false;
            }

            //Must upload at least one photo of the installation
            //Except for removal order

            if (!$('#fileUpload1').val() && !$('#fileUpload2').val() && !$('#fileUpload3').val()) {
                helper.alertError('Photos must be uploaded.');
                self.prop('disabled', false);
                return false;
            }

            //If panel not selected then order is incomplete
            if ( $('#panelCheckbox').length && ! $('#panelCheckbox').is(':checked')) {
                const panelOut = ! $('#panelCheckbox').next('.repair-out-of-inventory').hasClass('d-none');
                if ( ! panelOut) {
                    orderStatus = 'incomplete';

                    missingPanelId = $('#panelCheckbox').next('.repair-out-of-inventory').data('item-id');
                } else {
                    panelOutOfInventoryId = $('#panelCheckbox').next('.repair-out-of-inventory').data('item-id');
                }
            } else {
                repairedPanelId = $('#panelCheckbox').data('item-id');
            }

            //Loop through accessories
            let comments = $('#installerComments').val();
            let repairedAccessoriesIds = '';
            $('.accessory-div').each((i, el) => {
                const elem = $(el);
                if ( ! elem.hasClass('d-none')) {
                    const checkbox = elem.find('.accessories-checkbox');
                    //If not checked then it means order will be incomplete
                    if ( ! checkbox.is(':checked')) {
                        const accessoryOut = ! checkbox.next('.repair-out-of-inventory').hasClass('d-none');
                        const accessoryId = checkbox.next('.repair-out-of-inventory').data('item-id');
                        if ( ! accessoryOut) {
                            orderStatus = 'incomplete';

                            missingAccessoriesIds += `${accessoryId},`;
                        } else {
                            accessoriesOutOfInventoryIds += `${accessoryId},`;
                        }
                    }

                    //Need to attach any accessory item to the end of comments
                    if (checkbox.is(':checked')) {
                        const accessoryItem = elem.find('.accessories-item');
                        comments += `
                        ${accessoryItem.data('accessory-name')} ${accessoryItem.find('option:selected').text()}`;

                        if ( ! checkbox.hasClass('.to-remove')) {
                            repairedAccessoriesIds += `${checkbox.data('item-id')},`;
                        }
                    }
                }
            });
            accessoriesOutOfInventoryIds = accessoriesOutOfInventoryIds.replace(/\,+$/, '');
            missingAccessoriesIds = missingAccessoriesIds.replace(/\,+$/, '');
            repairedAccessoriesIds = repairedAccessoriesIds.replace(/\,+$/, '');

            //Any items out of inventory?
            let outOfInventory = '';;
            $('.repair-out-of-inventory').each((i, el) => {
                const elem = $(el);
                if ( ! elem.hasClass('d-none')) {
                    outOfInventory += `${elem.data('item-name')},`;

                    /*comments += `
                    Item out of inventory: ${elem.data('item-name')}`;*/
                }
            });
            outOfInventory = outOfInventory.replace(/\,+$/, '');

            //console.log(comments)
            let params = [];
            params['comments'] = comments.replace(/undefined/g, '');
            params['outOfInventory'] = outOfInventory;
            params['missingPanelId'] = missingPanelId;
            params['missingAccessoriesIds'] = missingAccessoriesIds;
            params['orderStatus'] = orderStatus;
            params['orderId'] = $('#orderId').val();
            params['orderType'] = $('#orderType').val();
            params['panelOutOfInventoryId'] = panelOutOfInventoryId;
            params['accessoriesOutOfInventoryIds'] = accessoriesOutOfInventoryIds;
            params['repairedAccessoriesIds'] = repairedAccessoriesIds;
            params['repairedPanelId'] = repairedPanelId;
            if (typeof missingReplaceRepairPost != 'undefined') {
                params['missingReplaceRepairPost'] = missingReplaceRepairPost;
            }
            if (typeof missingRelocatePost != 'undefined') {
                params['missingRelocatePost'] = missingRelocatePost;
            }
            if (typeof postReplaceRepair != 'undefined') {
                params['postReplaceRepair'] = postReplaceRepair;
            }
            if (typeof postRelocate != 'undefined') {
                params['postRelocate'] = postRelocate;
            }

            //If any item unmarked then ask for confirmation
            if (orderStatus == 'incomplete') {
                helper.confirm(
                    'Submit Order',
                    'There are unchecked items. Proceed?',
                    () => {
                        helper.showLoader();
                        User.repairOrderComplete(params);
                    },
                    () => {
                        self.prop('disabled', false);
                    }
                );
            } else {
                helper.showLoader();
                User.repairOrderComplete(params);
            }
        });

        $('#markRemovalCompleteBtn').on('click', (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);

            self.prop('disabled', true);

            let orderStatus = 'complete';

            let missingPostId = '';
            let missingPanelId = '';
            let missingAccessoriesIds = '';
            let removedPostId = '';
            let removedPanelId = '';
            let removedAccessoriesIds = '';
            let panelAction = '';

            //Make sure at least post is installed
            if ( ! $('#postCheckbox').is(':checked')) {
                orderStatus = 'incomplete';
                missingPostId = $('#postCheckbox').data('item-id');
            } else {
                removedPostId = $('#postCheckbox').data('item-id');
            }

            if ($('#panelCheckbox').length) {
                if (! $('#panelCheckbox').is(':checked')) {
                    orderStatus = 'incomplete';
                    missingPanelId = $('#panelCheckbox').data('item-id');
                } else {
                    removedPanelId = $('#panelCheckbox').data('item-id');
                    panelAction = $('#panelCheckbox').data('action');
                }
            }

            $('.accessory-div').each((i, el) => {
                const elem = $(el);
                const checkbox = elem.find('.accessories-checkbox');
                if ( ! checkbox.is(':checked')) {
                    orderStatus = 'incomplete';
                    missingAccessoriesIds += `${checkbox.data('item-id')},`;
                } else {
                    removedAccessoriesIds += `${checkbox.data('item-id')},`;
                }
            });
            missingAccessoriesIds = missingAccessoriesIds.replace(/\,+$/, '');
            removedAccessoriesIds = removedAccessoriesIds.replace(/\,+$/, '');

            //Make sure comment field is not empty or less than 3 characters
            if ($(`#installerComments`).val() == "" || ($(`#installerComments`).val() || "").trim().length < 3) {
                helper.alertError("Comments are required and at least 3 characters long.");
                self.prop('disabled', false);
                return false;
            }

            //Make sure address is verified
            if ( ! $('#addressVerified').is(':checked')) {
                helper.alertError('Address must be verified.');
                self.prop('disabled', false);
                return false;
            }

            //Loop through accessories
            let comments = $('#installerComments').val();

            //console.log(comments)
            let params = [];
            params['comments'] = comments.replace(/undefined/g, '');
            params['missingPostId'] = missingPostId;
            params['missingPanelId'] = missingPanelId;
            params['missingAccessoriesIds'] = missingAccessoriesIds;
            params['removedPostId'] = removedPostId;
            params['removedPanelId'] = removedPanelId;
            params['removedAccessoriesIds'] = removedAccessoriesIds;
            params['orderStatus'] = orderStatus;
            params['orderId'] = $('#orderId').val();
            params['orderType'] = $('#orderType').val();
            params['panelAction'] = panelAction;

            //If any item unmarked then ask for confirmation
            if (orderStatus == 'incomplete') {
                helper.confirm(
                    'Submit Order',
                    'There are unchecked items. Proceed?',
                    () => {
                        helper.showLoader();
                        User.removalOrderComplete(params);
                    },
                    () => {
                        self.prop('disabled', false);
                    }
                );
            } else {
                helper.showLoader();
                User.removalOrderComplete(params);
            }
        });

        $('#markDeliveryCompleteBtn').on('click', (e) => {
            e.stopImmediatePropagation();
            const self = $(e.target);

            self.prop('disabled', true);

            let pickupPanelIds = '';
            let dropoffPanelIds = '';
            let pickupPanelQty = '';
            let dropoffPanelQty= '';
            $('.delivery-items').each((i, el) => {
                const checkbox = $(el);

                if (checkbox.is(':checked')) {
                    if (checkbox.data('action') == 'pickup') {
                        pickupPanelIds += `${checkbox.data('item-id')},`;
                        pickupPanelQty += `${$(`[data-qty-item-id="${checkbox.data('item-id')}"]`).val()},`;
                    } else {
                        dropoffPanelIds += `${checkbox.data('item-id')},`;
                        dropoffPanelQty += `${$(`[data-qty-item-id="${checkbox.data('item-id')}"]`).val()},`;
                    }
                }
            });
            pickupPanelIds = pickupPanelIds.replace(/\,+$/, '');
            dropoffPanelIds = dropoffPanelIds.replace(/\,+$/, '');
            pickupPanelQty = pickupPanelQty.replace(/\,+$/, '')
            dropoffPanelQty = dropoffPanelQty.replace(/\,+$/, '')

            //Make sure comment field is not empty or less than 3 characters
            if ($(`#installerComments`).val() == "" || ($(`#installerComments`).val() || "").trim().length < 3) {
                helper.alertError("Comments are required and at least 3 characters long.");
                self.prop('disabled', false);
                return false;
            }

            //Make sure address is verified
            if ( ! $('#addressVerified').is(':checked')) {
                helper.alertError('Address must be verified.');
                self.prop('disabled', false);
                return false;
            }

            //Make sure items are checked
            /*if (! pickupPanelIds.length && ! dropoffPanelIds.length) {
                helper.alertError('At least one item must be checked.');
                return false;
            }*/

            //Loop through accessories
            let comments = $('#installerComments').val();

            //console.log(comments)
            let params = [];
            params['comments'] = comments.replace(/undefined/g, '');
            params['pickupPanelIds'] = pickupPanelIds;
            params['dropoffPanelIds'] = dropoffPanelIds;
            params['orderId'] = $('#orderId').val();
            params['orderType'] = $('#orderType').val();
            params['pickupPanelQty'] = pickupPanelQty;
            params['dropoffPanelQty'] = dropoffPanelQty;

            helper.showLoader();
            User.deliveryOrderComplete(params);
        });
    },

    loadOfficePanels() {
        $.get(helper.getSiteUrl(`/get/office/${User.officeId}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".panel-list");
            listContainer.empty();
            let html = '<option value="0">--Select Panel Style--</option>';
            res.forEach(panel => {
                let isChecked = '';

                html += `
                    <option value="${panel.id}">${panel.panel_name}</option>
                `;
            })

            listContainer.each(function(i, el) {
                $(el).append(html);
            });
        });
    },

    loadAgentPanels() {
        $.get(helper.getSiteUrl(`/get/agent/${User.agentId}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".panel-list");
            listContainer.empty();
            let html = '<option value="0">--Select Panel Style--</option>';
            res.forEach(panel => {
                 html += `
                    <option value="${panel.id}">${panel.panel_name}</option>
                `;
            })

            listContainer.each(function(i, el) {
                $(el).append(html);
            });
        });
    },


    installOrderComplete(params) {
        const url = `${helper.getSiteUrl()}/installer/complete/install/order`;
        const fd = new FormData();

        fd.append('installer_comments', params['comments']);
        fd.append('orderStatus', params['orderStatus']);
        fd.append('orderId', params['orderId']);
        fd.append('orderType', params['orderType']);
        fd.append('outOfInventory', params['outOfInventory']);
        fd.append('outOfInventoryIds', params['outOfInventoryIds']);
        fd.append('missingPostId', params['missingPostId']);
        fd.append('missingPanelId', params['missingPanelId']);
        fd.append('missingAccessoriesIds', params['missingAccessoriesIds']);
        fd.append('postOutOfInventoryId', params['postOutOfInventoryId']);
        fd.append('panelOutOfInventoryId', params['panelOutOfInventoryId']);
        fd.append('accessoriesOutOfInventoryIds', params['accessoriesOutOfInventoryIds']);
        fd.append('installedAccessoriesIds', params['installedAccessoriesIds']);
        fd.append('installedPostId', params['installedPostId']);
        fd.append('installedPanelId', params['installedPanelId']);
        fd.append('signPanels', JSON.stringify(User.signPanels));

        if ($('#fileUpload1').val()) {
            fd.append('photo1', $('#fileUpload1')[0].files[0]);
        }
        if ($('#fileUpload2').val()) {
            fd.append('photo2', $('#fileUpload2')[0].files[0]);
        }
        if ($('#fileUpload3').val()) {
            fd.append('photo3', $('#fileUpload3')[0].files[0]);
        }

        $.ajax({
            url: url,
            data: fd,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
        }).done(res => {
            //console.log(res)
            //return false;
            if (User.userRole == 4) {
                helper.redirectTo(`${helper.getSiteUrl()}/dashboard`);
            } else {
                parent.location.reload();
            }
        }).fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        });
    },

    repairOrderComplete(params) {
        const url = `${helper.getSiteUrl()}/installer/complete/repair/order`;
        const fd = new FormData();

        fd.append('installer_comments', params['comments']);
        fd.append('orderStatus', params['orderStatus']);
        fd.append('orderId', params['orderId']);
        fd.append('orderType', params['orderType']);
        fd.append('outOfInventory', params['outOfInventory']);
        fd.append('missingPanelId', params['missingPanelId']);
        fd.append('missingAccessoriesIds', params['missingAccessoriesIds']);
        fd.append('panelOutOfInventoryId', params['panelOutOfInventoryId']);
        fd.append('accessoriesOutOfInventoryIds', params['accessoriesOutOfInventoryIds']);
        fd.append('repairedAccessoriesIds', params['repairedAccessoriesIds']);
        fd.append('repairedPanelId', params['repairedPanelId']);
        fd.append('signPanels', JSON.stringify(User.signPanels));
        if (params['missingReplaceRepairPost']) {
            fd.append('missingReplaceRepairPost', params['missingReplaceRepairPost']);
        }
        if (params['missingRelocatePost']) {
            fd.append('missingRelocatePost', params['missingRelocatePost']);
        }
        if (params['postReplaceRepair']) {
            fd.append('postReplaceRepair', params['postReplaceRepair']);
        }
        if (params['postRelocate']) {
            fd.append('postRelocate', params['postRelocate']);
        }

        if ($('#fileUpload1').val()) {
            fd.append('photo1', $('#fileUpload1')[0].files[0]);
        }
        if ($('#fileUpload2').val()) {
            fd.append('photo2', $('#fileUpload2')[0].files[0]);
        }
        if ($('#fileUpload3').val()) {
            fd.append('photo3', $('#fileUpload3')[0].files[0]);
        }

        $.ajax({
            url: url,
            data: fd,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
        }).done(res => {
            // console.log(res)
            //return false;
            if (User.userRole == 4) {
                helper.redirectTo(`${helper.getSiteUrl()}/dashboard`);
            } else {
                parent.location.reload();
            }
        }).fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        });
    },

    removalOrderComplete(params) {
        const url = `${helper.getSiteUrl()}/installer/complete/removal/order`;
        const fd = new FormData();

        fd.append('installer_comments', params['comments']);
        fd.append('orderStatus', params['orderStatus']);
        fd.append('orderId', params['orderId']);
        fd.append('orderType', params['orderType']);
        fd.append('missingPostId', params['missingPostId']);
        fd.append('missingPanelId', params['missingPanelId']);
        fd.append('missingAccessoriesIds', params['missingAccessoriesIds']);
        fd.append('removedPostId', params['removedPostId']);
        fd.append('removedPanelId', params['removedPanelId']);
        fd.append('removedAccessoriesIds', params['removedAccessoriesIds']);
        fd.append('signPanels', JSON.stringify(User.signPanels));
        fd.append('panelAction', params['panelAction']);

        if ($('#fileUpload1').val()) {
            fd.append('photo1', $('#fileUpload1')[0].files[0]);
        }
        if ($('#fileUpload2').val()) {
            fd.append('photo2', $('#fileUpload2')[0].files[0]);
        }
        if ($('#fileUpload3').val()) {
            fd.append('photo3', $('#fileUpload3')[0].files[0]);
        }

        $.ajax({
            url: url,
            data: fd,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
        }).done(res => {
            //console.log(res)
            //return false;
            if (User.userRole == 4) {
                helper.redirectTo(`${helper.getSiteUrl()}/dashboard`);
            } else {
                parent.location.reload();
            }
        }).fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        });
    },

    deliveryOrderComplete(params) {
        const url = `${helper.getSiteUrl()}/installer/complete/delivery/order`;
        const fd = new FormData();

        fd.append('installer_comments', params['comments']);
        fd.append('orderId', params['orderId']);
        fd.append('orderType', params['orderType']);
        fd.append('pickupPanelIds', params['pickupPanelIds']);
        fd.append('dropoffPanelIds', params['dropoffPanelIds']);
        fd.append('pickupPanelQty', params['pickupPanelQty']);
        fd.append('dropoffPanelQty', params['dropoffPanelQty']);

        if ($('#fileUpload1').val()) {
            fd.append('photo1', $('#fileUpload1')[0].files[0]);
        }
        if ($('#fileUpload2').val()) {
            fd.append('photo2', $('#fileUpload2')[0].files[0]);
        }
        if ($('#fileUpload3').val()) {
            fd.append('photo3', $('#fileUpload3')[0].files[0]);
        }

        $.ajax({
            url: url,
            data: fd,
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
        }).done(res => {
            /*console.log(User.userRole)
            return false;*/
            if (User.userRole == 4) {
                helper.redirectTo(`${helper.getSiteUrl()}/dashboard`);
            } else {
                parent.location.reload();
            }
        }).fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        });
    },

    async loadInstallerOrders() {
        //helper.showLoader();
        const installerId = $('#installerId').val();
        const installTmpl = $('#installCardTmpl').html();
        const repairTmpl = $('#repairCardTmpl').html();
        const removalTmpl = $('#removalCardTmpl').html();
        const deliveryTmpl = $('#deliveryCardTmpl').html();

        const container = $('#installerCardContainer');
        container.empty();

        const url = `${helper.getSiteUrl()}/get/installer/assigned/orders`;
        const orders = await $.post(url, {installerId: installerId});
        if (orders) {
            //console.log(orders)
            let html = '';
            $.each(orders, (i, order) => {
                let address = helper.initialUppercaseWord(order.address);

                if (order.order_type == 'install') {
                    let tmpl = installTmpl.replace(/replace_address/g, address);
                    let agentOffice = order.office_name;
                    if (order.agent_name) {
                        agentOffice = `${order.agent_name}, ${order.office_name}`
                    }
                    agentOffice = helper.initialUppercaseWord(agentOffice);

                    tmpl = tmpl.replace(/agent_office/g, agentOffice);
                    tmpl = tmpl.replace(/post_name/g, order.post_name);
                    tmpl = tmpl.replace(/ORDER_ID/g, order.id);
                    tmpl = tmpl.replace(/ORDER_TYPE/g, order.order_type);

                    html += tmpl;
                }

                if (order.order_type == 'repair') {
                    let tmpl = repairTmpl.replace(/replace_address/g, address);
                    let agentOffice = order.office_name;
                    if (order.agent_name) {
                        agentOffice = `${order.agent_name}, ${order.office_name}`
                    }
                    agentOffice = helper.initialUppercaseWord(agentOffice);

                    tmpl = tmpl.replace(/agent_office/g, agentOffice);
                    tmpl = tmpl.replace(/post_name/g, order.post_name);
                    tmpl = tmpl.replace(/ORDER_ID/g, order.id);
                    tmpl = tmpl.replace(/ORDER_TYPE/g, order.order_type);

                    html += tmpl;
                }

                if (order.order_type == 'removal') {
                    let tmpl = removalTmpl.replace(/replace_address/g, address);
                    let agentOffice = order.office_name;
                    if (order.agent_name) {
                        agentOffice = `${order.agent_name}, ${order.office_name}`
                    }
                    agentOffice = helper.initialUppercaseWord(agentOffice);

                    tmpl = tmpl.replace(/agent_office/g, agentOffice);
                    tmpl = tmpl.replace(/post_name/g, order.post_name);
                    tmpl = tmpl.replace(/ORDER_ID/g, order.id);
                    tmpl = tmpl.replace(/ORDER_TYPE/g, order.order_type);

                    html += tmpl;
                }

                if (order.order_type == 'delivery') {
                    let tmpl = deliveryTmpl.replace(/replace_address/g, address);
                    let agentOffice = order.office_name;
                    if (order.agent_name) {
                        agentOffice = `${order.agent_name}, ${order.office_name}`
                    }
                    agentOffice = helper.initialUppercaseWord(agentOffice);

                    tmpl = tmpl.replace(/agent_office/g, agentOffice);

                    let deliveryType = 'Pickup';
                    if (order.post_name == 1) {
                        deliveryType = 'Dropoff';
                    }
                    tmpl = tmpl.replace(/post_name/g, deliveryType);
                    tmpl = tmpl.replace(/ORDER_ID/g, order.id);
                    tmpl = tmpl.replace(/ORDER_TYPE/g, order.order_type);

                    html += tmpl;
                }
            });

            container.append(html);

            //helper.hideLoader();
        }
    },

    installerSettings() {
        $('#installerToggler').on('click', () => {
            if($('#installerSettings').hasClass('d-none')) {
                $('#installerSettings').removeClass('d-none');
            } else {
                $('#installerSettings').addClass('d-none');
            }
        });

        $('#contentDiv').on('click', (e) => {
            if (e.target.id != 'installerToggler') {
                $('#installerSettings').addClass('d-none');
            } else {
                if($('#installerSettings').hasClass('d-none')) {
                    $('#installerSettings').removeClass('d-none');
                } else {
                    $('#installerSettings').addClass('d-none');
                }
            }
        });

        $('#changePasswordBtn').on('click', (e) => {
            const newPassword = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();
            if (!newPassword || !confirmPassword) {
                helper.alertError('All fields are required.');
                return false;
            }

            if (newPassword !== confirmPassword) {
                helper.alertError("Passwords don't match.");
                return false;
            }

            $.post(`${helper.getSiteUrl()}/change/password`, {newPassword: newPassword})
            .done(() => {
                $('#newPassword').val('');
                $('#confirmPassword').val('');
                helper.alertMsg('Change Password','Password changed successfully.');

                helper.closeModal('changePasswordModal');
            });
        });
    },

    pullList() {
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
    },

    activeTab() {
        let pill = $(`[data-toggle="pill"]`);

        if (this.userRole == 1) {
            if (localStorage.user_tab) {
                $("#" + localStorage.user_tab).trigger("click");
            }
        }

        if (this.userRole == 2) {
            $("#pills-agents-tab").trigger("click");
        }

        pill.on("click", (e) => {
            localStorage.setItem("user_tab", e.target.attributes.id.value);
        });
    },

    installerDatatable() {
        let table = $("#installersTable");
        let e = window.e;
        if (table.length) {
            window.installerDataTable = table.dataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                },
                pageLength: 10,
                dom: "rtip",
                ajax: helper.getSiteUrl("/datatable/installers"),
                serverSide: true,
                searchCols: [{ search: 0 }],
                columns: [
                    {
                        data: "inactive",
                        name: "inactive",
                        defaultContent: "404",
                        title: "inactive",
                        visible: 0,
                    },
                    {
                        data: "name",
                        name: "name",
                        defaultContent: "404",
                        title: "Name",
                        visible: 1,
                    },
                    {
                        data: "phone",
                        defaultContent: "404",
                        title: "Phone Number",
                        name: "users.phone",
                        visible: 1,
                    },
                    {
                        data: "email",
                        defaultContent: "404",
                        title: "Email",
                        name: "email",
                        visible: 1,
                    },
                    {
                        data: "hire_date",
                        defaultContent: "",
                        title: "Hire Date",
                        name: "hire_date",
                        visible: 1,
                        render(d, t, r) {
                            return helper.formatDateUsa(d);
                        }
                    },
                    {
                        data: "pay_rate",
                        defaultContent: "",
                        title: "Pay rate",
                        name: "pay_rate",
                        visible: 1,
                        render(d, t, r) {
                            return `$${d}`;
                        }
                    },

                    {
                        defaultContent: "Action",
                        orderable: 0,
                        searchable: 0,
                        title: "Action",
                        render(d, t, r) {
                            let content = "<div class='text-center'>";
                            content += e("a", {
                                htmlContent: "Password Reset <br>",
                                href: helper.getSiteUrl(
                                    `/installer/${r.id}/reset/password`
                                ),
                                class: "",
                            });
                            content += e("a", {
                                htmlContent: "Edit Account <br>",
                                // href: helper.getSiteUrl(`/installers/${r.id}/edit`),
                                onclick: `window.editInstallerModal(${r.id})`,
                                class: "",
                            });
                            content += "</div>";
                            return content;
                        },
                    },
                ],
            });
        }
    },

    showInstallersEntries() {
        let select = $("#showInstallersEntries");
        if (select.length) {
            select.on("change", (event) => {
                let selected = parseInt(event.target.value);
                window.installerDataTable.api().context[0]._iDisplayLength =
                    selected;
                window.installerDataTable.api().draw();
            });
        }
    },

    installerSearchInput() {
        let input = $("#installersSearchInput");
        if (input.length) {
            input.on("keyup", (event) => {
                let input = event.target;
                window.installerDataTable.fnFilter(input.value);
            });
        }
    },

    activeInactive() {
        let select = $("#installersStatus");
        if (select.length) {
            select.on("change", () => {
                let inactive = select.val();
                let dt = window.installerDataTable;
                dt.api().column(0).search(inactive).draw();
            });
        }
    },

    editInstallerModal(id) {
        $.get(helper.getSiteUrl(`/install/get/${id}`))
            .done((installer) => {
                console.log(installer)
                const editInstallerFormModal = $("#editInstallerFormModal");
                const editInstallerForm = $("#editInstallerForm");

                editInstallerForm.prop(
                    "action",
                    helper.getSiteUrl("/installer/update/" + installer.id)
                );

                editInstallerForm.find('input').each( (i, el) => {
                    if (el.name != '_token') {
                        el.value = installer[el.name];
                    }

                    if (el.name == 'hire_date') {
                        el.value =  helper.formatDateUsa(installer[el.name]);
                    }
                });

                editInstallerForm.find('[name="state"]').val(installer.state);
                editInstallerForm.find('[name="inactive"]').val(installer.inactive);

                editInstallerFormModal.modal();
            })
            .fail((res) => {
                helper.alertError(helper.serverErrorMessage());
            });
    },

    acceptJob() {
        $('body').on('click', '#acceptJobBtn', (e) => {
            const self = $(e.target);
            self.prop('disabled', true);

            helper.showLoader();

            const orderType = self.data('order-type');
            const orderId = self.data('order-id');
            //console.log(orderType, orderId)

            //Automatically assign job to installer
            const params = {
                installerId: User.installerId,
                orderType: orderType,
                orderId: orderId,
                route_date: $('#installerRouteDateSelect').val()
            };

            User.assignJob(params);

            return false;
        });
    },

    getOrderItems(orderId, orderType) {
        if (!orderId || !orderType) {
            return false;
        }

        if (orderType == 'install') {
            User.getInstallOrderItems(orderId, orderType);
        }

        if (orderType == 'repair') {
            User.getRepairOrderItems(orderId, orderType);
        }

        if (orderType == 'removal') {
            User.getRemovalOrderItems(orderId, orderType);
        }

        if (orderType == 'delivery') {
            User.getDeliveryOrderItems(orderId, orderType);
        }

    },

    getInstallOrderItems(orderId, orderType) {
        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {
            console.log(order)

            let phone = order.office.user.phone;
            if (order.agent) {
                phone = order.agent.user.phone;
            }

            let post = '';
            let panel = '';
            let accessories = '';

            post = order.post.post_name

            if(order.panel) {
                panel = order.panel.panel_name;
                if (order.panel.id_number) {
                    panel +=` ID#${order.panel.id_number}`;
                }
            }

            if (order.accessories) {
                $.each(order.accessories, (i, orderAcessory) => {
                    accessories += `${orderAcessory.accessory.accessory_name}, `;
                });
                accessories = accessories.replace(/\,\s+$/, '');
            }

            const items = `<strong>Post:</strong> ${post}<br><br>
            <strong>Sign Panel:</strong> ${panel}<br><br>
            <strong>Accessories:</strong> ${accessories}</span><br><br>
            ORDER COMMENTS<br>
            <textarea id="job_comments" rows="5" class="w-100" readonly>${order.comment}</textarea>`;

            $('#acceptJobModal').find('#acceptJobModalHeader').html('Add install to route?')
            $('#acceptJobModal').find('#jobItems').html(items);
            $('#acceptJobModal').find('#jobPhone').html(phone)
            .prop('href', `tel:${phone}`);
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    getRepairOrderItems(orderId, orderType) {
        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {
            //console.log(order)

            const installOrder = order.order;

            let phone = installOrder.office.user.phone;
            if (installOrder.agent) {
                phone = installOrder.agent.user.phone;
            }

            let post = '';
            let accessories = '';
            let items = '';
            let panel = '';

            if (order.replace_repair_post) {
                post += 'Replace/Repair post, ';
            }
            if (order.relocate_post) {
                post += 'Relocate post';
            }
            post = post.replace(/\,\s+$/, '');
            items += `${post}<br><br>`;

            if(order.panel) {
                panel = order.panel.panel_name;
                if (order.panel.id_number) {
                    panel +=` ID#${order.panel.id_number}`;
                }
                items += `Swap panel: ${panel}<br><br>`;
            }

            $.each(order.accessories, (i, orderAcessory) => {
                if (orderAcessory.action == 0) {
                    accessories += `Add/Replace ${orderAcessory.accessory.accessory_name}<br>`;
                } else {
                    accessories += `Remove ${orderAcessory.accessory.accessory_name}<br>`;
                }
            });
            accessories = accessories.replace(/\,\s+$/, '');

            items += `${accessories}<br>`;

            items += `ORDER COMMENTS<br>
            <textarea id="job_comments" rows="5" class="w-100" readonly>${order.comment}</textarea>`;

            $('#acceptJobModal').find('#acceptJobModalHeader').html('Add repair to route?')
            $('#acceptJobModal').find('#jobItems').html(items);
            $('#acceptJobModal').find('#jobPhone').html(phone)
            .prop('href', `tel:${phone}`);
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    getRemovalOrderItems(orderId, orderType) {
        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {
            console.log(order)

            const installOrder = order.order;

            let phone = installOrder.office.user.phone;
            if (installOrder.agent) {
                phone = installOrder.agent.user.phone;
            }

            let post = '';
            let panel = '';
            let accessories = '';

            post = installOrder.post.post_name

            if(installOrder.panel) {
                panel = installOrder.panel.panel_name
                if (installOrder.panel.id_number) {
                    panel +=` ID#${installOrder.panel.id_number}`;
                }
            }
            if(installOrder.repair) {
                panel = installOrder.repair.panel.panel_name
                if (installOrder.repair.panel.id_number) {
                    panel +=` ID#${installOrder.repair.panel.id_number}`;
                }
            }

            if (installOrder.repair) {
                $.each(installOrder.repair.accessories, (i, orderAcessory) => {
                    accessories += `${orderAcessory.accessory.accessory_name}, `;
                });
                accessories = accessories.replace(/\,\s+$/, '');
            } else {
                $.each(installOrder.accessories, (i, orderAcessory) => {
                    accessories += `${orderAcessory.accessory.accessory_name}, `;
                });
                accessories = accessories.replace(/\,\s+$/, '');
            }

            const items = `<strong>Post:</strong> ${post}<br><br>
            <strong>Sign Panel:</strong> ${panel}<br><br>
            <strong>Accessories:</strong> ${accessories}</span><br><br>
            ORDER COMMENTS<br>
            <textarea id="job_comments" rows="5" class="w-100" readonly>${order.comment}</textarea>`;

            $('#acceptJobModal').find('#acceptJobModalHeader').html('Add removal to route?')
            $('#acceptJobModal').find('#jobItems').html(items);
            $('#acceptJobModal').find('#jobPhone').html(phone)
            .prop('href', `tel:${phone}`);
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    getDeliveryOrderItems(orderId, orderType) {
        const url = `${helper.getSiteUrl()}/order/${orderId}/${orderType}`;
        $.get(url)
        .done(order => {
            //console.log(order)

            let phone = order.office.user.phone;
            if (order.agent) {
                phone = order.agent.user.phone;
            }

            let pickups = '';
            if ( ! $.isEmptyObject(order.pickups)) {
                $.each(order.pickups, (i, pickup) => {
                    if (pickup.panel.id_number) {
                        pickups += `${pickup.quantity} ${pickup.panel.panel_name} ID#${pickup.panel.id_number},`;
                    } else {
                        pickups += `${pickup.quantity} ${pickup.panel.panel_name},`;
                    }
                });
                pickups = pickups.replace(/\,\s+$/, '');
            }

            let dropoffs = '';
            if ( ! $.isEmptyObject(order.dropoffs)) {
                $.each(order.dropoffs, (i, dropoff) => {
                    if (dropoff.panel.id_number) {
                        dropoffs += `${dropoff.quantity} ${dropoff.panel.panel_name} ID#${dropoff.panel.id_number},`;
                    } else {
                        dropoffs += `${dropoff.quantity} ${dropoff.panel.panel_name},`;
                    }
                });
                dropoffs = dropoffs.replace(/\,\s+$/, '');
            }

            const items = `<strong>Pick Up:</strong> ${pickups}<br><br>
            <strong>Drop Off:</strong> ${dropoffs}<br><br>
            ORDER COMMENTS<br>
            <textarea id="job_comments" rows="5" class="w-100" readonly>${order.comment}</textarea>`;

            $('#acceptJobModal').find('#acceptJobModalHeader').html('Add install to route?')
            $('#acceptJobModal').find('#jobItems').html(items);
            $('#acceptJobModal').find('#jobPhone').html(phone)
            .prop('href', `tel:${phone}`);
        })
        .fail(res => {
            helper.hideLoader();
            helper.alertError(helper.serverErrorMessage());
        })
    },

    onOrderCommentChange() {
        let textarea = $(`#installerComments`);
        if (textarea.length) {
            textarea.on("keyup", (e) => {
                let len = (textarea.val() || "").trim().length;
                $(".char-used").html(len);
                if (len > 500 || len < 3) {
                    textarea.addClass("is-invalid");
                } else {
                    textarea.removeClass("is-invalid");
                }
            });
        }
    },

    onRouteDateChange() {
        $('#installerRouteDateSelect').on('change', async (e) => {
            const self = $(e.target);

            const routeDate = self.val();
            const url = `${helper.getSiteUrl()}/dashboard/${routeDate}`;

            helper.showLoader();

            helper.redirectTo(url);
        });
    }
};

$(() => {
    User.init();
});

export default User;
