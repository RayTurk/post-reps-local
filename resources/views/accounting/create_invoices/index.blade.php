@extends('layouts.auth')

@section('content')

    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>

    {{-- desktop view --}}
    <div class="container-fluid pl-4 mt-1 pr-4 desktop-view">
        <div class="row ">
            <div class="col-md-1 pb-3">
                @include('layouts.includes.order_bar_icons')
            </div>
            <div class="col-md-10">
                @include('accounting.menu')

                <div class="card auth-card mt-1">
                    <div class="card-header d-flex justify-content-between">
                        <h6>CREATE INVOICES</h6>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-12">
                                @include('accounting.create_invoices.create_invoice_form')
                            </div>
                        </div>

                        <hr />

                        <div class="d-flex justify-content-between">
                            <h6>UNINVOICED ORDERS</h6>
                            <div class="">
                                <input type="text" class="createInvoicesInput mb-4 form-control form-control-sm"
                                    name="search" id="createInvoicesInput" placeholder="Search...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            @include('accounting.create_invoices.create_invoices_table')
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>

    {{-- tablet view
    <div class="container-fluid pl-4 mt-1 pr-4 tablet-view">
        <div class="row ">
            <div class="col-md-1 pb-3">
                @include('layouts.includes.order_bar_icons')
            </div>
            <div class="col-md-10">
                @include('accounting.menu')

                <div class="card auth-card mt-1">
                    <div class="card-header d-flex justify-content-between">
                        <h6>CREATE INVOICES</h6>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-12">
                                @include('accounting.create_invoices.create_invoice_form_tablet')
                            </div>
                        </div>

                        <hr />

                        <div class="d-flex justify-content-between">
                            <h6>UNINVOICED ORDERS</h6>
                            <div class="">
                                <input type="text" class="createInvoicesInput mb-4 form-control form-control-sm"
                                    name="search" id="createInvoicesInput" placeholder="Search...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            @include('accounting.create_invoices.create_invoices_table_tablet')
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                @include('layouts.includes.account_resources_icons')
            </div>
        </div>
    </div>
    --}}

    {{-- mobile view --}}
    <div class="container-fluid pl-4 pr-4 mobile-view" style="margin-top: -15px;">
        <div class="row ">
            <div class="col-12 pb-3">
                @include('accounting.menu')

                <div class="card auth-card">
                    <div class="card-header d-flex justify-content-between">
                        <h6>CREATE INVOICES</h6>
                    </div>
                    <div class="card-body">

                        <div class="row">
                            <div class="col-12">
                                @include('accounting.create_invoices.create_invoice_form_mobile')
                            </div>
                        </div>

                        <hr />

                        <div class="d-flex flex-column ">
                            <h6>UNINVOICED ORDERS</h6>
                            <div class="">
                                <input type="text" class="createInvoicesInput mb-4 form-control form-control-sm"
                                    name="search" id="createInvoicesInput" placeholder="Search...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            @include('accounting.create_invoices.create_invoices_table_mobile')
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

    <div class="modal fade" id="orderDetailsModal" data-keyboard="true" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h5 id="orderDetailsHeader">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetailsModalBody">
                    <div class="row">
                        <div class="col-md-6">
                            <b>Date/Time Created:</b> <span created_at></span>
                        </div>
                        <div class="col-md-6">
                            <b>Order Total:</b> <span order_total></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <b>Office:</b> <span office></span>
                        </div>
                        <div class="col-md-6">
                            <b>Agent:</b> <span agent></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <b>Address:</b> <span address></span>
                        </div>
                        <div class="col-md-6">
                            <b>Property Type:</b> <span property_type></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="removalOrderPickupAddress" style="display: none">
                        <div class="col-md-6">
                            <b>Pickup Address:</b> <span pickup_address></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <b>Service Date:</b> <span service_date></span>
                        </div>
                        <div class="col-md-6">
                            <b>Status:</b> <span status></span>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-4 text-center">
                            <b>Post:</b> <span post></span><br>
                            <span post_image></span>
                        </div>
                        <div class="col-md-4 text-center">
                            <b>Sign Panel:</b> <span panel></span><br>
                            <span panel_image></span>
                        </div>
                        <div class="col-md-4 text-center">
                            <b>Accessories:</b> <span accessories></span></span><br>
                            <span accessories_images></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <b>Comments:</b> <span comments></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="OrderDetailsInstallerName">
                        <div class="col-md-12">
                            <b>Installer Name:</b> <span installer_name></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="OrderDetailsInstallerDateCompleted">
                        <div class="col-md-12">
                            <b>Date/Time Completed:</b> <span date_completed></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="OrderDetailsInstallerComments">
                        <div class="col-md-12">
                            <b>Installer Comments:</b> <span installer_comments></span>
                        </div>
                    </div>
                    <div class="row mt-2" >
                        <div class="col-md-6 d-none" id="OrderDetailsAttachments">
                            <b>Order Attachments:</b><br> <span attachments></span>
                        </div>
                        <div class="col-md-6 d-none" id="OrderDetailsInstallerPhotos">
                            <b>Installer Photos:</b><br> <span installer_photos></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deliveryDetailsModal" data-keyboard="true" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h5 id="deliveryDetailsHeader">Delivery Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="deliveryDetailsModalBody">
                    <div class="row">
                        <div class="col-md-12">
                            <b>Date/Time Created:</b> <span created_at></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <b>Office:</b> <span office></span>
                        </div>
                        <div class="col-md-6">
                            <b>Agent:</b> <span agent></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <b>Address:</b> <span address></span>
                        </div>
                        <div class="col-md-3">
                            <b>Service Date:</b> <span service_date></span>
                        </div>
                        <div class="col-md-3">
                            <b>Status:</b> <span status></span>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <b>Pick Up:</b><br> <span pickups></span>
                        </div>
                        <div class="col-md-6">
                            <b>Drop off:</b><br> <span dropoffs></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <b>Comments:</b> <span comments></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="deliveryDetailsInstallerName">
                        <div class="col-md-12">
                            <b>Installer Name:</b> <span installer_name></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="deliveryDetailsInstallerDateCompleted">
                        <div class="col-md-12">
                            <b>Date/Time Completed:</b> <span date_completed></span>
                        </div>
                    </div>
                    <div class="row mt-2 d-none" id="deliveryDetailsInstallerComments">
                        <div class="col-md-12">
                            <b>Installer Comments:</b> <span installer_comments></span>
                        </div>
                    </div>
                    <div class="row mt-2" >
                        <div class="col-md-12 d-none" id="deliveryDetailsInstallerPhotos">
                            <b>Installer Photos:</b><br> <span installer_photos></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="markOrderCompletedModal" data-keyboard="true" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content auth-card">
                <div class="modal-header">
                    <h5 id="orderDetailsHeader">Mark Order Completed</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetailsModalBody">
                    <iframe src=""
                        id="completeOrderIframe"
                        frameborder="0"
                        style="width: 100%; min-height: 650px; margin-top: -20px;"
                    ></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/accounting-create-invoices.js') }}" defer></script>
@endsection
