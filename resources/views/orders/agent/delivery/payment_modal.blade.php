<!-- Modal -->
<div class="modal fade" id="deliveryPaymentModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h3 class="text-orange">PAYMENT INFORMATION</h3>
                <!-- <button type="button" class="close order-hold" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button> -->
            </div>
            <div class="modal-body">
                <form action="{{ url('/payment/delivery/pay') }}" id="deliveryPaymentForm" method="post">
                    @csrf
                    <input type="hidden" name="delivery_order_id"  >
                    <div class="row">
                        <div class="col-12">
                            <input type="checkbox" name="delivery_payment_type" value="use_card" id="delivery_use_card_profile"
                                class="m-0 mx-1 scale-1_5">
                            <label for="delivery_use_card_profile" class="text-primary font-px-18"><strong>Use Card On
                                    File</strong></label>
                        </div>
                        <div class="col-12">
                            <select name="delivery_card_profile" id="delivery_card_profile_select" class="form-control">
                            </select>
                        </div>
                        <div class="col-12">
                            <hr>
                        </div>
                        <div class="col-12">
                            <input type="checkbox" name="delivery_payment_type" value="new_card" id="delivery_use_another_card" class="m-0 mx-1 scale-1_5">
                            <label for="delivery_use_another_card" class="text-primary font-px-18"><strong>Enter Another
                                    Card</strong></label>
                        </div>
                        <div class="row mx-4 form-another-card">
                            <div class="col-12 mb-2">
                                <label for="card_number" class="text-dark">
                                    <strong>Card Number</strong>
                                </label>
                                <input type="text" maxlength="19" name="delivery_card_number" id="delivery_card_number"
                                    class="form-control cc-number-input" autocomplete="cc-number">
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-4">
                                        <label for="expire_date_month" class="text-dark">
                                            <strong>Expiration Month</strong>
                                        </label>
                                        <input type="text" name="delivery_expire_date_month" id="delivery_expire_date_month"
                                            class="form-control date-input-month" autocomplete="cc-exp-month">
                                    </div>
                                    <div class="col-4">
                                        <label for="expire_date_month" class="text-dark">
                                            <strong>Expiration Year</strong>
                                        </label>
                                        <input type="text" name="delivery_expire_date_year" id="delivery_expire_date_year"
                                            class="form-control date-input-year" autocomplete="cc-exp-year">
                                    </div>
                                    <div class="col-4">
                                        <label for="expire_date_month" class="text-dark">
                                            <strong>Security Code</strong>
                                        </label>
                                        <input type="text" name="delivery_card_code" id="delivery_card_code"
                                            class="form-control" maxlength="4" autocomplete="cc-csc">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="delivery_billing_name" class="text-dark">
                                            <strong>Name on Card</strong>
                                        </label>
                                        <input type="text" name="delivery_billing_name" delivery-billing-name id="delivery_billing_name"
                                            class="form-control" value="{{auth()->user()->name}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-7">
                                        <label for="billing_address" class="text-dark">
                                            <strong>Billing Address</strong>
                                        </label>
                                        <input type="text" name="delivery_billing_address" delivery-billing-address id="delivery_billing_address"
                                            class="form-control" value="{{auth()->user()->address}}">
                                    </div>
                                    <div class="col-5">
                                        <label for="billing_city" class="text-dark">
                                            <strong>Billing City</strong>
                                        </label>
                                        <input type="text" name="delivery_billing_city" delivery-billing-city id="delivery_billing_city"
                                            class="form-control" value="{{auth()->user()->city}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="row">
                                    <div class="col-7">
                                        <label for="billing_state" class="text-dark">
                                            <strong>Billing State</strong>
                                        </label>
                                        <select id="delivery_billing_state" class="form-control" delivery-billing-state name="delivery_billing_state">
                                            @foreach ($states as $code => $state)
                                                <option value="{{ $code }}" @if (auth()->user()->state == $code) selected @endif>
                                                    {{ $state }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <label for="billing_zip" class="text-dark">
                                            <strong>Billing Zipcode</strong>
                                        </label>
                                        <input type="text" name="delivery_billing_zip" delivery-billing-zip id="delivery_billing_zip"
                                            class="form-control zipcode" value="{{auth()->user()->zipcode}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-center mt-2">
                                <strong class="text-muted ">Card is encripted and saved for future
                                    transactions</strong>
                            </div>
                            <div class="col-12 mb-2">
                                <hr>
                            </div>
                            <div class="col-12 mb-2 text-center">
                                <strong class="text-dark">Total Payment: $<span
                                        delivery-payment-total-amount>48.00</span></strong>
                            </div>
                            <div class="col-12 mb-2">
                                <button class="btn btn-orange rounded-pill mx-auto d-block width-px-200 text-white font-weight-bold"
                                     id="submitDeliveryPaymentBtn" type="submit"
                                >SUBMIT PAYMENT
                                </button>
                            </div>

                            <div class="col-12 mb-2 text-center">
                                <a class="text-primary font-weight-bold font-px-18 delivery-order-hold" href="#">Order Hold</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
