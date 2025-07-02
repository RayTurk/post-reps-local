import helper from "./helper";
import accounting from "./accounting";

const accountingSettings = {
    init() {
        accounting.init();
        this.globalSettings();
    },

    globalSettings() {

        let settingsContainer = $(`#service-global-settings`);
        if (helper.isMobilePhone()) {
            settingsContainer = $(`#service-global-settings-mobile`);
        }
        if (helper.isTablet()) {
            settingsContainer = $(`#service-global-settings-tablet`);
        }

        settingsContainer.find(`input`).on("change", (e) => {
            e = $(e.target);
            let column = e.attr("name");
            let value = e.val();
            $.get(`${helper.getSiteUrl()}/update/column/service/settings/${column}/${value}`);
        });
    }
};

$(() => {
    accountingSettings.init();
});
