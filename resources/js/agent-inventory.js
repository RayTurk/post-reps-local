import Panel from './Panel';

const AgentInventory = {
    init() {
        Panel.agentPanelsDatatable();
        Panel.agentPanelsSearchInput();
        Panel.showAgentPanelsEntries();
        this.activeTab();
    },
    activeTab() {
        $("#pills-panels-tab").trigger("click");
    },
};

$(() => AgentInventory.init());