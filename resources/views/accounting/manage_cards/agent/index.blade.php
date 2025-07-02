@extends('layouts.auth')

@section('content')

    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>
    <div class="container-fluid pl-4 mt-1 pr-4 desktop-view tablet-view">
        <div class="row ">
            <div class="col-md-1 pb-3">
                @include('layouts.includes.order_bar_icons')
            </div>
            <div class="col-md-10">
                @include('accounting.menu')

                <div class="card auth-card mt-1">
                    <div class="card-header d-flex justify-content-between">
                        <h6>MANAGE CREDIT CARDS</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 text-left">
                                <button data-toggle="modal" data-target="#addCardModal"
                                    class="btn bg-primary text-white ml-2 pt-1 pb-1 pl-4 pr-4 font-weight-bold"
                                >Add New Card</button>
                            </div>
                        </div>
                        @if (isset($cards))
                            @foreach ($cards as $key => $card)
                                <div class="text-center mt-4 font-weight-bold">
                                    <span>{{ $card['cardType'] }}: {{ $card['cardNumber'] }} exp {{ $card['expDate'] }}</span>
                                    <button class="btn bg-danger text-white ml-2 pt-1 pb-1 pl-4 pr-4 rounded-0 remove-card" id="removeCardButton" data-payment_profile_id="{{$key}}">Remove</button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>

    <div class="container-fluid pl-4 pr-4 mobile-view" style="margin-top: -15px;">
        <div class="row ">
            <div class="col-12 pb-3">
                @include('accounting.menu')
                <div class="card auth-card">
                    <div class="card-header d-flex justify-content-between">
                        <h6>MANAGE CREDIT CARDS</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 text-left">
                                <button data-toggle="modal" data-target="#addCardModal"
                                    class="btn bg-primary text-white ml-2 pt-1 pb-1 pl-4 pr-4 font-weight-bold"
                                >Add New Card</button>
                            </div>
                        </div>
                        @if (isset($cards))
                            @foreach ($cards as $key => $card)
                                <div class="text-center mt-4 font-weight-bold">
                                    <span>{{ $card['cardType'] }}: {{ $card['cardNumber'] }} exp {{ $card['expDate'] }}</span>
                                    <button class="btn bg-danger text-white ml-2 pt-1 pb-1 pl-4 pr-4 rounded-0 remove-card" id="removeCardButton" data-payment_profile_id="{{$key}}">Remove</button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Card Modal -->
    <div class="modal fade" id="addCardModal" data-keyboard="true" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h4 class="text-orange">ADD NEW CARD</h4>
                    <!-- <button type="button" class="close order-hold" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button> -->
                </div>
                <div class="modal-body">
                    <form action="{{ url('/accounting/manage-cards/add-card') }}" id="addCardForm" method="post">
                        @csrf
                        <div class="row">
                            <div class="row mx-4">
                                <div class="col-12 mb-2">
                                    <label for="add_card_number" class="text-dark">
                                        <strong>Card Number</strong>
                                    </label>
                                    <input type="text" maxlength="19" name="add_card_number" id="add_card_number"
                                        class="form-control cc-number-input" autocomplete="cc-number">
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="row">
                                        <div class="col-4">
                                            <label for="add_card_expire_date_month" class="text-dark">
                                                <strong>Expiration Month</strong>
                                            </label>
                                            <input type="text" name="add_card_expire_date_month" id="add_card_expire_date_month"
                                                class="form-control date-input-month" autocomplete="cc-exp-month">
                                        </div>
                                        <div class="col-4">
                                            <label for="add_card_expire_date_month" class="text-dark">
                                                <strong>Expiration Year</strong>
                                            </label>
                                            <input type="text" name="add_card_expire_date_year" id="add_card_expire_date_year"
                                                class="form-control date-input-year" autocomplete="cc-exp-year">
                                        </div>
                                        <div class="col-4">
                                            <label for="add_card_expire_date_month" class="text-dark">
                                                <strong>Security Code</strong>
                                            </label>
                                            <input type="text" name="add_card_code" id="add_card_code"
                                                class="form-control" maxlength="4" autocomplete="cc-csc">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="add_card_billing_name" class="text-dark">
                                                <strong>Name on Card</strong>
                                            </label>
                                            <input type="text" name="add_card_billing_name" billing-name id="add_card_billing_name"
                                                class="form-control" value="{{auth()->user()->name}}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="row">
                                        <div class="col-7">
                                            <label for="add_card_billing_address" class="text-dark">
                                                <strong>Address</strong>
                                            </label>
                                            <input type="text" name="add_card_billing_address" billing-address id="add_card_billing_address"
                                                class="form-control" value="{{auth()->user()->address}}">
                                        </div>
                                        <div class="col-5">
                                            <label for="add_card_billing_city" class="text-dark">
                                                <strong>City</strong>
                                            </label>
                                            <input type="text" name="add_card_billing_city" billing-city id="add_card_billing_city"
                                                class="form-control" value="{{auth()->user()->city}}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="row">
                                        <div class="col-7">
                                            <label for="add_card_billing_state" class="text-dark">
                                                <strong>State</strong>
                                            </label>
                                            <select id="add_card_billing_state" class="form-control" name="add_card_billing_state" >
                                                @foreach ($states as $code => $state)
                                                    <option value="{{ $code }}" @if (auth()->user()->state == $code) selected @endif>
                                                        {{ $state }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-5">
                                            <label for="add_card_billing_zip" class="text-dark">
                                                <strong>Zipcode</strong>
                                            </label>
                                            <input type="text" name="add_card_billing_zip" billing-zip id="add_card_billing_zip"
                                                class="form-control zipcode"  value="{{auth()->user()->zipcode}}">
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
                                <div class="col-6 mb-2">
                                    <button class="btn btn-orange  mx-auto d-block width-px-120 text-white font-weight-bold"
                                        id="submitCardBtn" type="submit"
                                    >SUBMIT
                                    </button>
                                </div>
                                <div class="col-6 mb-2">
                                    <button class="btn btn-secondary mx-auto d-block width-px-120 text-white font-weight-bold"
                                        data-dismiss="modal" type="button"
                                    >CANCEL
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    @include('orders.agent.install.install_modal')
    @include('orders.agent.install.payment_modal')
    @include('orders.agent.install.rush_order_modal')
    @include('orders.agent.install.duplicated_order_modal')

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/accounting-manage-cards.js') }}" defer></script>
    <script src="{{ mix('/js/agent-orders.js') }}" defer></script>
@endsection
