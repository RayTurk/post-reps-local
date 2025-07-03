@extends('layouts.auth')

@section('content')
    <div class="inventory-page">
        <div class="container p-0">
            @include('layouts.includes.alerts')
        </div>
        <div class="container-fluid p-0 inventory-page">
            <div class="row justify-content-center">
                <div class="col-md-2 pb-3">
                    <div class="desktop-view tablet-view">
                        @include('layouts.includes.order_bar_icons')
                    </div>
                </div>
                <div class="col-md-8 position-relative bg-white px-0">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pills-panels-tab" data-toggle="pill" href="#pills-panels"
                                role="tab" aria-controls="pills-panels" aria-selected="true">Panels</a>
                        </li>
                    </ul>
                    <div class="tab-content p-2" id="pills-tabContent">
                        @include('inventory.agent.taps.panels')
                    </div>
                </div>
                <div class="col-md-2 pb-3 d-flex justify-content-end">
                    <div class="desktop-view tablet-view">
                        @include('layouts.includes.account_resources_icons')
                    </div>
                </div>
            </div>
        </div>

        {{-- modals --}}
        @include('orders.agent.install.install_modal')
        @include('orders.agent.install.payment_modal')
        @include('orders.agent.install.edit_order')
        @include('orders.agent.install.rush_order_modal')
        @include('orders.agent.install.duplicated_order_modal')
        @include('orders.agent.install.pricing_adjustment_modal')
    </div>
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/agent-orders.js') }}" defer></script>
    <script src="{{ mix('/js/agent-inventory.js') }}" defer></script>
@endsection
