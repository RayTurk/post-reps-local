@extends('users.installer.header')

@section('content')
    <div class="container p-0">
        @include('layouts.includes.alerts')
    </div>

    <div id="installerCardContainer" class="container-fluid margin-top-52px" >
        @if ($orders->isNotEmpty())
            @foreach($orders as $order)
                @if ($order->order_type == 'install')
                    @php
                        $orderType = $order->order_type;
                        $order = \App\Models\Order::find($order->id);
                    @endphp
                    <div
                        class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0 overflow-x-auto overflow-y-hidden"
                        data-order-id="{{$order->id}}"
                        data-order-type="{{$orderType}}"
                    >
                        <div class="col-2 text-left px-2 py-3" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <img src="{{asset('storage/images/Install_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                        </div>
                        <div class="col-10 py-1 pl-2" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <span class="text-success-dark font-weight-bold" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">Install: {{$order->post->post_name}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">{{$order->address}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                                @if ($order->agent)
                                    {{$order->agent->user->name}}, {{$order->office->user->name}}
                                @else
                                    {{$order->office->user->name}}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif

                @if ($order->order_type == 'repair')
                    @php
                        $orderType = $order->order_type;
                        $order = \App\Models\RepairOrder::find($order->id);
                    @endphp
                    <div
                        class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0 overflow-x-auto overflow-y-hidden"
                        data-order-id="{{$order->id}}"
                        data-order-type="{{$orderType}}"
                    >
                        <div class="col-2 text-left px-2 py-3" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <img src="{{asset('storage/images/Repair_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                        </div>
                        <div class="col-10 py-1 pl-2" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <span class="text-primary-dark font-weight-bold" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">Repair: {{$order->order->post->post_name}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">{{$order->order->address}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                                @if ($order->order->agent)
                                    {{$order->order->agent->user->name}}, {{$order->order->office->user->name}}
                                @else
                                    {{$order->order->office->user->name}}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif

                @if ($order->order_type == 'removal')
                    @php
                        $orderType = $order->order_type;
                        $order = \App\Models\RemovalOrder::find($order->id);
                    @endphp
                    <div
                        class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0 overflow-x-auto overflow-y-hidden"
                        data-order-id="{{$order->id}}"
                        data-order-type="{{$orderType}}"
                    >
                        <div class="col-2 text-left px-2 py-3" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <img src="{{asset('storage/images/Removal_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                        </div>
                        <div class="col-10 py-1 pl-2" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <span class="text-danger font-weight-bold" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">Removal: {{$order->order->post->post_name}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">{{($order->pickup_address != 'null' && !is_null($order->pickup_address)) ? $order->pickup_address : $order->order->address}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                                @if ($order->order->agent)
                                    {{$order->order->agent->user->name}}, {{$order->order->office->user->name}}
                                @else
                                    {{$order->order->office->user->name}}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif

                @if ($order->order_type == 'delivery')
                    @php
                        $orderType = $order->order_type;

                        $order = \App\Models\DeliveryOrder::find($order->id);

                        $pickup_delivery = '';
                        foreach ($order->panels as $orderPanel) {
                            if ($orderPanel->pickup_delivery == $order::PICKUP) {
                                $pickup_delivery = 'Pickup/';
                            }
                            if ($orderPanel->pickup_delivery == $order::DELIVERY) {
                                $pickup_delivery .= 'Delivery';
                            }
                        }

                        $pickup_delivery = rtrim($pickup_delivery, '/');
                    @endphp
                    <div
                        class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0 overflow-x-auto overflow-y-hidden"
                        data-order-id="{{$order->id}}"
                        data-order-type="{{$orderType}}"
                    >
                        <div class="col-2 text-left px-2 py-3" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <img src="{{asset('storage/images/Install_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                        </div>
                        <div class="col-10 py-1 pl-2" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                            <span class="font-weight-bold"  style="color:#ad6333;" data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">Delivery: {{$pickup_delivery}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">{{$order->address}}</span><br>
                            <span data-order-id="{{$order->id}}" data-order-type="{{$orderType}}">
                                @if ($order->agent)
                                    {{$order->agent->user->name}}, {{$order->office->user->name}}
                                @else
                                    {{$order->office->user->name}}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>

    <div id="installCardTmpl" style="display:none">
        <div
            class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0 overflow-x-auto overflow-y-hidden"
            data-order-id="ORDER_ID"
            data-order-type="ORDER_TYPE"
        >
            <div class="col-2 text-left px-2 py-3" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <img src="{{asset('storage/images/Install_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
            </div>
            <div class="col-10 py-1 pl-0" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <span class="text-success-dark font-weight-bold" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">Install: post_name</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">replace_address</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">agent_office</span>
            </div>
        </div>
    </div>
    <div id="repairCardTmpl" style="display:none">
        <div class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0  overflow-x-auto overflow-y-hidden"  data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
            <div class="col-2 text-left px-2 py-3"  data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <img src="{{asset('storage/images/Repair_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
            </div>
            <div class="col-10 py-1 pl-0" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <span class="text-primary-dark font-weight-bold" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">Repair: post_name</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">replace_address</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">agent_office</span>
            </div>
        </div>
    </div>
    <div id="removalCardTmpl" style="display:none">
        <div class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0  overflow-x-auto overflow-y-hidden" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE" >
            <div class="col-2 text-left px-2 py-3" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <img src="{{asset('storage/images/Removal_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
            </div>
            <div class="col-10 py-1 pl-0" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <span class="text-danger font-weight-bold" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">Removal: post_name</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">replace_address</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">agent_office</span>
            </div>
        </div>
    </div>
    <div id="deliveryCardTmpl" style="display:none">
        <div class="row installer-order-card font-px-14 w-100 height-px-76 mb-2 ml-0  overflow-x-auto overflow-y-hidden" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE" >
            <div class="col-2 text-left px-2 py-3">
                <img src="{{asset('storage/images/Deliver_Icon.png')}}" title="Edit" alt="Edit" class="width-px-45" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
            </div>
            <div class="col-10 py-1 pl-0" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">
                <span class="font-weight-bold" style="color:#ad6333;" data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">Delivery: post_name</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">replace_address</span><br>
                <span data-order-id="ORDER_ID" data-order-type="ORDER_TYPE">agent_office</span>
            </div>
        </div>
    </div>

    @include('users.installer.password_modal')

@endsection
