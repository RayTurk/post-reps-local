@extends('layouts.auth')

@section('content')
    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>
    <div class="container-fluid pl-4 mt-1 pr-4 desktop-view hide-on-tablet">
        <div class="row ">
            <div class="col-md-3 menu-bar-single pb-3">
                @include('orders.office.removal.order_bar_removal')
            </div>
            <div class="col-md-7">
                <div class="card auth-card">
                        @include('orders.office.removal.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.office.removal.order_table')

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 menu-bar pb-5">
                @include('layouts.includes.account_resources')
            </div>
        </div>
    </div>

    <div class="container-fluid pl-4 mt-1 pr-4 tablet-view">
        <div class="row ">
            <div class="col-md-3 menu-bar pb-3">
                @include('orders.office.removal.order_bar_removal')
            </div>
            <div class="col-md-8">
                <div class="card auth-card">
                    @include('orders.office.removal.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.office.removal.order_table_tablet')
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 menu-bar text-center pb-5" style="padding-left: 2vw;">
                @include('layouts.includes.account_resources_tablet')
            </div>
        </div>
    </div>

    <div class="container-fluid pl-4 pr-4 mobile-view" style="margin-top: -30px;">
        <div class="row ">
            <div class="col-1"></div>
            <div class="col-12 p-4">
                <div class="card auth-card">
                    @include('orders.office.removal.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.office.removal.order_table_mobile')

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-1"></div>
        </div>
    </div>
    @include('orders.office.removal.order_modal');
    @include('orders.office.removal.rush_order_modal')
    @include('orders.office.removal.payment_modal')
    @include('orders.office.removal.multiple_posts_modal')
    @include('orders.office.removal.pricing_adjustment_modal')
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/office-removal-order.js') }}" defer></script>
@endsection
