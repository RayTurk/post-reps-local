<!-- Modal -->
<div class="modal fade" id="duplicateOrderModal" tabindex="-1" aria-labelledby="rushOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-orange ">
            <div class="modal-header bg-orange  d-flex justify-content-center">
                <h5 class="modal-title text-white" id="rushOrderModalLabel"><i class="fas fa-exclamation-triangle"></i>
                    DUPLICATE ORDER DETECTED</h5>
            </div>
            <div class="modal-body ">
                <strong>
                    The system shows you have an order already pending for the same address.
                     Do you wish to add another install for this property?
                </strong>
                <div class="d-flex justify-content-around align-items-center mt-3">
                    <button id="yesDuplicateOrderBtn" data-dismiss-modal="#duplicateOrderModal"
                        class="btn btn-orange text-white width-rem-10"><strong>YES</strong></button>
                    <button id="noDuplicateOrderBtn" data-dismiss-modal="#duplicateOrderModal"
                        class="btn btn-primary text-white width-rem-10"><strong>NO</strong></button>
                </div>
            </div>

        </div>
    </div>
</div>
