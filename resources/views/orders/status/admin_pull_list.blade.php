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
                <div class="d-flex justify-content-start">
                    <a href="{{url('/order/status')}}" class="btn btn-primary btn-sm width-px-100 font-weight-bold font-px-17" id="ordersActive">Active</a>
                    <a href="{{url('/order/status/history')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersHistory">History</a>
                    @can('Admin', auth()->user())
                    <a href="{{url('/order/status/routes')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersRoutes">Routes</a>
                    <a href="{{url('/order/status/pull-list')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersPullList">Pull List</a>
                    @endCan
                </div>
                @include('orders.status.admin_pull_list_header')
                <div class="card auth-card">
                    <div class="card-body">
                        @include('orders.status.admin_pull_list_card')
                    </div>
                </div>
            </div>
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>

    <div class="container-fluid pl-4 pr-4 mobile-view" style="margin-top: -30px;">
        <div class="row ">
            <div class="col-1"></div>
            <div class="col-12  p-4">
                <div class="d-flex justify-content-start">
                    <a href="{{url('/order/status')}}" class="btn btn-primary btn-sm width-px-100 font-weight-bold font-px-17" id="ordersActive">Active</a>
                    <a href="{{url('/order/status/history')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersHistory">History</a>
                    @can('Admin', auth()->user())
                    <a href="{{url('/order/status/routes')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersRoutes">Routes</a>
                    <a href="{{url('/order/status/pull-list')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersPullList">Pull List</a>
                    @endCan
                </div>
                @include('orders.status.admin_pull_list_header')
                <div class="card auth-card">
                    <div class="card-body">
                        @include('orders.status.admin_pull_list_card')
                    </div>
                </div>
            </div>
            <div class="col-1"></div>
        </div>
    </div>

    @include('layouts.includes.install_modal')
    @include('layouts.includes.payment_modal')
    @include('layouts.includes.edit_order')
    @include('layouts.includes.rush_order_modal')
    @include('layouts.includes.duplicated_order_modal')
    @include('layouts.includes.pricing_adjustment_modal')

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/dashboard.js') }}" defer></script>
@endsection
