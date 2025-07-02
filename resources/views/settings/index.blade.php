@extends('layouts.auth')

@section('content')
    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>
    <div class="container-fluid pl-4 mt-1 pr-4">
        <div class="row d-flex justify-content-between">
            <div class="px-3 pb-3 desktop-view tablet-view">
                @include('layouts.includes.order_bar_icons')
            </div>
            <div class="col-md-8">
                <div class="card auth-card">
                    @include('settings.settings_form_desktop')
                </div>
            </div>
            <div class="px-3 pb-3 desktop-view tablet-view">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>

    <!--<div class="container-fluid pl-4 mt-2 pr-4 mobile-view">
        <div class="row ">
            {{-- <div class="col-1"></div> --}}
            <div class="col-12 pb-3">
                {{-- MOBILE FORM --}}
                <div class="card auth-card">
                    @include('settings.settings_form_mobile')
                </div>
            </div>
            {{-- <div class="col-1"></div> --}}
        </div>
    </div>-->

    @can('Admin', auth()->user())
        @include('layouts.includes.install_modal')
        @include('layouts.includes.payment_modal')
        @include('layouts.includes.edit_order')
        @include('layouts.includes.rush_order_modal')
        @include('layouts.includes.duplicated_order_modal')
        @include('layouts.includes.pricing_adjustment_modal')
    @endCan

    @can('Office', auth()->user())
        @include('orders.office.install.install_modal')
        @include('orders.office.install.payment_modal')
        @include('orders.office.install.rush_order_modal')
        @include('orders.office.install.duplicated_order_modal')
        @include('settings.notifications.office.notification_email_modal')
        @include('settings.notifications.office.add_email_modal')
    @endCan

    @can('Agent', auth()->user())
        @include('orders.agent.install.install_modal')
        @include('orders.agent.install.payment_modal')
        @include('orders.agent.install.rush_order_modal')
        @include('orders.agent.install.duplicated_order_modal')
        @include('settings.change_office_modal')
        @include('settings.notifications.agent.notification_email_modal')
        @include('settings.notifications.agent.add_email_modal')
    @endCan

    @include('settings.change_password_modal')

    @section('page_scripts')
        @can('Office', auth()->user())
            <script src="{{ mix('/js/office-orders.js') }}" defer></script>
        @endcan

        @can('Agent', auth()->user())
            <script src="{{ mix('/js/agent-orders.js') }}" defer></script>
        @endcan
        <script src="{{ mix('js/user.js') }}" defer></script>
    @endsection
@endsection
