import helper from "./helper";
import User from "./user";
import Agent from "./agent";
import _ from "lodash";

let office = {
    officeId: null,
    officeEmail: null,
    officeAgentId: null,

    init() {
        this.watchLogoImageInput();
        window.editOfficeModal = this.editOfficeModal;
        window.manageCardsModal = this.manageCardsModal;
        this.removeCardButton();
        window.editOfficeAgentModal = this.editOfficeAgentModal;
        window.noteOfficeModal = this.noteOfficeModal;
        window.viewAgents = this.viewAgents;
        window.viewOrders = this.viewOrders;
        window.addAgent = this.addAgent;
        window.updatePaymentMethodOffice = this.updatePaymentMethodOffice;
        window.updatePaymentMethodOfficeAgent = this.updatePaymentMethodOfficeAgent;
        if (window.isHaveErrorCreateOfficeFormModel) {
            $("#createOfficeFormModal").modal();
        }
        window.disconnectOfficeAgent = this.disconnectOfficeAgent;
        this.officeSearchInput();
        this.showOfficesEntries();
        this.destroyAllOffices();
        this.officeType();
        this.changeOfficePassword();
        this.onAdditionalSettingsClick();
        window.onNotificationClick = this.onNotificationClick;
        this.onNewEmailSubmit();
        window.removeEmail = this.removeEmail;
        this.onClosePasswordSettingsModal();
    },

    officeType() {
        let select = $("#officeType");
        if (select.length) {
            select.on("change", () => {
                let inactive = select.val();
                let dt = window.officeDataTable;
                dt.api().column(0).search(inactive).draw();
            });
        }
    },
    noteOfficeModal(id) {
        let modal = $("#noteOfficeFormModal");
        let form = $("#noteOfficeForm");
        if (modal.length) {
            $.get(helper.getSiteUrl(`/get/office/${id}`))
                .done((office) => {
                    form.attr(`action`,helper.getSiteUrl(`/office/${office.id}/create/note`));
                    modal.find(`[name='note']`).text(office.note);
                    $("[note-office-name]").text(office.name)
                    modal.modal();
                })
                .fail((res) => {
                    console.log(res);
                });
        }
    },
    updatePaymentMethodOffice(office, payment_method) {
        $.post(helper.getSiteUrl(`/office/${office}/update/payment/method`), {
            payment_method,
        })
            .done((res) => { })
            .fail((res) => {
                console.log(res);
            });
    },
    addAgent(id) {
        let modal = $("#createAgentFormModal");
        if (modal.length) {
            let option = modal
                .find('[name="agent_office"]')
                .find(`option[value="${id}"]`);
            if (option.length) {
                // select office
                option.prop("selected", true);
                //open modal
                modal.modal();
            }
        } else {
            console.error("#createAgentFormModal does not exist");
        }
    },
    viewAgents(id, inactive) {

        $.get(helper.getSiteUrl(`/get/office/${id}`)).done(res => {
            if ($("#agentSearchInput").length) $("#agentSearchInput").val(res.name);
            // let agentType = $("#agentType");
            // if (agentType.length) {
            //     agentType.val(inactive);
            //     agentType.get(0).dispatchEvent(new Event("change"));
            // }
            if (window.agentDataTable) {
                // Added agent office column index to only filter by agent office
                window.agentDataTable.fnFilter(res.name, 8);
                $("#pills-agents-tab").trigger("click");
            } else {
                console.error("Agent datatable does does not exist");
            }
        })
    },
    destroyAllOffices() {
        let button = $("#destroyAllOffices");
        if (button.length) {
            button.on("click", () => {
                helper.confirm(
                    "",
                    "",
                    //on click Yes
                    () => {
                        //delete end point
                        let deletePath = helper.getSiteUrl(
                            "/offices/delete/all"
                        );
                        let href = $.post(deletePath)
                            .done((res) => {
                                if (res.type === "success") {
                                    //rerender dataTable
                                    window.officeDataTable.api().draw();
                                }
                            })
                            .fail((res) => {
                                console.error(res);
                            });
                    },
                    //on click cancel
                    () => ""
                );
            });
        }
    },
    officeSearchInput() {
        let input = $("#officeSearchInput");
        if (input.length) {
            input.on("keyup", (event) => {
                let input = event.target;
                window.officeDataTable.fnFilter(input.value);
            });
        } else {
            console.error(`#officeSearchInput does not exist`);
        }
    },
    showOfficesEntries() {
        let select = $("#showOfficesEntries");
        if (select.length) {
            select.on("change", (event) => {
                let selected = parseInt(event.target.value);
                window.officeDataTable.api().context[0]._iDisplayLength =
                    selected;
                window.officeDataTable.api().draw();
            });
        } else {
            console.error(`#showOfficesEntries does not exist`);
        }
    },
    officesDatatable() {
        let table = $("#officesTable");
        let e = window.e;
        if (table.length) {
            window.officeDataTable = table.dataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                },
                pageLength: 10,
                dom: "rtip",
                ajax: helper.getSiteUrl("/datatable/offices"),
                serverSide: true,
                searchCols: [{ search: 0 }],
                columns: [
                    {
                        data: "inactive",
                        name: "offices.inactive",
                        defaultContent: "404",
                        title: "offices.inactive",
                        visible: 0,
                    },
                    {
                        data: "name",
                        name: "users.name",
                        defaultContent: "404",
                        title: "Name",
                        visible: 0,
                    },
                    {
                        data: "address",
                        defaultContent: "404",
                        title: "address",
                        name: "users.address",
                        visible: 0,
                    },
                    {
                        data: "city",
                        defaultContent: "404",
                        title: "city",
                        name: "users.city",
                        visible: 0,
                    },
                    {
                        data: "state",
                        defaultContent: "404",
                        title: "state",
                        name: "users.state",
                        visible: 0,
                    },
                    {
                        data: "zipcode",
                        defaultContent: "404",
                        title: "zipcode",
                        name: "users.zipcode",
                        visible: 0,
                    },
                    {
                        data: "primary_contact",
                        defaultContent: "404",
                        title: "primary_contact",
                        visible: 0,
                    },
                    {
                        data: "phone",
                        defaultContent: "404",
                        title: "phone",
                        name: "users.phone",
                        visible: 0,
                    },
                    {
                        data: "email",
                        defaultContent: "404",
                        title: "email",
                        name: "users.email",
                        visible: 0,
                    },
                    {
                        data: "id",
                        defaultContent: "OFFICE DETAILS",
                        title: "OFFICE DETAILS",
                        render(d, t, r) {
                            let content = "";
                            if (r.name) {
                                content += e("b", {
                                    htmlContent: r.name + "<br>",
                                });
                            }
                            if (r.address) {
                                content += e("span", {
                                    htmlContent: r.address + "<br>",
                                });
                            }
                            content += e("span", {
                                htmlContent: `${r.city ?? ""}, ${r.state ?? ""
                                    }, ${r.zipcode ?? ""}<br>`,
                            });
                            if (r.primary_contact) {
                                content += e("span", {
                                    htmlContent: r.primary_contact + "<br>",
                                });
                            }
                            if (r.phone) {
                                content += e("span", {
                                    htmlContent: r.phone + "<br>",
                                });
                            }
                            if (r.email) {
                                content += e("span", {
                                    htmlContent: r.email + "<br>",
                                });
                            }
                            return content;
                        },
                    },
                    {
                        defaultContent: "OFFICE DETAILS",
                        orderable: 0,
                        searchable: 0,
                        title: "OFFICE AGENTS",
                        render(d, t, r) {
                            let content = "<div class='text-center'>";
                            content += e("span", {
                                htmlContent:
                                    " <b>TOTAL AGENTS:</b> " +
                                    r.agents_count +
                                    "<br>",
                            });
                            content += e("b", {
                                htmlContent: `<a onclick="window.viewAgents('${r.id}',${r.inactive})">View Agents</a> <br>`,
                            });
                            content += e("button", {
                                htmlContent: "Add Agent",
                                class: "btn btn-sm btn-primary mt-2 mb-3",
                                onclick: `window.addAgent(${r.id})`,
                            });
                            content +=
                                "<div class='d-flex justify-content-center'>";
                            content += e("select", {
                                htmlContent: `
                                <option ${r.payment_method == "1" ? "selected" : ""
                                    } value="1">Pay at time of Order</option>
                                <option ${r.payment_method == "2" ? "selected" : ""
                                    } value="2">Invoiced</option>
                            `,
                                class: "form-control text-center payment-method-select",
                                onchange: `window.updatePaymentMethodOffice(${r.id},this.value)`,
                            });
                            content += "</div>";
                            content += "</div>";
                            return content;
                        },
                    },
                    {
                        defaultContent: "ACTION",
                        orderable: 0,
                        searchable: 0,
                        title: "ACTION",
                        render(d, t, r) {
                            let content = "<div class='text-center'>";
                            content += e("a", {
                                htmlContent: "Password Reset <br>",
                                href: helper.getSiteUrl(
                                    `/office/${r.id}/reset/password`
                                ),
                                class: "",
                            });
                            //console.log(r.name)
                            r.name = helper.decodeHtml(r.name)
                            r.name = r.name.replace(/'/g, "\\'");
                            content += e("a", {
                                htmlContent: "View Orders <br>",
                                // href: "",
                                class: "",
                                onclick: `window.viewOrders('${r.name}')`,
                            });
                            content += e("a", {
                                htmlContent: "Account Notes <br>",
                                class: "",
                                onclick: `window.noteOfficeModal(${r.id})`,
                            });
                            content += e("a", {
                                htmlContent: "Edit Account <br>",
                                // href: helper.getSiteUrl(`/offices/${r.id}/edit`),
                                onclick: `window.editOfficeModal(${r.id}, '${r.email}')`,
                                class: "",
                            });
                            content += e("a", {
                                htmlContent: "Manage Cards <br>",
                                onclick: `window.manageCardsModal(${r.id})`,
                                class: "",
                            });
                            return content;
                        },
                    },
                ],
            });
        }
    },
    editOfficeModal(id, email) {
        office.officeId = id;
        office.officeEmail = email;

        $.get(helper.getSiteUrl(`/get/office/${id}`))
            .then((office) => {
                let editOfficeFormModal = $("#editOfficeFormModal");
                let editOfficeForm = $("#editOfficeForm");
                editOfficeForm.attr(
                    "action",
                    helper.getSiteUrl("/offices/" + office.id)
                );
                editOfficeFormModal.find(`[name='user_id']`).val(office.user_id);
                editOfficeFormModal.find(`[name='name']`).val(office.name);
                editOfficeFormModal
                    .find(`[name='primary_contact']`)
                    .val(office.primary_contact);
                editOfficeFormModal
                    .find(`[name='address']`)
                    .val(office.address);
                editOfficeFormModal
                    .find(`[name='phone']`)
                    .val(office.phone);
                editOfficeFormModal.find(`[name='city']`).val(office.city);
                editOfficeFormModal
                    .find(`[name='email']`)
                    .val(office.email);
                editOfficeFormModal
                    .find(`[name='state']`)
                    .find(`option[value="${office.state}"]`)
                    .prop("selected", true);
                editOfficeFormModal
                    .find(`[name='zipcode']`)
                    .val(office.zipcode);
                editOfficeFormModal
                    .find(`[name='website']`)
                    .val(office.website);
                editOfficeFormModal
                    .find(`[name='inactive']`)
                    .find(`option[value="${office.inactive}"]`)
                    .prop("selected", true);
                editOfficeFormModal
                    .find(`[name='private']`)
                    .find(`option[value="${office.private}"]`)
                    .prop("selected", true);
                editOfficeFormModal
                    .find(`[name='name_abbreviation']`)
                    .val(office.name_abbreviation);
                editOfficeFormModal
                    .find(`[name='region_id']`)
                    .find(`option[value="${office.region_id}"]`)
                    .prop("selected", true);
                editOfficeFormModal
                    .find(".edit_logo_preview")
                    .find("img")
                    .prop(
                        "src",
                        helper.getSiteUrl(
                            "/private/image/office/" + office.logo_image
                        )
                    );
                editOfficeFormModal.modal();
            })
            .fail((res) => console.error(`failed to get office for edit form`));
    },
    manageCardsModal(id) {
        helper.showLoader();
        let manageOfficeCardsModal = $("#manageOfficeCardsModal");
        $('.cards-container').remove();
        $.get(helper.getSiteUrl(`/get/office-cards/${id}`))
            .then((cards) => {
                if (_.isEmpty(cards)) {
                    manageOfficeCardsModal.find('#modalBody').append(`
                        <div class="row mb-3 mt-1 cards-container">
                            <div class="col-md-3 text-center font-weight-bold">
                            </div>
                            <div class="col-md-12 text-center font-weight-bold">
                                <span class="w-50 mx-auto alert alert-warning">Currently there are no cards saved for this account.</span>
                            </div>
                            <div class="col-md-2 text-center">
                            </div>
                        </div>
                    `);
                }

                Object.keys(cards).forEach(key => {
                    manageOfficeCardsModal.find('#modalBody').append(
                        `
                            <div class="row mb-3 mt-1 cards-container">
                                <div class="col-md-3 text-center font-weight-bold">
                                </div>
                                <div class="col-md-4 text-left font-weight-bold">
                                    <span>${cards[key]['cardType']}: ${cards[key]['cardNumber']} exp ${cards[key]['expDate']}</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <button class="btn bg-danger text-white ml-2 pt-1 pb-1 pl-4 pr-4 rounded-0 remove-card" id="removeCardButton" data-payment_profile_id="${key}" data-office_id="${id}">Remove</button>
                                </div>
                            </div>
                        `
                    );
                });

                helper.hideLoader();
                manageOfficeCardsModal.modal();
            })
            .fail((res) => {
                helper.hideLoader();
                helper.alertError(helper.serverErrorMessage());
            });
    },
    removeCardButton() {
        $('body').on('click', '.remove-card', (e)=> {
            const self = $(e.target);
            const paymentProfileId = self.data('payment_profile_id');
            const officeId = self.data('office_id');

            helper.confirm('Remove Card', 'Are you sure you want to remove this card?',
                () => {
                    helper.showLoader();

                    $.post(`${helper.getSiteUrl()}/office-cards/remove/${paymentProfileId}/${officeId}`)
                    .then((response) => {
                        helper.hideLoader();
                        helper.alertMsg('Card Removed', 'Card removed successfully.');
                        this.manageCardsModal(officeId);
                    })
                    .fail((res) => {
                        helper.hideLoader();
                        res.responseJSON.type == 'error' ? helper.alertError(res.responseJSON.message) : helper.alertError(helper.alertError(helper.serverErrorMessage()));
                    });
                },
                () => {}
            )
        });
    },
    watchLogoImageInput() {
        $("[name='logo_image']").on("change", (e) => {
            let logo_image = e.target.files[0];
            $(".logo_preview img").prop("src", URL.createObjectURL(logo_image));
            $(".logo_preview").show();
        });
        $("[name='edit_logo_image']").on("change", (e) => {
            let logo_image = e.target.files[0];
            $(".edit_logo_preview img").prop(
                "src",
                URL.createObjectURL(logo_image)
            );
        });
    },
    viewOrders(name) {
        console.log(name)
        helper.redirectTo(helper.getSiteUrl(`/order/status?search=${name}`));
    },
    changeOfficePassword() {
        $('#changeOfficePasswordBtn').on('click', (e) => {
            const newPassword = $('#newOfficePassword').val();
            const confirmPassword = $('#confirmOfficePassword').val();
            if (!newPassword || !confirmPassword) {
                helper.alertError('All fields are required.');
                return false;
            }

            if (newPassword !== confirmPassword) {
                helper.alertError("Passwords don't match.");
                return false;
            }

            $.post(`${helper.getSiteUrl()}/office/${office.officeId}/change/password`, {newPassword: newPassword})
            .done(() => {
                $('#newOfficePassword').val('');
                $('#confirmOfficePassword').val('');
                helper.alertMsg('Change Password','Password changed successfully.');

                helper.closeModal('changeOfficePasswordModal');
            });
        });
    },

    editOfficeAgentModal(id) {
        office.officeAgentId = id;

        office.getOfficeAgent();

        $.get(helper.getSiteUrl(`/office-agents/get/agent/${id}`))
            .then((agent) => {
                let editOfficeAgentFormModal = $("#editOfficeAgentFormModal");
                let editOfficeAgentForm = $("#editOfficeAgentForm");

                editOfficeAgentForm.attr(
                    "action",
                    helper.getSiteUrl("/office-agents/" + agent.id)
                );
                editOfficeAgentFormModal
                    .find(`[name='first_name']`)
                    .val(agent.first_name);
                editOfficeAgentFormModal
                    .find(`[name='last_name']`)
                    .val(agent.last_name);
                editOfficeAgentFormModal.find(`[name='phone']`).val(agent.phone);
                editOfficeAgentFormModal
                    .find(`[name='address']`)
                    .val(agent.address);
                editOfficeAgentFormModal.find(`[name='city']`).val(agent.city);
                editOfficeAgentFormModal
                    .find(`[name='re_license']`)
                    .val(agent.re_license);
                editOfficeAgentFormModal.find(`[name='email']`).val(agent.email);
                editOfficeAgentFormModal
                    .find(`[name='zipcode']`)
                    .val(agent.zipcode);
                editOfficeAgentFormModal
                    .find(`[name='state']`)
                    .find(`[value="${agent.state}"]`)
                    .prop("selected", true);
                // editOfficeAgentFormModal
                //     .find(`[name='agent_office']`)
                //     .find(`[value="${agent.agent_office}"]`)
                //     .prop("selected", true);
                // editOfficeAgentFormModal
                //     .find(`[name='inactive']`)
                //     .find(`[value="${agent.inactive}"]`)
                //     .prop("selected", true);
                editOfficeAgentFormModal.modal();
            })
            .fail((res) => console.error(`failed to get agent for edit from`));
    },

    updatePaymentMethodOfficeAgent(agent, payment_method) {
        $.post(helper.getSiteUrl(`/office-agents/${agent}/update/payment/method`), {
            payment_method,
        })
            .done((res) => { })
            .fail((res) => {
                helper.alertError(helper.serverErrorMessage());
                // console.log(res);
            });
    },

    disconnectOfficeAgent(id) {
        helper.confirm(
            "Disconnect agent",
            "Are you sure you wish to remove this agent from the office?",
            (event) => {
                $.post(helper.getSiteUrl(`/office-agents/remove/${id}`))
                .done((response) => {
                    helper.reloadPage();
                })
                .fail((error) => {
                    helper.alertError(helper.serverErrorMessage());
                });
            },
            (event) => {}
        );
    },

    officeAgentsDatatable() {
        let table = $("#officeAgentsTable");
        let e = window.e;
        if (table.length) {
            window.agentDataTable = table.dataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                },
                pageLength: 10,
                dom: "rtip",
                ajax: helper.getSiteUrl("/datatable/office-agents"),
                serverSide: true,
                searchCols: [{ search: 0 }],

                columns: [
                    {
                        data: "inactive",
                        defaultContent: "404",
                        title: "inactive",
                        name: "agents.inactive",
                        visible: 0,
                    },
                    {
                        data: "name",
                        defaultContent: "404",
                        title: "Name",
                        name: "users.name",
                        visible: 0,
                    },
                    {
                        data: "address",
                        defaultContent: "404",
                        title: "address",
                        name: "users.address",
                        visible: 0,
                    },
                    {
                        data: "city",
                        defaultContent: "404",
                        title: "city",
                        name: "users.city",
                        visible: 0,
                    },
                    {
                        data: "state",
                        defaultContent: "404",
                        title: "state",
                        name: "users.state",
                        visible: 0,
                    },
                    {
                        data: "zipcode",
                        defaultContent: "404",
                        title: "zipcode",
                        name: "users.zipcode",
                        visible: 0,
                    },
                    {
                        data: "phone",
                        defaultContent: "404",
                        title: "phone",
                        name: "users.phone",
                        visible: 0,
                    },
                    {
                        data: "email",
                        defaultContent: "404",
                        title: "email",
                        name: "users.email",
                        visible: 0,
                    },
                    {
                        data: "office_name",
                        defaultContent: "404",
                        title: "office_name",
                        name: "office_users.name",
                        visible: 0,
                    },
                    {
                        data: "id",
                        defaultContent: "AGENT DETAILS",
                        title: "AGENT DETAILS",
                        render(d, t, r) {
                            let content = "";
                            if (r.name) {
                                content += e("b", {
                                    htmlContent: r.name + "<br>",
                                });
                            }
                            if (r.address) {
                                content += e("span", {
                                    htmlContent: r.address + "<br>",
                                });
                            }
                            content += e("span", {
                                htmlContent: `${r.city ?? ""},${r.state ?? ""
                                    },${r.zipcode ?? ""}<br>`,
                            });
                            if (r.phone) {
                                content += e("span", {
                                    htmlContent: r.phone + "<br>",
                                });
                            }
                            if (r.email) {
                                content += e("span", {
                                    htmlContent: r.email + "<br>",
                                });
                            }
                            return content;
                        },
                    },
                    {
                        defaultContent: "404",
                        orderable: 0,
                        searchable: 0,
                        title: "AGENT OFFICE",
                        render(d, t, r) {
                            let content = "<div class='text-center'>";
                            content += e("b", {
                                htmlContent:
                                    " <b>" +
                                    (r.office_name ?? "No Office Assigned") +
                                    "</b><br>",
                            });
                            content += e("b", {
                                htmlContent:
                                    "" + (r.office_phone ?? "") + " <br>",
                            });
                            content += e("button", {
                                htmlContent: "Disconnect",
                                class: "btn btn-sm btn-primary mt-2 mb-3",
                                onclick: `window.disconnectOfficeAgent(${r.id})`,
                            });
                            content += e("select", {
                                htmlContent: `
                                <option ${r.payment_method == "1" ? "selected" : "" } value="1">Pay at time of Order</option>
                                <option ${r.payment_method == "3" ? "selected" : "" } value="3">Office Pay</option>
                            `,
                                class: "form-control text-center payment-method-select mx-auto",
                                onchange: `window.updatePaymentMethodOfficeAgent(${r.id},this.value)`,
                            });
                            content += "</div>";
                            return content;
                        },
                    },
                    {
                        defaultContent: "ACTION",
                        orderable: 0,
                        searchable: 0,
                        title: "ACTION",
                        render(d, t, r) {
                            let content = "<div class='text-center'>";
                            r.name = helper.decodeHtml(r.name)
                            r.name = r.name.replace(/'/g, "\\'");
                            content += e("a", {
                                htmlContent: "View Orders <br>",
                                // href: "",
                                class: " font-weight-bold",
                                onclick: `window.viewOrders('${r.name}')`,
                            });
                            content += e("a", {
                                htmlContent: "Edit Account <br>",
                                // href: helper.getSiteUrl(`/offices/${r.id}/edit`),
                                onclick: `window.editOfficeAgentModal(${r.id})`,
                                class: " font-weight-bold",
                            });
                            return content;
                        },
                    },
                ],
            });
        }
    },

    officeNotificationEmailDatatable() {
        $("#emailNotificationTable").DataTable({
            initComplete: (event) => {
                $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
                office.getRecordsCount();
            },
            retrieve: true,
            ordering: true,
            pageLength: 10,
            dom: 't<"text-muted h6" i>',
            language: {
                "info": "(_TOTAL_ of 5 email accounts used)",
            },
            ajax: helper.getSiteUrl(`/datatable/office/email-settings/${office.officeId}`),
            serverSide: true,

            columns: [
                {
                    data: "email",
                    defaultContent: "404",
                    title: "",
                    name: "email",
                    visible: 1,
                    width: "50%",
                    orderable: false
                },
                {
                    data: "order",
                    defaultContent: "404",
                    title: "Order Notifcations",
                    name: "order",
                    visible: 1,
                    render(d, t, r) {
                        return `<input type="checkbox" name="order" id="orderNotification${r.id}" onclick="window.onNotificationClick('${r.email}', ${r.id})" ${r.order ? "checked" : ""} class="m-0 mx-1 scale-1_5">`;
                    },
                },
                {
                    data: "accounting",
                    defaultContent: "404",
                    title: "Accounting Notifications",
                    name: "accounting",
                    visible: 1,
                    render(d, t, r) {
                        return `<input type="checkbox" name="accounting" id="accountingNotification${r.id}" onclick="window.onNotificationClick('${r.email}', ${r.id})" ${r.accounting ? "checked" : ""} class="m-0 mx-1 scale-1_5">`;
                    },
                },
                {
                    data: "",
                    defaultContent: "404",
                    title: "",
                    name: "",
                    visible: 1,
                    render(d, t, r) {
                        if (r.email == office.officeEmail) {
                            return ``;
                        }else {
                            return `<a href="#" onclick="window.removeEmail('${r.email}')">Remove</a>`;
                        }
                    },
                },
            ],
        });
    },

    onAdditionalSettingsClick() {
        $('#additionalSettingsBtn').on('click', (event) => {
            this.officeNotificationEmailDatatable();
            $.post(`${helper.getSiteUrl()}/office/email-settings/add`, {
                office_id: office.officeId,
                email: office.officeEmail,
                user_email: office.officeEmail,
            }).done((response) => {
                $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
            }).fail((error) => {
                $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
            });
        });
    },

    onNotificationClick(email, id) {
        $.post(`${helper.getSiteUrl()}/office/email-settings/update`, {
            office_id: office.officeId,
            email: email,
            order: $(`#orderNotification${id}`).is(":checked") ? 1 : 0,
            accounting: $(`#accountingNotification${id}`).is(":checked") ? 1 : 0,
        }).done((response) => {
            $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
        }).fail((error) => {
            $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
        });
    },

    onNewEmailSubmit() {
        $('#newOfficeEmailForm').on('submit', (event) => {
            event.preventDefault();
            $.post(`${helper.getSiteUrl()}/office/email-settings/add`, {
                email: $('#newEmail').val(),
                office_id: office.officeId,
                order: $(`#orderNotification`).is(":checked") ? 1 : 0,
                accounting: $(`#accountingNotification`).is(":checked") ? 1 : 0,
            }).done((response) => {
                $('#newEmail').val("");
                $(`#orderNotification`).prop('checked', false);
                $(`#accountingNotification`).prop('checked', false);
                $("#addEmailNotificationSettingsModal").modal("hide");
                helper.alertMsg("New Email added", "The new email was added successfully.");
                $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
            }).fail((error) => {
                $('#newEmail').val("");
                $("#addEmailNotificationSettingsModal").modal("hide");
                helper.alertError(error.responseJSON.errors.email);
                $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
            });
        });
    },

    removeEmail(email) {
        helper.confirm("Are you sure?", " This action is irreversible", () => {
            $.post(`${helper.getSiteUrl()}/office/email-settings/remove`, {
                email: email,
                office_id: office.officeId
            }).done((response) => {
                helper.alertMsg("Email deleted", "The email was deleted successfully.");
                $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
            }).fail((error) => {
                helper.alertError(helper.serverErrorMessage());
                $("#emailNotificationTable").DataTable().ajax.reload(office.getRecordsCount);
            });
        }, () => {});
    },

    onClosePasswordSettingsModal() {
        $("#emailNotificationSettingsModal").on('hide.bs.modal', () => {
            $("#emailNotificationTable").DataTable().destroy();
        });
    },

    getRecordsCount() {
        $("#emailNotificationTable").DataTable().page.info().recordsTotal == 5 ? $('#addNewEmailBtn').prop('disabled', true) : $('#addNewEmailBtn').prop('disabled', false);
    },

    getOfficeAgent() {
        $.get(helper.getSiteUrl(`/office-agents/get/agent/${office.officeAgentId}`)).done((agent) => {
            User.agent = agent;
            Agent.agentId = agent.id,
            Agent.agentEmail = agent.email
        }).fail((error) => {
            helper.alertError(helper.serverErrorMessage());
        });
    }
};

export default office;
