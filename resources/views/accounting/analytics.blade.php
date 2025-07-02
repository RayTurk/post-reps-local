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
                    <a
                        href="{{url('/accounting/')}}"
                        class="order-tab-active btn btn-primary btn-sm width-px-200 font-weight-bold font-px-17"
                        id="accountingAnalytics"
                    >Accounting Analytics</a>
                    <a
                        href="{{url('/accounting/unpaid/invoices')}}"
                        class="btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
                        id="accountingUnpaidInvoices"
                    >Unpaid Invoices</a>
                    <a
                        href="{{url('/accounting/payments')}}"
                        class="btn btn-primary btn-sm ml-1 width-px-150 font-weight-bold font-px-17"
                        id="accountingPayments"
                    >Payments</a>
                    <a
                        href="{{url('/accounting/create/invoices')}}"
                        class="btn btn-primary btn-sm ml-1 width-px-150 font-weight-bold font-px-17"
                        id="accountingCreateInvoices"
                    >Create Invoices</a>
                    <a
                        href="{{url('/accounting/transaction/summary')}}"
                        class="btn btn-primary btn-sm ml-1 width-px-200 font-weight-bold font-px-17"
                        id="accountingTransactionSummary"
                    >Transaction Summary</a>
                </div>
                <div class="card auth-card mt-1">
                    <div class="card-header d-flex justify-content-between">
                        <h6>ACCOUNTING ANALYTICS</h6>
                        <select name="analytics_year" id="analytics_year" class="form-control width-px-100">
                            @foreach ($invoiceYears as $year)
                                <option value="{{$year}}" {{$year == $yearSelected ? 'selected' : ''}}>
                                    {{$year}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body font-weight-bold">
                        <div class="row">
                            <div class="col-md-3">
                            </div>
                            <div class="col-md-2 text-center">
                                NUMBER OF INVOICES
                            </div>
                            <div class="col-md-2 text-center">
                                BALANCE TOTALS
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3 text-right">
                                UNPAID INVOICES
                            </div>
                            <div class="col-md-2 text-center">
                                <input
                                    type="text"
                                    class="border-none width-px-150 py-0 font-px-18 text-center bg-white" readonly
                                    value="{{$countUnpaidInvoices}}"
                                >
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                        class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                        value="{{$sumUnpaidInvoices}}" readonly
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 text-right">
                                PAST DUE INVOICES
                            </div>
                            <div class="col-md-2 text-center">
                                <input type="text"
                                class="border-none width-px-150 py-0 font-px-18 text-center bg-white"
                                readonly value="{{$countPastDueInvoices}}">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                        class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                        value="{{$sumPastDueInvoices}}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 text-right">
                                PAYMENTS RCVD MONTH
                            </div>
                            <div class="col-md-2 text-center">
                                <input type="text"
                                    class="border-none width-px-150 py-0 font-px-18 text-center bg-white"
                                    readonly value="{{$countPaymentsCurrentMonth}}">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                    class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                    value="{{$sumPaymentsCurrentMonth}}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 text-right">
                                PAYMENTS RCVD YTD
                            </div>
                            <div class="col-md-2 text-center">
                                <input type="text"
                                class="border-none width-px-150 py-0 font-px-18 text-center bg-white"
                                readonly value="{{$countPaymentsYtd}}">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                    class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                    value="{{$sumPaymentsYtd}}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-12 px-5">
                                Chart here
                            </div>
                        </div>
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
                <div class="card auth-card">
                    <div class="card-body">
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

    <form id="analyticsYearForm" action="{{url('/accounting/analytics')}}" method="post">
        @csrf
        <input type="hidden" id="yearInput" name="year_selected" value="{{now()->year}}">
    </form>

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/accounting.js') }}" defer></script>
    <script src="{{ mix('/js/accounting-analytics.js') }}" defer></script>
@endsection
