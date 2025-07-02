import helper from "./helper";
import OfficesSearch from "./officesSearch";
import _ from "lodash";

let Agent = {
    agentId: null,
    agentEmail: null,

    init() {
        window.editAgentModal = this.editAgentModal;
        window.manageCardsModalAgent = this.manageCardsModalAgent;
        this.removeCardButtonAgent();
        window.updatePaymentMethodAgent = this.updatePaymentMethodAgent;
        window.noteAgentModal = this.noteAgentModal;
        window.changeOffice = this.changeOffice;
        this.agentSearchInput();
        this.showAgentsEntries();
        this.destroyAllAgents();
        this.agentType();
        window.OfficesSearch = OfficesSearch.list({
            table: "#changeOfficeTable",
            search_element: "#changeOfficeTableSearch",
            tableName: "changeOfficeTable",
        });

        this.importAgents();
        $("#importAgentFile").on('change', () => $('.loading-overlay').toggleClass('d-none'))

        window.viewOrders = this.viewOrders;
        this.changeAgentPassword();
        this.onAdditionalSettingsClick();
        window.onAgentNotificationClick = this.onAgentNotificationClick;
        this.onNewEmailSubmit();
        window.removeAgentEmail = this.removeAgentEmail;
        this.onClosePasswordSettingsModal();
    },
    changeOffice(id, current_office) {
        //
        localStorage.selected_agent_for_change_office = id;
        localStorage.current_office = current_office;

        let modal = $("#changeOfficeFormModal");
        if (modal.length) {
            window.OfficesSearch.api().draw();
            modal.modal();
            modal.on("hidden.bs.modal", () => {
                localStorage.removeItem("selected_agent_for_change_office");
            });
        }
    },
    agentType() {
        let select = $("#agentType");
        if (select.length) {
            select.on("change", () => {
                let inactive = select.val();
                let dt = window.agentDataTable;
                dt.api().column(0).search(inactive).draw();
            });
        }
    },
    noteAgentModal(id) {
        let modal = $("#noteAgentFormModal");
        let form = $("#noteAgentForm");
        if (modal.length) {
            $.get(helper.getSiteUrl(`/get/agent/${id}`))
                .done((agent) => {
                    form.attr(`action`, helper.getSiteUrl(`/agent/${agent.id}/create/note`));
                    modal.find(`[name='note']`).text(agent.note);
                    $("[note-agent-name]").text(agent.name)
                    modal.modal();
                })
                .fail((res) => {
                    console.log(res);
                });
        }
    },
    updatePaymentMethodAgent(agent, payment_method) {
        $.post(helper.getSiteUrl(`/agent/${agent}/update/payment/method`), {
            payment_method,
        })
            .done((res) => { })
            .fail((res) => {
                console.log(res);
            });
    },
    destroyAllAgents() {
        let button = $("#destroyAllAgents");
        if (button.length) {
            button.on("click", () => {
                helper.confirm(
                    "",
                    "",
                    //on click Yes
                    () => {
                        //delete end point
                        let deletePath =
                            helper.getSiteUrl("/agents/delete/all");
                        let href = $.post(deletePath)
                            .done((res) => {
                                if (res.type === "success") {
                                    //rerender dataTable
                                    window.agentDataTable.api().draw();
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
    showAgentsEntries() {
        let select = $("#showAgentsEntries");
        if (select.length) {
            select.on("change", (event) => {
                let selected = parseInt(event.target.value);
                window.agentDataTable.api().context[0]._iDisplayLength =
                    selected;
                window.agentDataTable.api().draw();
            });
        } else {
            console.error(`#showAgentsEntries not exists`);
        }
    },
    agentSearchInput() {
        let input = $("#agentSearchInput");
        if (input.length) {
            input.on("keyup", (event) => {
                let input = event.target;
                window.agentDataTable.fnFilter(input.value);
            });
        } else {
            console.error(`#officeSearchInput no exists`);
        }
    },
    agentsDatatable() {
        let table = $("#agentsTable");
        let e = window.e;
        if (table.length) {
            window.agentDataTable = table.dataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                },
                pageLength: 10,
                dom: "rtip",
                ajax: helper.getSiteUrl("/datatable/agents"),
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
                                htmlContent: "Change Office",
                                class: "btn btn-sm btn-primary mt-2 mb-3",
                                onclick: `window.changeOffice(${r.id},${r.agent_office})`,
                            });
                            content += e("select", {
                                htmlContent: `
                                <option ${r.payment_method == "1" ? "selected" : "" } value="1">Pay at time of Order</option>
                                <option ${r.payment_method == "2" ? "selected" : "" } value="2">Invoiced</option>
                                <option ${r.payment_method == "3" ? "selected" : "" } value="3">Office Pay</option>
                            `,
                                class: "form-control text-center payment-method-select mx-auto",
                                onchange: `window.updatePaymentMethodAgent(${r.id},this.value)`,
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
                            content += e("a", {
                                htmlContent: "Password Reset <br>",
                                href: helper.getSiteUrl(
                                    `/agent/${r.id}/reset/password`
                                ),
                                class: " font-weight-bold",
                            });
                            r.name = helper.decodeHtml(r.name)
                            r.name = r.name.replace(/'/g, "\\'");
                            content += e("a", {
                                htmlContent: "View Orders <br>",
                                // href: "",
                                class: " font-weight-bold",
                                onclick: `window.viewOrders('${r.name}')`,
                            });
                            content += e("a", {
                                htmlContent: "Account Notes <br>",
                                class: " font-weight-bold",
                                onclick: `window.noteAgentModal(${r.id})`,
                            });
                            content += e("a", {
                                htmlContent: "Edit Account <br>",
                                // href: helper.getSiteUrl(`/offices/${r.id}/edit`),
                                onclick: `window.editAgentModal(${r.id}, '${r.email}')`,
                                class: " font-weight-bold",
                            });
                            content += e("a", {
                                htmlContent: "Manage Cards <br>",
                                onclick: `window.manageCardsModalAgent(${r.id})`,
                                class: " font-weight-bold",
                            });
                            return content;
                        },
                    },
                ],
            });
        }
    },
    editAgentModal(id, email) {
        Agent.agentId = id;
        Agent.agentEmail = email;

        $.get(helper.getSiteUrl(`/get/agent/${id}`))
            .then((agent) => {
                let editAgentFormModal = $("#editAgentFormModal");
                let editAgentForm = $("#editAgentForm");

                editAgentForm.attr(
                    "action",
                    helper.getSiteUrl("/agents/" + agent.id)
                );
                editAgentFormModal.find(`[name='user_id']`).val(agent.user_id);
                editAgentFormModal
                    .find(`[name='first_name']`)
                    .val(agent.first_name);
                editAgentFormModal
                    .find(`[name='last_name']`)
                    .val(agent.last_name);
                editAgentFormModal.find(`[name='phone']`).val(agent.phone);
                editAgentFormModal
                    .find(`[name='address']`)
                    .val(agent.address);
                editAgentFormModal.find(`[name='city']`).val(agent.city);
                editAgentFormModal
                    .find(`[name='re_license']`)
                    .val(agent.re_license);
                editAgentFormModal.find(`[name='email']`).val(agent.email);
                editAgentFormModal
                    .find(`[name='zipcode']`)
                    .val(agent.zipcode);
                editAgentFormModal
                    .find(`[name='state']`)
                    .find(`[value="${agent.state}"]`)
                    .prop("selected", true);
                editAgentFormModal
                    .find(`[name='agent_office']`)
                    .find(`[value="${agent.agent_office}"]`)
                    .prop("selected", true);
                editAgentFormModal
                    .find(`[name='inactive']`)
                    .find(`[value="${agent.inactive}"]`)
                    .prop("selected", true);
                editAgentFormModal.modal();
            })
            .fail((res) => console.error(`failed to get agent for edit from`));
    },
    manageCardsModalAgent(id) {
        helper.showLoader();
        let manageAgentCardsModal = $("#manageAgentCardsModal");
        $('.cards-container').remove();
        $.get(helper.getSiteUrl(`/get/agent-cards/${id}`))
            .then((cards) => {
                if (_.isEmpty(cards)) {
                    manageAgentCardsModal.find('#modalBody').append(`
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
                    manageAgentCardsModal.find('#modalBody').append(
                        `
                            <div class="row mb-3 mt-1 cards-container">
                                <div class="col-md-3 text-center font-weight-bold">
                                </div>
                                <div class="col-md-4 text-left font-weight-bold">
                                    <span>${cards[key]['cardType']}: ${cards[key]['cardNumber']} exp ${cards[key]['expDate']}</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <button class="btn bg-danger text-white ml-2 pt-1 pb-1 pl-4 pr-4 rounded-0 remove-card-agent" id="removeCardButton" data-payment_profile_id="${key}" data-agent_id="${id}">Remove</button>
                                </div>
                            </div>
                        `
                    );
                });
                helper.hideLoader();
                manageAgentCardsModal.modal();
            })
            .fail((res) => {
                helper.hideLoader();
                helper.alertError(helper.serverErrorMessage());
            });
    },
    removeCardButtonAgent() {
        $('body').on('click', '.remove-card-agent', (e)=> {
            const self = $(e.target);
            const paymentProfileId = self.data('payment_profile_id');
            const agentId = self.data('agent_id');

            helper.confirm('Remove Card', 'Are you sure you want to remove this card?',
                () => {
                    helper.showLoader();

                    $.post(`${helper.getSiteUrl()}/agent-cards/remove/${paymentProfileId}/${agentId}`)
                    .then((response) => {
                        helper.hideLoader();
                        helper.alertMsg('Card Removed', 'Card removed successfully.');
                        this.manageCardsModalAgent(agentId);
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
    importAgents() {
        $('#importAgentFile').on('change', () => {
            $('#importAgentsForm').trigger('submit');
        });

        $('#importAgentsForm').on('submit', () => {
            $('label[for="importAgentFile"]').text('PROCESSING...').css('cursor', 'none');
        });
    },

    viewOrders(name) {
        helper.redirectTo(helper.getSiteUrl(`/order/status?search=${name}`));
    },

    changeAgentPassword() {
        $('#changeAgentPasswordBtn').on('click', (e) => {
            const newPassword = $('#newAgentPassword').val();
            const confirmPassword = $('#confirmAgentPassword').val();
            if (!newPassword || !confirmPassword) {
                helper.alertError('All fields are required.');
                return false;
            }

            if (newPassword !== confirmPassword) {
                helper.alertError("Passwords don't match.");
                return false;
            }

            $.post(`${helper.getSiteUrl()}/agent/${Agent.agentId}/change/password`, {newPassword: newPassword})
            .done(() => {
                $('#newAgentPassword').val('');
                $('#confirmAgentPassword').val('');
                helper.alertMsg('Change Password','Password changed successfully.');

                helper.closeModal('changeAgentPasswordModal');
            });
        });
    },

    agentNotificationEmailDatatable() {
        $("#emailNotificationTableAgent").DataTable({
            initComplete: (event) => {
                $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
                Agent.getRecordsCount();
            },
            retrieve: true,
            ordering: true,
            pageLength: 10,
            dom: 't<"text-muted h6" i>',
            language: {
                "info": "(_TOTAL_ of 5 email accounts used)",
            },
            ajax: helper.getSiteUrl(`/datatable/agent/email-settings/${Agent.agentId}`),
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
                        return `<input type="checkbox" name="order" id="orderNotification${r.id}" onclick="window.onAgentNotificationClick('${r.email}', ${r.id})" ${r.order ? "checked" : ""} class="m-0 mx-1 scale-1_5">`;
                    },
                },
                {
                    data: "accounting",
                    defaultContent: "404",
                    title: "Accounting Notifications",
                    name: "accounting",
                    visible: 1,
                    render(d, t, r) {
                        return `<input type="checkbox" name="accounting" id="accountingNotification${r.id}" onclick="window.onAgentNotificationClick('${r.email}', ${r.id})" ${r.accounting ? "checked" : ""} class="m-0 mx-1 scale-1_5">`;
                    },
                },
                {
                    data: "",
                    defaultContent: "404",
                    title: "",
                    name: "",
                    visible: 1,
                    render(d, t, r) {
                        if (r.email == Agent.agentEmail) {
                            return ``;
                        }else {
                            return `<a href="#" onclick="window.removeAgentEmail('${r.email}')">Remove</a>`;
                        }
                    },
                },
            ],
        });
    },

    onAdditionalSettingsClick() {
        $('#agentAdditionalSettingsBtn').on('click', (event) => {
            this.agentNotificationEmailDatatable();
            $.post(`${helper.getSiteUrl()}/agent/email-settings/add`, {
                agent_id: Agent.agentId,
                email: Agent.agentEmail,
                user_email: Agent.agentEmail,
            }).done((response) => {
                $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
            }).fail((error) => {
                $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
            });
        });
    },

    onAgentNotificationClick(email, id) {
        $.post(`${helper.getSiteUrl()}/agent/email-settings/update`, {
            agent_id: Agent.agentId,
            email: email,
            order: $(`#orderNotification${id}`).is(":checked") ? 1 : 0,
            accounting: $(`#accountingNotification${id}`).is(":checked") ? 1 : 0,
        }).done((response) => {
            $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
        }).fail((error) => {
            $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
        });
    },

    onNewEmailSubmit() {
        $('#newAgentEmailForm').on('submit', (event) => {
            event.preventDefault();
            $.post(`${helper.getSiteUrl()}/agent/email-settings/add`, {
                email: $('#newEmailAgent').val(),
                agent_id: Agent.agentId,
                order: $(`#orderNotificationAgent`).is(":checked") ? 1 : 0,
                accounting: $(`#accountingNotificationAgent`).is(":checked") ? 1 : 0,
            }).done((response) => {
                $('#newEmailAgent').val("");
                $(`#orderNotificationAgent`).prop('checked', false),
                $(`#accountingNotificationAgent`).prop('checked', false),
                $("#addEmailNotificationSettingsModalAgent").modal("hide");
                helper.alertMsg("New Email added", "The new email was added successfully.");
                $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
            }).fail((error) => {
                $('#newEmail').val("");
                $("#addEmailNotificationSettingsModalAgent").modal("hide");
                helper.alertError(error.responseJSON.errors.email);
                $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
            });
        });
    },

    removeAgentEmail(email) {
        helper.confirm("Are you sure?", " This action is irreversible", () => {
            $.post(`${helper.getSiteUrl()}/agent/email-settings/remove`, {
                email: email,
                agent_id: Agent.agentId
            }).done((response) => {
                helper.alertMsg("Email deleted", "The email was deleted successfully.");
                $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
            }).fail((error) => {
                helper.alertError(helper.serverErrorMessage());
                $("#emailNotificationTableAgent").DataTable().ajax.reload(Agent.getRecordsCount);
            });
        }, () => {});
    },

    onClosePasswordSettingsModal() {
        $("#emailNotificationSettingsModalAgent").on('hide.bs.modal', () => {
            $("#emailNotificationTableAgent").DataTable().destroy();
        });
    },

    getRecordsCount() {
        $("#emailNotificationTableAgent").DataTable().page.info().recordsTotal == 5 ? $('#addNewEmailBtnAgent').prop('disabled', true) : $('#addNewEmailBtnAgent').prop('disabled', false);
    }
};

export default Agent;
