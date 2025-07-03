<div class="row">
     <div class="col-12 mt-4">
         <div class="d-block ml-auto width-px-200 font-px-16">
             <span>Delivery Fee: <span delivery-fee>${{$serviceSettings->delivery_trip_fee ?? 0}}</span></span><br>
             <span>Zone Fee: <span delivery-zone-fee>$0.00</span></span><br>
             <span>Adjustments: <span delivery-adjustments>$0.00</span></span><br>
             <span id="rushFeeDelivery" class="d-none">Rush Fee: <span>${{ $serviceSettings->delivery_rush_order ?? 0}}</span></span><br>
             <span><h5>TOTAL: <span delivery-total>$0</span></h5></span>
         </div>
         <input type="hidden"  name="delivery_order_fee" value="{{ $serviceSettings->delivery_trip_fee ?? 0}}">
         <input type="hidden"  name="delivery_order_rush_fee" value="{{ $serviceSettings->delivery_rush_order ?? 0}}">
         <input type="hidden"  name="delivery_order_zone_fee" value="0">
     </div>
     <div class="col-12 mt-4 d-flex">
        <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200" type="submit" id="submitDeliveryOrder">
             <strong class="text-white">SUBMIT DELIVERY</strong>
        </button>

        @can('Admin', auth()->user())
         <button type="button" class="btn btn-primary rounded-pill mx-auto d-block width-px-100" id="openDeliveryPriceAdjustmentModalBtn">
             <strong class="text-white">+/- Fees</strong>
        </button>
        @endCan
     </div>
 </div>
