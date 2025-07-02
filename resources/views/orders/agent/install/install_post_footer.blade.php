 <div class="row">
     <div class="col-12 mt-4">
         <div class="d-block ml-auto width-px-200 font-px-16">
             <span>Signage Fee: <span install-post-signage>$0.00</span></span><br>
             <span>Trip/Zone Fee: <span install-post-zone-fee>$0.00</span></span><br>
             <span>Adjustments: <span install-post-adjustments>$0.00</span></span><br>
             <span id="rushFee" class="d-none">Rush Fee: <span install-post-rush-fee>${{$service_settings->rush_order}}</span></span><br>
             <span><h5>TOTAL: <span install-post-total>$0.00</span></h5></span>
         </div>
         <input type="hidden"  name="install_post_rush_fee" value="0">
         <input type="hidden"  name="install_post_signage" value="0">
         <input type="hidden"  name="install_post_zone_fee" value="0">
     </div>
     <div class="col-12 mt-4 d-flex">
         <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200" type="submit" id="submitOrder"><strong class="text-white">SUBMIT INSTALL</strong></button>

         @can('Admin', auth()->user())
         <button type="button" class="btn btn-primary rounded-pill mx-auto d-block width-px-100" id="openInstallPriceAdjustmentModalBtn">
             <strong class="text-white">+/- Fees</strong>
        </button>
        @endCan
     </div>
 </div>
