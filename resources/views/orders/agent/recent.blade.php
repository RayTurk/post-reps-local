@extends('layouts.auth')

@section('content')
    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>
    <div class="container-fluid pl-4 mt-1 pr-4 desktop-view hide-on-tablet">
        <div class="row ">
            <div class="col-md-3 menu-bar pb-3 h-25">
                @include('layouts.includes.order_bar')
            </div>
            <div class="col-md-7">
                @include('orders.notice_alert')
                <div class="card auth-card">
                        @include('orders.agent.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.agent.order_table')

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 menu-bar pb-3 h-25">
                @include('layouts.includes.account_resources')
            </div>
        </div>
    </div>
    <div class="container-fluid pl-4 mt-1 pr-4 tablet-view">
        <div class="row ">
            <div class="col-md-3 menu-bar pb-3 h-25">
                @include('layouts.includes.order_bar')
            </div>
            <div class="col-md-8">
                @include('orders.notice_alert')
                <div class="card auth-card">
                    @include('orders.agent.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.agent.order_table')
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 menu-bar text-center pb-5 h-25" style="padding-left: 2vw;">
                @include('layouts.includes.account_resources_tablet')
            </div>
        </div>
    </div>

    <div class="container-fluid pl-4 pr-4 mobile-view" style="margin-top: -30px;">
        <div class="row ">
            <div class="col-1"></div>
            <div class="col-12 p-4">
                @include('orders.notice_alert')
                <div class="card auth-card">
                    @include('orders.agent.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.agent.order_table')

                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="col-1"></div> --}}
        </div>
    </div>

    @if (!empty($latestNotice))
        @include('orders.agent.notice_acknowledgement_modal')
    @endif

    @include('orders.agent.install.install_modal')
    @include('orders.agent.install.payment_modal')
    @include('orders.agent.install.rush_order_modal')
    @include('orders.agent.install.duplicated_order_modal')

    @section('page_scripts')
        <script src="{{ mix('/js/agent-orders.js') }}" defer></script>
    @endsection

@endsection
