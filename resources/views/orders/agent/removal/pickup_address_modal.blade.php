<div class="modal fade" id="pickupAddressModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 class="modal-title">Pickup Address</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="px-4">
                    <div class="row">
                        <div class="col-12 mt-4">
                            <label for="address" class="text-primary"><span class="blue-label">Property Address</span></label>
                            <div class="d-flex flex-column flex-md-row flex-lg-row">
                                <input type="text" class="form-control col-12 col-md-4 col-lg-4 mt-2 mr-2" name="install_post_address"
                                    placeholder="Type Home Address" id="address">
                                <input type="text" class="form-control col-12 col-md-3 col-lg-4 mt-2 mr-2" name="install_post_city"
                                    placeholder="City" id="city">
                                <select type="text" class="form-control col-12 col-md-2 col-lg-2 mt-2 mr-2" name="install_post_state" id="state">
                                    @if (!empty($states))
                                        @foreach ($states as $key => $state)
                                            <option value="{{ $key }}" {{ $key == "ID" ? 'selected' : '' }}>{{$state}}</option>
                                        @endforeach
                                    @endif
                                </select>

                                <button class="btn btn-orange col-12 col-md-3 col-lg-2 mt-2" type="button" id="updateRemovalMap"><strong class="text-white">UPDATE MAP</strong></button>
                            </div>
                        </div>
                        <div class="col-12 my-3">
                            <div class="width-100 height-rem-22 border border-dark shadow" id="removal-pickup-search-map"></div>
                        </div>
                        <div class="col-12">
                            <label class="text-primary"><span class="blue-label">Location Adjustment</span></label>
                            <div class="d-flex justify-content-start align-items-center px-2">
                                <input type="checkbox" class="form-control w-h-px-25 mr-2" name="removal_pickup_location_adjustment"
                                    id="removal_pickup_location_adjustment">
                                <label for="removal_pickup_location_adjustment" class="text-dark m-0"><b>Manually move pin placement on
                                        map</b></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6"></div>
                    <div class="col-md-6 text-center text-danger font-weight-bold font-rem-1">
                        Additional Address change Fee: ${{\App\Models\ServiceSetting::find(1)->additional_pickup_fee}}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row mt-0">
                    <div class="col-12 d-flex">
                        <button class="btn btn-orange rounded-pill mx-2 d-block width-px-200" type="button" id="savePickupAddressBtn">
                            <strong class="text-white">Save</strong>
                        </button>
                        <button type="button" class="btn btn-secondary rounded-pill mx-2 d-block" data-dismiss="modal" aria-label="Close">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
