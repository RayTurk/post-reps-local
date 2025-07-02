<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">2</span>
    <h5 class="pt-2">PROPERTY INFORMATION</h5>
</div>
<div class="px-4">
    <div class="row">
        <div class="col-12 mt-1">
            <label for="address" class="text-primary"><span class="blue-label">Property Address</span></label>
            <div class="d-flex flex-column flex-md-row flex-lg-row">
                {{-- <input type="text" class="form-control w-75" name="delivery_order_address"
                    placeholder="Write address and press [ENTER]" id="delivery_order_address" value="fuller park , boise"> --}}
                <input type="text" class="form-control col-12 col-md-4 col-lg-4 mt-2 mr-2" name="delivery_order_address"
                placeholder="Type Home Address" id="delivery_order_address" required>
                <input type="text" class="form-control col-12 col-md-3 col-lg-4 mt-2 mr-2" name="delivery_order_city"
                    placeholder="City" id="delivery_order_city" required>
                <select type="text" class="form-control col-12 col-md-2 col-lg-2 mt-2 mr-2" name="delivery_order_state" id="delivery_order_state" required>
                    @if (!empty($states))
                        @foreach ($states as $key => $state)
                            <option value="{{ $key }}" {{ $key == "ID" ? 'selected' : '' }}>{{$state}}</option>
                        @endforeach
                    @endif
                </select>
                <button class="btn btn-orange col-12 col-md-3 col-lg-2 mt-2 mr-2" type="button" id="updateDeliveryMap"><strong class="text-white">UPDATE MAP</strong></button>
            </div>
        </div>
        <div class="col-12 my-3">
            <div class="width-100 height-rem-22 border border-dark shadow" id="deliveryOrderMap"></div>
        </div>
        <div class="col-12">
            <label class="text-primary"><span class="blue-label">Location Adjustment</span></label>
            <div class="d-flex justify-content-start align-items-center px-2">
                <input type="checkbox" class="form-control w-h-px-25 mr-2" name="delivery_location_adjustment"
                    id="delivery_location_adjustment">
                <label for="delivery_location_adjustment" class="text-dark m-0"><b>Manually move pin placement on
                        map</b></label>
            </div>
        </div>
    </div>
</div>

