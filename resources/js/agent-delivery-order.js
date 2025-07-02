import helper from './helper';
import global from "./global";
import Payment from "./Payment";
import obs from '../../node_modules/observable-slim/observable-slim';
import { isEmpty } from 'lodash';

//Watch for changes and update zone settings and fees
let _obj_ = {
    zones: [],
}

let proxy = obs.create(_obj_, true, function (changes) {
    if (_obj_.zones.length == AgentDeliveryOrder.zones_count) {
        let zones = _obj_.zones.filter(x => x != null);

        if (zones.length) {
            let lowest_zone_fee = null;
            zones.forEach(zone => {
                if (lowest_zone_fee == null) {
                    lowest_zone_fee = zone
                } else {
                    if (lowest_zone_fee.zone_fee > zone.zone_fee) lowest_zone_fee = zone;
                };
            })
            AgentDeliveryOrder.addresIsOut = false;
            $.get(helper.getSiteUrl(`/get/zone/settings`)).done(settings => {
                AgentDeliveryOrder.settings = settings;

                AgentDeliveryOrder._currentZone = lowest_zone_fee;
                AgentDeliveryOrder.zone = lowest_zone_fee;
                AgentDeliveryOrder.zoneSettings = settings;
                AgentDeliveryOrder.updateCalendar(false);

                let fee = parseFloat(lowest_zone_fee.zone_fee || 0) * (settings.delivery / 100);

                if (AgentDeliveryOrder.ignoreZoneFee) {
                    $(`[delivery-zone-fee]`).html(`$0`);
                    $(`[name="delivery_order_zone_fee"]`).val(0);
                } else {
                    $(`[delivery-zone-fee]`).html(`$${fee.toFixed(2)}`);
                    $(`[name="delivery_order_zone_fee"]`).val(fee);
                }

                $(`[name="delivery_order_zone_fee"]`).trigger("change");

                AgentDeliveryOrder.zoneId = lowest_zone_fee.id;
            })
        } else {
            helper.alertMsg('Address Out of Service Area', 'This address appears to be outside the service area. Please verify that the address and pin location are correct.');
            AgentDeliveryOrder._currentZone = null;
            AgentDeliveryOrder.disableAllDates();
            AgentDeliveryOrder.addresIsOut = true;
            $(`[delivery-zone-fee]`).html("00.00");
            $(`[name="delivery_order_zone_fee"]`).val("00.00");
            $(`[name="delivery_order_zone_fee"]`).get(0).dispatchEvent(new Event("change"));
            //$("[googlescript]").remove();
            helper.hideLoader('');
        }

    }
});

const AgentDeliveryOrder = {

    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    order: {},
    zone: {},
    settings: {},
    total:0,
    totalPanel:0,
    create: true,
    payment_method_office_pay: 3,
    delivery_order_id: 0,
    zoneId: null,
    ignoreZoneFee: false,
    zoneSettings: {},
    agent:{},
    officeId: $('#officeId').val(),
    agentId: $('#agentId').val(),

    dailyOrderCap: 0,
    countOrders: null,

    init() {
        this.loadPage();

        this.loadDatatable();
        this.showDeliveryOrderEntries();
        this.deliveryOrderSearchInput();
        // this.onAgentChange();

        window.createDeliveryOrder = this.createDeliveryOrder;
        window.editDeliveryOrder = this.editDeliveryOrder;
        window.deliveryOrderCancel = this.deliveryOrderCancel;
        this. onDesiredDateChange();

        if (! helper.urlContains('/order/status')) {
            this.initDeliveryMap();
        }

        this.searchAddress();
        this.totalFee();

        //Disable holidays in calendar
        $.get(helper.getSiteUrl('/get/holidays')).done(holidays => {
            AgentDeliveryOrder.holidays = holidays;
        });

        $.get(helper.getSiteUrl('/get/zone/settings')).done(settings => {
            AgentDeliveryOrder.dailyOrderCap = settings.daily_order_cap;
        })

        $.get(helper.getSiteUrl('/agent/count-orders')).done(countorders => {
            AgentDeliveryOrder.countOrders = countorders;
        })

        this.signPanel();

        this.onCommentChange();

        this.onSubmitForm();

        Payment.init()
        helper.cardNumberInput('.cc-number-input');

        helper.inputNumber('.qty-box');

        this.onCityChange();
    },

    deliveryOrderCancel(deliveryOrderId) {
        helper.confirm('', "This action is irreversible!",
            () => {
                $.get(`/delivery/order/${deliveryOrderId}/cancel`).done(res => {
                    //DeliveryOrder.table.api().draw();
                    window.location.reload();
                })
            },
            () => {}
        );
    },

    loadPage() {
        $('.order-delivery').on('click', () => {
            helper.redirectTo(`${helper.getSiteUrl()}/delivery`);
        });
    },



    loadDatatable() {
        let tableId = '#deliveryOrdersTable';
        if (helper.isMobilePhone()) {
            tableId = '#deliveryOrdersTableMobile';
        }
        if (helper.isTablet()) {
            tableId = '#deliveryOrdersTableTablet';
        }

        AgentDeliveryOrder.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            infoCallback: function( settings, start, end, max, total, pre ) {
                return `Showing ${start} to ${end} of ${total} entries`;
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/agent/orders/delivery"),
            serverSide: true,
            columns: [
                {
                    data: "address",
                    defaultContent: "404",
                    title: "Address",
                    name: "delivery_orders.address",
                    visible: 1,
                },
                {
                    data: "office_name",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "office.name",
                    visible: 0,
                },
                {
                    data: "agent_name",
                    defaultContent: "404",
                    title: "Office - Agent",
                    name: "agent.name",
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
                    data: "status",
                    defaultContent: "...",
                    title: "Status",
                    name: "orders.status",
                    searchable: false,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        let html = '';

                        if (d == AgentDeliveryOrder.status_received) {
                            return `<span class="badge badge-pill badge-primary">Received</span>`
                        } else if (d == AgentDeliveryOrder.status_incomplete) {
                            if (r.assigned_to > 0) {
                                return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                            } else {
                                return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                            }
                        } else if (d == AgentDeliveryOrder.status_scheduled) {
                            return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                        } else if (d == AgentDeliveryOrder.status_completed) {
                            return `<span class="badge badge-pill badge-success">Completed</span>`;
                        } else if (d == AgentDeliveryOrder.status_cancelled) {
                            return `<span class="badge badge-pill badge-danger">Cancelled</span>`;
                        }
                    }
                }, {
                    data: "service_date",
                    defaultContent: "404",
                    title: "Service Date",
                    name: "",
                    visible: 1,
                    searchable: false,
                    orderable: false,
                    render(d, t, r) {
                        let s = ''
                        if (r.service_date_type == 1) {
                            s = "Rush Order";
                        } else {
                            s = helper.formatDateUsa(r.service_date);
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
                        let action = '<div class="d-flex justify-content-between">';

                        if (r.status == AgentDeliveryOrder.status_scheduled || r.status == AgentDeliveryOrder.status_completed || r.status == AgentDeliveryOrder.status_cancelled) {
                            return "";
                        }

                        action += `<a class='link mx-1' onclick="window.editDeliveryOrder(${r.id})">
                            <img src="./images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                        </a>`;

                        if (r.status != AgentDeliveryOrder.status_cancelled) {
                            action += `<a class='link text-danger mx-1' onclick="window.deliveryOrderCancel(${r.id})">
                                <img src="./images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                            </a>`;
                            //action += `<br><a class='link text-success font-weight-bold mx-1' onclick="window.markOrderCompleted(${r.id})">Mark Completed</a>`;
                        }

                        action += '</div>';

                        return action;
                    }
                },
                {
                    data: "order_number",
                    defaultContent: "404",
                    title: "Order ID#",
                    name: "delivery_orders.order_number",
                    visible: 1,
                },
            ]
        });
    },

    showDeliveryOrderEntries() {
        $('body').on("change", '#showDeliveryOrderEntries', (event) => {
            const selected = parseInt(event.target.value);
            AgentDeliveryOrder.table.api().context[0]._iDisplayLength = selected;
            AgentDeliveryOrder.table.api().draw();
        });
    },

    deliveryOrderSearchInput() {
        $('body').on("keyup", '#deliveryOrderSearchInput', (event) => {
            AgentDeliveryOrder.table.fnFilter(event.target.value);
        });
    },

    rowCountPickup: 1,
    rowCountDropoff: 1,
    signPanels: {
        pickup: {
            panel: [],
            qty: []
        },
        dropoff: {
            panel: [],
            qty: []
        }
    },
    countNewSigns: 0,

    signPanel(){
        const pickupTmpl = $('#pickupTmpl').html();
        const pickupContainer = $('#pickupContainer');
        const dropoffTmpl = $('#dropoffTmpl').html();
        const dropoffContainer = $('#dropoffContainer');

        $('#addAnotherPickup').on('click', ()=> {
            AgentDeliveryOrder.rowCountPickup++;

            let newTmpl = pickupTmpl.replace(/rowCount/g, AgentDeliveryOrder.rowCountPickup);
            pickupContainer.append(newTmpl);

            const panelHtml = $(`[name="pickup_panel_style[1]"]`).html();
            $(`[name="pickup_panel_style[${AgentDeliveryOrder.rowCountPickup}]"]`).html(panelHtml);
            $(`[name="pickup_panel_style[${AgentDeliveryOrder.rowCountPickup}]"]`)
                .find('option[value="-1"]')
                .remove();
        });

        $('body').on('click', '.add-new-pickup', (e)=> {
            e.stopImmediatePropagation();
            const self = $(e.target);
            let rowNumber = self.data('row');
            sessionStorage.setItem('rowNumber', rowNumber);

            if ( ! AgentDeliveryOrder.officeId) {
                helper.alertError('Please select office or agent.')
                return false;
            }

            //Generate panel as inactive until installer confirm
            helper.confirm(
                'New Sign Panel',
                "Do you have a new panel design that is DIFFERENT from those already stored with us?",
                () => {
                    //AgentDeliveryOrder.countNewSigns++;
                    //helper.alertMsg('Sign added', `${AgentDeliveryOrder.countNewSigns} new sign added to pickup list.`);

                    //Change dropdown style to New Panel
                    rowNumber = sessionStorage.getItem('rowNumber');

                    $(`[name="pickup_panel[${rowNumber}]"]`)
                        .prop('checked', true)
                        .prop('disabled', true);

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

        $('body').on('click', '.remove-pickup', (e)=> {
            const self = $(e.target);

            self.closest('.to-append').remove();
            AgentDeliveryOrder.rowCountPickup--;
        });

        $('body').on('change', '.pickup-checkbox', (e)=> {
            const self = $(e.target);
            const row = self.data('row');

            if (self.is(':checked')) {
                if ( ! AgentDeliveryOrder.officeId) {
                    self.prop('checked', false);
                    helper.alertError('Please select office or agent.')
                    return false;
                }
                $(`[name="pickup_panel_style[${row}]"]`).removeAttr('disabled');
                //$(`[name="pickup_panel_style[${row}]"]`).find('option[value="-1"]').remove();
            } else {
                $(`[name="pickup_panel_style[${row}]"]`).prop('disabled', true);
            }
        });

        $('#addAnotherDropoff').on('click', ()=> {
            AgentDeliveryOrder.rowCountDropoff++;

            let newTmpl = dropoffTmpl.replace(/rowCount/g, AgentDeliveryOrder.rowCountDropoff);
            dropoffContainer.append(newTmpl);

            const panelHtml = $(`[name="dropoff_panel_style[1]"]`).html();
            $(`[name="dropoff_panel_style[${AgentDeliveryOrder.rowCountDropoff}]"]`).html(panelHtml);
        });

        $('body').on('click', '.remove-dropoff', (e)=> {
            const self = $(e.target);

            self.closest('.to-append').remove();
            AgentDeliveryOrder.rowCountDropoff--;
        });

        $('body').on('change', '.dropoff-checkbox', (e)=> {
            const self = $(e.target);
            const row = self.data('row');

            if (self.is(':checked')) {
                if ( ! AgentDeliveryOrder.officeId) {
                    self.prop('checked', false);
                    helper.alertError('Please select office or agent.')
                    return false;
                }
                $(`[name="dropoff_panel_style[${row}]"]`).removeAttr('disabled');
            } else {
                $(`[name="dropoff_panel_style[${row}]"]`).prop('disabled', true);
            }
        });
    },

    loadOfficePanels() {
        let officeId = AgentDeliveryOrder.officeId;
        $.get(helper.getSiteUrl(`/get/office/${officeId}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".panel-list");
            listContainer.empty();
            let html = '<option value="0"></option>';
            res.forEach(panel => {
                let isChecked = '';

                html += `
                    <option value="${panel.id}">${panel.panel_name}</option>
                `;
            })

            listContainer.each(function(i, el) {
                $(el).append(html);
            });
            if ( ! AgentDeliveryOrder.create && $.isEmptyObject(AgentDeliveryOrder.agent)) {
                setTimeout(()=>{
                    if (AgentDeliveryOrder.order.adjustments) {
                        AgentDeliveryOrder.loadSavedAdjustments(AgentDeliveryOrder.order.adjustments);
                    }

                    AgentDeliveryOrder.setSignPanels(AgentDeliveryOrder.order);
                }, 2500)
            }
        });
    },

    loadAgentPanels() {
        let agentId = AgentDeliveryOrder.agentId;
        $.get(helper.getSiteUrl(`/get/agent/${agentId}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".panel-list");
            listContainer.empty();
            let html = '<option value="0"></option>';
            res.forEach(panel => {
                 html += `
                    <option value="${panel.id}">${panel.panel_name}</option>
                `;
            })

            listContainer.each(function(i, el) {
                $(el).append(html);
            });

            if ( ! AgentDeliveryOrder.create) {
                setTimeout(()=>{
                    AgentDeliveryOrder.rowCount = 0;
                    if (AgentDeliveryOrder.order.adjustments) {
                        AgentDeliveryOrder.loadSavedAdjustments(AgentDeliveryOrder.order.adjustments);
                    }

                    AgentDeliveryOrder.setSignPanels(AgentDeliveryOrder.order);
                }, 2500)
            }
        });
    },

    googleKey: global.googleKey,
    initDeliveryMap() {
        window.initDeliveryMap = this.startDeliveryMap;

        const src = `https://maps.googleapis.com/maps/api/js?key=${AgentDeliveryOrder.googleKey}&callback=window.initDeliveryMap&libraries=drawing,geometry,places&v=weekly`;
        $("body").append(window.e("script", { src, googlescript: true }));
    },

    startDeliveryMap() {
        // The location of defaultLocation
        const defaultLocation = {
            lat: 43.633994,
            lng: -116.433707,
        };
        // The map, centered at defaultLocation
        const map = new google.maps.Map(document.getElementById("deliveryOrderMap"),
            {
                zoom: 11,
                center: defaultLocation,
            }
        );
        window.deliveryMap = map;
    },

    marker_position: null,
    searchAddress() {
        // let input = $("#delivery_order_address");
        // if (input.length) {
        //     input.on("keyup", async (e) => {
        //         if (e.key === "Enter") {
        //             AgentDeliveryOrder.marker_position = null
        //             //search input value
        //             let address = input.val();

        //             if (!address) {
        //                 helper.alertError('Please enter property address.');
        //                 return false;
        //             }

        //             //Make sure users enter city and state
        //             const addressParts = address.split(',');
        //             if (! addressParts[1] || ! addressParts[2]) {
        //                 helper.alertMsg('Incorrect Address Formatting', "Please use this format using commas to separate city and state: [Address], [City], [State]");
        //                 return false;
        //             }

        //             //get place
        //             this.findThePlace(address)

        //             if ($(`[name="delivery_location_adjustment"]`).is(':checked')) {
        //                 $(`[name="delivery_location_adjustment"]`).trigger('click');
        //             }

        //             AgentDeliveryOrder.movedNextMonth = false;
        //             AgentDeliveryOrder.updateCalendar(false);
        //         }
        //     }
        //     )
        // }

        let updateMapBtn = $("#updateDeliveryMap");
        if (updateMapBtn.length) {
            updateMapBtn.on("click", async (e) => {
                AgentDeliveryOrder.marker_position = null
                //search input value
                // let input = $(`[name="delivery_order_address"]`);
                let address = `${$("#delivery_order_address").val().replace(/[,]/g, '').trim()}, ${$("#delivery_order_city").val().replace(/[,]/g, '').trim()}, ${$("#delivery_order_state").val().replace(/[,]/g, '').trim()}`;

                if (isEmpty($("#delivery_order_address").val()) || isEmpty($("#delivery_order_city").val()) || isEmpty($("#delivery_order_state").val())) {
                    helper.alertError('Please enter property address.');
                    return false;
                }

                //Make sure users enter city and state
                const addressParts = address.split(',');
                if (! addressParts[1] || ! addressParts[2]) {
                    helper.alertMsg('Incorrect Address Formatting', "Please use this format using commas to separate city and state: [Address], [City], [State]");
                    return false;
                }

                //get place
                this.findThePlace(address)

                if ($(`[name="delivery_location_adjustment"]`).is(':checked')) {
                    $(`[name="delivery_location_adjustment"]`).trigger('click');
                }

                AgentDeliveryOrder.movedNextMonth = false;
                AgentDeliveryOrder.updateCalendar(false);
            });
        }

        let locationAdjustment = $(`[name="delivery_location_adjustment"]`);
        if (locationAdjustment.length) {
            locationAdjustment.on("change", (e) => {
                if (window?.addressMarkerDelivery?.setDraggable) {
                    window.addressMarkerDelivery.setDraggable($(`[name="delivery_location_adjustment"]`).get(0).checked);
                } else {
                    alert("no marker on map");
                    locationAdjustment.prop("checked", false);
                }
            });
        }
        this.onAddressChange();
    },

    addresIsOut: false,
    async findThePlace(query, marker_position = false, zoomIn = true) {
        let service = new google.maps.places.PlacesService(window.deliveryMap);
        let request = { query, fields: ["name", "geometry"] };
        const geocoder = new google.maps.Geocoder();

        // If not calling this function from order edit modal, then execute this piece, which is the normal flow
        service.findPlaceFromQuery(request, async (results, status) => {
            if (results == null) {
                AgentDeliveryOrder.addresIsOut = true;
                helper.alertError('Address not found. Please verify property address is correct and move the marker to the correct property location on the map.')
                console.log("ADDESS NOT FOUND");
                let position = { lat: 43.593469, lng: -116.434029 }
                window.deliveryMap.setCenter(position);
                window.deliveryMap.setZoom(12);

                const map = window.deliveryMap;

                //not found marker
                if (window.addressMarkerDelivery) {
                    window.addressMarkerDelivery.setMap(null);
                }
                let icon = {
                    url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
                    anchor: new google.maps.Point(0, 50), // anchor
                };
                window.addressMarkerDelivery = new google.maps.Marker({ position, map, title: query, icon, draggable: false, });

                //If address not found disable dates in calendar
                AgentDeliveryOrder.disableAllDates();

                window.addressMarkerDelivery.addListener('dragend', (e) => {
                    // Call Maps reverse geocoding to get the new address on dragend, otherwise it will take the same address as previous and marker will get back to its previous position
                    geocoder.geocode({ location: e.latLng }, (results, status) => {
                        // Process if geocoder succeed
                        if (status === "OK") {
                            // Proceed if there's at least on e location
                            if (results[0]) {
                                console.log(e)
                                let lat = e.latLng.lat()
                                let lng = e.latLng.lng()
                                AgentDeliveryOrder.marker_position = { lat, lng };

                                //At the end of drag event it should detect the new location and run query on the new location
                                //The variable $query should have the new address/location instead of the input value

                                AgentDeliveryOrder.findThePlace(results[0].formatted_address, AgentDeliveryOrder.marker_position, false)
                            } else {
                                // Error if there are no locations fot the point marked
                                helper.alertError('No results for this location.');
                            }
                        } else {
                            // Error if geocoder fails
                            helper.alertError('Address not found. Please verify property address is correct and move the marker to the correct property location on the map.');
                        }
                    });

                });

                return;
            }
            ////===============================================
            if (results) {
                let place = results[0];
                let position = marker_position ? marker_position : place.geometry.location;

                // Always save latitude and longitude when address is found
                let lat = place.geometry.location.lat();
                let lng = place.geometry.location.lng();
                AgentDeliveryOrder.marker_position = marker_position ? marker_position : { lat, lng };

                //center place in map
                window.deliveryMap.setCenter(position);

                //create marker
                if (window.addressMarkerDelivery) {
                    window.addressMarkerDelivery.setMap(null);
                }

                const map = window.deliveryMap;

                let icon = {
                    url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
                    anchor: new google.maps.Point(0, 50), // anchor
                };
                window.addressMarkerDelivery = new google.maps.Marker({
                    position,
                    map,
                    title: query,
                    icon,
                    draggable: false,
                });
                window.addressMarkerDelivery.setDraggable($(`[name="delivery_location_adjustment"]`).get(0).checked);

                window.addressMarkerDelivery.addListener('dragend', (e) => {
                    let lat = e.latLng.lat()
                    let lng = e.latLng.lng()
                    AgentDeliveryOrder.marker_position = { lat, lng };

                    //At the end of drag event it should detect the new location and run query on the new location
                    //The variable $query should have the new address/location instead of the input value

                    AgentDeliveryOrder.findThePlace(query, AgentDeliveryOrder.marker_position, false)
                });

                //zoom to marker place
                if (zoomIn) {
                    window.deliveryMap.setZoom(17);
                }

                this.getZoneFee(position);
            }
            ////===============================================
        });
    },

    loadAddressOnMap(address, position) {
        window.deliveryMap.setCenter(position);
        const map = window.deliveryMap;

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

        window.deliveryMap.setZoom(17);
    },

    _currentZone: null,
    zones_count: 0,
    async getZoneFee(place_position) {
        let zones = await this.getZones();
        AgentDeliveryOrder.zones_count = zones.length;
        let delay = 100;
        let counter = 0;
        proxy.zones = [];
        zones.forEach(zone => {
            counter++;
            setTimeout(() => { //NEED TO DELAY TO AVOID CROSSING 10 REQUESTS PER SECOND
                let paths = zone.points;
                let googleZone = new google.maps.Polygon({ paths });
                if (zone) {
                    const inZone = google.maps.geometry.poly.containsLocation(place_position, googleZone);
                    if (inZone) {
                        proxy.zones.push(zone)
                    } else {
                        proxy.zones.push(null)
                    }
                }
            }, delay * counter);
        })
    },

    async getZones() {
        let zones = await $.get(helper.getSiteUrl(`/get/zones/orderby/zone_fee/desc`));
        await zones.map((zone) => {
            zone.points = helper.parseZonePoints(zone.points);
            return zone;
        });

        return zones;
    },

    setPropertyInfo(order) {
        $('#deliveryOrderAddress').val(order.address);

        //Map
        AgentDeliveryOrder.loadAddressOnMap(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)});

        $('#deliveryOrderPropertyType').find(`option[value="${order.property_type}"]`).prop('selected', true);
    },

    async getDeliveryZone(zoneId) {
        const zone = await $.post(helper.getSiteUrl(`/delivery/get/zone`), {zoneId: zoneId});

        return zone;
    },

    disableAllDates() {
        $("#deliveryOrderDatePicker").datepicker("destroy");
        $("#deliveryOrderDatePicker").datepicker({
            beforeShowDay: function (date) { return [false] }
        })
    },

    movedNextMonth: false,
    updateCalendar(savedDate) {
        $("#deliveryOrderDatePicker").datepicker("destroy");
        $("#deliveryOrderDatePicker").datepicker({
            onSelect: function (dateText) {
                //console.log(dateText)
                $(`[name="delivery_order_custom_desired_date"]`).val(dateText);
                return AgentDeliveryOrder.updateCalendar(helper.parseUSDate(dateText));
            },
            beforeShowDay: function (date) {
                let dateString = helper.getDateStringUsa(date);

                if (AgentDeliveryOrder.holidays.includes(dateString)) {
                    return [false];
                }

                let serviceDate = $.datepicker.formatDate('yy-mm-dd', new Date(date));
                let ordersCount = AgentDeliveryOrder.countOrders[serviceDate];
                if (typeof ordersCount !== 'undefined') {
                    if (ordersCount >= AgentDeliveryOrder.dailyOrderCap) {
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
                if (AgentDeliveryOrder.zone) {
                    let zone = AgentDeliveryOrder.zone;
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
            $("#deliveryOrderDatePicker").datepicker('setDate', usDate);
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !AgentDeliveryOrder.movedNextMonth && AgentDeliveryOrder.create) {
                AgentDeliveryOrder.movedNextMonth = true;
                setTimeout(() => {
                    $('#deliveryOrderDatePicker .ui-datepicker-next').trigger("click");
                }, 3000);
            }
        }
    },

    setDate(deliveryOrder) {
        let datePicker = $("#deliveryOrderDatePicker");
        if (deliveryOrder.service_date_type == 1) {
            $(`[name="delivery_order_desired_date"][value="asap"]`).prop('checked', true);
            datePicker.addClass("d-none");
        } else {
            $(`[name="delivery_order_desired_date"][value="custom_date"]`).prop('checked', true);
            $(`[name="delivery_order_desired_date"][value="custom_date"]`).trigger('change')
            //Need to review this part. Why does it need setTimeout?
            setTimeout(()=>{
                datePicker.removeClass("d-none");
                //datePicker.datepicker("setDate", d);
                $(`[name="delivery_order_custom_desired_date"]`).val(deliveryOrder.service_date);

                AgentDeliveryOrder.updateCalendar(helper.parseDate(deliveryOrder.service_date));
            }, 1000);
        }
    },

    setSignPanels(deliveryOrder) {
        const panels = deliveryOrder.panels;
        const pickupTmpl = $('#pickupTmpl').html();
        const pickupContainer = $('#pickupContainer');
        const dropoffTmpl = $('#dropoffTmpl').html();
        const dropoffContainer = $('#dropoffContainer');

        AgentDeliveryOrder.resetSignPanels();
        // console.log(panels)
        if (panels) {
            $.each(panels, (index, panel)=> {
                let i = AgentDeliveryOrder.rowCountPickup;
                let c = AgentDeliveryOrder.rowCountDropoff;
                if (panel.pickup_delivery == 0) {
                    if (i == 1) {
                        $(`[name="pickup_panel[${i}]"]`).prop('checked', true);
                        $(`[name="pickup_panel_qty[${i}]"]`).val(panel.quantity);
                        $(`[name="pickup_panel_style[${i}]"]`).val(panel.panel_id).prop('disabled', false);
                        if (panel.panel_id == -1) {
                            $(`[name="pickup_panel_style[${i}]"]`).text('New Panel');
                        }
                    } else {
                        let newTmpl = pickupTmpl.replace(/rowCount/g, i);
                        pickupContainer.append(newTmpl);

                        if (panel.existing_new == 0) {
                            const panelHtml = $(`[name="pickup_panel_style[1]"]`).html();
                            $(`[name="pickup_panel_style[${i}]"]`).html(panelHtml);
                            $(`[name="pickup_panel[${i}]"]`).prop('checked', true);
                            $(`[name="pickup_panel_qty[${i}]"]`).val(panel.quantity);
                            $(`[name="pickup_panel_style[${i}]"]`).val(panel.panel_id).prop('disabled', false);
                        } else {
                            $(`[name="pickup_panel_style[${i}]"]`)
                                .html('<option value="-1" selected>New Panel</option>')
                                .prop('disabled', true);
                            $(`[name="pickup_panel[${i}]"]`).prop('checked', true).prop('disabled', true);
                            $(`[name="pickup_panel_qty[${i}]"]`).val(1).prop('disabled', true);
                            $(`#addNewPickup${i}`).hide();
                        }
                    }
                    AgentDeliveryOrder.rowCountPickup++;
                } else {
                    if (c == 1) {
                        $(`[name="dropoff_panel[${c}]"]`).prop('checked', true);
                        $(`[name="dropoff_panel_qty[${c}]"]`).val(panel.quantity);
                        $(`[name="dropoff_panel_style[${c}]"]`).val(panel.panel_id).prop('disabled', false);
                    } else {
                        let newTmpl = dropoffTmpl.replace(/rowCount/g, c);
                        dropoffContainer.append(newTmpl);

                        const panelHtml = $(`[name="dropoff_panel_style[1]"]`).html();
                        $(`[name="dropoff_panel_style[${c}]"]`).html(panelHtml);
                        $(`[name="dropoff_panel[${c}]"]`).prop('checked', true);
                        $(`[name="dropoff_panel_qty[${c}]"]`).val(panel.quantity);
                        $(`[name="dropoff_panel_style[${c}]"]`).val(panel.panel_id).prop('disabled', false);
                    }
                    AgentDeliveryOrder.rowCountDropoff++;
                }
            });
        }
    },

    resetForm() {
        this.disableAllDates()
        $('[name="delivery_agent"]').val('');
        $(`[name="delivery_order_comment"]`).val('');
        $(`[name="delivery_order_address"]`).val('');
        this.startDeliveryMap();

        $("[delivery-adjustments]").html("$0.00");

        AgentDeliveryOrder.rowCount = 0;
        AgentDeliveryOrder.countNewSigns = 0;
        AgentDeliveryOrder.rowCountPickup = 1;
        AgentDeliveryOrder.rowCountDropoff = 1;
        AgentDeliveryOrder.agent = {};

        this.resetTotals();
        this.setRushFee(0);
        this.setDeliveryFee();

        $(`[name="delivery_order_desired_date"][value="custom_date"]`).trigger('click');

        $(".list-container-accessories").empty();

        this.resetPricingAdjustment();
    },

    createDeliveryOrder() {
        AgentDeliveryOrder.create = true;

        AgentDeliveryOrder.resetForm();

        AgentDeliveryOrder.resetSignPanels();

        AgentDeliveryOrder.setFooter();

        //Load panels for office
        AgentDeliveryOrder.loadOfficePanels();

        helper.openModal('deliveryOrderModal');
    },

    setFooter() {
        if (AgentDeliveryOrder.create) {
            $('#submitDeliveryOrder').html('<strong class="text-white">SUBMIT DELIVERY</strong>').prop('disabled', false);
        } else {
            $('#submitDeliveryOrder').html('<strong class="text-white">UPDATE ORDER</strong>').prop('disabled', false);
        }
    },

    setComment(deliveryOrder) {
        $(`[name="delivery_order_comment"]`).val(deliveryOrder.comment);
    },

    setOfficeAndAgent(deliveryOrder) {
        if (deliveryOrder.agent_id) {
            $('[name="delivery_agent"]').val(deliveryOrder.agent_id);
            this.loadAgentPanels();
        } else {
            this.loadOfficePanels();
        }
    },

    setPropertyInfo(deliveryOrder) {
        $('[name="delivery_order_address"]').val(deliveryOrder.address.split(',')[0]);
        $('[name="delivery_order_city"]').val(deliveryOrder.address.split(',')[1]);
        //Set selected state
        $(`#delivery_order_state option[value=${deliveryOrder.address.split(',')[2]?.trimStart().substr(0, 2)}]`).attr('selected', 'selected');

        //Map
        AgentDeliveryOrder.loadAddressOnMap(deliveryOrder.address, {lat: Number(deliveryOrder.latitude), lng: Number(deliveryOrder.longitude)});
    },

    loadAddressOnMap(address, position) {
        window.deliveryMap.setCenter(position);

        const map = window.deliveryMap;

        let icon = {
            url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
            anchor: new google.maps.Point(0, 50)
        };
        window.addressMarkerDelivery = new google.maps.Marker({
            position,
            map,
            title: address,
            icon,
            draggable: false,
        });

        window.deliveryMap.setZoom(17);

        AgentDeliveryOrder.marker_position = position;
    },

    editDeliveryOrder(orderId) {
        AgentDeliveryOrder.resetForm();

        $("#loader_image").modal('show');
        $.get('/delivery/get/order/' + orderId).done(async deliveryOrder => {
            AgentDeliveryOrder.create = false;

            AgentDeliveryOrder.order = deliveryOrder;

            AgentDeliveryOrder.delivery_order_id = deliveryOrder.id;

            AgentDeliveryOrder.zone = await AgentDeliveryOrder.getDeliveryZone(deliveryOrder.zone_id);
            AgentDeliveryOrder.zoneId = deliveryOrder.zone_id;
            AgentDeliveryOrder.settings = await helper.getZoneSettings();

            AgentDeliveryOrder.setOfficeAndAgent(deliveryOrder);
            AgentDeliveryOrder.setPropertyInfo(deliveryOrder);
            AgentDeliveryOrder.setDate(deliveryOrder, false);
            AgentDeliveryOrder.setComment(deliveryOrder);
            AgentDeliveryOrder.setFooter();
            AgentDeliveryOrder.setRushFee(deliveryOrder.rush_fee);

            if (AgentDeliveryOrder.zone.data && AgentDeliveryOrder.settings) {
                const zone = AgentDeliveryOrder.zone.data;
                const settings = AgentDeliveryOrder.settings;

                const zoneFee = parseFloat(zone.zone_fee) * settings.delivery / 100;
                $(`[name="delivery_order_zone_fee"]`).val(zoneFee);
                $('[delivery-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                $(`[name="delivery_order_zone_fee"]`).trigger('change');
            }

            /*AgentDeliveryOrder.rowCount = 0;
            if (deliveryOrder.adjustments) {
                AgentDeliveryOrder.loadSavedAdjustments(deliveryOrder.adjustments);
            }*/

            setTimeout(()=>{
                $("#loader_image").modal('hide');

                $(".modal").css({ "overflow-y": "scroll" });
                $('#deliveryOrderModal').modal();
            }, 3000)
        });
    },

    onDesiredDateChange() {
        let dates_input = document.getAll(`[name="delivery_order_desired_date"]`);
        let datepicker = $("#deliveryOrderDatePicker");
        dates_input.forEach((d) => {
            d.onchange = (e) => {
                let type = e.target.value;
                $(`[name="delivery_order_desired_date"]`).removeAttr('checked')
                $(e.target).prop('checked', true);
                if (type === "custom_date") {
                    AgentDeliveryOrder.setRushFee(0)
                    datepicker.removeClass("d-none");
                    $('#rushFeeDelivery').addClass('d-none');
                } else {
                    helper.openModal('deliveryRushOrderModal');
                    datepicker.addClass("d-none");
                }
            };
        });

        $("[delivery-rush-fee-decline-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            this.setRushFee(0)
            $(`[name="delivery_order_desired_date"][value="custom_date"]`).trigger('click');
        });

        $("[delivery-rush-fee-accept-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            const rush_fee = $('#rushOrder').attr('delivery-rush-order-fee');
            this.setRushFee(rush_fee)
        });
    },

    setRushFee(value) {
        let rush_fee_input = $(`input[name="delivery_order_rush_fee"]`);
        rush_fee_input.val(value)
        rush_fee_input.trigger('change');

        if (value > 0) {
            $('#rushFeeDelivery').removeClass('d-none');
        }
    },

    totalFee() {
        $(`[name="delivery_order_rush_fee"]`).on("change", (e) => {
            const total = parseFloat($(`[name="delivery_order_fee"]`).val()) + AgentDeliveryOrder.getTotalFees() + parseFloat(AgentDeliveryOrder.totalAdjusted);
            $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

            AgentDeliveryOrder.total = total;
        });
        $(`[name="delivery_order_fee"]`).on("change", (e) => {
            const total = parseFloat($(`[name="delivery_order_fee"]`).val()) + AgentDeliveryOrder.getTotalFees() + parseFloat(AgentDeliveryOrder.totalAdjusted);
            $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

            AgentDeliveryOrder.total = total;
        });
        $(`[name="delivery_order_zone_fee"]`).on("change", (e) => {
            const total = parseFloat($(`[name="delivery_order_fee"]`).val()) + AgentDeliveryOrder.getTotalFees() + parseFloat(AgentDeliveryOrder.totalAdjusted);
            $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

            AgentDeliveryOrder.total = total;
        });
        $(`[name="delivery_options_post[]"]`).on("change", (e) => {
            const self = $(e.target);

            if (self.is(':checked')) {

                let totalPost = 0;
                $(`[name="delivery_options_post[]"]`).each((i, el)=> {
                    if (el.checked) {
                        totalPost = totalPost + parseFloat(el.value);
                    }
                })

                AgentDeliveryOrder.totalPost = totalPost;

                const total = parseFloat(AgentDeliveryOrder.totalPanel) + AgentDeliveryOrder.getTotalFees() + parseFloat(AgentDeliveryOrder.totalAdjusted);
                $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

                const deliveryFee = parseFloat(AgentDeliveryOrder.totalPanel);
                $(`[delivery-fee]`).html(`$${deliveryFee.toFixed(2)}`);

                AgentDeliveryOrder.total = total;
            } else {
                AgentDeliveryOrder.totalPost = AgentDeliveryOrder.totalPost - parseFloat(self.val());

                const total = parseFloat(AgentDeliveryOrder.totalPanel) + AgentDeliveryOrder.getTotalFees() + parseFloat(AgentDeliveryOrder.totalAdjusted);
                $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

                const deliveryFee = parseFloat(AgentDeliveryOrder.totalPanel);
                $(`[delivery-fee]`).html(`$${deliveryFee.toFixed(2)}`);

                AgentDeliveryOrder.total = total;
            }
        });
    },

    getTotalFees() {
        const rush_fee = $(`[name="delivery_order_rush_fee"]`).val();
        const zone_fee = $(`[name="delivery_order_zone_fee"]`).val();

        return parseFloat(zone_fee) + parseFloat(rush_fee);
    },

    onCommentChange() {
        let textarea = $(`[name="delivery_order_comment"]`);
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

    setDeliveryFee() {
        const deliveryFee = parseFloat($(`[name="delivery_order_fee"]`).val());
        $(`[delivery-fee]`).html(`$${deliveryFee.toFixed(2)}`);

        $(`[name="delivery_order_fee"]`).trigger('change');
    },

    resetTotals() {
        AgentDeliveryOrder.totalAdjusted = 0;
    },

    onSubmitForm() {
        $("#deliveryOrderForm").on("submit", (e) => {
            e.preventDefault();
            let form = $(e.target);

            let fd = new FormData();
            //Date
            fd.append("delivery_order_desired_date", form.find(`[name="delivery_order_desired_date"]:checked`).val());
            fd.append("delivery_order_custom_desired_date", form.find(`[name="delivery_order_custom_desired_date"]`).val());
            //Comment
            fd.append("delivery_order_comment", form.find(`[name="delivery_order_comment"]`).val());
            //Create/Edit action
            fd.append("create_order", AgentDeliveryOrder.create);
            //Order Id
            fd.append("delivery_order_id", AgentDeliveryOrder.delivery_order_id);
            //Files
            //Fees and total
            fd.append("delivery_order_rush_fee", form.find(`[name="delivery_order_rush_fee"]`).val());
            fd.append("delivery_order_fee", form.find(`[name="delivery_order_fee"]`).val());
            fd.append("delivery_order_zone_fee", form.find(`[name="delivery_order_zone_fee"]`).val());
            fd.append('total', AgentDeliveryOrder.total);
            //Zone and coordinates
            fd.append("delivery_order_address", `${$("#delivery_order_address").val().replace(/[,]/g, '').trim()}, ${$("#delivery_order_city").val().replace(/[,]/g, '').trim()}, ${$("#delivery_order_state").val().replace(/[,]/g, '').trim()}`);
            fd.append("delivery_marker_position", JSON.stringify(AgentDeliveryOrder.marker_position));
            fd.append("zone_id", AgentDeliveryOrder.zoneId);
            //Office and agent
            fd.append("office_id", AgentDeliveryOrder.officeId);
            fd.append("agent_id", AgentDeliveryOrder.agentId);

            //New signs
            AgentDeliveryOrder.countNewSigns = $('.panel-list').children('option[value="-1"]').length;
            fd.append("countNewSigns", AgentDeliveryOrder.countNewSigns);

            //Panel pickup/dropoff
            AgentDeliveryOrder.signPanels = {
                pickup: {
                    panel: [],
                    qty: []
                },
                dropoff: {
                    panel: [],
                    qty: []
                }
            };
            for (let i=1; i <= AgentDeliveryOrder.rowCountPickup; i++) {
                if ($(`[name="pickup_panel[${i}]"]`).is(':checked')) {
                    let panelId = $(`[name="pickup_panel_style[${i}]"]`).val();
                    let qty = $(`[name="pickup_panel_qty[${i}]"]`).val();

                    if (panelId > 0) {
                        if (!qty) {
                            helper.alertError('Please enter quantity.')
                            return false;
                        }

                        AgentDeliveryOrder.signPanels['pickup']['panel'][i] = panelId;
                        AgentDeliveryOrder.signPanels['pickup']['qty'][i] = qty;
                    } else {
                        if (panelId != -1) {
                            helper.alertError('Please select a sign panel.')
                            return false;
                        }
                    }
                }
            }
            for (let i=1; i <= AgentDeliveryOrder.rowCountDropoff; i++) {
                if ($(`[name="dropoff_panel[${i}]"]`).is(':checked')) {
                    let panelId = $(`[name="dropoff_panel_style[${i}]"]`).val();
                    let qty = $(`[name="dropoff_panel_qty[${i}]"]`).val();

                    if (panelId > 0) {
                        if (!qty) {
                            helper.alertError('Please enter quantity.')
                            return false;
                        }

                        AgentDeliveryOrder.signPanels['dropoff']['panel'][i] = panelId;
                        AgentDeliveryOrder.signPanels['dropoff']['qty'][i] = qty;
                    } else {
                        if (panelId != -1) {
                            helper.alertError('Please select a sign panel.')
                            return false;
                        }
                    }
                }
            }

            /*console.log(AgentDeliveryOrder.countNewSigns)
            return false;*/
            fd.append("signPanels", JSON.stringify(AgentDeliveryOrder.signPanels));

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

                if (res.deliveryOrder.editOrder && !res.deliveryOrder.needPayment) {
                    window.location.reload();
                }

                if (res.deliveryOrder.needPayment) {
                    $(`[delivery-payment-total-amount]`).html(parseFloat(res.deliveryOrder.total).toFixed(2));
                    $(`[delivery-payment-card-name]`).val(res.billing.name);

                    $(`[delivery-billing-name]`).val(res.billing.name);
                    $(`[delivery-billing-address]`).val(res.billing.address);
                    $(`[delivery-billing-city]`).val(res.billing.city);
                    $(`[delivery-billing-state]`).val(res.billing.state);
                    $(`[delivery-billing-zip]`).val(res.billing.zipcode);

                    if (res.deliveryOrder.agent.user.authorizenet_profile_id) {
                        $('#delivery_use_card_profile').prop('checked', true);
                        $('#delivery_card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#delivery_use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        Payment.loadCards($('#delivery_card_profile_select'), res.deliveryOrder.agent.user.id);

                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#delivery_use_another_card').prop('checked', true);
                        $('#delivery_use_card_profile').prop('checked', false);
                        $('#delivery_card_profile_select').prop('disabled', true);
                    }

                    //Load any office card visible to agent
                    Payment.loadOfficeCardsVisibleToAgent(
                        $('#delivery_card_profile_select'),
                        res.deliveryOrder.office.user.id,
                        () => {
                            $('#delivery_use_card_profile').prop('checked', true);
                            $('#delivery_card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#delivery_use_another_card').prop('checked', false);
                        }
                    );

                    let deliveryOrderModal = $("#deliveryOrderModal");
                    if (deliveryOrderModal.length) deliveryOrderModal.modal('hide')

                    let paymentModal = $("#deliveryPaymentModal");
                    if (paymentModal.length) {
                        paymentModal.find(`[name="delivery_order_id"]`).val(res.deliveryOrder.id);

                        helper.hideLoader('deliveryPaymentModal');
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

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = AgentDeliveryOrder.rowCount;

        for (let i=1; i <= totalRows; i++) {
            charge = $(`[name="delivery_price_adjustment_charge[${i}]"]`).val();
            discount = $(`[name="delivery_price_adjustment_discount[${i}]"]`).val();

            if (charge > 0) {
                totalAdjustments = parseFloat(totalAdjustments) + parseFloat(charge);
            }

            if (discount > 0) {
                totalAdjustments = parseFloat(totalAdjustments) - parseFloat(discount);
            }
        }

        AgentDeliveryOrder.totalAdjusted = totalAdjustments;

        if (AgentDeliveryOrder.totalAdjusted < 0) {
            $('[delivery-adjustments]').html(`<span class="text-danger">- $${AgentDeliveryOrder.totalAdjusted*(-1)}</span>`);
        } else {
            $('[delivery-adjustments]').html(`$${AgentDeliveryOrder.totalAdjusted}`);
        }

        $(`[name="delivery_order_zone_fee"]`).trigger('change');
    },

    loadSavedAdjustments(adjustments) {
        const rowTmpl = $('#rowTmplDelivery').html();
        const rowContainer = $('#rowContainerDelivery');
        let totalAdjustments = 0;
        this.resetPricingAdjustment();

        rowContainer.empty();
        $.each(adjustments, (i, row)=> {
            AgentDeliveryOrder.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, AgentDeliveryOrder.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="delivery_price_adjustment_description[${AgentDeliveryOrder.rowCount}]"]`).val(row.description);
            $(`[name="delivery_price_adjustment_charge[${AgentDeliveryOrder.rowCount}]"]`).val(row.charge);
            $(`[name="delivery_price_adjustment_discount[${AgentDeliveryOrder.rowCount}]"]`).val(row.discount);

            AgentDeliveryOrder.pricingAdjustments['description'][i] = row.description;
            AgentDeliveryOrder.pricingAdjustments['charge'][i] = row.charge;
            AgentDeliveryOrder.pricingAdjustments['discount'][i] = row.discount;

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            AgentDeliveryOrder.totalAdjusted = totalAdjustments;

            if (AgentDeliveryOrder.totalAdjusted < 0) {
                $('[delivery-adjustments]').html(`<span class="text-danger">- $${AgentDeliveryOrder.totalAdjusted*(-1)}</span>`);
            } else {
                $('[delivery-adjustments]').html(`$${AgentDeliveryOrder.totalAdjusted}`);
            }
        });

        this.calculateAdjustments();
    },

    resetPricingAdjustment() {
        AgentDeliveryOrder.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
    },

    resetSignPanels() {
        $(`[name="pickup_panel[1]"]`).prop('checked', false).prop('disabled', false);
        $(`[name="pickup_panel_qty[1]"]`).val('').prop('disabled', false);
        $(`[name="pickup_panel_style[1]"]`).val(0).prop('disabled', true);

        $('#pickupContainer').find('.to-append').remove();

        $('.add-new-pickup').show();
    },

    onAddressChange() {
        $(`[name="delivery_order_address"]`).on("change", (event) => {
            let input = event.target;
            $(input).val($(input).val().replace(/[,]/g, '').trim());
            this.startDeliveryMap();
            this.disableAllDates();
        });
    },

    onCityChange() {
        $("#delivery_order_city").on("change", (event) => {
            let input = event.target;
            $(input).val($(input).val().replace(/[,]/g, '').trim());
        });
    },

}

$(() => {
    AgentDeliveryOrder.init();
});
