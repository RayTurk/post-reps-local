<div class="tab-pane fade" id="pills-installers" role="tabpanel">
    <select id="installersStatus" class="form-control text-center col-2 rounded-0 mb-1 ml-auto be-in-corner" id="">
        <option value="0">Active</option>
        <option value="1">Inactive</option>
    </select>
    <div class="card auth-card mb-2">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mt-2">Installers</h6>
            <div class="d-flex align-items-center" style="gap: 3px">
                <span>Show</span>
                <select class="form-control" id="showInstallersEntries">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>Entries</span>
            </div>

            <div>
                <input type="text" style="width: 200px !important;" class="form-control" placeholder="search..." id="installersSearchInput">
            </div>
            <div>
                <!-- Button trigger modal -->
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createInstallerFormModal">
                    Add New Installer
                </button>

            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive pt-3">
                <table class="table table-hover  w-100" id="installersTable"> </table>
            </div>
        </div>
    </div>

</div>
