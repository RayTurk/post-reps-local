<div class="row">
     <div class="col-12 mt-4">
         <div class="d-block ml-auto width-px-200 font-px-16">
             <span>Repair Trip: <span repair-trip-fee>${{ $serviceSettings->repair_trip_fee ?? 0}}</span></span><br>
             <span>Changes/Repairs: <span repair-fee>$0.00</span></span><br>
             <span>Zone Fee: <span repair-zone-fee>$0.00</span></span><br>
             <span>Adjustments: <span repair-adjustments>$0.00</span></span><br>
             <span id="rushFeeRepair" class="d-none">Rush Fee: <span>${{ $serviceSettings->repair_rush_order ?? 0}}</span></span><br>
             <span><h5>TOTAL: <span repair-total>${{ $serviceSettings->repair_trip_fee ?? 0 }}</span></h5></span>
         </div>
         <input type="hidden"  name="repair_trip_fee" value="{{ $serviceSettings->repair_trip_fee ?? 0}}">
         <input type="hidden"  name="repair_order_rush_fee" value="0.00">
         <input type="hidden"  name="repair_order_fee" value="0.00">
         <input type="hidden"  name="repair_order_zone_fee" value="0.00">
     </div>
     <div class="col-12 mt-4 d-flex">
        <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200" type="submit" id="submitRepairOrder">
             <strong class="text-white">SUBMIT REPAIR</strong>
        </button>

        @can('Admin', auth()->user())
         <button type="button" class="btn btn-primary rounded-pill mx-auto d-block width-px-100" id="openRepairPriceAdjustmentModalBtn">
             <strong class="text-white">+/- Fees</strong>
        </button>
        @endCan
     </div>
 </div>
