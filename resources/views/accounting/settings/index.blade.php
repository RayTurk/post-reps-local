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
                        <h6>ACCOUNTING SETTINGS</h6>
                    </div>
                    <div class="card-body font-weight-bold">
                        @include('accounting.settings.settings_desktop')
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
                        <h6>ACCOUNTING SETTINGS</h6>
                    </div>
                    <div class="card-body font-weight-bold">
                        @include('accounting.settings.settings_mobile')
                    </div>
                </div>
            </div>
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
<script src="{{ mix('/js/accounting-settings.js') }}" defer></script>
@endsection
