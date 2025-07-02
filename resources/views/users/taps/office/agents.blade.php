<div class="tab-pane fade  " id="pills-agents" role="tabpanel">
    <div class="card auth-card mb-2">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mt-2">Agents</h6>
            <div class="d-flex align-items-center" style="gap: 3px">
                <span>Show</span>
                <select class="form-control" id="showAgentsEntries">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>Entries</span>
            </div>
            <div>
                <!-- <a id="destroyAllAgents" class="btn btn-danger">Delete All Agents</a> -->
            </div>
            <div>
                <input type="text" class="form-control" placeholder="search..." id="agentSearchInput">
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createOfficeAgentFormModal">
                    Add New Agent
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive pt-3">
                <table class="table table-hover  w-100" id="officeAgentsTable"> </table>
            </div>
        </div>
    </div>
</div>
