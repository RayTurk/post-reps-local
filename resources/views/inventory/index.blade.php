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
                            <a class="nav-link active" id="pills-posts-tab" data-toggle="pill" href="#pills-posts" role="tab"
                                aria-controls="pills-posts" aria-selected="false">Posts</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pills-panels-tab" data-toggle="pill" href="#pills-panels"
                                role="tab" aria-controls="pills-panels" aria-selected="true">Panels</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pills-accessories-tab" data-toggle="pill"
                                href="#pills-accessories" role="tab" aria-controls="pills-accessories"
                                aria-selected="false">Accessories</a>
                        </li>
                    </ul>
                    <div class="tab-content p-2" id="pills-tabContent">
                        @include('inventory.taps.posts')
                        @include('inventory.taps.panels')
                        @include('inventory.taps.accessories')
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
        @include('inventory.post.create_modal')
        @include('inventory.post.edit_modal')
        @include('inventory.post.access_model')
        @include('inventory.panel.create_modal')
        @include('inventory.panel.edit_modal')
        @include('inventory.panel.settings_modal')
        @include('inventory.post.settings_modal')
        @include('inventory.accessory.create_modal')
        @include('inventory.accessory.edit_modal')

        @include('layouts.includes.install_modal')
        @include('layouts.includes.payment_modal')
        @include('layouts.includes.edit_order')
        @include('layouts.includes.rush_order_modal')
        @include('layouts.includes.duplicated_order_modal')
        @include('layouts.includes.pricing_adjustment_modal')
    </div>
@endsection

@section('page_scripts')
    <script src="{{ mix('/js/inventory.js') }}" defer></script>
@endsection
