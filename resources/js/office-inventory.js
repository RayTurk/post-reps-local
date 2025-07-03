import Panel from './Panel';

const OfficeInventory = {
    init() {
        Panel.officePanelsDatatable();
        Panel.officePanelsSearchInput();
        Panel.showOfficePanelsEntries();
        this.activeTab();
    },
    activeTab() {
        $("#pills-panels-tab").trigger("click");
    },
};

$(() => OfficeInventory.init());
