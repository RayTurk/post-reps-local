<!-- Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content auth-card">
            <div class="modal-header text-center">
                <h5 class="modal-title font-weight-bold w-100" id="editPaymentModal">EDIT PAYMENT</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="w-100">
                    <form id="editPaymentForm">
                        <div class="form-group">
                            <label for="paymentAmount">Amount</label>
                            <input type="text" name="payment_amount" step="0.01" class="width-px-120 form-control text-right" id="paymentAmountEdit" required>
                        </div>
                        <div class="form-group">
                            <label for="paymentCheckNumber">Check Number</label>
                            <input type="text" name="payment_check_number" class="form-control" id="paymentCheckNumberEdit" required>
                        </div>
                        <div class="form-group">
                            <label for="paymentComment">Comment</label>
                            <textarea name="payment_comments" class="form-control" id="paymentCommentEdit" cols="30" rows="5" required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-orange font-weigth-bold text-white" id="submitEditPaymentButton">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
