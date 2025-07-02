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
                    <a href="{{url('/communications/notices')}}" class="btn btn-primary btn-sm width-px-100 font-weight-bold font-px-17" id="communicationsNotices">Notices</a>
                    <a href="{{url('/communications/emails')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsEmails">Emails</a>
                    @can('Admin', auth()->user())
                    <a href="{{url('/communications/feedback')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsFeedback">Feedback</a>
                    @endCan
                </div>
            </div>
            {{-- DESKTOP TABLE --}}
            {{-- <div class="card auth-card">
                @include('orders.status.card_header')
                <div class="card-body">
                    <div class="table-responsive">
                        @include('orders.status.order_table')
                    </div>
                </div>
            </div> --}}
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>

    <div class="container-fluid pl-4 mt-2 pr-4 mobile-view">
        <div class="row ">
            <div class="col-1"></div>
            <div class="col-10  pb-3">
                <div class="">
                    <div class="d-flex justify-content-start">
                        <a href="{{url('/communications/notices')}}" class="btn btn-primary btn-sm width-px-100 font-weight-bold font-px-17" id="communicationsNotices">Notices</a>
                        <a href="{{url('/communications/emails')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsEmails">Emails</a>
                        @can('Admin', auth()->user())
                        <a href="{{url('/communications/feedback')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsFeedback">Feedback</a>
                        @endCan
                    </div>
                </div>
                {{-- MOBILE TABLE --}}
                {{-- <div class="card auth-card">
                    @include('orders.status.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.status.order_table')

                        </div>
                    </div>
                </div> --}}
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
    <script src="{{ mix('/js/communication.js') }}" defer></script>
@endsection
