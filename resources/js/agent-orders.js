import helper from "./helper";
import global from "./global";
import obs from '../../node_modules/observable-slim/observable-slim';
import Payment from "./Payment";
import { isEmpty } from "lodash";
import OrderDetails from "./order-details";

let _obj_ = {
    zones: [],
}

let proxy = obs.create(_obj_, true, function (changes) {
    if (_obj_.zones.length == AgentOrders.zones_count) {
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
            AgentOrders.addresIsOut = false;
            $.get(helper.getSiteUrl(`/get/zone/settings`)).done(settings => {

                AgentOrders._currentZone = lowest_zone_fee;

                AgentOrders.updateCalendar(AgentOrders.savedServiceDate);

                let fee = parseFloat(lowest_zone_fee.zone_fee || 0) * (settings.install / 100);

                if (AgentOrders.ignoreZoneFee) {
                    $(`[install-post-zone-fee]`).html(`$0`);
                    $(`[name="install_post_zone_fee"]`).val(0);
                    $(`[name="install_post_zone_fee"]`).trigger('change');
                } else {
                    $(`[install-post-zone-fee]`).html(`$${fee.toFixed(2)}`);
                    $(`[name="install_post_zone_fee"]`).val(fee);
                    $(`[name="install_post_zone_fee"]`).get(0).dispatchEvent(new Event("change"));
                }

                AgentOrders.zoneId = lowest_zone_fee.id;
            })
        } else {
            helper.alertMsg('Address Out of Service Area', 'This address appears to be outside the service area. Please verify that the address and pin location are correct.');
            AgentOrders._currentZone = null;
            //AgentOrders.updateCalendar();
            AgentOrders.disableAllDates();
            AgentOrders.addresIsOut = true;
            $(`[install-post-zone-fee]`).html("00.00");
            $(`[name="install_post_zone_fee"]`).val("00.00");
            $(`[name="install_post_zone_fee"]`).get(0).dispatchEvent(new Event("change"));
            $("[googlescript]").remove();
            // AgentOrders.initMap()
            // console.log("reset map");
            helper.hideLoader('');

        }

    }
});

const AgentOrders = {
    createpost: true,
    order_id: null,
    payment_method_office_pay: 3,
    zoneId: null,
    ignoreZoneFee: false,
    total:0,
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
    officeId: $('#officeId').val(),
    agentId: $('#agentId').val(),
    savedServiceDate: '',

    modal: $('#noticeAcknowledgement'),

    dailyOrderCap: 0,
    countOrders: null,

    init() {
        Payment.init();

        if (window.location.href.indexOf('/dashboard') != -1) {
            this.datatable()
        }
        if (window.location.href.indexOf('/order/status') != -1) {
            if (
                window.location.href.indexOf('history') == -1
                // && window.location.href.indexOf('routes') == -1
            ) {
                this.datatableOrderStatus();
                $('.ordersHistory').removeClass('order-tab-active');
                $('.ordersActive').addClass('order-tab-active');
                $('.ordersRoutes').removeClass('order-tab-active');
            }
            this.searchOrderStatus();
            this.showOrderStatusEntries();
            if (
                window.location.href.indexOf('history') != -1
                // && window.location.href.indexOf('routes') == -1
            ) {
                $('.ordersHistory').addClass('order-tab-active');
                $('.ordersActive').removeClass('order-tab-active');
                $('.ordersRoutes').removeClass('order-tab-active');
                this.datatableOrderStatusHistory();
            }
        }

        // console.log("Recent orders");
        this.showOrderEntries();
        this.showNoticeModal();

        window.orderCancel = this.orderCancel;
        window.repairOrderCancel = this.repairOrderCancel;
        window.removalOrderCancel = this.removalOrderCancel;
        window.deliveryOrderCancel = this.deliveryOrderCancel;
        window.viewDetails = this.viewDetails;

        this.initMap();

        $('.open_install_post_modal').on('click', e => {
            let modal = $("#install_post_modal");
            AgentOrders.resetInstallModalForm()

            $('#submitOrder').html('<strong class="text-white">SUBMIT INSTALL</strong>');

            $(".list-container-posts").empty();
            $(".list-container-signs").empty();
            $(".list-container-accessories-install").empty();

            AgentOrders.hidePreviewsImages()

            //Load inventory for office
            AgentOrders.loadAgentInventory();

            modal.modal()
            AgentOrders.createpost = true;
        });

        this.searchAddress();
        this.onLocationAdjustmentChange();
        window.onInstallPostSelectPostChange = this.onInstallPostSelectPostChange;
        window.onInstallPostSelectSignChange = this.onInstallPostSelectSignChange;
        window.onInstallPostSelectAccessoriesChange = this.onInstallPostSelectAccessoriesChange;
        window.accessoryClicked = this.accessoryClicked
        this.onInstallPostCommentChange();
        this.onFileUplaoded();
        window.removeFile = this.removeFile;
        this.totalFee();
        this.onDesiredDateChange();
        // this.onAgentChange();
        this.installPostPropertyType();
        window.uploadAccessoryFile = this.uploadAccessoryFile;
        this.onSubmitForm();

        this.handleDuplicatedOrder();

        this.onCityChange();

        $.get(helper.getSiteUrl('/get/holidays')).done(holidays => {
            AgentOrders.holidays = holidays;
        })

        $.get(helper.getSiteUrl('/get/zone/settings')).done(settings => {
            AgentOrders.dailyOrderCap = settings.daily_order_cap;
        })

        $.get(helper.getSiteUrl('/agent/count-orders')).done(countorders => {
            AgentOrders.countOrders = countorders;
        })

        let type = $(`[name="install_post_panel_type"]`)
        if (type.length) {
            type.on('change', e => {
                AgentOrders.panel_type = e.target.value;
                $("#sign_image_preview").addClass('d-none').removeClass('d-block');
                // console.log(AgentOrders.panel_type);
                $(`[name="install_post_panel"]`).prop('checked', false)
                /*$('.disable-layer').remove()
                $(`.list-container-signs`).append('<div class="disable-layer"><div>');*/

                //Remove any previously selected panel
                AgentOrders.panel = '';
            })
        }

        if ($('.cc-number-input').length) {
            helper.cardNumberInput('.cc-number-input');
        }

        $("#uploadOtherDoc").on('click', e => {
            AgentOrders.upload_accessory_file = null
        })

        $("[rush-fee-decline-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            this.setRushFee(0)
            $(`[name="install_post_desired_date"][value="custom_date"]`).trigger('click');
        })
        $("[rush-fee-accept-button]").on('click', e => {
            $(".modal").css({ "overflow-y": "scroll" });
            let rush_fee = $(`[rush-order-fee]`).attr('rush-order-fee');
            this.setRushFee(rush_fee)

        });

        window.editOrder = this.editOrder;
    },

    resetInstallModalForm() {
        let modal = $("#install_post_modal");
        $("#install-post-search-map").html(' ')
        $("#files_list").html(' ')
        $("#warning-alerts").html(' ');
        this.startMap()
        $(".list-container-posts,.list-container-signs,.list-container-accessories-install").html('')
        $(".order-preview-imgs,.accessory_image_preview").addClass('d-none').removeClass('d-block')
        $(`[install-post-zone-fee]`).html(`$0.00`)
        $(`[install-post-signage]`).html(`$0.00`)
        $("[install-post-total]").html("$0.00")

        $(`[name="install_post_signage"]`).val(0)
        $(`[name="install_post_zone_fee"]`).val(0)
        $('[name="install_post_comment"]').val('');
        $('[name="install_post_comment"]').text('');
        this.disableAllDates()
        modal.find('form').trigger('reset')
        AgentOrders.prices_obj = { post: 0, panels: 0, accessories: 0 }
        $("[install-post-adjustments]").html("$0.00");
        AgentOrders.totalAdjusted = 0;
        AgentOrders.rowCount = 0;
        AgentOrders.getSignageFee();
        AgentOrders.ignoreZoneFee = false;
        AgentOrders.savedServiceDate = '';
    },

    googleKey: global.googleKey,
    initMap() {
        window.initMap = this.startMap;
        const src = `https://maps.googleapis.com/maps/api/js?key=${AgentOrders.googleKey}&callback=window.initMap&libraries=drawing,geometry,places&v=weekly`;
        $("body").append(window.e("script", { src, googlescript: true }));
    },

    startMap() {
        // Initialize and add the map
        // The location of defaultLocation
        const defaultLocation = {
            lat: 43.633994,
            lng: -116.433707,
        };
        // The map, centered at defaultLocation
        const map = new google.maps.Map(document.getElementById("install-post-search-map"),
            {
                zoom: 11,
                center: defaultLocation,
            }
        );
        window.map = map;
        $(`[name="install_location_adjustment"]`).get(0).checked = false;
    },

    disableAllDates() {
        $("#selectdate_custom_date").datepicker("destroy");
        $("#selectdate_custom_date").datepicker({
            beforeShowDay: function (date) { return [false] }
        })
    },

    loadAgentInventory() {
        let value = AgentOrders.agentId;

        if (value.trim()) {
            $(".options-posts,.options-signs,.options-accessories").removeClass('d-none')
            $('.order-preview-imgs').show();
        } else {
            $(".options-posts,.options-signs,.options-accessories").addClass('d-none')
            $('.order-preview-imgs').hide();
        };
        //
        $(`[name="install_post_panel_type"]`).prop('checked', false)
        $.get(helper.getSiteUrl(`/get/agent/${value}/posts`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-posts");
            listContainer.empty();
            let html = '';
            res.forEach(post => {
                html+=`
                    <div class="form-check d-flex justify-content-between" post-office-access="">
                        <input
                            type="radio"
                            name="install_post_post"
                            value="${post.id}"
                            data-price="${post.price}"
                            data-image="${helper.getSiteUrl(`/private/image/post/${post.image_path}`)}"
                            class="form-check-input"
                            id="install_post_option_${post.id}"
                            onchange="window.onInstallPostSelectPostChange(event)"
                            >
                        <label class="form-check-label text-dark" for="install_post_option_${post.id}">${post.post_name}</label>
                        <span price="">$${post.price}</span>
                    </div>
                `;
            })
            listContainer.html(html);
        })
        $.get(helper.getSiteUrl(`/get/agent/${value}/panels`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-signs");
            listContainer.empty();
            let html = '';
            res.forEach(panel => {
                listContainer.append(`
                    <div class="form-check d-flex justify-content-between">
                        <input type="radio" onchange="window.onInstallPostSelectSignChange(event)" name="install_post_panel" value="${panel.id}" data-price="${panel.price}" data-image="${helper.getSiteUrl(`/private/image/panel/${panel.image_path}`)}" class="form-check-input" id="install_panel_option_${panel.id}">
                        <label class="form-check-label text-dark" for="install_panel_option_${panel.id}">${panel.panel_name}</label>
                        <span price="">$${panel.price}</span>
                    </div>
                    `)
            })
        });
        $.get(helper.getSiteUrl(`/get/agent/${value}/accessories`)).done(res => {
            if (!Array.isArray(res)) res = Object.values(res);
            let listContainer = $(".list-container-accessories-install");
            listContainer.empty();
            let html = '';
            res.forEach(a => {
                listContainer.append(`
                    <div class="form-check d-flex justify-content-between">
                    <input type="checkbox" name="install_post_accessories[]" value="${a.id}"
                        data-price="${a.price}"
                        data-image="${helper.getSiteUrl('/private/image/panel/' + a.image)}" class="form-check-input"
                        onchange="window.onInstallPostSelectAccessoriesChange(event)"
                        onclick="window.accessoryClicked(event)"
                        id="install_accessory_option_${a.id}">
                    <label class="form-check-label text-dark"
                        for="install_accessory_option_${a.id}">${a.accessory_name}</label>
                    <span price>$${a.price}</span>
                </div>
                `)
            })

            // reset messages
            $("#warning-alerts").html(' ');
        });
    },

    hidePreviewsImages() {
        $("#post_image_preview,#sign_image_preview").hide().attr("src", '');
        $(".accessory_image_preview").remove()
        AgentOrders.selected_arr = [];
        // $("#accessories-names").html(" ")
        // $(".accessories-install-document-required-warning").removeClass('d-block').addClass('d-none')
    },

    marker_position: null,
    searchAddress() {
        // let input = $("#address");
        let updateMapBtn = $("#updateMap");
        // if (input.length) {
        //     input.on("keyup", async (e) => {
        //         if (e.key === "Enter") {
        //             AgentOrders.marker_position = null
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

        //             if ($(`[name="install_location_adjustment"]`).is(':checked')) {
        //                 $(`[name="install_location_adjustment"]`).trigger('click');
        //             }

        //             AgentOrders.movedNextMonth = false;
        //             AgentOrders.updateCalendar(false);
        //         }
        //     }
        //     )
        // }
        if (updateMapBtn.length) {
            updateMapBtn.on("click", async (e) => {
                AgentOrders.marker_position = null
                //search input value
                // let address = input.val();
                let address = `${$("#address").val().replace(/[,]/g, '').trim()}, ${$("#city").val().replace(/[,]/g, '').trim()}, ${$("#state").val().replace(/[,]/g, '').trim()}`;

                if (isEmpty($("#address").val()) || isEmpty($("#city").val()) || isEmpty($("#state").val())) {
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

                if ($(`[name="install_location_adjustment"]`).is(':checked')) {
                    $(`[name="install_location_adjustment"]`).trigger('click');
                }

                AgentOrders.movedNextMonth = false;
                AgentOrders.updateCalendar(AgentOrders.savedServiceDate);
            });
        }
        this.onAddressChange();
    },

    addresIsOut: false,
    async findThePlace(query, marker_position = false, from_edit_modal = false, ignoreZoneFee = false, zoomIn = true) {
        let service = new google.maps.places.PlacesService(window.map);
        let request = { query, fields: ["name", "geometry"] };
        const geocoder = new google.maps.Geocoder();

        // TODO: Restructure this piece for repeating code
        // If calling this function because editing an order, then just call this piece, which takes data from the saved coordinates
        if (from_edit_modal) {
            let position = marker_position;
            //center place in map
            window.map.setCenter(position);
            //create marker
            if (window.addressMarker) {
                window.addressMarker.setMap(null);
            }

            let icon = {
                url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
                //scaledSize: new google.maps.Size(40, 50), // scaled size
                // origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(0, 50), // anchor
                // labelOrigin: new google.maps.Point(20, 55),
            };
            window.addressMarker = new google.maps.Marker({
                position,
                map,
                title: query,
                icon,
                draggable: false,
                //label,
            });
            window.addressMarker.setDraggable($(`[name="install_location_adjustment"]`).get(0).checked);

            window.addressMarker.addListener('dragend', (e) => {
                // Call Maps reverse geocoding to get the new address on dragend, otherwise it will take the same address as previous and marker will get back to its previous position
                geocoder.geocode({ location: e.latLng }, (results, status) => {
                    // Process if geocoder succeed
                    if (status === "OK") {
                        // Proceed if there's at least on e location
                        if (results[0]) {
                            console.log(e)
                            let lat = e.latLng.lat()
                            let lng = e.latLng.lng()
                            AgentOrders.marker_position = { lat, lng };

                            //At the end of drag event it should detect the new location and run query on the new location
                            //The variable $query should have the new address/location instead of the input value

                            AgentOrders.findThePlace(results[0].formatted_address, AgentOrders.marker_position, false, false, false)
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

            //zoom to marker place
            if (zoomIn) {
                window.map.setZoom(17);
            }
            //get fee
            this.getZoneFee(position);
            this.getSignageFee();

            //Check for duplicate address
            /*let orderId = 0;
            if (! AgentOrders.createpost) {
                orderId = AgentOrders.order_id;
            }
            const officeId = AgentOrders.officeId;
            const agentId = AgentOrders.agentId;
            // const agentId = $(`[name="install_post_agent"]`).val();
            if (officeId || agentId) {
                const checkAddress = await this.hasPendingOrderSameAddress(
                    query, position.lat, position.lng, officeId, agentId, orderId
                );
                if (checkAddress) {
                    if (checkAddress != '404') {
                        $('#duplicateOrderModal').modal();
                    }
                }
            }*/
        } else {
            // If not calling this function from order edit modal, then execute this piece, which is the normal flow
            service.findPlaceFromQuery(request, async (results, status) => {
                if (results == null) {
                    AgentOrders.addresIsOut = true;
                    helper.alertError('Address not found. Please verify property address is correct and move the marker to the correct property location on the map.')
                    console.log("ADDESS NOT FOUND");
                    let position = { lat: 43.593469, lng: -116.434029 }
                    window.map.setCenter(position);
                    window.map.setZoom(12);
                    //not found marker
                    if (window.addressMarker) {
                        window.addressMarker.setMap(null);
                    }
                    let icon = {
                        url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
                        anchor: new google.maps.Point(0, 50), // anchor
                    };
                    window.addressMarker = new google.maps.Marker({ position, map, title: query, icon, draggable: false, });


                    //If address not found disable dates in calendar
                    AgentOrders.disableAllDates();

                    window.addressMarker.addListener('dragend', (e) => {
                        // Call Maps reverse geocoding to get the new address on dragend, otherwise it will take the same address as previous and marker will get back to its previous position
                        geocoder.geocode({ location: e.latLng }, (results, status) => {
                            // Process if geocoder succeed
                            if (status === "OK") {
                                // Proceed if there's at least on e location
                                if (results[0]) {
                                    console.log(e)
                                    let lat = e.latLng.lat()
                                    let lng = e.latLng.lng()
                                    AgentOrders.marker_position = { lat, lng };

                                    //At the end of drag event it should detect the new location and run query on the new location
                                    //The variable $query should have the new address/location instead of the input value

                                    AgentOrders.findThePlace(results[0].formatted_address, AgentOrders.marker_position, false, false, false)
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
                    AgentOrders.marker_position = marker_position ? marker_position : { lat, lng };

                    //center place in map
                    window.map.setCenter(position);
                    //create marker
                    if (window.addressMarker) {
                        window.addressMarker.setMap(null);
                    }

                    let icon = {
                        url: helper.getSiteUrl(`/storage/images/map_pin_verified.png`),
                        //scaledSize: new google.maps.Size(40, 50), // scaled size
                        // origin: new google.maps.Point(0, 0), // origin
                        anchor: new google.maps.Point(0, 50), // anchor
                        // labelOrigin: new google.maps.Point(20, 55),
                    };
                    window.addressMarker = new google.maps.Marker({
                        position,
                        map,
                        title: query,
                        icon,
                        draggable: false,
                        //label,
                    });
                    window.addressMarker.setDraggable($(`[name="install_location_adjustment"]`).get(0).checked);

                    window.addressMarker.addListener('dragend', (e) => {
                        console.log(e)
                        let lat = e.latLng.lat()
                        let lng = e.latLng.lng()
                        AgentOrders.marker_position = { lat, lng };


                        //At the end of drag event it should detect the new location and run query on the new location
                        //The variable $query should have the new address/location instead of the input value

                        AgentOrders.findThePlace(query, AgentOrders.marker_position, false, false, false)
                    });

                    //zoom to marker place
                    if (zoomIn) {
                        window.map.setZoom(17);
                    }
                    //get fee
                    this.getZoneFee(position);
                    this.getSignageFee();

                    //Check for duplicate address
                    let orderId = 0;
                    if (! AgentOrders.createpost) {
                        orderId = AgentOrders.order_id;
                    }
                    const officeId = AgentOrders.officeId;
                    const agentId = AgentOrders.agentId;
                    // const agentId = $(`[name="install_post_agent"]`).val();
                    if (officeId || agentId) {
                        const checkAddress = await this.hasPendingOrderSameAddress(
                            query, lat, lng, officeId, agentId, orderId
                        );
                        if (checkAddress) {
                            if (checkAddress != '404') {
                                $('#duplicateOrderModal').modal();
                            }
                        }
                    }
                }
                ////===============================================
            });
        }
    },

    totalFee() {
        $(`[name="install_post_rush_fee"]`).on("change", (e) => {
            let zone_fee = $(`[name="install_post_zone_fee"]`).val();
            if (AgentOrders.ignoreZoneFee) {
                $(`[name="install_post_zone_fee"]`).val(0);
                $(`[install-post-zone-fee]`).html(`$0`);
                zone_fee = 0;
            }

            let signage_fee = $(`[name="install_post_signage"]`).val();
            let rush_fee = e.target.value;
            let total = parseFloat(zone_fee) + parseFloat(signage_fee) + parseFloat(rush_fee) + parseFloat(AgentOrders.totalAdjusted);
            AgentOrders.total = total;
            $(`[install-post-total]`).html(`$${total.toFixed(2)}`);
        });
        $(`[name="install_post_signage"]`).on("change", (e) => {
            let zone_fee = $(`[name="install_post_zone_fee"]`).val();
            if (AgentOrders.ignoreZoneFee) {
                $(`[install-post-zone-fee]`).html(`$0`);
                $(`[name="install_post_zone_fee"]`).val(0);
                zone_fee = 0;
            }
            let rush_fee = $(`[name="install_post_rush_fee"]`).val();
            let signage_fee = e.target.value;
            let total = parseFloat(zone_fee) + parseFloat(signage_fee) + parseFloat(rush_fee) + parseFloat(AgentOrders.totalAdjusted);
            AgentOrders.total = total;
            $(`[install-post-total]`).html(`$${total.toFixed(2)}`);
        });
        $(`[name="install_post_zone_fee"]`).on("change", (e) => {
            let signage_fee = $(`[name="install_post_signage"]`).val();
            let rush_fee = $(`[name="install_post_rush_fee"]`).val();
            let zone_fee = e.target.value;
            if (AgentOrders.ignoreZoneFee) {
                $(`[install-post-zone-fee]`).html(`$0`);
                $(`[name="install_post_zone_fee"]`).val(0);
                zone_fee = 0;
            }
            let total = parseFloat(zone_fee) + parseFloat(signage_fee) + parseFloat(rush_fee) + parseFloat(AgentOrders.totalAdjusted);
            AgentOrders.total = total;
            $(`[install-post-total]`).html(`$${total.toFixed(2)}`);
        });
    },

    getSignageFee() {
        let post_price = this.prices_obj.post;
        let panel_price = this.prices_obj.panels;
        let accessories_price = this.prices_obj.accessories;
        let signage = post_price + panel_price + accessories_price;

        $(`[install-post-signage]`).html(`$${signage.toFixed(2)}`);
        $(`[name="install_post_signage"]`).val(signage);
        $(`[name="install_post_signage"]`).get(0).dispatchEvent(new Event("change"));
    },

    _currentZone: null,
    zones_count: 0,
    async getZoneFee(place_position) {
        let zones = await this.getZones();
        AgentOrders.zones_count = zones.length;
        let _in = true;
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

    onLocationAdjustmentChange() {
        let checkbox = $(`[name="install_location_adjustment"]`).first();
        if (checkbox.length) {
            checkbox.on("change", (e) => {
                if (window?.addressMarker?.setDraggable) {

                    window.addressMarker.setDraggable($(`[name="install_location_adjustment"]`).get(0).checked);

                } else {
                    alert("no marker on map");
                    checkbox.prop("checked", false);
                }
            });
        }
    },

    movedNextMonth: false,
    updateCalendar(savedDate) {
        //console.log(savedDate)
        $("#selectdate_custom_date").datepicker("destroy");
        $("#selectdate_custom_date").datepicker({
            onSelect: function (dateText) {
                $(`[name="install_post_custom_desired_date"]`).val(dateText);
                return AgentOrders.updateCalendar(helper.parseUSDate(dateText));
            },
            beforeShowDay: function (date) {
                //holidays
                let dateString = helper.getDateStringUsa(date);
                // console.log(dateString,month,day);
                if (AgentOrders.holidays.includes(dateString)) {
                    return [false];
                }

                let serviceDate = $.datepicker.formatDate('yy-mm-dd', new Date(date));
                let ordersCount = AgentOrders.countOrders[serviceDate];
                if (typeof ordersCount !== 'undefined') {
                    if (ordersCount >= AgentOrders.dailyOrderCap) {
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

                //Get  days of operation for the zone
                if (AgentOrders._currentZone) {
                    let zone = AgentOrders._currentZone;
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
            $("#selectdate_custom_date").datepicker('setDate', usDate);
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !AgentOrders.movedNextMonth && AgentOrders.createpost) {
                AgentOrders.movedNextMonth = true;
                setTimeout(() => {
                    $('#selectdate_custom_date .ui-datepicker-next').trigger("click");
                }, 3000);
            }
        }
    },

    _files: [],
    onFileUplaoded() {
        let files = $(`input[name="install_post_files[]"]`);
        if (files.length) {
            files.on("change", (e) => {
                let file_input = e.target;
                let files = file_input.files;
                let accessory_id = AgentOrders.upload_accessory_file;
                for (let file of files) {
                    file._id = this.genId();
                    file._accessory_id = accessory_id;
                    AgentOrders._files.push(file);
                    AgentOrders.displayFiles(this._files);
                    AgentOrders.setFiles(this._files);
                    if (accessory_id != "plat-map") {
                        $(`[install_accessory_name_id_${accessory_id}]`).addClass('d-none').removeClass('d-block');
                    } else {
                        $(`.install-document-required-warning`).addClass('d-none').removeClass('d-block');
                    }
                }

            });
        }
    },

    setFiles(files) {
        let input = $(`input[name="install_post_files[]"]`);
        if (input.length) {
            input.files = files;
        }
    },

    displayFiles(files) {
        let files_list = $("#files_list");
        if (files_list.length) {
            files_list.html(``);
            files.forEach((file) => {
                files_list.append(`
                <li>
                    <span>
                        <a href="#"><strong>${file.name}</strong></a>
                        UPLOADED ${helper.formatDateTime((new Date).toISOString())}
                        <a class='text-danger c-p' onclick="window.removeFile('${file._id}')"><strong>REMOVE</strong></a>
                    </span>
                </li>`);
            });
        }
    },

    async removeFile(id) {
        let removed_file = await AgentOrders._files.filter((file) => file._id == id);
        let new_files = await AgentOrders._files.filter((file) => file._id != id);
        AgentOrders._files = new_files;
        AgentOrders.setFiles(AgentOrders._files);
        AgentOrders.displayFiles(AgentOrders._files);

        // Show plat maps warning if last plat map image was removed
        let platMapsCount = AgentOrders._files.filter(file => file._accessory_id == 'plat-map').length;
        const propertyType = $('[name="install_post_property_type"]').val();
        if (platMapsCount == 0 && (propertyType == 2 || propertyType == 3)) {
            $(`.install-document-required-warning`).addClass('d-block').removeClass('d-none');
        }

        // Show accessories warning if last accessory image was removed
        let accessoriesCount = AgentOrders._files.filter(file => file._accessory_id != 'plat-map').length;
        if (accessoriesCount == 0) {
            $(`.accessories-install-document-required-warning`).addClass('d-block').removeClass('d-none');
            $(`[install_accessory_name_id_${removed_file[0]._accessory_id}]`).addClass('d-block').removeClass('d-none');
        }

        if (!AgentOrders._files.length) {
            if (propertyType == 2 || propertyType == 3) {
                $(`.install-document-required-warning`).addClass('d-block').removeClass('d-none');
            }

            //$("#attachments .alert").addClass('d-block').removeClass('d-none');
            $(`.accessories-install-document-required-warning`).addClass('d-block').removeClass('d-none');

            // If all files were removed, reset files input to allow adding a new one that was already assigned
            $(`input[name="install_post_files[]"]`).val('');
        }
    },

    genId() {
        return (
            "id" +
            Math.floor(Math.random() * 99999999999999.66)
                .toString(36)
                .substring(1)
        );
    },

    onInstallPostCommentChange() {
        let textarea = $(`[name="install_post_comment"]`);
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

    prices_obj: { post: 0, panels: 0, accessories: 0 },
    post: "",
    panel: "",
    panel_type: null,
    accessories: [],
    onInstallPostSelectPostChange(event) {
        let select = $(event.target);
        let image = select.data('image');
        AgentOrders.prices_obj.post = parseFloat(select.data('price'));
        AgentOrders.post = select.val();
        let first_image = $(".install-post-preview-images").find("#post_image_preview");
        first_image.removeClass('d-none').addClass('d-block');
        first_image.get(0).src = image;
        AgentOrders.getSignageFee();

    },

    onInstallPostSelectSignChange(e) {
        $(`[name="install_post_panel_type"]`).prop('checked', false);
        AgentOrders.prices_obj.panels = parseFloat(e.target.dataset.price);
        let image = e.target.dataset.image;
        AgentOrders.panel = e.target.value;
        let first_image = $(".install-post-preview-images").find("#sign_image_preview");
        first_image.removeClass('d-none').addClass('d-block');
        first_image.get(0).src = image;
        AgentOrders.getSignageFee();

        //Remove any previously selected panel type
        AgentOrders.panel_type = '';
    },

    selected_arr: [],
    upload_accessory_file: null,
    uploadAccessoryFile(id) {
        AgentOrders.upload_accessory_file = id;
    },

    onInstallPostSelectAccessoriesChange(e) {
        let accessory_input = `[name="install_post_accessories[]"]`;
        if (!AgentOrders.selected_arr.includes(e.target.value)) {
            e.target.setAttribute('checked', true);
            AgentOrders.prices_obj.accessories += parseFloat(e.target.dataset.price);
            AgentOrders.selected_arr.push(e.target.value);
            AgentOrders.accessories = AgentOrders.selected_arr;

        } else {
            AgentOrders.selected_arr = AgentOrders.selected_arr.filter(a => a != e.target.value);
            AgentOrders.accessories = AgentOrders.selected_arr;
            AgentOrders.prices_obj.accessories -= parseFloat(e.target.dataset.price);
        }
        if (AgentOrders.prices_obj.accessories < 0) {
            AgentOrders.prices_obj.accessories = 0;
        }
        AgentOrders.getSignageFee();
        let images_container = $(".install-post-preview-images");
        if (images_container.length) {
            if (!AgentOrders.selected_arr.length) {
                images_container.find('.accessory_image_preview').remove();
                // images_container.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${helper.getSiteUrl('/private/image/accessory/0')}" />`)
                return;
            }
            images_container.find('.accessory_image_preview').remove();
            AgentOrders.selected_arr.forEach(id => {
                let accessory = $(`${accessory_input}[value="${id}"]`);
                let image = accessory.data("image") || helper.getSiteUrl(`/private/image/accessory/0`);
                if (accessory.length) {
                    images_container.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${image}">`)
                }
            })
        }

        let self = $(e.target);

        // $(".accessories-install-document-required-warning #accessories-names").html(' ');
        if (self.is(":checked")) {
            console.log(self.val())
            $.get(helper.getSiteUrl(`/get/accessory/${self.val()}/json`)).done(accessory => {
                if (AgentOrders.createpost) {
                    if (accessory.message) {
                        helper.alertMsg(accessory.popup_message_heading, accessory.popup_message_message)
                    }
                }
                if (accessory.prompt) {
                    if ($(`[install_accessory_name_id_${accessory.id}]`).length) return;
                    $("#warning-alerts").append(`
                    <div accessory_warning install_accessory_name_id_${accessory.id} class="alert alert-warning alert-dismissible fade show accessories-install-document-required-warning" role="alert">
                        <strong>
                            <label for="install_post_files" class="text-primary c-p m-0 underline" onclick="window.uploadAccessoryFile(${accessory.id})">
                                UPLOAD FILES
                            </label>
                            <i class="fas fa-exclamation-triangle ml-3"></i> Notice!
                        </strong> Upload required for
                        <strong>${accessory.accessory_name}</strong>
                    </div>
                    `)
                } else {
                    // $(".accessories-install-document-required-warning  #accessories-names").addClass('d-none').removeClass('d-block');
                }
            })
        }
    },

    accessoryClicked(event) {
        const self = $(event.target);
        if (!self.is(':checked')) {
            $(`[install_accessory_name_id_${self.val()}]`).remove();
        }
    },

    getSelectValues(select) {
        var result = [];
        var options = select && select.options;
        var opt;

        for (var i = 0, iLen = options.length; i < iLen; i++) {
            opt = options[i];

            if (opt.selected) {
                result.push(opt.value || opt.text);
            }
        }
        return result;
    },

    onDesiredDateChange() {
        let dates_input = document.getAll(`[name="install_post_desired_date"]`);
        let datepicker = $("#selectdate_custom_date");
        dates_input.forEach((d) => {
            d.onchange = (e) => {
                let type = e.target.value;
                $(`[name="install_post_desired_date"]`).removeAttr('checked')
                $(e.target).attr('checked', "true");
                if (type === "custom_date") {
                    AgentOrders.setRushFee(0)
                    datepicker.removeClass("d-none");
                    $('#rushFee').addClass('d-none');
                } else {
                    //if (AgentOrders.createpost) $("#rushOrderModal").modal();
                    $("#rushOrderModal").modal();
                    datepicker.addClass("d-none");
                }
            };
        });
    },

    installPostPropertyType() {
        $(`[name="install_post_property_type"]`).on('change', e => {
            if (e.target.value == 2 || e.target.value == 3) {
                $(".install-document-required-warning").addClass("d-block").removeClass('d-none');
            } else {
                $(".install-document-required-warning").addClass("d-none").removeClass('d-block');

            }
        })
    },

    alertMsgDocument(title = '', msg = '', accessory_id) {
        let modal = $("#documentModal");
        modal.find("#messageModelTitle").html(title);
        modal.find("#messageModelContent").html(msg);
        modal.find(`[name="document_files[]"]`).attr('accessory_id', accessory_id);
        modal.modal("show");
        $(".modal").css({ "overflow-y": "scroll" });

    },

    dates: [],
    onSubmitForm() {
        $("#install_post_modal").on("submit", async (e) => {

            let form = $(e.target);
            e.preventDefault();
            if (AgentOrders.addresIsOut) {
                helper.alertMsg('Address Out of Service Area', 'This address appears to be outside the service area. Please verify that the address and pin location are correct.');
                return;
            }

            //Prevent submission if property is Under Construction and no plat map uploaded
            let platMapsCount = AgentOrders._files.filter(file => file._accessory_id == 'plat-map' || file.plat_map == true).length;
            const propertyType = $('[name="install_post_property_type"]').val();
            const propertyDesc = $('[name="install_post_property_type"] option:selected').text();
            if (platMapsCount == 0 && (propertyType == 2 || propertyType == 3)) {
                helper.alertError(`${propertyDesc} requires a platmap or image of property be attached before order can be completed.`);
                return false;
            }

            helper.showLoader();

            let fd = new FormData();
            fd.append("install_post_address", `${$("#address").val().replace(/[,]/g, '').trim()}, ${$("#city").val().replace(/[,]/g, '').trim()}, ${$("#state").val().replace(/[,]/g, '').trim()}`);
            fd.append("install_post_property_type", form.find(`[name="install_post_property_type"]`).val());
            fd.append("install_post_desired_date", form.find(`[name="install_post_desired_date"]:checked`).val());
            fd.append("install_post_custom_desired_date", form.find(`[name="install_post_custom_desired_date"]`).val());
            fd.append("install_post_office", AgentOrders.officeId);
            fd.append("install_post_agent", AgentOrders.agentId);
            fd.append("install_post_select_post", this.post);
            fd.append("install_post_select_sign", this.panel);
            fd.append("install_post_select_sign_type", this.panel_type);
            fd.append("install_post_select_accessories", JSON.stringify(this.accessories));
            fd.append("install_post_comment", form.find(`[name="install_post_comment"]`).val());
            fd.append("is_create", AgentOrders.createpost);
            fd.append("ignore_zone_fee", AgentOrders.ignoreZoneFee);
            fd.append("order_id", AgentOrders.order_id);
            this._files.forEach((file, index) => fd.append(`file${index}_${file._accessory_id}`, file));
            fd.append("install_post_rush_fee", form.find(`[name="install_post_rush_fee"]`).val());
            fd.append("install_post_signage", form.find(`[name="install_post_signage"]`).val());
            fd.append("install_post_zone_fee", form.find(`[name="install_post_zone_fee"]`).val());
            fd.append("install_marker_position", JSON.stringify(AgentOrders.marker_position));
            form.find(`[type="submit"]`).prop('disabled', true);
            form.find(`[type="submit"]`).html(`<strong class="text-white">SENDING...</strong>`);
            fd.append("zone_id", AgentOrders.zoneId);
            fd.append("total", AgentOrders.total);

            $.ajax({
                url: helper.getSiteUrl(`/install/post`),
                data: fd,
                type: "POST",
                contentType: false, // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
                processData: false, // NEEDED, DON'T OMIT THIS
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

                if (res.order.editOrder && !res.order.needPayment) {
                    window.location.reload();
                }

                if (res.order.needPayment) {
                    console.log(res);

                    let paymentModal = $("#payment_modal");
                    $(`[payment-total-amount]`).html(parseFloat(res.order.total).toFixed(2));
                    $(`[payment-card-name]`).val(res.billing.name);

                    $(`[billing-name]`).val(res.billing.name);
                    $(`[billing-address]`).val(res.billing.address);
                    $(`[billing-city]`).val(res.billing.city);
                    $(`[billing-state]`).val(res.billing.state);
                    $(`[billing-zip]`).val(res.billing.zipcode);

                    $('#use_another_card').prop('checked', true);
                    $(`.form-another-card input`).prop('disabled', false);
                    $('#card_profile_select').prop('disabled', true);

                    //Load agent cards
                    if (res.order.agent.user.authorizenet_profile_id) {
                        $('#use_card_profile').prop('checked', true);
                        $('#card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        Payment.loadCards($('#card_profile_select'), res.order.agent.user.id);
                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#use_another_card').prop('checked', true);
                        $('#use_card_profile').prop('checked', false);
                        $('#card_profile_select').prop('disabled', true);
                    }

                    //Load any office card visible to agent
                    Payment.loadOfficeCardsVisibleToAgent(
                        $('#card_profile_select'),
                        res.order.office.user.id,
                        () => {
                            $('#use_card_profile').prop('checked', true);
                            $('#card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#use_another_card').prop('checked', false);
                        }
                    );

                    helper.hideLoader('payment_modal');
                    /*if (paymentModal.length) {
                        paymentModal.modal()
                    }*/

                    paymentModal.find(`[name="order_id"]`).val(res.order.id)
                    let installModal = $("#install_post_modal");

                    if (installModal.length) installModal.modal('hide')
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

    async hasPendingOrderSameAddress(address, lat, lng, officeId, agentId, orderId = 0) {
        AgentOrders.ignoreZoneFee = false;
        officeId = officeId == '' ? 0 : officeId;

        address = encodeURIComponent(address);

        const checkAddress = await $.get(`
            ${helper.getSiteUrl()}/order/check/address/${address}/lat/${lat}/lng/${lng}/office/${officeId}/agent/${agentId}/order/${orderId}
        `);

        return checkAddress;
    },

    handleDuplicatedOrder() {
        $('#noDuplicateOrderBtn').on('click', ()=> {
            $(".modal").css({ "overflow-y": "scroll" });
            $('[name="install_post_address"]').val('');

            AgentOrders.ignoreZoneFee = false;

            $(`[name="install_post_zone_fee"]`).trigger('change');
        });

        $('#yesDuplicateOrderBtn').on('click', ()=> {
            $(".modal").css({ "overflow-y": "scroll" });
            AgentOrders.ignoreZoneFee = true;

            $(`[name="install_post_zone_fee"]`).trigger('change');
        });
    },

    setRushFee(value) {
        let rush_fee_input = $(`input[name="install_post_rush_fee"]`);
        rush_fee_input.val(value)
        rush_fee_input[0].dispatchEvent(new Event('change'))

        $('#rushFee').removeClass('d-none');
    },

    orderCancel(id) {
        helper.confirm('', "This action is irreversible!", () => {
            $.get(`/order/${id}/cancel`).done(res => {
                /*let tables = document.getAll(".ordersTable");
                tables.forEach((e, index) => {
                    window['orderTable' + index].api().draw();
                })

                Order.table.api().draw();*/

                window.location.reload();
            })
        });
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

    showNoticeModal() {

        let id = $('#noticeAcknowledgementId').val();

        $("#understoodButton").on('click', (event) => {
            $.ajax({
                url: `${helper.getSiteUrl()}/acknowledge/notice/${id}`,
                type: "POST",
                success: function () {},
                error: function (error) {},
            });
        });

        this.modal.modal('show');
    },

    tables: [],
    datatable() {
        let tables = document.getAll(".ordersTableOffice");
        tables.forEach((e, index) => {
            let table = $(e)
            if (table.length) {
                window['orderTableOffice' + index] = table.dataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    },
                    infoCallback: function( settings, start, end, max, total, pre ) {
                        return `Showing ${start} to ${end} of ${total} entries`;
                    },
                    pageLength: 10,
                    dom: "rtip",
                    ajax: helper.getSiteUrl("/datatable/agent/orders/status"),
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
                                if (d == AgentOrders.status_received) {
                                    return `<span class="badge badge-pill badge-primary">Received</span>`
                                } else if (d == AgentOrders.status_incomplete) {
                                    if (r.assigned_to > 0) {
                                        return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                                    } else {
                                        return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                                    }
                                } else if (d == AgentOrders.status_scheduled) {
                                    return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                                } else if (d == AgentOrders.status_completed) {
                                    return `<span class="badge badge-pill badge-success">Installed</span>`;
                                } else if (d == AgentOrders.status_cancelled) {
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
                                if (r.desired_date_type == AgentOrders.date.asap) {
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
                AgentOrders.tables.push(table);
            }
        })
    },

    showOrderEntries() {
        let selects = document.getAll(".showOrderOfficeEntries");
        selects.forEach(select => {
            select = $(select);
            select.on("change", (event) => {
                let selected = parseInt(event.target.value);
                AgentOrders.tables.forEach(table => {
                    table.api().context[0]._iDisplayLength = selected;
                    table.api().draw();
                })
            });

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

        //$("#loader_image").modal('show');
        AgentOrders.table = $(tableId).dataTable({
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            infoCallback: function( settings, start, end, max, total, pre ) {
                return `Showing ${start} to ${end} of ${total} entries`;
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/agent/orders/status/active"),
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
                        if (d == AgentOrders.status_received) {
                            return `<span class="badge badge-pill badge-primary">Received</span>`
                        } else if (d == AgentOrders.status_incomplete) {
                            if (r.assigned_to > 0) {
                                return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                            } else {
                                return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                            }
                        } else if (d == AgentOrders.status_scheduled) {
                            return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                        } else if (d == AgentOrders.status_completed) {
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
                        } else if (d == AgentOrders.status_cancelled) {
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
                        if (r.desired_date_type == AgentOrders.date.asap) {
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

                        if (r.status == AgentOrders.status_scheduled || r.status == AgentOrders.status_completed || r.status == AgentOrders.status_cancelled) {
                            return `<a class="link" onclick="window.viewDetails(${r.id}, '${r.order_type}')">
                                    <img src="${helper.getSiteUrl()}/images/ViewDetails_Icon.png" title="View Details" alt="View Details" class="width-px-40">
                                </a>`;
                        }

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

                        if (r.status != AgentOrders.status_cancelled) {
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

                            if (AgentOrders.userRole == 1) {
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

        AgentOrders.table = $(tableId).dataTable({
            retrieve: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
            },
            infoCallback: function( settings, start, end, max, total, pre ) {
                return `Showing ${start} to ${end} of ${total} entries`;
            },
            pageLength: 10,
            dom: "rtip",
            ajax: helper.getSiteUrl("/datatable/agent/orders/status/history"),
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
                        if (d == AgentOrders.status_received) {
                            return `<span class="badge badge-pill badge-primary">Received</span>`
                        } else if (d == AgentOrders.status_incomplete) {
                            if (r.assigned_to > 0) {
                                return `<span class="badge badge-pill badge-warning">Incomplete</span>`;
                            } else {
                                return `<span class="badge badge-pill badge-warning">Action Needed</span>`;
                            }
                        } else if (d == AgentOrders.status_scheduled) {
                            return `<span class="badge badge-pill badge-info">Scheduled</span>`;
                        } else if (d == AgentOrders.status_completed) {
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
                        } else if (d == AgentOrders.status_cancelled) {
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
                        if (r.desired_date_type == AgentOrders.date.asap) {
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

    searchOrderStatus() {
        $('body').on("keyup", '#searchOrders', (event) => {
            AgentOrders.table.fnFilter(event.target.value);
        });
    },

    showOrderStatusEntries() {
        $('body').on("change", '#showOrderStatusEntries', (event) => {
            const selected = parseInt(event.target.value);
            AgentOrders.table.api().context[0]._iDisplayLength = selected;
            AgentOrders.table.api().draw();
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

        if (status == AgentOrders.status_received) {
            statusDescription = "Received";
        }

        if (status == AgentOrders.status_incomplete) {
            statusDescription = "Action Needed";
        }

        if (status == AgentOrders.status_cancelled) {
            statusDescription = "Cancelled";
        }

        if (status == AgentOrders.status_scheduled) {
            statusDescription = "Scheduled";
        }

        if (status == AgentOrders.status_completed) {
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

    editOrder(id) {
        AgentOrders.ignoreZoneFee = false;

        helper.showLoader();
        let modal = $("#install_post_modal");
        if (modal.length) {
            $.get(`/get/order/` + id).done(order => {
                AgentOrders.resetInstallModalForm()
                AgentOrders.createpost = false;
                AgentOrders.order_id = order.id;
                AgentOrders.upload_accessory_file = null;
                AgentOrders._files = [];
                AgentOrders.setOfficeAndAgent(order)
                AgentOrders.setPropertyInfo(order)
                AgentOrders.setDate(order);
                AgentOrders.setComment(order)
                AgentOrders.setFilesEdit(order);
                AgentOrders.setFooter();
            })
        }
    },

    loadSavedAdjustments(adjustments) {
        const rowTmpl = $('#rowTmplInstallAdjustment').html();
        const rowContainer = $('#rowContainerInstallAdjustments');
        let totalAdjustments = 0;
        AgentOrders.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };

        rowContainer.empty();
        $.each(adjustments, (i, row)=> {
            AgentOrders.rowCount++;
            let newTmpl = rowTmpl.replace(/rowCount/g, AgentOrders.rowCount);
            rowContainer.append(newTmpl);

            $(`[name="install_price_adjustment_description[${AgentOrders.rowCount}]"]`).val(row.description);
            $(`[name="install_price_adjustment_charge[${AgentOrders.rowCount}]"]`).val(row.charge);
            $(`[name="install_price_adjustment_discount[${AgentOrders.rowCount}]"]`).val(row.discount);

            AgentOrders.pricingAdjustments['description'][i] = row.description;
            AgentOrders.pricingAdjustments['charge'][i] = row.charge;
            AgentOrders.pricingAdjustments['discount'][i] = row.discount;

            totalAdjustments = parseFloat(totalAdjustments) + parseFloat(row.charge);
            totalAdjustments = parseFloat(totalAdjustments) - parseFloat(row.discount);

            AgentOrders.totalAdjusted = totalAdjustments;

            if (AgentOrders.totalAdjusted < 0) {
                $('[install-post-adjustments]').html(`<span class="text-danger">- $${AgentOrders.totalAdjusted*(-1)}</span>`);
            } else {
                $('[install-post-adjustments]').html(`$${AgentOrders.totalAdjusted}`);
            }
        });

        AgentOrders.calculateAdjustments();
    },

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = AgentOrders.rowCount;

        for (let i=1; i <= totalRows; i++) {
            charge = $(`[name="install_price_adjustment_charge[${i}]"]`).val();
            discount = $(`[name="install_price_adjustment_discount[${i}]"]`).val();

            if (charge > 0) {
                totalAdjustments = parseFloat(totalAdjustments) + parseFloat(charge);
            }

            if (discount > 0) {
                totalAdjustments = parseFloat(totalAdjustments) - parseFloat(discount);
            }
        }

        AgentOrders.totalAdjusted = totalAdjustments;

        if (AgentOrders.totalAdjusted < 0) {
            $('[install-post-adjustments]').html(`<span class="text-danger">- $${AgentOrders.totalAdjusted*(-1)}</span>`);
        } else {
            $('[install-post-adjustments]').html(`$${AgentOrders.totalAdjusted}`);
        }

        $(`[name="install_post_zone_fee"]`).trigger('change');
    },

    setOfficeAndAgent(order) {
        AgentOrders.loadAgentInventory();

        setTimeout(() => {
            let selectagent = $(`[name="install_post_agent"]`);
            if (order.agent_id) {
                selectagent.find(`option[value="${order.agent_id}"]`).prop('selected', true)
                console.log(order.agent_id)
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
                            console.log(order);

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

                    AgentOrders.rowCount = 0;
                    if (order.adjustments) {
                        AgentOrders.loadSavedAdjustments(order.adjustments);
                    }

                    $(".modal").css({ "overflow-y": "scroll" });
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
                AgentOrders.ignoreZoneFee = true;
            }

            AgentOrders.findThePlace(order.address, {lat: Number(order.latitude), lng: Number(order.longitude)}, true);
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

            AgentOrders.setRushFee(order.rush_fee);
        } else {
            $(`[name="install_post_desired_date"][value="custom_date"]`).trigger('click')
            let d = helper.parseDate(order.desired_date)

            //Need to review this part. Why does it need setTimeout?
            setTimeout(()=>{
                datePicker.removeClass("d-none");
                //datePicker.datepicker("setDate", d);
                AgentOrders.updateCalendar(d);
                $(`[name="install_post_custom_desired_date"]`).val(order.desired_date)

                //Use this to prevent saved date from being cleared after updating map when editing order
                AgentOrders.savedServiceDate = d;
            }, 3000);

            $('#rushFee').addClass('d-none');
        }
    },

    setComment(order) {
        $(`[name="install_post_comment"]`).text(order.comment)
    },

    setFilesEdit(order) {
        $("#files_list").html(` `);
        order.files.forEach(file => {
            AgentOrders._files.push(file);
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
        });
    },

    onAddressChange() {
        $("#address").on("change", (event) => {
            let input = event.target;
            $(input).val($(input).val().replace(/[,]/g, '').trim());
            this.startMap();
            this.disableAllDates();
        });
    },

    onCityChange() {
        $("#city").on("change", (event) => {
            let input = event.target;
            $(input).val($(input).val().replace(/[,]/g, '').trim());
        });
    },

}

$(() => {
    AgentOrders.init();
});

// export default AgentOrders;
