import helper from "./helper";
import communication from "./communication";

const EmailCommunication = {
    init() {
        communication.init();
        console.log('Emails page');
        this.onEmailOfficeChange();
        this.onInternalStaffChange();
    },

    async getAgent(office) {
        return await $.get(helper.getSiteUrl(`/office/${office}/agents/order/by/name/json`));
    },

    onEmailOfficeChange() {
        let input = $(`[name="office"]`);

        if(input.length) {
            input.on("change", async (event) => {
                $('[id=installerspicker]').val('default');
                $('[id=installerspicker]').selectpicker('refresh');
                let value = event.target.value;
                let agents = await this.getAgent(value);
                if (!Array.isArray(agents)) {
                    agents = Object.values(agents);
                }
                let agentsInput = $(`[name="agents[]"]`);
                agentsInput.html("");
                agents.forEach((agent) => {
                    agentsInput.append(window.e("option", { value: agent.id, htmlContent: agent.user.lastNameFirstName, }));
                });
                $('[id=agentspicker]').selectpicker('refresh');
            });
        }
    },

    onInternalStaffChange() {
        let input = $(`[name="installers[]"]`);

        if(input.length) {
            input.on("change", (event) => {
                $('[id=officepicker]').val('');
                $('[id=officepicker]').selectpicker('refresh');

                $('[id=agentspicker]').html('');
                $('[id=agentspicker]').selectpicker('refresh');
            });
        }
    },

}

$(() => {
    EmailCommunication.init();
});
