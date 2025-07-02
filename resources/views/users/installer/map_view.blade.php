@extends('users.installer.header')

@section('content')

    <div id="installerMap" class="height-px-400 pt-0 mb-2 margin-top-52px" ></div>

    @if ($orderType == 'install')
        <div class="row installer-card font-px-15 w-100 height-auto mb-2 ml-0 pb-2 overflow-x-auto overflow-y-auto">
            <div class="col-2 text-left px-2 py-3" >
                <img src="{{asset('storage/images/Install_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
            </div>
            <div class="col-10 py-1 pl-2" >
                <div class="text-success-dark font-weight-bold" >
                    <div>Install: {{$order->post->post_name}}</div>
                </div>
                <span ><a style="color: blue;" href="{{url('/installer/order/details')}}/{{$order->id}}/{{$orderType}}">{{$order->address}}</a></span><br>
                <div class="row">
                    @if ($order->agent)
                    <div class="col-6">
                        {{$order->agent->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->agent->user->phone}}">{{$order->agent->user->phone}}</a>
                    </div>
                    @endif
                    <div class="col-6">
                    {{$order->office->user->name}}<br>
                    Ph: <a style="color: blue;" href="tel:{{$order->office->user->phone}}">{{$order->office->user->phone}}</a>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Stop #</strong>
                        <select
                            class="installer-stop-number width-px-50 text-center"
                            data-order-id="{{$order->id}}"
                            data-order-type="{{$orderType}}"
                        >
                            @for ($i = 1; $i <= $countOrders; $i++)
                                <option
                                    value="{{$i}}"
                                    {{$order->stop_number == $i ? 'selected' : ''}}
                                >
                                    {{$i}}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($orderType == 'repair')
        <div class="row installer-card font-px-15 w-100 height-auto mb-2 ml-0 pb-2 overflow-x-auto overflow-y-auto">
            <div class="col-2 text-left px-2 py-3" >
                <img src="{{asset('storage/images/Repair_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
            </div>
            <div class="col-10 py-1 pl-1" >
                <div class="text-primary-dark font-weight-bold" >
                    <div>Repair: {{$order->order->post->post_name}}</div>
                </div>
                <span ><a style="color: blue;" href="{{url('/installer/order/details')}}/{{$order->id}}/{{$orderType}}">{{$order->order->address}}</a></span><br>
                <div class="row">
                    @if ($order->order->agent)
                    <div class="col-6">
                        {{$order->order->agent->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->order->agent->user->phone}}">{{$order->order->agent->user->phone}}</a>
                    </div>
                    @endif
                    <div class="col-6">
                    {{$order->order->office->user->name}}<br>
                    Ph: <a style="color: blue;" href="tel:{{$order->order->office->user->phone}}">{{$order->order->office->user->phone}}</a>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Stop #</strong>
                        <select
                            class="installer-stop-number width-px-50 text-center"
                            data-order-id="{{$order->id}}"
                            data-order-type="{{$orderType}}"
                        >
                            @for ($i = 1; $i <= $countOrders; $i++)
                                <option
                                    value="{{$i}}"
                                    {{$order->stop_number == $i ? 'selected' : ''}}
                                >
                                    {{$i}}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($orderType == 'removal')
        <div class="row installer-card font-px-15 w-100 height-auto mb-2 ml-0 pb-2 overflow-x-auto overflow-y-auto">
            <div class="col-2 text-left px-2 py-3" >
                <img src="{{asset('storage/images/Removal_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
            </div>
            <div class="col-10 py-1 pl-1" >
                <div class="text-danger font-weight-bold" >
                    <div>Removal: {{$order->order->post->post_name}}</div>
                </div>
                <span ><a style="color: blue;" href="{{url('/installer/order/details')}}/{{$order->id}}/{{$orderType}}">{{($order->pickup_address != 'null' && !is_null($order->pickup_address)) ? $order->pickup_address : $order->order->address}}</a></span><br>
                <div class="row">
                    @if ($order->order->agent)
                    <div class="col-6">
                        {{$order->order->agent->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->order->agent->user->phone}}">{{$order->order->agent->user->phone}}</a>
                    </div>
                    @endif
                    <div class="col-6">
                    {{$order->order->office->user->name}}<br>
                    Ph: <a style="color: blue;" href="tel:{{$order->order->office->user->phone}}">{{$order->order->office->user->phone}}</a>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Stop #</strong>
                        <select
                            class="installer-stop-number width-px-50 text-center"
                            data-order-id="{{$order->id}}"
                            data-order-type="{{$orderType}}"
                        >
                            @for ($i = 1; $i <= $countOrders; $i++)
                                <option
                                    value="{{$i}}"
                                    {{$order->stop_number == $i ? 'selected' : ''}}
                                >
                                    {{$i}}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($orderType == 'delivery')
        <div class="row installer-card font-px-15 w-100 height-auto mb-2 ml-0 pb-2 overflow-x-auto overflow-y-auto">
            <div class="col-2 text-left px-2 py-3" >
                <img src="{{asset('storage/images/Deliver_Icon.png')}}" title="Edit" alt="Edit" class="width-px-55" >
            </div>
            <div class="col-10 py-1 pl-1" >
                <div class="text-success-dark font-weight-bold" >
                    <div style="color:#ad6333;">Delivery: {{$order->pickup_delivery}}</div>
                </div>
                <span ><a style="color: blue;" href="{{url('/installer/order/details')}}/{{$order->id}}/{{$orderType}}">{{$order->address}}</a></span><br>
                <div class="row">
                    @if ($order->agent)
                    <div class="col-6">
                        {{$order->agent->user->name}}<br>
                        Ph: <a style="color: blue;" href="tel:{{$order->agent->user->phone}}">{{$order->agent->user->phone}}</a>
                    </div>
                    @endif
                    <div class="col-6">
                    {{$order->office->user->name}}<br>
                    Ph: <a style="color: blue;" href="tel:{{$order->office->user->phone}}">{{$order->office->user->phone}}</a>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Stop #</strong>
                        <select
                            class="installer-stop-number width-px-50 text-center"
                            data-order-id="{{$order->id}}"
                            data-order-type="{{$orderType}}"
                        >
                            @for ($i = 1; $i <= $countOrders; $i++)
                                <option
                                    value="{{$i}}"
                                    {{$order->stop_number == $i ? 'selected' : ''}}
                                >
                                    {{$i}}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('users.installer.password_modal')

    @section('page_scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js"></script>
    @endsection
@endsection


<div class="modal fade" id="acceptJobModal" data-keyboard="true" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content auth-card">
                <div class="modal-header row">
                    <div class="col-12 text-center p-0">
                        <h5 id="acceptJobModalHeader">Add Order to Route?</h5>
                    </div>
                </div>
                <div class="modal-body" id="acceptJobModalBody" style="margin-top: -30px;">
                    <span id="jobAddress">27916 Klahr Rd, Parma, ID 83660</span><br>
                    <span id="jobOfficeAgent">Test Office 1 - Xantha Ratliff</span><br>
                    <a id="jobPhone"style="color: blue;" href="tel:999-999-9999">999-999-9999</a><br>
                    <br>
                    <div id="jobItems"></div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-orange width-px-110 text-white font-weight-bold"
                        id="acceptJobBtn" data-order-type="0" data-order-id="0">ACCEPT</button>
                    <button type="button" class="btn btn-secondary width-px-110 font-weight-bold"
                        data-dismiss="modal">CANCEL</button>
                </div>
            </div>
        </div>
    </div>
