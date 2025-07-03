<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">2</span>
    <h5 class="pt-2">PROPERTY INFORMATION</h5>
</div>
<div class="px-4">
    <div class="row">
        <div class="col-12 mt-4">
            <label for="removalOrderAddress" class="text-primary"><span class="blue-label">Property Address</span></label>
            <div class="d-flex flex-column flex-md-row flex-lg-row">
                <{{-- <input type="text" class="form-control w-100" id="removalOrderAddress" disabled
                    placeholder="Write address and press [ENTER]"  value="fuller park , boise"> --}}
                <input type="text" class="form-control col-12 col-md-4 col-lg-4 mt-2 mr-2"
                placeholder="Type Home Address" id="removalOrderAddress" disabled>
                <input type="text" class="form-controlcol-12 col-md-4 col-lg-4 mt-2 mr-2"
                    placeholder="City" id="removalOrderCity" disabled>
                <select type="text" class="form-control col-12 col-md-3 col-lg-3 mt-2 mr-2" id="removalOrderState" disabled>
                    @if (!empty($states))
                        @foreach ($states as $key => $state)
                            <option value="{{ $key }}" {{ $key == "ID" ? 'selected' : '' }}>{{$state}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="col-12 mt-4 d-none additional-pickup-address-row">
            <label for="removalPickupAddress" class="text-primary"><span class="blue-label">Pickup Address</span></label>
            <div class="d-flex flex-column flex-md-row flex-lg-row">
                <input type="text" class="form-control col-12 col-md-4 col-lg-4 mt-2 mr-2"
                placeholder="Type Home Address" id="removalPickupAddress" disabled>
                <input type="text" class="form-control col-12 col-md-4 col-lg-4 mt-2 mr-2"
                    placeholder="City" id="removalPickupCity" disabled>
                <select type="text" class="form-control col-12 col-md-3 col-lg-3 mt-2 mr-2" id="removalPickupState" disabled>
                    @if (!empty($states))
                        @foreach ($states as $key => $state)
                            <option value="{{ $key }}" {{ $key == "ID" ? 'selected' : '' }}>{{$state}}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="col-12 mt-1 text-center">
            <a href="#" class="btn btn-orange text-center mt-2 mr-2" data-toggle="modal" data-target="#pickupAddressModal"><strong class="text-white">Pickup at Different Address</strong></a>
        </div>
        <!-- <div class="col-12 my-3">
            <div class="width-100 height-rem-22 border border-dark shadow" id="removalOrderMap"></div>
        </div> -->

    </div>
</div>
<!-- <div class="text-orange-dark d-flex align-items-center mt-4 gap-px-4">
    <span class="cnumber">3</span>
    <h5 class="pt-2">PROPERTY TYPE</h5>
</div> -->
<!-- <div class="d-flex justify-content-start align-items-center pl-5">
    <label for="removalOrderPropertyType" class="text-dark m-0"><b>What type of Property is
            this?</b></label>
    <select  id="removalOrderPropertyType"
        class="form-control form-control-sm width-rem-15 ml-2" disabled>
        <option value="" selected>Select property type</option>
        <option value="1">Existing Home/Condo</option>
        <option value="2">New Construction</option>
        <option value="3">Vacant Land</option>
        <option value="4">Commercial/Industrial</option>
    </select>
</div> -->
