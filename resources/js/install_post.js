import global from "./global";
import helper from "./helper";
import obs from '../../node_modules/observable-slim/observable-slim';
import Payment from "./Payment";
import { isEmpty } from "lodash";

//watch this object

let _obj_ = {
    zones: [],
}
let proxy = obs.create(_obj_, true, function (changes) {
    if (_obj_.zones.length == InstallPost.zones_count) {
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
            InstallPost.addresIsOut = false;
            $.get(helper.getSiteUrl(`/get/zone/settings`)).done(settings => {

                InstallPost._currentZone = lowest_zone_fee;
                InstallPost.movedNextMonth = false;
                InstallPost.updateCalendar(InstallPost.savedServiceDate, false);

                let fee = parseFloat(lowest_zone_fee.zone_fee || 0) * (settings.install / 100);

                if (InstallPost.ignoreZoneFee) {
                    $(`[install-post-zone-fee]`).html(`$0`);
                    $(`[name="install_post_zone_fee"]`).val(0);
                    $(`[name="install_post_zone_fee"]`).trigger('change');
                } else {
                    $(`[install-post-zone-fee]`).html(`$${fee.toFixed(2)}`);
                    $(`[name="install_post_zone_fee"]`).val(fee);
                    $(`[name="install_post_zone_fee"]`).get(0).dispatchEvent(new Event("change"));
                }

                InstallPost.zoneId = lowest_zone_fee.id;
            })
        } else {
            helper.alertMsg('Address Out of Service Area', 'This address appears to be outside the service area. Please verify that the address and pin location are correct.');
            InstallPost._currentZone = null;
            //InstallPost.updateCalendar();
            InstallPost.disableAllDates();
            InstallPost.addresIsOut = true;
            $(`[install-post-zone-fee]`).html("00.00");
            $(`[name="install_post_zone_fee"]`).val("00.00");
            $(`[name="install_post_zone_fee"]`).get(0).dispatchEvent(new Event("change"));
            $("[googlescript]").remove();
            // InstallPost.initMap()
            // console.log("reset map");
            helper.hideLoader('');

        }

    }
});
const InstallPost = {
    createpost: true,
    order_id: null,
    payment_method_office_pay: 3,
    payment_method_at_time_of_order: 1,
    zoneId: null,
    ignoreZoneFee: false,
    total:0,
    agentChangeCount: 0,
    savedServiceDate: '',

    init() {
        Payment.init()
        $('.open_install_post_modal').on('click', e => {
            //Display import order checkbox
            $('#import-order-div').show();

            let modal = $("#install_post_modal");
            InstallPost.resetInstallModalForm()

            $('#submitOrder').html('<strong class="text-white">SUBMIT INSTALL</strong>');

            modal.modal()
            InstallPost.createpost = true;
        })
        if (window.location.href.indexOf('routes') == -1) {
            this.initMap();
        }

        this.searchAddress();
        this.onLocationAdjustmentChange();
        this.onPostOfficeChange();
        window.onInstallPostSelectPostChange = this.onInstallPostSelectPostChange;
        window.onInstallPostSelectSignChange = this.onInstallPostSelectSignChange;
        window.onInstallPostSelectAccessoriesChange = this.onInstallPostSelectAccessoriesChange;
        window.accessoryClicked = this.accessoryClicked
        this.onInstallPostCommentChange();
        this.onFileUplaoded();
        window.removeFile = this.removeFile;
        this.totalFee();
        this.onDesiredDateChange();
        this.onAgentChange();
        this.installPostPropertyType();
        window.uploadAccessoryFile = this.uploadAccessoryFile;
        this.onSubmitForm();

        this.handleDuplicatedOrder();
        this.pricingAdjustment();

        this.onCityChange();

        $.get(helper.getSiteUrl('/get/holidays')).done(holidays => {
            InstallPost.holidays = holidays;
        })

        $(`[name="document_files[]"`).on('change', e => {
            let fd = new FormData()
            Object.values(e.target.files).forEach((file, index) => {
                fd.append('file' + index, file);
            })

            $.ajax({
                url: helper.getSiteUrl(`/store/douctment/accessory/${e.target.getAttribute('accessory_id')}`),
                data: fd,
                type: "POST",
                contentType: false, // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
                processData: false, // NEEDED, DON'T OMIT THIS
                cache: false,
            }).done(res => {
                $("#documentModal").find('form').trigger('reset')
                $("#documentModal").modal('hide')

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

            });

        })

        // var config = {
        //     attributes: true,
        //     childList: true,
        //     subtree: true
        // };

        // var observer = new MutationObserver((m, o) => {
        //     let mt = m[0];
        //     if ($("#accessories-names").get(0).childNodes.length) {
        //         $(".accessories-install-document-required-warning").addClass("d-block").removeClass('d-none');
        //     } else {
        //         $(".accessories-install-document-required-warning").addClass("d-none").removeClass('d-block');
        //     }
        // });


        // observer.observe($(".accessories-install-document-required-warning").get(0), config);


        let type = $(`[name="install_post_panel_type"]`)
        if (type.length) {
            type.on('change', e => {
                InstallPost.panel_type = e.target.value;
                $("#sign_image_preview").addClass('d-none').removeClass('d-block');
                // console.log(InstallPost.panel_type);
                $(`[name="install_post_panel"]`).prop('checked', false)
                /*$('.disable-layer').remove()
                $(`.list-container-signs`).append('<div class="disable-layer"><div>');*/

                //Remove any previously selected panel
                InstallPost.panel = '';
            })
        }

        if ($('.cc-number-input').length) {
            helper.cardNumberInput('.cc-number-input');
        }

        $("#uploadOtherDoc").on('click', e => {
            InstallPost.upload_accessory_file = null
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

        })


    },

    setRushFee(value) {
        let rush_fee_input = $(`input[name="install_post_rush_fee"]`);
        rush_fee_input.val(value)
        rush_fee_input[0].dispatchEvent(new Event('change'))

        $('#rushFee').removeClass('d-none');
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
        InstallPost.prices_obj = { post: 0, panels: 0, accessories: 0 }
        $("[install-post-adjustments]").html("$0.00");
        InstallPost.totalAdjusted = 0;
        InstallPost.rowCount = 0;
        InstallPost.getSignageFee();
        InstallPost.ignoreZoneFee = false;
        InstallPost.accessories = [];
        InstallPost.post = '';
        InstallPost.panel = '';
        InstallPost.panel_type = '';
        InstallPost.savedServiceDate = '';

        InstallPost.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
    },

    resetInstallFormKeepOfficeAgent() {
        let modal = $("#install_post_modal");
        modal.find(':input, select').each((i, el) => {
            if (
                el.name != 'install_post_agent'
                && el.name != 'install_post_office'
                && el.name != 'install_post_desired_date'
                && el.name != 'install_post_panel_type'
                && el.name != 'install_post_state'
            ) {
                el.value = '';
            }
        });

        $(`[name="install_post_signage"]`).val(0)
        $(`[name="install_post_zone_fee"]`).val(0)
        $(`[name="install_post_rush_fee"]`).val(0)

        $("#install-post-search-map").html(' ')
        $("#files_list").html(' ')
        $("#warning-alerts").html(' ');
        this.startMap()
        $(".list-container-posts,.list-container-signs,.list-container-accessories-install").html('')
        $(".order-preview-imgs,.accessory_image_preview").addClass('d-none').removeClass('d-block')
        $('[name="install_post_comment"]').val('');
        $('[name="install_post_comment"]').text('');
        this.disableAllDates()

        InstallPost.prices_obj = { post: 0, panels: 0, accessories: 0 }
        $("[install-post-adjustments]").html("$0.00");
        InstallPost.totalAdjusted = 0;
        InstallPost.rowCount = 0;
        InstallPost.getSignageFee();
        InstallPost.ignoreZoneFee = false;
        InstallPost.accessories = [];
        InstallPost.post = '';
        InstallPost.panel = '';
        InstallPost.panel_type = '';

        InstallPost.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
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
            e.stopImmediatePropagation();

           let form = $(e.target);
            e.preventDefault();
            if (InstallPost.addresIsOut) {
                helper.alertMsg('Address Out of Service Area', 'This address appears to be outside the service area. Please verify that the address and pin location are correct.');
                return;
            }

            //Prevent submission if property is Under Construction and no plat map uploaded
            let platMapsCount = InstallPost._files.filter(file => file._accessory_id == 'plat-map' || file.plat_map == true).length;
            const propertyType = $('[name="install_post_property_type"]').val();
            const propertyDesc = $('[name="install_post_property_type"] option:selected').text();
            if (platMapsCount == 0 && (propertyType == 2 || propertyType == 3)) {
                helper.alertError(`${propertyDesc} requires a platmap or image of property be attached before order can be completed.`);
                return false;
            }

            helper.showLoader();

            let fd = new FormData();

            const street = $('#install_post_modal').find('#address').val();
            const city = $('#install_post_modal').find('#city').val();
            const state = $('#install_post_modal').find('#state').val();
            const address = `${street.replace(/[,]/g, '').trim()}, ${city.replace(/[,]/g, '').trim()}, ${state.replace(/[,]/g, '').trim()}`;
            fd.append("install_post_address", address);

            fd.append("install_post_property_type", form.find(`[name="install_post_property_type"]`).val());
            fd.append("install_post_desired_date", form.find(`[name="install_post_desired_date"]:checked`).val());
            fd.append("install_post_custom_desired_date", form.find(`[name="install_post_custom_desired_date"]`).val());
            fd.append("install_post_office", form.find(`[name="install_post_office"]`).val());
            fd.append("install_post_agent", form.find(`[name="install_post_agent"]`).val());
            fd.append("install_post_select_post", this.post);
            fd.append("install_post_select_sign", this.panel);
            fd.append("install_post_select_sign_type", this.panel_type);
            fd.append("install_post_select_accessories", JSON.stringify(this.accessories));
            fd.append("install_post_comment", form.find(`[name="install_post_comment"]`).val());
            fd.append("is_create", InstallPost.createpost);
            fd.append("ignore_zone_fee", InstallPost.ignoreZoneFee);
            fd.append("order_id", InstallPost.order_id);
            this._files.forEach((file, index) => fd.append(`file${index}_${file._accessory_id}`, file));
            fd.append("install_post_rush_fee", form.find(`[name="install_post_rush_fee"]`).val());
            fd.append("install_post_signage", form.find(`[name="install_post_signage"]`).val());
            fd.append("install_post_zone_fee", form.find(`[name="install_post_zone_fee"]`).val());
            fd.append("install_marker_position", JSON.stringify(InstallPost.marker_position));
            form.find(`[type="submit"]`).prop('disabled', true);
            form.find(`[type="submit"]`).html(`<strong class="text-white">SENDING...</strong>`);
            fd.append("zone_id", InstallPost.zoneId);
            fd.append("pricingAdjustments", JSON.stringify(InstallPost.pricingAdjustments));
            fd.append("total", InstallPost.total);
            fd.append("imported_order", $("#imported_order").prop('checked') ? 1 : 0);

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
                    //console.log(res);

                    let paymentModal = $("#payment_modal");
                    $(`[payment-total-amount]`).html(parseFloat(res.order.total).toFixed(2));

                    $(`[billing-name]`).val(res.billing.name);
                    $(`[billing-address]`).val(res.billing.address);
                    $(`[billing-city]`).val(res.billing.city);
                    $(`[billing-state]`).val(res.billing.state);
                    $(`[billing-zip]`).val(res.billing.zipcode);

                    $('#use_another_card').prop('checked', true);
                    $(`.form-another-card input`).prop('disabled', false);
                    $('#card_profile_select').prop('disabled', true);

                    //If user has card on file then enable Use Cards on File. Otherwise enable Enter Another Card
                    if (res.order.office.user.authorizenet_profile_id) {
                        $('#use_card_profile').prop('checked', true);
                        $('#card_profile_select').prop('disabled', false);
                        $(`.form-another-card input`).prop('disabled', true);
                        $('#use_another_card').prop('checked', false);

                        //Load cards in dropdown
                        if (!res.order.agent) {
                            Payment.loadCards($('#card_profile_select'), res.order.office.user.id);
                        } else {
                            //Load any office card visible to agent
                            Payment.loadOfficeCardsVisibleToAgent(
                                $('#card_profile_select'),
                                res.order.office.user.id
                            );
                        }
                    } else {
                        $(`.form-another-card input`).prop('disabled', false);
                        $('#use_another_card').prop('checked', true);
                        $('#use_card_profile').prop('checked', false);
                        $('#card_profile_select').prop('disabled', true);
                    }

                    //Load any saved card for agent
                    if (res.order.agent) {
                        if (res.order.agent.user.authorizenet_profile_id) {
                            $('#use_card_profile').prop('checked', true);
                            $('#card_profile_select').prop('disabled', false);
                            $(`.form-another-card input`).prop('disabled', true);
                            $('#use_another_card').prop('checked', false);

                            //Load cards in dropdown
                            Payment.loadCards($('#card_profile_select'), res.order.agent.user.id);
                        }
                    }

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
                    InstallPost.setRushFee(0)
                    datepicker.removeClass("d-none");
                    $('#rushFee').addClass('d-none');
                } else {
                    //if (InstallPost.createpost) $("#rushOrderModal").modal();
                    $("#rushOrderModal").modal();
                    datepicker.addClass("d-none");
                }
            };
        });
    },
    disableAllDates() {
        $("#selectdate_custom_date").datepicker("destroy");
        $("#selectdate_custom_date").datepicker({
            beforeShowDay: function (date) { return [false] }
        })
    },

    //NOTE: savedDate = Date() instance
    movedNextMonth: false,
    updateCalendar(savedDate, dateClicked = false) {
        //console.log(savedDate)
        $("#selectdate_custom_date").datepicker("destroy");
        $("#selectdate_custom_date").datepicker({
            onSelect: function (dateText) {
                //NOTE:dateText = mm/dd/yyyy
                //console.log(dateText)
                $(`[name="install_post_custom_desired_date"]`).val(dateText);
                return InstallPost.updateCalendar(helper.parseUSDate(dateText), true);
            },
            beforeShowDay: function (date) {
                //NOTE: date = Date() instance

                let dateString = helper.getDateStringUsa(date);

                //Disable past dates and current date
                let today = helper.getDateStringUsa(new Date());
                let daysdiff = helper.diffDays(dateString, today);
                let cdate = dateString;

                //Superadmin can always select today's date
                if (today == dateString) {
                    return [true];
                }

                //holidays
                //console.log(dateString);
                if (InstallPost.holidays.includes(dateString)) {
                    return [false];
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

                //Get  days of operation for the zone
                if (InstallPost._currentZone) {
                    let zone = InstallPost._currentZone;
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
            if (! InstallPost.createpost) {
                if (! dateClicked) {
                    setTimeout(() => {
                        const usDate = helper.getDateStringUsa(savedDate);
                        $("#selectdate_custom_date").datepicker('setDate', usDate);
                    }, 2000);
                } else {
                    const usDate = helper.getDateStringUsa(savedDate);
                    $("#selectdate_custom_date").datepicker('setDate', usDate);
                }
            } else {
                const usDate = helper.getDateStringUsa(savedDate);
                $("#selectdate_custom_date").datepicker('setDate', usDate);
            }
        } else {
            //Move calendar to next month if today is the last day of the month
            let currDate = new Date();
            if (helper.isLastDayOfMonth(currDate) && !InstallPost.movedNextMonth && InstallPost.createpost) {
                InstallPost.movedNextMonth = true;
                setTimeout(() => {
                    $('#selectdate_custom_date .ui-datepicker-next').trigger("click");
                }, 3000);
            }

            $("#selectdate_custom_date").find('a.ui-state-active').removeClass('ui-state-active')
            .removeClass('ui-state-highlight').removeClass('ui-state-hover');
        }
    },

    _files: [],
    onFileUplaoded() {
        let files = $(`input[name="install_post_files[]"]`);
        if (files.length) {
            files.on("change", (e) => {
                let file_input = e.target;
                let files = file_input.files;
                let accessory_id = InstallPost.upload_accessory_file;
                for (let file of files) {
                    file._id = this.genId();
                    file._accessory_id = accessory_id;
                    InstallPost._files.push(file);
                    InstallPost.displayFiles(this._files);
                    InstallPost.setFiles(this._files);
                    if (accessory_id != "plat-map") {
                        $(`[install_accessory_name_id_${accessory_id}]`).addClass('d-none').removeClass('d-block');
                    } else {
                        $(`.install-document-required-warning`).addClass('d-none').removeClass('d-block');
                    }
                    // console.log(InstallPost);
                    // hide warning alerts
                    // if (this._files.length) {
                    //     $("#attachments .alert").addClass('d-none').removeClass('d-block');
                    // } else {
                    //     $("#attachments .alert").addClass('d-block').removeClass('d-none');
                    // }
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
                        <a class='text-danger c-p' onclick="window.removeFile('${file._id}')">
                        <strong>REMOVE</strong></a>
                    </span>
                </li>`);
            });
        }
    },
    async removeFile(id) {
        let removed_file = await InstallPost._files.filter((file) => file._id == id);
        let new_files = await InstallPost._files.filter((file) => file._id != id);
        InstallPost._files = new_files;
        InstallPost.setFiles(InstallPost._files);
        InstallPost.displayFiles(InstallPost._files);

        // Show plat maps warning if last plat map image was removed
        let platMapsCount = InstallPost._files.filter(file => file._accessory_id == 'plat-map').length;
        const propertyType = $('[name="install_post_property_type"]').val();
        if (platMapsCount == 0 && (propertyType == 2 || propertyType == 3)) {
            $(`.install-document-required-warning`).addClass('d-block').removeClass('d-none');
        }

        // Show accessories warning if last accessory image was removed
        let accessoriesCount = InstallPost._files.filter(file => file._accessory_id != 'plat-map').length;
        if (accessoriesCount == 0) {
            $(`.accessories-install-document-required-warning`).addClass('d-block').removeClass('d-none');
            $(`[install_accessory_name_id_${removed_file[0]._accessory_id}]`).addClass('d-block').removeClass('d-none');
        }

        if (!InstallPost._files.length) {
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
        InstallPost.prices_obj.post = parseFloat(select.data('price'));
        InstallPost.post = select.val();
        let first_image = $(".install-post-preview-images").find("#post_image_preview");
        first_image.removeClass('d-none').addClass('d-block');
        first_image.get(0).src = image;
        InstallPost.getSignageFee();

    },
    onInstallPostSelectSignChange(e) {
        $(`[name="install_post_panel_type"]`).prop('checked', false);
        InstallPost.prices_obj.panels = parseFloat(e.target.dataset.price);
        let image = e.target.dataset.image;
        InstallPost.panel = e.target.value;
        let first_image = $(".install-post-preview-images").find("#sign_image_preview");
        first_image.removeClass('d-none').addClass('d-block');
        first_image.get(0).src = image;
        InstallPost.getSignageFee();

        //Remove any previously selected panel type
        InstallPost.panel_type = '';
    },
    selected_arr: [],
    upload_accessory_file: null,
    uploadAccessoryFile(id) {
        InstallPost.upload_accessory_file = id;
    },
    onInstallPostSelectAccessoriesChange(e) {
        let accessory_input = `[name="install_post_accessories[]"]`;
        if (!InstallPost.selected_arr.includes(e.target.value)) {
            e.target.setAttribute('checked', true);
            InstallPost.prices_obj.accessories += parseFloat(e.target.dataset.price);
            InstallPost.selected_arr.push(e.target.value);
            InstallPost.accessories = InstallPost.selected_arr;

        } else {
            InstallPost.selected_arr = InstallPost.selected_arr.filter(a => a != e.target.value);
            InstallPost.accessories = InstallPost.selected_arr;
            InstallPost.prices_obj.accessories -= parseFloat(e.target.dataset.price);
        }
        if (InstallPost.prices_obj.accessories < 0) {
            InstallPost.prices_obj.accessories = 0;
        }
        InstallPost.getSignageFee();
        let images_container = $(".install-post-preview-images");
        if (images_container.length) {
            if (!InstallPost.selected_arr.length) {
                images_container.find('.accessory_image_preview').remove();
                // images_container.append(`<img class="max-width-125px max-height-113px accessory_image_preview" src="${helper.getSiteUrl('/private/image/accessory/0')}" />`)
                return;
            }
            images_container.find('.accessory_image_preview').remove();
            InstallPost.selected_arr.forEach(id => {
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
            //console.log(self.val())
            $.get(helper.getSiteUrl(`/get/accessory/${self.val()}/json`)).done(accessory => {
                if (InstallPost.createpost) {
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

    onPostOfficeChange() {
        $('.order-preview-imgs').hide();
        let input = $(`[name="install_post_office"]`);
        if (input.length) {
            input.on("change", async (e) => {
                let value = e.target.value;

                InstallPost.resetFeesOnOfficeAgentChange();

                $(".list-container-posts").empty();
                $(".list-container-signs").empty();
                $(".list-container-accessories-install").empty();

                InstallPost.hidePreviewsImages()
                // console.log(value);
                if (value.trim()) {
                    $(".options-posts,.options-signs,.options-accessories").removeClass('d-none')
                    $('.order-preview-imgs').show();
                } else {
                    $(".options-posts,.options-signs,.options-accessories").addClass('d-none')
                    $('.order-preview-imgs').hide();
                };
                if (!value) {
                    $(`[name="install_post_agent"]`).html(" ")
                    return;
                }

                let agents = await this.getAgent(value);
                if (!Array.isArray(agents)) {
                    agents = Object.values(agents);
                }
                let agentsInput = $(`[name="install_post_agent"]`);
                agentsInput.html("");
                agentsInput.append(window.e('option', { value: "", htmlContent: "Select Agent" }))
                agents.forEach((agent) => {
                    agentsInput.append(window.e("option", { value: agent.id, htmlContent: agent.user.lastNameFirstName, }));
                });

                //
                $(`[name="install_post_panel_type"]`).prop('checked', false)
                $.get(helper.getSiteUrl(`/get/office/${value}/posts`)).done(res => {
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
                $.get(helper.getSiteUrl(`/get/office/${value}/panels`)).done(res => {
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
                $.get(helper.getSiteUrl(`/get/office/${value}/accessories`)).done(res => {
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

            });
        }
    },
    hidePreviewsImages() {
        $("#post_image_preview,#sign_image_preview").hide().attr("src", '');
        $(".accessory_image_preview").remove()
        InstallPost.selected_arr = [];
        // $("#accessories-names").html(" ")
        // $(".accessories-install-document-required-warning").removeClass('d-block').addClass('d-none')
    },
    onAgentChange() {
        let agentsInput = $(`[name="install_post_agent"]`);
        agentsInput.on('change', e => {
            const self = $(e.target);

            //If creating order then increment agentChangeCount to force reset
            if (InstallPost.createpost) {
                InstallPost.agentChangeCount++;
            }

            if (InstallPost.agentChangeCount > 0) {
                InstallPost.resetInstallFormKeepOfficeAgent();
            }

            InstallPost.resetFeesOnOfficeAgentChange();

            $(`[name="install_post_panel_type"]`).prop('checked', false)
            InstallPost.hidePreviewsImages();

            $(".list-container-posts").empty();
            $(".list-container-signs").empty();
            $(".list-container-accessories-install").empty();

            //If no agent selected then trigger office change to update inventory list
            if (!self.val()) {
                $(`[name="install_post_office"]`).trigger('change');
            }

            // $("#sign_image_preview").show().attr('src', helper.getSiteUrl(`/private/image/panel/0`));
            $.get(helper.getSiteUrl(`/get/agent/${self.val()}/posts`)).done(res => {
                if (!Array.isArray(res)) res = Object.values(res);
                let listContainer = $(".list-container-posts");
                listContainer.empty();
                let html = '';
                res.forEach(post => {
                    html += `
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
            $.get(helper.getSiteUrl(`/get/agent/${self.val()}/panels`)).done(res => {
                if (!Array.isArray(res)) res = Object.values(res);
                let listContainer = $(".list-container-signs");
                listContainer.empty();
                let html = '';
                res.forEach(panel => {
                    html += `
                    <div class="form-check d-flex justify-content-between">
                        <input type="radio" onchange="window.onInstallPostSelectSignChange(event)" name="install_post_panel" value="${panel.id}" data-price="${panel.price}" data-image="${helper.getSiteUrl(`/private/image/panel/${panel.image_path}`)}" class="form-check-input" id="install_panel_option_${panel.id}">
                        <label class="form-check-label text-dark" for="install_panel_option_${panel.id}">${panel.panel_name}</label>
                        <span price="">$${panel.price}</span>
                    </div>
                    `;
                })

                listContainer.html(html);
            });
            $.get(helper.getSiteUrl(`/get/agent/${self.val()}/accessories`)).done(res => {
                if (!Array.isArray(res)) res = Object.values(res);
                let listContainer = $(".list-container-accessories-install");
                listContainer.empty();
                let html = '';
                res.forEach(a => {
                    html += `
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
                   `;
                })
                listContainer.html(html)

                // reset messages
                $("#warning-alerts").html(' ');
            });

            InstallPost.agentChangeCount++;
        })
    }
    ,
    async getAgent(office) {
        return await $.get(helper.getSiteUrl(`/office/${office}/agents/order/by/name/json`));
    },
    googleKey: global.googleKey,
    initMap() {
        window.initMap = this.startMap;
        const src = `https://maps.googleapis.com/maps/api/js?key=${InstallPost.googleKey}&callback=window.initMap&libraries=drawing,geometry,places&v=weekly`;
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
    marker_position: null,
    searchAddress() {
        // let input = $("#address");
        let updateMapBtn = $("#updateMap");
        // if (input.length) {
        //     input.on("keyup", async (e) => {
        //         if (e.key === "Enter") {

        //             InstallPost.marker_position = null
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

        //             if ($(`[name="install_location_adjustment"]`).is(':checked')) {
        //                 $(`[name="install_location_adjustment"]`).trigger('click');
        //             }

        //             InstallPost.movedNextMonth = true;
        //             InstallPost.updateCalendar(false);
        //         }
        //     })
        // }
        if (updateMapBtn.length) {
            updateMapBtn.on("click", async (e) => {
                InstallPost.marker_position = null

                //search input value
                // let address = input.val();
                const street = $('#install_post_modal').find('#address').val();
                const city = $('#install_post_modal').find('#city').val();
                const state = $('#install_post_modal').find('#state').val();

                if (isEmpty(street) || isEmpty(city) || isEmpty(state)) {
                    helper.alertError('Please enter property address.');
                    return false;
                }

                const address = `${street.replace(/[,]/g, '').trim()}, ${city.replace(/[,]/g, '').trim()}, ${state.replace(/[,]/g, '').trim()}`;

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

                InstallPost.movedNextMonth = true;
                InstallPost.updateCalendar(InstallPost.savedServiceDate, false);
            });
        }
        this.onAddressChange();
    },

    async hasPendingOrderSameAddress(address, lat, lng, officeId, agentId, orderId = 0) {
        InstallPost.ignoreZoneFee = false;

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

            InstallPost.ignoreZoneFee = false;

            $(`[name="install_post_zone_fee"]`).trigger('change');
        });

        $('#yesDuplicateOrderBtn').on('click', ()=> {
            $(".modal").css({ "overflow-y": "scroll" });
            InstallPost.ignoreZoneFee = true;

            $(`[name="install_post_zone_fee"]`).trigger('change');
        });
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
                            //console.log(e)
                            let lat = e.latLng.lat()
                            let lng = e.latLng.lng()
                            InstallPost.marker_position = { lat, lng };

                            //At the end of drag event it should detect the new location and run query on the new location
                            //The variable $query should have the new address/location instead of the input value

                            InstallPost.findThePlace(results[0].formatted_address, InstallPost.marker_position, false, false, false)
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
            if (! InstallPost.createpost) {
                orderId = InstallPost.order_id;
            }
            const officeId = $(`[name="install_post_office"]`).val();
            const agentId = $(`[name="install_post_agent"]`).val() || 0;
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
                    InstallPost.addresIsOut = true;
                    helper.alertError('Address not found. Please verify property address is correct and move the marker to the correct property location on the map.')
                    //console.log("ADDESS NOT FOUND");
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
                    InstallPost.disableAllDates();

                    window.addressMarker.addListener('dragend', (e) => {
                        // Call Maps reverse geocoding to get the new address on dragend, otherwise it will take the same address as previous and marker will get back to its previous position
                        geocoder.geocode({ location: e.latLng }, (results, status) => {
                            // Process if geocoder succeed
                            if (status === "OK") {
                                // Proceed if there's at least on e location
                                if (results[0]) {
                                    //console.log(e)
                                    let lat = e.latLng.lat()
                                    let lng = e.latLng.lng()
                                    InstallPost.marker_position = { lat, lng };

                                    //At the end of drag event it should detect the new location and run query on the new location
                                    //The variable $query should have the new address/location instead of the input value

                                    InstallPost.findThePlace(results[0].formatted_address, InstallPost.marker_position, false, false, false)
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
                    InstallPost.marker_position = marker_position ? marker_position : { lat, lng };

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
                        //console.log(e)
                        let lat = e.latLng.lat()
                        let lng = e.latLng.lng()
                        InstallPost.marker_position = { lat, lng };


                        //At the end of drag event it should detect the new location and run query on the new location
                        //The variable $query should have the new address/location instead of the input value

                        InstallPost.findThePlace(query, InstallPost.marker_position, false, false, false)
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
                    if (! InstallPost.createpost) {
                        orderId = InstallPost.order_id;
                    }
                    const officeId = $(`[name="install_post_office"]`).val();
                    const agentId = $(`[name="install_post_agent"]`).val() || 0;

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
            if (InstallPost.ignoreZoneFee) {
                $(`[name="install_post_zone_fee"]`).val(0);
                $(`[install-post-zone-fee]`).html(`$0`);
                zone_fee = 0;
            }

            let signage_fee = $(`[name="install_post_signage"]`).val();
            let rush_fee = e.target.value;
            let total = parseFloat(zone_fee) + parseFloat(signage_fee) + parseFloat(rush_fee) + parseFloat(InstallPost.totalAdjusted);
            InstallPost.total = total;
            $(`[install-post-total]`).html(`$${total.toFixed(2)}`);
        });
        $(`[name="install_post_signage"]`).on("change", (e) => {
            let zone_fee = $(`[name="install_post_zone_fee"]`).val();
            if (InstallPost.ignoreZoneFee) {
                $(`[install-post-zone-fee]`).html(`$0`);
                $(`[name="install_post_zone_fee"]`).val(0);
                zone_fee = 0;
            }
            let rush_fee = $(`[name="install_post_rush_fee"]`).val();
            let signage_fee = e.target.value;
            let total = parseFloat(zone_fee) + parseFloat(signage_fee) + parseFloat(rush_fee) + parseFloat(InstallPost.totalAdjusted);
            InstallPost.total = total;
            $(`[install-post-total]`).html(`$${total.toFixed(2)}`);
        });
        $(`[name="install_post_zone_fee"]`).on("change", (e) => {
            let signage_fee = $(`[name="install_post_signage"]`).val();
            let rush_fee = $(`[name="install_post_rush_fee"]`).val();
            let zone_fee = e.target.value;
            if (InstallPost.ignoreZoneFee) {
                $(`[install-post-zone-fee]`).html(`$0`);
                $(`[name="install_post_zone_fee"]`).val(0);
                zone_fee = 0;
            }
            let total = parseFloat(zone_fee) + parseFloat(signage_fee) + parseFloat(rush_fee) + parseFloat(InstallPost.totalAdjusted);
            InstallPost.total = total;
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
        InstallPost.zones_count = zones.length;
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
        let checkbox = $(`[name="install_location_adjustment"]`);
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

    pricingAdjustments: {
        description: [],
        charge: [],
        discount: []
    },
    totalAdjusted: 0,
    rowCount: 0,
    pricingAdjustment() {
        const rowTmpl = $('#rowTmplInstallAdjustment').html();
        const rowContainer = $('#rowContainerInstallAdjustments');

        $('#openInstallPriceAdjustmentModalBtn').on('click', ()=> {
            if (InstallPost.rowCount == 0) {
                InstallPost.rowCount++;
                let newTmpl = rowTmpl.replace(/rowCount/g, InstallPost.rowCount);
                rowContainer.empty().append(newTmpl);
            }

            helper.openModal('installPriceAdjustmentModal');
        });

        $('#closeInstallPriceAdjustmentModalBtn').on('click', ()=> {
            helper.closeModal('installPriceAdjustmentModal');
        });

        $('#addAdjustmentInstallBtn').on('click', ()=> {
            InstallPost.rowCount++;

            let newTmpl = rowTmpl.replace(/rowCount/g, InstallPost.rowCount);
            rowContainer.append(newTmpl);
        });

        $('body').on('click', '.remove-price-adjustment-row', (e)=> {
            const self = $(e.target);

            self.closest('.row').remove();

            //InstallPost.rowCount--;
        });

        $('#savePricingAdjustmentInstallBtn').on('click', ()=> {
            let hasError = false;
            let message = '';
            let totalRows = InstallPost.rowCount;
            let description;
            let charge;
            let discount;
            InstallPost.pricingAdjustments = {
                description: [],
                charge: [],
                discount: []
            };

            for (let i=1; i <= totalRows; i++) {
                if ($(`[name="install_price_adjustment_description[${i}]"]`).length) {
                    description = $(`[name="install_price_adjustment_description[${i}]"]`).val();
                    charge = parseFloat($(`[name="install_price_adjustment_charge[${i}]"]`).val()) || 0;
                    discount = parseFloat($(`[name="install_price_adjustment_discount[${i}]"]`).val()) || 0;

                    if ( ! description && (charge || discount)) {
                        message = 'Please provide description.';
                        hasError = true;
                    }

                    if ( ! description && ! charge && ! discount) {
                        message = 'Please fill out all fields.';
                        hasError = true;
                    }

                    InstallPost.pricingAdjustments['description'][i] = description;
                    InstallPost.pricingAdjustments['charge'][i] = charge;
                    InstallPost.pricingAdjustments['discount'][i] = discount;
                }
            }

            if (hasError) {
                helper.alertError(message);
                return false;
            }

            InstallPost.calculateAdjustments();
            helper.closeModal('installPriceAdjustmentModal');
        });
    },

    calculateAdjustments() {
        let totalAdjustments = 0;
        let charge;
        let discount;
        let totalRows = InstallPost.rowCount;

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

        InstallPost.totalAdjusted = totalAdjustments;

        if (InstallPost.totalAdjusted < 0) {
            $('[install-post-adjustments]').html(`<span class="text-danger">- $${InstallPost.totalAdjusted*(-1)}</span>`);
        } else {
            $('[install-post-adjustments]').html(`$${InstallPost.totalAdjusted}`);
        }

        $(`[name="install_post_zone_fee"]`).trigger('change');
    },

    resetFeesOnOfficeAgentChange() {
        InstallPost.prices_obj = { post: 0, panels: 0, accessories: 0 }
        $("[install-post-adjustments]").html("$0.00");
        InstallPost.totalAdjusted = 0;
        InstallPost.rowCount = 0;
        //InstallPost.getSignageFee();
        InstallPost.ignoreZoneFee = false;

        InstallPost.pricingAdjustments = {
            description: [],
            charge: [],
            discount: []
        };
        InstallPost.getSignageFee();
    },

    onAddressChange() {
        $('#install_post_modal').find('#address').on("change", (event) => {
            let input = event.target;
            $(input).val($(input).val().replace(/[,]/g, '').trim());
            this.startMap();
            this.disableAllDates();
        });
    },

    onCityChange() {
        $('#install_post_modal').find('#city').on("change", (event) => {
            let input = event.target;
            $(input).val($(input).val().replace(/[,]/g, '').trim());
        });
    },
};

export default InstallPost;
