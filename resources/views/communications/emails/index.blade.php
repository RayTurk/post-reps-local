@extends('layouts.auth')

@section('page_styles')
    <link rel="stylesheet" href="../css/bootstrap-multiselect.css">    
@endsection

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
                <a href="{{url('/communications/feedback')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsFeedback">Feedback</a>
            </div>

            {{-- DESKTOP FORM --}}
            <div class="card auth-card d-flex mt-1">
                <div class="card-body">
                    <div class="table-responsive">
                        @include("communications.emails.email_form")
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-1 pb-3 d-flex justify-content-end">
            @include('layouts.includes.account_resources_icons')
        </div>
    </div>
</div>

<div class="container-fluid pl-4 mt-2 pr-4 mobile-view">
    <div class="row ">
        {{-- <div class="col-1"></div> --}}
        <div class="col-12 pb-3">
            <div class="">
                <div class="d-flex justify-content-start">
                    <a href="{{url('/communications/notices')}}" class="btn btn-primary btn-sm width-px-100 font-weight-bold font-px-17" id="communicationsNotices">Notices</a>
                    <a href="{{url('/communications/emails')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsEmails">Emails</a>
                    <a href="{{url('/communications/feedback')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="communicationsFeedback">Feedback</a>
                </div>
            </div>
            {{-- MOBILE FORM --}}
            <div class="card auth-card">
                <div class="card-body">
                    <div class="table-responsive">
                        @include("communications.emails.email_form_mobile")
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-1"></div> --}}
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
    <script src="{{ mix('/js/email-communication.js') }}" defer></script>
@endsection
