@extends('layouts.auth')

@section('content')
    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>
    <div class="container-fluid pl-4 mt-1 pr-4">
        <div class="row">
            <div class="col-md-1 pb-3 desktop-view">
                @include('layouts.includes.order_bar_icons')
            </div>
            <div class="col-md-10">
                <div class="row">
                    <div class="col-md-6">
                        <a href="{{url('/order/status')}}" class="btn btn-primary btn-sm width-px-100 font-weight-bold font-px-17" id="ordersActive">Active</a>
                        <a href="{{url('/order/status/history')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersHistory">History</a>
                        @can('Admin', auth()->user())
                        <a href="{{url('/order/status/routes')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersRoutes">Routes</a>
                        <a href="{{url('/order/status/pull-list')}}" class="btn btn-primary btn-sm ml-1 width-px-100 font-weight-bold font-px-17" id="ordersPullList">Pull List</a>
                        @endCan
                    </div>
                    @can('Admin', auth()->user())
                    <div class="col-md-3 d-flex text-white justify-content-end">
                        <div class="font-px-17 pt-2">
                            <strong>Assigned:</strong><span id="totalAssigned"></span>
                        </div>
                        <div class="font-px-17 ml-4 pt-2">
                            <strong>Unassigned:</strong><span id="totalUnassigned"></span>
                        </div>
                    </div>
                    @endCan
                </div>
                <div class="row">
                    <div class="col-md-9">
                        <div class="height-px-714 mt-1" id="routeMap">
                        </div>
                    </div>
                    <div class="col-md-3" >
                        @php
                            use Carbon\Carbon;
                            $today = Carbon::now();
                            $tomorrow = Carbon::tomorrow();
                            $formattedToday = 'Today - ' . $today->format('F jS, Y');
                            $formattedTomorrow = 'Tomorrow - ' . $tomorrow->format('F j, Y');
                        @endphp
                        <span class="font-px-17 text-white">Route Date:</span>
                        <select id="routeDateSelect" class="form-control width-px-284 mb-3">
                            <option value="{{$today->format('Y-m-d')}}">{{$formattedToday}}</option>
                            <option value="{{$tomorrow->format('Y-m-d')}}">{{$formattedTomorrow}}</option>
                            @php
                                for ($i = 0; $i < 5; $i++) {
                                    // Increment the date by one more day
                                    $dayAfterTomorrow = Carbon::tomorrow()->addDay();
                                    $nextDate = $dayAfterTomorrow->addDay($i);

                                    // Format the date as "Day, Month Day, Year"
                                    $formattedDate = $nextDate->format('l, F jS, Y');
                                    $value = $nextDate->format('Y-m-d');

                                    echo '<option value="' . $value . '">' . $formattedDate . '</option>';
                                }
                            @endphp
                        </select>
                        <a href="#" class="font-px-17 underline text-white" id="removeStops">Remove All Stops</a>
                        <select id="installerSelect" id="" class="form-control width-px-284" data-installers="{{json_encode($installers)}}">
                            <option value="0">All Installers</option>
                            @if ($installers->isNotEmpty())
                                @foreach ($installers as $installer)
                                    <option value="{{$installer->id}}">
                                        {{$installer->name}} ({{substr($installer->first_name,0,1)}}{{substr($installer->last_name,0,1)}})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div
                            id="installerCardContainer"
                            class="mt-1 pl-3 height-px-680 pr-0 width-px-284"
                            style="overflow-y: auto; overflow-x: hidden;"
                        >

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-1 pb-3 d-flex justify-content-end">
                <div class="desktop-view">
                    @include('layouts.includes.account_resources_icons')
                </div>
            </div>
        </div>
    </div>

    {{-- MOBILE VIEW --}}
    {{-- <div class="container-fluid pl-4 mt-2 pr-4 mobile-view">
        <div class="row ">
            <div class="col-1"></div>
            <div class="col-12  p-4">
                <div class="card auth-card">
                    @include('orders.status.card_header')
                    <div class="card-body">
                        <div class="table-responsive">
                            @include('orders.status.order_table')

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-1"></div>
        </div>
    </div> --}}

    <div id="installCardTmpl" style="display:none">
        <div class="row auth-card font-px-12 width-px-284 height-px-65 mb-1" >
            <div class="col-2 text-left px-2 py-2">
                <img src="{{asset('storage/images/Install_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45">
            </div>
            <div class="col-10 py-1">
                <span class="text-success-dark font-weight-bold" style="white-space: nowrap;">Install - post_name</span><br>
                <span style="white-space: nowrap;">replace_address</span><br>
                <span style="white-space: nowrap;">agent_office</span>
            </div>
        </div>
    </div>
    <div id="repairCardTmpl" style="display:none">
        <div class="row auth-card font-px-12 width-px-284 height-px-65 mb-1" >
            <div class="col-2 text-left px-2 py-2">
                <img src="{{asset('storage/images/Repair_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45">
            </div>
            <div class="col-10 py-1">
                <span class="text-primary-dark font-weight-bold" style="white-space: nowrap;">Repair - post_name</span><br>
                <span style="white-space: nowrap;">replace_address</span><br>
                <span style="white-space: nowrap;">agent_office</span>
            </div>
        </div>
    </div>
    <div id="removalCardTmpl" style="display:none">
        <div class="row auth-card font-px-12 width-px-284 height-px-65 mb-1" >
            <div class="col-2 text-left px-2 py-2">
                <img src="{{asset('storage/images/Removal_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45">
            </div>
            <div class="col-10 py-1">
                <span class="text-danger font-weight-bold" style="white-space: nowrap;">Removal - post_name</span><br>
                <span style="white-space: nowrap;">replace_address</span><br>
                <span style="white-space: nowrap;">agent_office</span>
            </div>
        </div>
    </div>
    <div id="deliveryCardTmpl" style="display:none">
        <div class="row auth-card font-px-12 width-px-284 height-px-65 mb-1" >
            <div class="col-2 text-left px-2 py-2">
                <img src="{{asset('storage/images/Deliver_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45">
            </div>
            <div class="col-10 py-1">
                <span class="text-orange font-weight-bold" style="white-space: nowrap;">Delivery - post_name</span><br>
                <span style="white-space: nowrap;">replace_address</span><br>
                <span style="white-space: nowrap;">agent_office</span>
            </div>
        </div>
    </div>

    @include('layouts.includes.install_modal')
    @include('layouts.includes.payment_modal')
    @include('layouts.includes.edit_order')
    @include('layouts.includes.rush_order_modal')
    @include('layouts.includes.duplicated_order_modal')
    @include('layouts.includes.pricing_adjustment_modal')

   <!--Remove stop Modal -->
   <div class="modal fade" id="removeStopsModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
      <div class="modal-dialog modal-md">
         <div class="modal-content auth-card">
            <div class="modal-header">
            </div>
            <div class="modal-body text-center">
                <h4 class="text-orange" style="margin-top: -15px;">REMOVE ALL STOPS</h4><br>
                <span class="font-px-18">This will REMOVE all scheduleds stops for:</span><br>
                <span class="font-px-18 font-weight-bold" id="removeFor">All Installers</span><br>
                <span class="font-px-18">Are you sure you want to continue?</span>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger" type="button" id="confirmRemoveStopsBtn">REMOVE</button>
                <button class="btn btn-secondary ml-4" type="button" data-dismiss="modal">CANCEL</button>
            </div>
         </div>
      </div>
   </div>

@endsection

@section('page_scripts')
    <script src="{{ mix('/js/dashboard.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js"></script>
@endsection
