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
                        <h6>PAYMENTS</h6>
                        <a href="#" class="btn btn-dark font-weight-bold"  data-toggle="modal" data-target="#exportToCsvModal">EXPORT TO CSV</a>
                        <a href="#" class="btn btn-dark font-weight-bold"  data-toggle="modal" data-target="#exportToExcelModal">EXPORT TO EXCEL</a>
                        <div class="">
                            <input type="text" class="accountingPaymentsInput form-control" name="search" id="accountingPaymentsInput" placeholder="Search...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('accounting.payments.payments_table')
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
                @include('accounting.menu')
                <div class="card auth-card">
                    <div class="card-header d-flex justify-content-between">
                        <h6>PAYMENTS</h6>
                        <div class="">
                            <input type="text" class="accountingPaymentsInput form-control" name="search" id="accountingPaymentsInput" placeholder="Search...">
                        </div>
                    </div>
                    <a href="#" class="btn btn-dark font-weight-bold mt-2"  data-toggle="modal" data-target="#exportToCsvModal">EXPORT TO CSV</a>
                    <a href="#" class="btn btn-dark font-weight-bold mt-2"  data-toggle="modal" data-target="#exportToExcelModal">EXPORT TO EXCEL</a>
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('accounting.payments.payments_table_mobile')
                        </div>
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

    @include('accounting.payments.reverse_payment_modal')
    @include('accounting.unpaid_invoices.unpaid_invoice_details_modal')

    @include('accounting.payments.export_to_csv_modal')
    @include('accounting.payments.export_to_excel_modal')
    @include('layouts.includes.order_details_modal')

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/accounting-payments.js') }}" defer></script>
@endsection
