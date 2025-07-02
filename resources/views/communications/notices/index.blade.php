@extends('layouts.auth')

@section('content')

<style>
    .table-borderless thead tr th {
        border: none !important;
    }

    table {
        border-collapse: separate;
        border-spacing: 0 .5em;
    }

    table tbody tr:hover {
        cursor: pointer;
    }

    .border-2 {
        border-width: 2px !important;
    }

    .fa-trash-alt {
        font-size: 20px;
    }

    hr.solid {
        border-top: 3px solid #999;
    }

    .card-body{
        height: 600px;
        overflow-y: auto;
    }

    .table-responsive{
        margin-top: -20px;
    }
</style>

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

            {{-- DESKTOP TABLE --}}
            <div class="card auth-card d-flex mt-1">
                <button type="button" class="btn btn-orange text-white font-weight-bold m-4 width-px-180 rounded-pill" data-toggle="modal" data-target="#noticeModal" id="createNotice">
                    CREATE NOTICE
                </button>
                <div class="card-body">
                    <div class="table-responsive">
                        @include('communications.notices.notices_table')
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
            {{-- MOBILE TABLE --}}
            <div class="card auth-card">
                <button type="button" class="btn btn-orange text-white font-weight-bold m-4 width-px-180 rounded-pill" data-toggle="modal" data-target="#noticeModal" id="createNotice">
                    CREATE NOTICE
                </button>
                <div class="card-body">
                    <div class="table-responsive">
                        @include('communications.notices.notices_table')
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-1"></div> --}}
    </div>
</div>

{{-- CREATE NOTICE MODAL --}}
@include('communications.notices.create_notice_modal')

{{-- NOTICE DETAILS MODAL --}}
@include('communications.notices.notice_details_modal')

@include('layouts.includes.install_modal')
@include('layouts.includes.payment_modal')
@include('layouts.includes.edit_order')
@include('layouts.includes.rush_order_modal')
@include('layouts.includes.duplicated_order_modal')
@include('layouts.includes.pricing_adjustment_modal')

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/notice-communication.js') }}" defer></script>
@endsection
