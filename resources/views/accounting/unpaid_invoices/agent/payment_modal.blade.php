<!-- Modal -->
<div class="modal fade" id="invoicePaymentModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 >Process Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="margin-top: -10px;">
                <form action="{{ url('/accounting/unpaid/invoices/payment') }}" id="invoicePaymentForm" method="post">
                    @csrf
                    <input type="hidden" name="invoice_id">
                    <input type="hidden" name="payment_method" value="card">
                    <input type="hidden" name="convenience_fee_amount" id="convenience_fee_amount" value="0">

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="card_payment_amount" class="text-dark">
                                <strong>Payment Amount</strong>
                            </label>
                            <input type="number" step="any" name="card_payment_amount"
                            id="card_payment_amount" class="form-control col-4 text-right"
                            required autocomplete="off" value="{{old('card_payment_amount')}}">
                        </div>
                        <div class="col-12">
                            <input type="checkbox" name="payment_type" value="use_card" id="use_card_profile"
                                class="m-0 mx-1 scale-1_5">
                            <label for="use_card_profile" class="text-primary font-px-15"><strong>Use Card On
                                    File</strong></label>
                        </div>
                        <div class="col-12">
                            <select name="card_profile" id="card_profile_select" class="form-control">
                            </select>
                            <input type="hidden" name="card_info">
                        </div>
                        <div class="col-12">
                            <hr>
                        </div>
                        <div class="col-12">
                            <input type="checkbox" name="payment_type" value="new_card" id="use_another_card" class="m-0 mx-1 scale-1_5">
                            <label for="use_another_card" class="text-primary font-px-15"><strong>Enter Another
                                    Card</strong></label>
                        </div>
                        <div class="row mx-4 form-another-card">
                            <div class="col-12 mb-2">
                                <label for="card_number" class="text-dark">
                                    <strong>Card Number</strong>
                                </label>
                                <input type="text" maxlength="19" name="card_number" id="card_number"
                                    class="form-control cc-number-input">
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-4">
                                        <label for="expire_date_month" class="text-dark">
                                            <strong>Expiration Month</strong>
                                        </label>
                                        <input type="text" name="expire_date_month" id="expire_date_month"
                                            class="form-control date-input-month">
                                    </div>
                                    <div class="col-4">
                                        <label for="expire_date_month" class="text-dark">
                                            <strong>Expiration Year</strong>
                                        </label>
                                        <input type="text" name="expire_date_year" id="expire_date_year"
                                            class="form-control date-input-year">
                                    </div>
                                    <div class="col-4">
                                        <label for="expire_date_month" class="text-dark">
                                            <strong>Security Code</strong>
                                        </label>
                                        <input type="text" name="card_code" id="card_code"
                                            class="form-control" maxlength="4" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="billing_first_name" class="text-dark">
                                            <strong>Name on Card</strong>
                                        </label>
                                        <input type="text" name="billing_name" billing-name id="billing_name"
                                            class="form-control" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-7">
                                        <label for="billing_address" class="text-dark">
                                            <strong>Billing Address</strong>
                                        </label>
                                        <input type="text" name="billing_address" billing-address id="billing_address"
                                            class="form-control" >
                                    </div>
                                    <div class="col-5">
                                        <label for="billing_city" class="text-dark">
                                            <strong>Billing City</strong>
                                        </label>
                                        <input type="text" name="billing_city" billing-city id="billing_city"
                                            class="form-control" >
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-7">
                                        <label for="billing_state" class="text-dark">
                                            <strong>Billing State</strong>
                                        </label>
                                        <select id="billing_state" class="form-control" billing-state name="billing_state" disabled>
                                            @foreach ($states as $code => $state)
                                                <option value="{{ $code }}" >
                                                    {{ $state }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <label for="billing_zip" class="text-dark">
                                            <strong>Billing Zipcode</strong>
                                        </label>
                                        <input type="text" name="billing_zip" billing-zip id="billing_zip"
                                            class="form-control zipcode">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-center mt-2">
                                <strong class="text-muted ">
                                    Card is encripted and saved for future transactions
                                </strong>
                            </div>
                            <div class="col-12 text-center d-none" id="convenienceFeeMessageDiv" data-convenience-fee="{{$service_settings->convenience_fee}}">
                                <strong class="text-muted ">
                                    A {{$service_settings->convenience_fee}}% conveninence fee will be applied.
                                </strong>
                            </div>
                            <div class="col-12 mb-2">
                                <hr>
                            </div>
                            <div class="col-12 mb-2 text-center">
                                <strong class="text-dark">Amount Due: $<span
                                    invoice-amount-due>48.00</span></strong>
                            </div>
                            <div class="col-12 mb-2">
                                <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200 text-white font-weight-bold"
                                        id="submitDeliveryPaymentBtn" type="submit"
                                >SUBMIT PAYMENT
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
