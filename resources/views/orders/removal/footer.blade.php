<div class="row">
     <div class="col-12 mt-4">
         <div class="d-block ml-auto width-px-200 font-px-16">
             <span>Removal Fees: <span removal-fee>${{ $serviceSettings->removal_fee ?? 0}}</span></span><br>
             <span>Zone Fee: <span removal-zone-fee>$0.00</span></span><br>
             <span>Adjustments: <span removal-adjustments>$0.00</span></span><br>
             <span id="rushFeeRemoval" class="d-none">Rush Fee: <span>${{ $serviceSettings->removal_rush_order ?? 0}}</span></span><br>
             <span><h5>TOTAL: <span removal-total>$0</span></h5></span>
         </div>

         <input type="hidden" name="removal_order_rush_fee" value="0.00">
         <input type="hidden" name="removal_order_fee" value="{{ $serviceSettings->removal_fee ?? 0}}">
         <input type="hidden" name="removal_order_zone_fee" value="0.00">
         <input type="hidden" name="discount_extra_post_removal" value="{{ $serviceSettings->discount_extra_post_removal ?? 0}}">
     </div>
     <div class="col-12 mt-4 d-flex">
        <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200" type="submit" id="submitRemovalOrder">
             <strong class="text-white">SUBMIT REMOVAL</strong>
        </button>

        @can('Admin', auth()->user())
         <button type="button" class="btn btn-primary rounded-pill mx-auto d-block width-px-100" id="openRemovalPriceAdjustmentModalBtn">
             <strong class="text-white">+/- Fees</strong>
        </button>
        @endCan
     </div>
 </div>
