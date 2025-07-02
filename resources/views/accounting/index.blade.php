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
                        <h6>ACCOUNTING ANALYTICS</h6>
                        <select name="analytics_year" id="analytics_year" class="font-weight-bold font-px-16 form-control width-px-100">
                            @foreach ($invoiceYears as $year)
                                <option value="{{$year}}" {{$year == now()->year ? 'selected' : ''}}>
                                    {{$year}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body font-weight-bold">
                        @include('accounting.analytics_desktop')
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
                        <h6>ACCOUNTING ANALYTICS</h6>
                        <select name="analytics_year" id="analytics_year" class="font-weight-bold font-px-16 form-control width-px-100">
                            @foreach ($invoiceYears as $year)
                                <option value="{{$year}}" {{$year == now()->year ? 'selected' : ''}}>
                                    {{$year}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body font-weight-bold">
                        @include('accounting.analytics_mobile')
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endsection
