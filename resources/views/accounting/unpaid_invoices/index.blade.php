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
                        <h6>UNPAID INVOICES</h6>
                        <div class="">
                            <input type="text" class="unpaidInvoicesInput form-control" name="search" id="unpaidInvoicesInput" placeholder="Search...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('accounting.unpaid_invoices.unpaid_invoices_table')
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
                        <h6>UNPAID INVOICES</h6>
                        <div class="">
                            <input type="text" class="unpaidInvoicesInput form-control" name="search" id="unpaidInvoicesInput" placeholder="Search...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('accounting.unpaid_invoices.unpaid_invoices_table_mobile')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- unpaid invoice details modal --}}
    @include('accounting.unpaid_invoices.unpaid_invoice_details_modal')
    @include('accounting.unpaid_invoices.payment_modal')
    @include('accounting.unpaid_invoices.invoice_adjustment_modal')

    @include('layouts.includes.install_modal')
    @include('layouts.includes.payment_modal')
    @include('layouts.includes.rush_order_modal')
    @include('layouts.includes.duplicated_order_modal')
    @include('layouts.includes.pricing_adjustment_modal')
    @include('layouts.includes.order_details_modal')

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/accounting-unpaid-invoices.js') }}" defer></script>
@endsection
