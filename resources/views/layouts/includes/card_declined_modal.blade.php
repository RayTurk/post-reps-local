<div class="modal fade" id="cardDeclinedModal" tabindex="-1" aria-labelledby="cardDeclinedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-orange ">
            <div class="modal-header bg-orange  d-flex justify-content-center">
                <h5 class="modal-title text-white" id="cardDeclinedModalLabel">
                    CARD DECLINED
                </h5>
            </div>
            <div class="modal-body ">
                <span class="font-weight-bold" id="cardDeclinedMsg">
                    The card selected has been entered incorrectly or has been declined. Please check the card numbers or enter a different card. If a card is declined three consecutive times, the card will be removed from our system and would need to be reentered.
                </span>
                <div class="text-center mt-3">
                    <button data-dismiss-modal="#cardDeclinedModal"
                        class="btn btn-orange text-white width-rem-10"><strong>OK</strong></button>
                    <br><br>
                    <a class="text-primary font-weight-bold font-px-18" id="cardDeclinedHoldOrderBtn"
                        href="#" >
                        Hold Order
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
