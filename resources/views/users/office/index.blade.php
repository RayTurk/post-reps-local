@extends('layouts.auth')

@section('content')
    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>
    <div class="container-fluid p-0 offices-page">
        <div class="row justify-content-center">
            <div class="col-md-2 pb-3">
                <div class="desktop-view tablet-view">
                    @include('layouts.includes.order_bar_icons')
                </div>
            </div>
            <div class="col-md-8 position-relative bg-white px-0 mx-0">
                <ul class="nav nav-pills mb-3 ml-0" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link " id="pills-agents-tab" data-toggle="pill" href="#pills-agents"
                            role="tab">Agents</a>
                    </li>
                </ul>
                <div class="tab-content p-2" id="pills-tabContent">
                    @include('users.taps.office.agents')
                </div>
            </div>
            <div class="col-md-2 pb-3 d-flex justify-content-end">
                <div class="desktop-view tablet-view">
                    @include('layouts.includes.account_resources_icons')
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="container-fluid pl-4 mt-1 pr-4 tablet-view">
        <div class="row ">
            <div class="col-md-3 menu-bar pb-3">
                @include('orders.removal.order_bar_removal')
            </div>
            <div class="col-md-8 position-relative bg-white px-0 mx-0">
                <ul class="nav nav-pills mb-3 ml-0" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="pills-offices-tab" data-toggle="pill" href="#pills-offices"
                            role="tab">Offices</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link " id="pills-agents-tab" data-toggle="pill" href="#pills-agents"
                            role="tab">Agents</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link " id="pills-installers-tab" data-toggle="pill" href="#pills-installers"
                            role="tab">Installers</a>
                    </li>
                </ul>
                <div class="tab-content p-2" id="pills-tabContent">
                    @include('users.taps.offices')
                    @include('users.taps.agents')
                    @include('users.taps.installers')
                </div>
            </div>
            <div class="col-md-1 menu-bar text-center pb-5" style="padding-left: 2vw;">
                @include('layouts.includes.account_resources_tablet')
            </div>
        </div>
    </div> --}}

    {{-- modals --}}
    @include('users.office.create_agent_modal')
    @include('users.office.edit_agent_modal')


    @include('orders.office.install.install_modal')
    @include('orders.office.install.payment_modal')
    @include('orders.office.install.edit_order')
    @include('orders.office.install.rush_order_modal')
    @include('orders.office.install.duplicated_order_modal')
    @include('orders.office.install.pricing_adjustment_modal')

@endsection

@section('page_scripts')
    <script>
        window.isHaveErrorCreateOfficeFormModel = {{ old('createOfficeForm') ? 1 : 0 }}
    </script>
    <script src="{{ mix('/js/user.js') }}" defer></script>
    <script src="{{ mix('/js/office-orders.js') }}" defer></script>
@endsection
