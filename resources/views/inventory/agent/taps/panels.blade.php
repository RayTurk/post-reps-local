<div class="tab-pane fade" id="pills-panels" role="tabpanel" aria-labelledby="pills-panels-tab">
    <div class="card auth-card mb-2">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mt-2">Panels</h6>
            <div class="d-flex align-items-center" style="gap: 3px">
                <span>Show</span>
                <select class="form-control" id="showAgentPanelsEntries">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>Entries</span>
            </div>
            <div>
                <input type="text" class="form-control" placeholder="search..." id="agentPanelsSearchInput">
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive pt-3">
                <table class="table inventory-table table-bordered table-hover w-100 h-100" id="AgentPanelsTable">
                </table>
            </div>
        </div>
    </div>
</div>
