<!-- Modal -->
<form id="editAccessoryForm" class="Accessory-form Accessory-edit-form" method="post" files=true
    enctype="multipart/form-data">
    @csrf
    @method("PATCH")
    <div class="modal fade" id="editAccessoryFormModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content auth-card">
                <div class="modal-header pb-0">
                    <h5 class="modal-title" id="exampleModalLabel">Edit Accessory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="p-2">
                        <div class="row h-100">
                            <div class="col-12 col-md-4 col-lg-4 mb-2" id="accessoryImageContainerEdit">
                                <div id="imgContainer" class="text-center">
                                    <img src="{{ url('/private/image/accessory/0') }}" class="img-fluid"
                                        style="max-width: 230px; max-height: 307px;">
                                </div>

                                <div
                                    class="d-flex flex-column justify-content-center align-items-center gap-px-3 w-100">
                                    <label for="accessoryImageInputEdit"
                                        class="text-center btn btn-primary w-100 font-weight-bold mx-auto mt-3">UPLOAD
                                        PHOTO</label>
                                    <input type="file" name="accessory_image_edit" id="accessoryImageInputEdit"
                                        class="d-none">
                                    <button class="btn btn-orange  w-100 font-weight-bold mx-auto mt-3">SAVE</button>
                                    <button class="btn btn-secondary w-100 font-weight-bold mx-auto mt-3"
                                        data-dismiss="modal">CANCEL</button>
                                </div>
                            </div>
                            <div class="col-12 col-md-8 col-lg-8 mb-2">
                                <div class="form-group row">
                                    <label class="col-form-label text-dark m-0 col-12 col-md-3 col-lg-3"
                                        for="accessory_name">
                                        <b>ACCESSORY:</b>
                                    </label>
                                    <input type="text" id="post_name" name="accessory_name"
                                        class="form-control col-12 col-md-8 col-lg-8" required="">
                                </div>
                                <div class="form-group row">
                                    <label class="col-form-label text-dark m-0 col-12 col-md-3 col-lg-3"
                                        for="quantity"><b>QUANTITY:</b></label>
                                    <input type="number" id="quantity" name="quantity"
                                        class="form-control col-12 col-md-3 col-lg-3">
                                </div>
                                <div class="form-group row">
                                    <label class="col-form-label text-dark m-0 col-12 col-md-3 col-lg-3"
                                        for="price"><b>PRICE:</b></label>
                                    <div class="input-group pl-0 col-12 col-md-4 col-lg-4">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" step="any" id="price" name="price" class="form-control "
                                            required="" required-error-msg="Price is required">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-form-label text-dark m-0 col-12 col-md-3 col-lg-3"
                                        for="loss_damage"><b>LOSS/DAMAGE:</b></label>
                                    <div class="input-group pl-0 col-12 col-md-4 col-lg-4">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" step="any" id="loss_damage" name="loss_damage"
                                            class="form-control" required=""
                                            required-error-msg="Loss / Damage is required">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-form-label text-dark m-0 col-12 col-md-3 col-lg-3"
                                        for="point_value"><b>POINT VALUE:</b></label>
                                    <input type="number" step="any" id="point_value" name="point_value"
                                        class="form-control col-12 col-md-3 col-lg-3">
                                </div>
                                <div class="form-group row">
                                    <label class="col-form-label text-dark m-0 col-12 col-md-3 col-lg-3"
                                        for="listing_order"><b>ORDER:</b></label>
                                    <input type="number" step="0.01" id="listing_order" name="listing_order"
                                        class="form-control col-12 col-md-3 col-lg-3">
                                </div>
                                <div class="row">
                                    <div class="w-100 text-center border-top border-bottom border-dark mb-3 py-2 ">
                                        <h4 class="m-0 p-0 font-px-18">Special Accessory Options</h4>
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input w-h-px-20" type="checkbox" name="prompt"
                                        id="deocument_upload_prompt">
                                    <label class="form-check-label m-0 ml-1  padding-top-px-4 text-dark"
                                        for="edocument_upload_prompt">
                                        <b> DOCUMENT UPLOAD PROMPT
                                        </b>
                                    </label>
                                    <span><b><a class="link" data-open-modal="#editPrompt">(EDIT
                                                PROMPT)</a></b></span>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input w-h-px-20" type="checkbox" name="message"
                                        id="emessage">
                                    <label class="form-check-label m-0 ml-1  padding-top-px-4 text-dark" for="emessage">
                                        <b>AGENT/OFFICE POPUP MESSAGE
                                        </b>
                                    </label>
                                    <span><b><a class="link" data-open-modal="#editMessage">(EDIT
                                                MESSAGE)</a></b></span>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input w-h-px-20" type="checkbox" name="inventory"
                                        id="einventory">
                                    <label class="form-check-label m-0 ml-1  padding-top-px-4 text-dark"
                                        for="einventory">
                                        <b>AGENT/OFFICE INVENTORY</b>
                                    </label>
                                    <span><b><a data-open-modal="#editAccessoryAgentOffice" class="link">(EDIT
                                                INVENTORY)</a></b></span>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input w-h-px-20" type="checkbox" name="pull_list"
                                        id="epull_list">
                                    <label class="form-check-label m-0 ml-1  padding-top-px-4 text-dark"
                                        for="epull_list">
                                        <b>DISPLAY AGENT/OFFICE ON PULL LIST
                                    </label>
                                </div>

                                <div class="w-100 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center justify-content-between my-1">
                                        <label class="text-dark m-0" for="default"><b>DEFAULT:</b></label>
                                        <select type="text" id="default" name="default" class="form-control m-1"
                                            required="" required-error-msg="Default is required">
                                            <option value="1" class="text-muted">ON</option>
                                            <option value="0" class="text-muted">OFF</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label class="text-dark m-0" for="status"><b>STATUS:</b></label>
                                        <select type="text" id="status" name="status"
                                            class="form-control m-1 select-active" required=""
                                            required-error-msg="Status is required">
                                            <option value="1" class="text-muted">ACTIVE</option>
                                            <option value="0" class="text-muted">INACTIVE</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 font-weight-bold  mt-3">
                                    <a class="link" accessory-id accessory-name>EDIT AGENTS/OFFICE ACCESS</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('inventory.accessory.edit_prompt_modal')
        @include('inventory.accessory.edit_message_modal')
        @include('inventory.accessory.edit_accessory_offices_agent_modal')
    </div>
    </div>

</form>
