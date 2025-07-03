<!-- Modal -->
<form id="createPostSettingsForm" class="panelSettings-form panelSettings-create-form" method="post"
    action="{{ url('/post/global/settings') }}">
    @csrf
    <div class="modal fade" id="createPostSettingsFormModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content auth-card">
                <div class="modal-header pb-0">
                    <h5 class="modal-title" id="exampleModalLabel">Post Global Settings</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="p-2">
                        <div class="row ">

                            <div class="col-12 col-md-12 col-lg-12 mb-2">

                                <div class="row">
                                    <div class="col-12 co-md-8 col-lg-8 mx-auto">

                                        <div class="form-group row ">
                                            <label
                                                class="col-form-label text-right text-dark m-0 col-12 col-md-6 col-lg-6 pr-1"
                                                for="repair_replace_post"><b>REPAIR/REPLACE POST:</b></label>
                                            <div class="pl-0  col-12 col-md-4 col-lg-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" step="any"
                                                        value="{{ $serviceSettings->repair_replace_post ?? 0 }}" required step="any"
                                                        id="repair_replace_post" name="repair_replace_post" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row ">
                                            <label
                                                class="col-form-label text-right text-dark m-0 col-12 col-md-6 col-lg-6 pr-1"
                                                for="relocate_post"><b>RELOCATE POST:</b></label>
                                            <div class="pl-0  col-12 col-md-4 col-lg-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">$</span>
                                                    </div>
                                                    <input type="number" step="any"
                                                        value="{{ $serviceSettings->relocate_post ?? 0 }}" required step="any"
                                                        id="relocate_post" name="relocate_post" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-around align-items-center">
                                        <button class="btn btn-orange col-md-3 col-lg-3 font-weight-bold">
                                            SAVE
                                        </button>
                                        <button type="button" data-dismiss="modal"
                                            class="btn btn-dark col-md-3 col-lg-3">
                                            CANCEL
                                        </button>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

</form>
