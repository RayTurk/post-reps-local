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
    if (_obj_.zones.length == DeliveryOrder.zones_count) {
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
            DeliveryOrder.addresIsOut = false;
            $.get(helper.getSiteUrl(`/get/zone/settings`)).done(settings => {
                DeliveryOrder.settings = settings;

                DeliveryOrder._currentZone = lowest_zone_fee;
                DeliveryOrder.zone = lowest_zone_fee;
                DeliveryOrder.zoneSettings = settings;

                DeliveryOrder.updateCalendar(false);

                let fee = parseFloat(lowest_zone_fee.zone_fee || 0) * (settings.delivery / 100);

                if (DeliveryOrder.ignoreZoneFee) {
                    $(`[delivery-zone-fee]`).html(`$0`);
                    $(`[name="delivery_order_zone_fee"]`).val(0);
                } else {
                    $(`[delivery-zone-fee]`).html(`$${fee.toFixed(2)}`);
                    $(`[name="delivery_order_zone_fee"]`).val(fee);
                }

                $(`[name="delivery_order_zone_fee"]`).trigger("change");

                DeliveryOrder.zoneId = lowest_zone_fee.id;
            })
        } else {
            helper.alertMsg('Address Out of Service Area', 'This address appears to be outside the service area. Please verify that the address and pin location are correct.');
            DeliveryOrder._currentZone = null;
            DeliveryOrder.disableAllDates();
            DeliveryOrder.addresIsOut = true;
            $(`[delivery-zone-fee]`).html("00.00");
            $(`[name="delivery_order_zone_fee"]`).val("00.00");
            $(`[name="delivery_order_zone_fee"]`).get(0).dispatchEvent(new Event("change"));
            //$("[googlescript]").remove();
            helper.hideLoader('');
        }

    }
});

const DeliveryOrder = {
    order: {},
    zone: {},
    settings: {},
    total:0,
    totalPanel:0,
    create: true,
    payment_method_office_pay: 3,
    status_received: 0,
    status_incomplete: 1,
    status_scheduled: 2,
    status_completed: 3,
    status_cancelled: 4,
    delivery_order_id: 0,
    zoneId: null,
    ignoreZoneFee: false,
    zoneSettings: {},
    agent:{},
    agentChangeCount: 0,

    init() {
        this.loadPage();

        this.loadDatatable()
        this.showDeliveryOrderEntries();
        this.deliveryOrderSearchInput();

        this.onOfficeChange();
        this.onAgentChange();

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
            DeliveryOrder.holidays = holidays;
        });

        this.signPanel();

        this.onCommentChange();

        this.onSubmitForm();

        Payment.init()
        helper.cardNumberInput('.cc-number-input');

        this.deleteOrders();

        helper.inputNumber('.qty-box');

        this.pricingAdjustment();

        window.markDeliveryOrderCompleted = this.markDeliveryOrderCompleted;

        this.onCityChange();
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

        DeliveryOrder.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/delivery/orders/datatable"),
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
                    defaultContent: "",
                    title: "Status",
                    name: "status",
                    searchable: false,
                    orderable: false,
                    visible: 1,
                    render(d, t, r) {
                        if (d == DeliveryOrder.status_received) {
                            return `<span class="badge badge-pill badge-primary">Received</span>`
                        } else if (d == DeliveryOrder.status_incomplete) {
                            if (r.assigned_to > 0) {
                                return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                            } else {
                                return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                            }
                        } else if (d == DeliveryOrder.status_scheduled) {
                            return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                        } else if (d == DeliveryOrder.status_completed) {
                            return `<span class="badge badge-pill badge-success">Completed</span>`;
                        } else if (d == DeliveryOrder.status_cancelled) {
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

                        if (r.status != DeliveryOrder.status_cancelled) {
                            action += `<a class='link text-danger mx-1' onclick="window.deliveryOrderCancel(${r.id})">
                                <img src="./images/Cancel_Icon.png" title="Cancel" alt="Cancel" class="width-px-40">
                            </a>`;
                        }

                        if ((r.status == DeliveryOrder.status_scheduled || r.status == DeliveryOrder.status_completed) && r.status != DeliveryOrder.status_cancelled) {
                            action += '</div>';
                            return action;
                        }

                        action += `<a class='link mx-1' onclick="window.editDeliveryOrder(${r.id})">
                            <img src="./images/Edit_Icon.png" title="Edit" alt="Edit" class="width-px-40">
                        </a>`;

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
            DeliveryOrder.table.api().context[0]._iDisplayLength = selected;
            DeliveryOrder.table.api().draw();
        });
    },

    deliveryOrderSearchInput() {
        $('body').on("keyup", '#deliveryOrderSearchInput', (event) => {
            DeliveryOrder.table.fnFilter(event.target.value);
        });
    },

    onOfficeChange() {
        let input = $(`[name="delivery_office"]`);
        input.on("change", async (e) => {
            let value = e.target.value;

            let agents = await this.getAgent(value);
            if (!Array.isArray(agents)) {
                agents = Object.values(agents);
            }

            let agentsInput = $(`[name="delivery_agent"]`);
            agentsInput.html("");
            agentsInput.append('<option value="">Select Agent</option>');
            let options = '';

            let agentId = 0;
            if (DeliveryOrder.agent) {
                agentId = DeliveryOrder.agent.id;
            }
            agents.forEach((agent) => {
                options += `<option value="${agent.id}" ${agentId == agent.id ? 'selected' : ''}>
                    ${agent.user.lastNameFirstName}
                </option>`;

            });

            agentsInput.append(options);

            this.loadOfficePanels();

            //console.log(DeliveryOrder.agent)
            if ( ! $.isEmptyObject(DeliveryOrder.agent)) {
                $(`[name="delivery_agent"]`).trigger('change');
            }
        });
    },

    onAgentChange() {
        let input = $(`[name="delivery_agent"]`);
        input.on("change", async (e) => {
            //If creating order then increment agentChangeCount to force reset
            if (DeliveryOrder.create) {
                DeliveryOrder.agentChangeCount++;
            }

            if (DeliveryOrder.agentChangeCount > 0) {
                DeliveryOrder.resetFormKeepOfficeAgent();
            }

            if (e.target.value > 0) {
                this.loadAgentPanels();
            } /*else {
                this.loadOfficePanels();
            }*/
        });
    },

    async getAgent(office) {
        return await $.get(helper.getSiteUrl(`/office/${office}/agents/order/by/name/json`));
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
            DeliveryOrder.rowCountPickup++;

            let newTmpl = pickupTmpl.replace(/rowCount/g, DeliveryOrder.rowCountPickup);
            pickupContainer.append(newTmpl);

            const panelHtml = $(`[name="pickup_panel_style[1]"]`).html();
            $(`[name="pickup_panel_style[${DeliveryOrder.rowCountPickup}]"]`).html(panelHtml);
            $(`[name="pickup_panel_style[${DeliveryOrder.rowCountPickup}]"]`)
                .find('option[value="-1"]')
                .remove();
        });

        $('body').on('click', '.add-new-pickup', (e)=> {
            e.stopImmediatePropagation();
            const self = $(e.target);
            let rowNumber = self.data('row');
            sessionStorage.setItem('rowNumber', rowNumber);

            if ( ! $(`[name="delivery_office"]`).val()) {
                helper.alertError('Please select office or agent.')
                return false;
            }

            //Generate panel as inactive until installer confirm
            helper.confirm(
                'New Sign Panel',
                "Do you have a new panel design that is DIFFERENT from those already stored with us?",
                () => {
                    //DeliveryOrder.countNewSigns++;
                    //helper.alertMsg('Sign added', `${DeliveryOrder.countNewSigns} new sign added to pickup list.`);

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
            DeliveryOrder.rowCountPickup--;
        });

        $('body').on('change', '.pickup-checkbox', (e)=> {
            const self = $(e.target);
            const row = self.data('row');

            if (self.is(':checked')) {
                if ( ! $(`[name="delivery_office"]`).val()) {
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
            DeliveryOrder.rowCountDropoff++;

            let newTmpl = dropoffTmpl.replace(/rowCount/g, DeliveryOrder.rowCountDropoff);
            dropoffContainer.append(newTmpl);

            const panelHtml = $(`[name="dropoff_panel_style[1]"]`).html();
            $(`[name="dropoff_panel_style[${DeliveryOrder.rowCountDropoff}]"]`).html(panelHtml);
        });

        $('body').on('click', '.remove-dropoff', (e)=> {
            const self = $(e.target);

            self.closest('.to-append').remove();
            DeliveryOrder.rowCountDropoff--;
        });

        $('body').on('change', '.dropoff-checkbox', (e)=> {
            const self = $(e.target);
            const row = self.data('row');

            if (self.is(':checked')) {
                if ( ! $(`[name="delivery_office"]`).val()) {
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
        let officeId = $(`[name="delivery_office"]`).val();
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
            if ( ! DeliveryOrder.create && $.isEmptyObject(DeliveryOrder.agent)) {
                setTimeout(()=>{
                    DeliveryOrder.setSignPanels(DeliveryOrder.order);

                    DeliveryOrder.rowCount = 0;
                    if (DeliveryOrder.order.adjustments) {
                        DeliveryOrder.loadSavedAdjustments(DeliveryOrder.order.adjustments);
                    }
                }, 2500)
            }
        });
    },

    loadAgentPanels() {
        let agentId = $(`[name="delivery_agent"]`).val();
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

            if ( ! DeliveryOrder.create && DeliveryOrder.agentChangeCount == 0) {
                setTimeout(()=>{
                    DeliveryOrder.setSignPanels(DeliveryOrder.order);

                    DeliveryOrder.rowCount = 0;
                    if (DeliveryOrder.order.adjustments) {
                        DeliveryOrder.loadSavedAdjustments(DeliveryOrder.order.adjustments);
                    }

                    DeliveryOrder.agentChangeCount++;
                }, 2500);
            }
        });
    },

    googleKey: global.googleKey,
    initDeliveryMap() {
        window.initDeliveryMap = this.startDeliveryMap;

        const src = `https://maps.googleapis.com/maps/api/js?key=${DeliveryOrder.googleKey}&callback=window.initDeliveryMap&libraries=drawing,geometry,places&v=weekly`;
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
        //             DeliveryOrder.marker_position = null
        //             //search input value
        //             let address = e.target.value;

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

        //             DeliveryOrder.movedNextMonth = false;
        //             DeliveryOrder.updateCalendar(false);
        //         }
        //     }
        //     )
        // }

        let updateMapBtn = $("#updateDeliveryMap");
        if (updateMapBtn.length) {
            updateMapBtn.on("click", async (e) => {
                DeliveryOrder.marker_position = null
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

                DeliveryOrder.movedNextMonth = false;
                DeliveryOrder.updateCalendar(false);
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
                DeliveryOrder.addresIsOut = true;
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
                DeliveryOrder.disableAllDates();

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
                                DeliveryOrder.marker_position = { lat, lng };

                                //At the end of drag event it should detect the new location and run query on the new location
                                //The variable $query should have the new address/location instead of the input value

                                DeliveryOrder.findThePlace(results[0].formatted_address, DeliveryOrder.marker_position, false)
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
                DeliveryOrder.marker_position = marker_position ? marker_position : { lat, lng };

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
                    DeliveryOrder.marker_position = { lat, lng };

                    //At the end of drag event it should detect the new location and run query on the new location
                    //The variable $query should have the new address/location instead of the input value

                    DeliveryOrder.findThePlace(query, DeliveryOrder.marker_position, false)
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
        DeliveryOrder.zones_count = zones.length;
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
        DeliveryOrder.loadAddressOnMap(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)});

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
    updateCalendar(savedDate, dateClicked = false) {
        $("#deliveryOrderDatePicker").datepicker("destroy");
        $("#deliveryOrderDatePicker").datepicker({
            onSelect: function (dateText) {
                //console.log(dateText)
                $(`[name="delivery_order_custom_desired_date"]`).val(dateText);
                return DeliveryOrder.updateCalendar(helper.parseUSDate(dateText), true);
            },
            beforeShowDay: function (date) {
                let dateString = helper.getDateStringUsa(date);

                if (DeliveryOrder.holidays.includes(dateString)) {
                    return [false];
                }

                //Disable past dates and current date
                let today = helper.getDateStringUsa(new Date());
                let daysdiff = helper.diffDays(dateString, today);
                let cudate = today;
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
                if (DeliveryOrder.zone) {
                    let zone = DeliveryOrder.zone;
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
            if (! DeliveryOrder.create) {
                if (! dateClicked) {
                    setTimeout(() => {
                        const usDate = helper.getDateStringUsa(savedDate);
                        $("#deliveryOrderDatePicker").datepicker('setDate', usDate);
                    }, 2000);
                } else {
                    const usDate = helper.getDateStringUsa(savedDate);
                    $("#deliveryOrderDatePicker").datepicker('setDate', usDate);
                }
            } else {
                const usDate = helper.getDateStringUsa(savedDate);
                $("#deliveryOrderDatePicker").datepicker('setDate', usDate);
            }
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !DeliveryOrder.movedNextMonth && DeliveryOrder.create) {
                DeliveryOrder.movedNextMonth = true;
                setTimeout(() => {
                    $('#deliveryOrderDatePicker .ui-datepicker-next').trigger("click");
                }, 3000);
            }

            $("#deliveryOrderDatePicker").find('a.ui-state-active').removeClass('ui-state-active')
            .removeClass('ui-state-highlight').removeClass('ui-state-hover');
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
                $(`[name="delivery_order_custom_desired_date"]`).val(helper.formatDateUsa(deliveryOrder.service_date));

                DeliveryOrder.updateCalendar(helper.parseDate(deliveryOrder.service_date));
            }, 1000);
        }
    },

    setSignPanels(deliveryOrder) {
        const panels = deliveryOrder.panels;
        const pickupTmpl = $('#pickupTmpl').html();
        const pickupContainer = $('#pickupContainer');
        const dropoffTmpl = $('#dropoffTmpl').html();
        const dropoffContainer = $('#dropoffContainer');

        DeliveryOrder.resetSignPanels();
        // console.log(panels)
        if (panels) {
            $.each(panels, (index, panel)=> {
                let i = DeliveryOrder.rowCountPickup;
                let c = DeliveryOrder.rowCountDropoff;
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
                    DeliveryOrder.rowCountPickup++;
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
                    DeliveryOrder.rowCountDropoff++;
                }
            });
        }
    },

    resetForm() {
        this.disableAllDates()
        $('#deliveryOffice').val('');
        $('#deliveryAgent').val('').empty();
        $(`[name="delivery_order_comment"]`).val('');
        $(`[name="delivery_order_address"]`).val('');
        this.startDeliveryMap();

        $("[delivery-adjustments]").html("$0.00");

        DeliveryOrder.rowCount = 0;
        DeliveryOrder.countNewSigns = 0;
        DeliveryOrder.rowCountPickup = 1;
        DeliveryOrder.rowCountDropoff = 1;
        DeliveryOrder.agent = {};
        DeliveryOrder.signPanels = {};

        this.resetTotals();
        this.setRushFee(0);
        this.setDeliveryFee();

        $(`[name="delivery_order_desired_date"][value="custom_date"]`).trigger('click');

        $(".list-container-accessories").empty();

        this.resetPricingAdjustment();
    },

    resetFormKeepOfficeAgent() {
        this.disableAllDates()
        $(`[name="delivery_order_comment"]`).val('');
        $(`[name="delivery_order_address"]`).val('');
        $(`[name="delivery_order_city"]`).val('');
        this.startDeliveryMap();

        $("[delivery-adjustments]").html("$0.00");

        DeliveryOrder.rowCount = 0;
        DeliveryOrder.countNewSigns = 0;
        DeliveryOrder.rowCountPickup = 1;
        DeliveryOrder.rowCountDropoff = 1;
        DeliveryOrder.agent = {};
        DeliveryOrder.signPanels = {};

        this.resetTotals();
        this.setRushFee(0);
        this.setDeliveryFee();
        this.resetSignPanels();

        $(`[name="delivery_order_desired_date"][value="custom_date"]`).trigger('click');

        $(".list-container-accessories").empty();

        this.resetPricingAdjustment();
    },

    createDeliveryOrder() {
        DeliveryOrder.create = true;

        DeliveryOrder.resetForm();

        DeliveryOrder.resetSignPanels();

        DeliveryOrder.setFooter();

        helper.openModal('deliveryOrderModal');
    },

    setFooter() {
        if (DeliveryOrder.create) {
            $('#submitDeliveryOrder').html('<strong class="text-white">SUBMIT DELIVERY</strong>').prop('disabled', false);
        } else {
            $('#submitDeliveryOrder').html('<strong class="text-white">UPDATE ORDER</strong>').prop('disabled', false);
        }
    },

    setComment(deliveryOrder) {
        $(`[name="delivery_order_comment"]`).val(deliveryOrder.comment);
    },

    setOfficeAndAgent(deliveryOrder) {
        $('[name="delivery_office"]').val(deliveryOrder.office.id);
        if (deliveryOrder.agent) {
            DeliveryOrder.agent = deliveryOrder.agent;
        }

        //Set agent change count to 0 to prevent form reset
        DeliveryOrder.agentChangeCount = 0;

        $('[name="delivery_office"]').trigger('change');
    },

    setPropertyInfo(deliveryOrder) {
        $('[name="delivery_order_address"]').val(deliveryOrder.address.split(',')[0]);
        $('[name="delivery_order_city"]').val(deliveryOrder.address.split(',')[1]);
        //Set selected state
        $(`#delivery_order_state option[value=${deliveryOrder.address.split(',')[2]?.trimStart().substr(0, 2)}]`).attr('selected', 'selected');

        //Map
        DeliveryOrder.loadAddressOnMap(deliveryOrder.address, {lat: Number(deliveryOrder.latitude), lng: Number(deliveryOrder.longitude)});
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

        DeliveryOrder.marker_position = position;
    },

    editDeliveryOrder(orderId) {
        DeliveryOrder.resetForm();

        $("#loader_image").modal('show');
        $.get('/delivery/get/order/' + orderId).done(async deliveryOrder => {
            DeliveryOrder.create = false;

            DeliveryOrder.order = deliveryOrder;

            DeliveryOrder.delivery_order_id = deliveryOrder.id;

            DeliveryOrder.zone = await DeliveryOrder.getDeliveryZone(deliveryOrder.zone_id);
            DeliveryOrder.zoneId = deliveryOrder.zone_id;
            DeliveryOrder.settings = await helper.getZoneSettings();

            DeliveryOrder.setOfficeAndAgent(deliveryOrder);
            DeliveryOrder.setPropertyInfo(deliveryOrder);
            DeliveryOrder.setDate(deliveryOrder, false);
            DeliveryOrder.setComment(deliveryOrder);
            DeliveryOrder.setFooter();
            DeliveryOrder.setRushFee(deliveryOrder.rush_fee);

            if (DeliveryOrder.zone.data && DeliveryOrder.settings) {
                const zone = DeliveryOrder.zone.data;
                const settings = DeliveryOrder.settings;

                const zoneFee = parseFloat(zone.zone_fee) * settings.delivery / 100;
                $(`[name="delivery_order_zone_fee"]`).val(zoneFee);
                $('[delivery-zone-fee]').html(`$${zoneFee.toFixed(2)}`);
                $(`[name="delivery_order_zone_fee"]`).trigger('change');
            }

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
                    DeliveryOrder.setRushFee(0)
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
            const total = parseFloat($(`[name="delivery_order_fee"]`).val()) + DeliveryOrder.getTotalFees() + parseFloat(DeliveryOrder.totalAdjusted);
            $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

            DeliveryOrder.total = total;
        });
        $(`[name="delivery_order_fee"]`).on("change", (e) => {
            const total = parseFloat($(`[name="delivery_order_fee"]`).val()) + DeliveryOrder.getTotalFees() + parseFloat(DeliveryOrder.totalAdjusted);
            $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

            DeliveryOrder.total = total;
        });
        $(`[name="delivery_order_zone_fee"]`).on("change", (e) => {
            const total = parseFloat($(`[name="delivery_order_fee"]`).val()) + DeliveryOrder.getTotalFees() + parseFloat(DeliveryOrder.totalAdjusted);
            $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

            DeliveryOrder.total = total;
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

                DeliveryOrder.totalPost = totalPost;

                const total = parseFloat(DeliveryOrder.totalPanel) + DeliveryOrder.getTotalFees() + parseFloat(DeliveryOrder.totalAdjusted);
                $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

                const deliveryFee = parseFloat(DeliveryOrder.totalPanel);
                $(`[delivery-fee]`).html(`$${deliveryFee.toFixed(2)}`);

                DeliveryOrder.total = total;
            } else {
                DeliveryOrder.totalPost = DeliveryOrder.totalPost - parseFloat(self.val());

                const total = parseFloat(DeliveryOrder.totalPanel) + DeliveryOrder.getTotalFees() + parseFloat(DeliveryOrder.totalAdjusted);
                $(`[delivery-total]`).html(`$${total.toFixed(2)}`);

                const deliveryFee = parseFloat(DeliveryOrder.totalPanel);
                $(`[delivery-fee]`).html(`$${deliveryFee.toFixed(2)}`);

                DeliveryOrder.total = total;
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
        DeliveryOrder.totalAdjusted = 0;
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
            fd.append("create_order", DeliveryOrder.create);
            //Order Id
            fd.append("delivery_order_id", DeliveryOrder.delivery_order_id);
            //Files
            //Fees and total
            fd.append("delivery_order_rush_fee", form.find(`[name="delivery_order_rush_fee"]`).val());
            fd.append("delivery_order_fee", form.find(`[name="delivery_order_fee"]`).val());
            fd.append("delivery_order_zone_fee", form.find(`[name="delivery_order_zone_fee"]`).val());
            fd.append('total', DeliveryOrder.total);
            //Zone and coordinates
            fd.append("delivery_order_address", `${$("#delivery_order_address").val().replace(/[,]/g, '').trim()}, ${$("#delivery_order_city").val().replace(/[,]/g, '').trim()}, ${$("#delivery_order_state").val().replace(/[,]/g, '').trim()}`);
            fd.append("delivery_marker_position", JSON.stringify(DeliveryOrder.marker_position));
            fd.append("zone_id", DeliveryOrder.zoneId);
            //Office and agent
            fd.append("office_id", form.find(`[name="delivery_office"]`).val());
            fd.append("agent_id", form.find(`[name="delivery_agent"]`).val());

            //Pricing adjustment
            fd.append("pricingAdjustments", JSON.stringify(DeliveryOrder.pricingAdjustments));

            //New signs
            DeliveryOrder.countNewSigns = $('.panel-list').children('option[value="-1"]').length;
            fd.append("countNewSigns", DeliveryOrder.countNewSigns);

            //Panel pickup/dropoff
            DeliveryOrder.signPanels = {
                pickup: {
                    panel: [],
                    qty: []
                },
                dropoff: {
                    panel: [],
                    qty: []
                }
            };
            for (let i=1; i <= DeliveryOrder.rowCountPickup; i++) {
                if ($(`[name="pickup_panel[${i}]"]`).is(':checked')) {
                    let panelId = $(`[name="pickup_panel_style[${i}]"]`).val();
                    let qty = $(`[name="pickup_panel_qty[${i}]"]`).val();

                    if (panelId > 0) {
                        if (!qty) {
                            helper.alertError('Please enter quantity.')
                            return false;
                        }

                        DeliveryOrder.signPanels['pickup']['panel'][i] = panelId;
                        DeliveryOrder.signPanels['pickup']['qty'][i] = qty;
                    } else {
                        if (panelId != -1) {
                            helper.alertError('Please select a sign panel.')
                            return false;
                        }
                    }
                }
            }
            for (let i=1; i <= DeliveryOrder.rowCountDropoff; i++) {
                if ($(`[name="dropoff_panel[${i}]"]`).is(':checked')) {
                    let panelId = $(`[name="dropoff_panel_style[${i}]"]`).val();
                    let qty = $(`[name="dropoff_panel_qty[${i}]"]`).val();

                    if (panelId > 0) {
                        if (!qty) {
                            helper.alertError('Please enter quantity.')
                            return false;
                        }

                        DeliveryOrder.signPanels['dropoff']['panel'][i] = panelId;
                        DeliveryOrder.signPanels['dropoff']['qty'][i] = qty;
                    } else {
                        if (panelId != -1) {
                            helper.alertError('Please select a sign panel.')
                            return false;
                        }
                    }
                }
            }

            /*if (
                typeof DeliveryOrder.signPanels['pickup']['panel'][1] ==='undefined'
                && typeof DeliveryOrder.signPanels['dropoff']['panel'][1] ==='undefined'
            ) {
                helper.alertError('Please select a sign panel.')
                return false;
            }*/

            /*console.log(DeliveryOrder.countNewSigns)
            return false;*/
            fd.append("signPanels", JSON.stringify(DeliveryOrder.signPanels));

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

                    // let paymentModal = $("#deliveryPaymentModal");
                    $(`[delivery-payment-total-amount]`).html(parseFloat(res.deliveryOrder.total).toFixed(2));

                    $(`[delivery-billing-name]`).val(res.billing.name);
                    $(`[delivery-billing-address]`).val(res.billing.address);
                    $(`[delivery-billing-city]`).val(res.billing.city);
                    $(`[delivery-billing-state]`).val(res.billing.state);
                    $(`[delivery-billing-zip]`).val(res.billing.zipcode);

                    $('#delivery_use_another_card').prop('checked', true);
                    $(`.form-another-card input`).prop('disabled', false);
                    $('#delivery_card_profile_select').prop('disabled', true);

                    //If user has card on file then enable Use Cards on File. Otherwise enable Enter Another Card
                    if (res.deliveryOrder.office.user.authorizenet_profile_id) {
                        $('#delivery_use_card_profile').prop('checked', true);
                        $('#delivery_card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#delivery_use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        if (!res.deliveryOrder.agent) {
                            Payment.loadCards($('#delivery_card_profile_select'), res.deliveryOrder.office.user.id);
                        } else {
                            //Load any office card visible to agent
                            Payment.loadOfficeCardsVisibleToAgent(
                                $('#delivery_card_profile_select'),
                                res.deliveryOrder.office.user.id
                            );
                        }
                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#delivery_use_another_card').prop('checked', true);
                        $('#delivery_use_card_profile').prop('checked', false);
                        $('#delivery_card_profile_select').prop('disabled', true);
                    }

                    if (res.deliveryOrder.agent) {
                        if (res.deliveryOrder.agent.user.authorizenet_profile_id) {
                            $('#delivery_use_card_profile').prop('checked', true);
                            $('#delivery_card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#delivery_use_another_card').prop('checked', false);

                            //Load cards in dropdown
                            Payment.loadCards($('#delivery_card_profile_select'), res.deliveryOrder.agent.user.id);
                        }
                    }

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

    deleteOrders() {
        $('#deleteAllDeliveryOrders').on('click', (e) => {
            helper.confirm(
                '',
                "",
                () => {
                    const deletePath = helper.getSiteUrl("/delivery/order/delete/all");
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

    deliveryOrderCancel(deliveryOrderId) {
        $.get(`/delivery/get/order/${deliveryOrderId}`).done(deliveryOrder => {
            if (deliveryOrder.status == DeliveryOrder.status_scheduled) {
                helper.confirm2(
                    'REMOVE FROM ROUTE',
                    "Are you sure you wish to remove this order from its scheduled route?",
                    () => {
                        $.get(`/delivery/order/${deliveryOrderId}/cancel`).done(res => {

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
                        $.get(`/delivery/order/${deliveryOrderId}/cancel`).done(res => {

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
        const rowTmpl = $('#rowTmplDelivery').html();
        const rowContainer = $('#rowContainerDelivery');

        $('#openDeliveryPriceAdjustmentModalBtn').on('click', ()=> {
            if (DeliveryOrder.rowCount == 0) {
                DeliveryOrder.rowCount++;
                let newTmpl = rowTmpl.replace(/rowCount/g, DeliveryOrder.rowCount);
                rowContainer.empty().append(newTmpl);
            }

            helper.openModal('deliveryPriceAdjustmentModal');
        });

        $('#closeDeliveryPriceAdjustmentModalBtn').on('click', ()=> {
            helper.closeModal('deliveryPriceAdjustmentModal');
        });

        $('#addAdjustmentBtnDelivery').on('click', ()=> {
            DeliveryOrder.rowCount++;

            let newTmpl = rowTmpl.replace(/rowCount/g, DeliveryOrder.rowCount);
            rowContainer.append(newTmpl);
        });

        $('body').on('click', '.remove-price-adjustment-row', (e)=> {
            const self = $(e.target);

            self.closest('.row').remove();
            //DeliveryOrder.rowCount--;
        });

        $('#savePricingAdjustmentBtnDelivery').on('click', ()=> {
            let hasError = false;
            let message = '';
            let totalRows = DeliveryOrder.rowCount;
            let description;
            let charge;
            let discount;
            this.resetPricingAdjustment();

            for (let i=1; i <= totalRows; i++) {
                if ($(`[name="delivery_price_adjustment_description[${i}]"]`).length) {
                    description = $(`[name="delivery_price_adjustment_description[${i}]"]`).val();
                    charge = parseFloat($(`[name="delivery_price_adjustment_charge[${i}]"]`).val()) || 0;
                    discount = parseFloat($(`[name="delivery_price_adjustment_discount[${i}]"]`).val()) || 0;

                    if ( ! description && (charge || discount)) {
                        message = 'Please provide description.';
                        hasError = true;
                    }

                    if ( ! description && ! charge && ! discount) {
                        message = 'Please fill out all fields.';
                        hasError = true;
                    }

                    DeliveryOrder.pricingAdjustments['description'][i] = description;
                    DeliveryOrder.pricingAdjustments['charge'][i] = charge;
                    DeliveryOrder.pricingAdjustments['discount'][i] = discount;
                }
            }

            if (hasError) {
                helper.alertError(message);
                return false;
            }

            DeliveryOrder.calculateAdjustments();
            helper.closeModal('deliveryPriceAdjustmentModal');
        });
    },

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = DeliveryOrder.rowCount;

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

        DeliveryOrder.totalAdjusted = totalAdjustments;

        if (DeliveryOrder.totalAdjusted < 0) {
            $('[delivery-adjustments]').html(`<span class="text-danger">- $${DeliveryOrder.totalAdjusted*(-1)}</span>`);
        } else {
            $('[delivery-adjustments]').html(`$${DeliveryOrder.totalAdjusted}`);
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
            DeliveryOrder.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, DeliveryOrder.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="delivery_price_adjustment_description[${DeliveryOrder.rowCount}]"]`).val(row.description);
            $(`[name="delivery_price_adjustment_charge[${DeliveryOrder.rowCount}]"]`).val(row.charge);
            $(`[name="delivery_price_adjustment_discount[${DeliveryOrder.rowCount}]"]`).val(row.discount);

            DeliveryOrder.pricingAdjustments['description'][i] = row.description;
            DeliveryOrder.pricingAdjustments['charge'][i] = row.charge;
            DeliveryOrder.pricingAdjustments['discount'][i] = row.discount;

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            DeliveryOrder.totalAdjusted = totalAdjustments;

            if (DeliveryOrder.totalAdjusted < 0) {
                $('[delivery-adjustments]').html(`<span class="text-danger">- $${DeliveryOrder.totalAdjusted*(-1)}</span>`);
            } else {
                $('[delivery-adjustments]').html(`$${DeliveryOrder.totalAdjusted}`);
            }
        });

        this.calculateAdjustments();
    },

    resetPricingAdjustment() {
        DeliveryOrder.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
    },

    resetSignPanels() {
        $(`[name="pickup_panel[1]"]`).prop('checked', false).prop('disabled', false);
        $(`[name="pickup_panel_qty[1]"]`).val('').prop('disabled', false);
        $(`[name="pickup_panel_style[1]"]`).val(0).prop('disabled', true);
        $(`[name="dropoff_panel[1]"]`).prop('checked', false).prop('disabled', false);
        $(`[name="dropoff_panel_qty[1]"]`).val('').prop('disabled', false);
        $(`[name="dropoff_panel_style[1]"]`).val(0).prop('disabled', true);

        $('#pickupContainer').find('.to-append').remove();
        $('#dropoffContainer').find('.to-append').remove();

        $('.add-new-pickup').show();
    },

    markDeliveryOrderCompleted(id) {
        helper.showLoader();

        $.get('/delivery/get/order/' + id).done(order => {
            //Display same modal as installer in iFrame
            const installerId = order.assigned_to;
            const url = `${helper.getSiteUrl()}/admin/installer/order/details/${id}/delivery/${installerId}`;
            $('#completeOrderIframe').prop('src', url);

            setTimeout(() => {
                helper.hideLoader();

                helper.openModal('markOrderCompletedModal');
            }, 1000)
        });
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
    DeliveryOrder.init();
});
