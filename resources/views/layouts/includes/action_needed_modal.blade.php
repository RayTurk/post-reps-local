<div class="modal fade" id="actionNeededModal" tabindex="-1" aria-labelledby="actionNeededModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-orange ">
            <div class="modal-header bg-orange  d-flex justify-content-center">
                <h5 class="modal-title text-white" id="actionNeededModalLabel">
                    ACTION NEEDED
                </h5>
            </div>
            <div class="modal-body ">
                <span class="font-weight-bold" id="actionNeededMsg">
                    Your order has been put on HOLD and will not be completed until you
                     have updated the order with a valid payment method. Please be sure
                      to do so as soon as possible to avoid any delays.
                </span>
                <div class="text-center mt-3">
                    <button id="closeActionNeededModalBtn"
                        class="btn btn-orange text-white width-rem-10"><strong>CLOSE</strong>
                    </button>
                    <input type="hidden" id="actionNeededOrdertype">
                    <input type="hidden" id="actionNeededOrderId">
                </div>
            </div>

        </div>
    </div>
</div>
