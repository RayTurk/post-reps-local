<!-- Modal -->
<div class="modal fade" id="reverseCardPaymentModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 >Reverse Card Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body mb-5">
                <div class="row">
                    <div class="col-12 mb-2 d-flex justify-content-between">
                        <a
                            id="refundToBalance"
                            href="#"
                            class="btn btn-orange mx-auto d-block width-px-200 text-white font-weight-bold"
                            type="submit"
                        >CREDIT BALANCE
                        </a>
                        <a
                            id="refundToCard"
                            href="#"
                            class="btn btn-orange mx-auto d-block width-px-200 text-white font-weight-bold"
                            type="submit"
                        >REFUND CARD
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="reverseCardPaymentModalSingleOrder" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 >Refund Order Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="margin-top: -10px;">
                <form action="" id="reversePaymentFormSingleOrder" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="card_refund_amount" class="text-dark">
                                <strong>Amount to Refund</strong>
                            </label>
                            <input type="number" step="any" name="card_refund_amount"
                            id="card_refund_amount" class="form-control col-4 text-right"
                            required autocomplete="off" >
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-6 mb-2">
                            <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200 text-white font-weight-bold"
                                    id="submitRefundPaymentBtn" type="submit"
                            >SUBMIT REFUND
                            </button>
                        </div>
                        <div class="col-6 mb-2 text-right">
                            <button
                                class="btn btn-secondary rounded-pill width-px-100 text-white font-weight-bold"
                                type="button" data-dismiss="modal">CANCEL</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


