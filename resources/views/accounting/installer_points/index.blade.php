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
                        <h6>
                            INSTALLER
                            <select class="form-control-sm text-center ml-2" name="installer_select" id="installerSelect">
                                <option>All Installers</option>
                                @foreach ($installers as $installer)
                                    <option value="{{ $installer->id }}">{{ $installer->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control-sm ml-2 installerPointsInput" name="" id="" placeholder="Search...">
                        </h6>
                        <div class="">
                            Due: <span id="due_points"></span>
                        </div>
                        <div class="">
                            Paid: <span id="paid_points"></span>
                        </div>
                        <div class="">
                            Total: <span id="total_points"></span>
                        </div>
                        <div class="">
                            <a href="#" role="button" class="btn btn-orange text-white font-weight-bold rounded-pill" data-toggle="modal" data-target="#paymentsModal">Payments</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('accounting.installer_points.installer_points_table')
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
                        <div class="d-flex flex-column">
                            <span class="h6 font-weight-bold">INSTALLER</span>
                            <select class="form-control-sm text-center w-100 ml-2" name="installer_select_mobile" id="installerSelectMobile">
                                <option>All Installers</option>
                                @foreach ($installers as $installer)
                                    <option value="{{ $installer->id }}">{{ $installer->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control-sm mt-2 installerPointsInput" name="" id="" placeholder="Search...">
                        </div>
                        <div class="">
                            Due: <span id="due_points_mobile"></span>
                        </div>
                        <div class="">
                            Paid: <span id="paid_points_mobile"></span>
                        </div>
                        <div class="">
                            Total: <span id="total_points_mobile"></span>
                        </div>
                        <div class="">
                            <a href="#" role="button" class="btn btn-orange text-white font-weight-bold rounded-pill" data-toggle="modal" data-target="#paymentsModal">Payments</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('accounting.installer_points.installer_points_table_mobile')
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

    @include('accounting.installer_points.payments_modal')
    @include('accounting.installer_points.add_payment_modal')
    @include('accounting.installer_points.edit_payment_modal')
    @include('accounting.installer_points.points_adjustment_modal')
    @include('layouts.includes.order_details_modal')

@endsection

@section('page_scripts')
<script src="{{ mix('/js/accounting-installer-points.js') }}" defer></script>
@endsection
