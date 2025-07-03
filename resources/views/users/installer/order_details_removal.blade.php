<div class="row px-3 mt-3">
    <div class="col-2 pt-4">
        <input
            type="checkbox"
            class="form-control w-h-px-30 removal-items"
            id="postCheckbox"
            data-item-id="{{$order->order->post->id}}"
            checked
        >
    </div>
    <div class="col-2 pl-0">
        <img
            src="{{url('/private/image/post/')}}/{{$order->order->post->image_path}}"
            alt="{{$order->order->post->post_name}}"
            style="max-width: 4.6rem; max-height: 4.8rem;"
        >
    </div>
    <div class="col-7 pt-4">
        <span class="font-px-16">{{$order->order->post->post_name}}</span>
    </div>
</div>

@if ($order->order->repair && $order->order->repair->status == $order->order->repair::STATUS_COMPLETED)
    @if ($order->order->repair->panel)
        <div class="row px-3 mt-3">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 removal-items"
                    id="panelCheckbox"
                    data-item-id="{{$order->order->repair->panel->id}}"
                    data-action="{{$order->sign_panel}}"
                    checked
                >
            </div>
            <div class="col-2 pl-0">
                <img
                    src="{{url('/private/image/panel/')}}/{{$order->order->repair->panel->image_path}}"
                    alt="{{$order->order->repair->panel->panel_name}}"
                    style="max-width: 4.6rem; max-height: 4.8rem;"
                >
            </div>
            <div class="col-7 pt-4">
                <span class="font-px-16">{{$order->order->repair->panel->panel_name}}</span>
                <span class="font-px-14 font-weight-bold">
                    @if ($order->order->repair->panel->id_number)
                        ID#{{$order->order->repair->panel->id_number}}
                    @endif
                    @if ($order->sign_panel == $order::ADD_TO_INVENTORY)
                        &nbsp;(Add to inventory)
                    @else
                        &nbsp;(Agent Will Remove/Leave Sign at Property)
                    @endif
                </span>
            </div>
        </div>
    @else
        @if ($order->order->panel)
            <div class="row px-3 mt-3">
                <div class="col-2 pt-4">
                    <input
                        type="checkbox"
                        class="form-control w-h-px-30 removal-items"
                        id="panelCheckbox"
                        data-item-id="{{$order->order->panel->id}}"
                        data-action="{{$order->sign_panel}}"
                        checked
                    >
                </div>
                <div class="col-2 pl-0">
                    <img
                        src="{{url('/private/image/panel/')}}/{{$order->order->panel->image_path}}"
                        alt="{{$order->order->panel->panel_name}}"
                        style="max-width: 4.6rem; max-height: 4.8rem;"
                    >
                </div>
                <div class="col-7 pt-4">
                    <span class="font-px-16">{{$order->order->panel->panel_name}}</span>
                    <span class="font-px-14 font-weight-bold">
                        @if ($order->sign_panel == $order::ADD_TO_INVENTORY)
                            (Add to inventory)
                        @else
                            (Agent Will Remove/Leave Sign at Property)
                        @endif
                    </span>
                </div>
            </div>
        @endif
    @endif
@else
    @if ($order->order->panel)
        <div class="row px-3 mt-3">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 removal-items"
                    id="panelCheckbox"
                    data-item-id="{{$order->order->panel->id}}"
                    data-action="{{$order->sign_panel}}"
                    checked
                >
            </div>
            <div class="col-2 pl-0">
                <img
                    src="{{url('/private/image/panel/')}}/{{$order->order->panel->image_path}}"
                    alt="{{$order->order->panel->panel_name}}"
                    style="max-width: 4.6rem; max-height: 4.8rem;"
                >
            </div>
            <div class="col-7 pt-4">
                <span class="font-px-16">{{$order->order->panel->panel_name}}</span>
                <span class="font-px-14 font-weight-bold">
                    @if ($order->sign_panel == $order::ADD_TO_INVENTORY)
                        (Add to inventory)
                    @else
                        (Agent Will Remove/Leave Sign at Property)
                    @endif
                </span>
            </div>
        </div>
    @endif
@endif

<!--- When Agent hangs/leave panel at property and wants installers to pickup ---->
@if (! $order->order->panel && !isset($order->order->repair->panel))
    <div class="row px-3 mt-3">
        <div class="col-2 py-2">
            <input
                type="checkbox"
                class="form-control w-h-px-30 installation-items"
                disabled
            >
        </div>
        @if ($order->sign_panel == $order::ADD_TO_INVENTORY)
            <div class="col-9 py-2">
                <span class="font-px-16">Add Sign to Inventory</span>
            </div>
        @else
            <div class="col-9 py-2">
                <span class="font-px-16">Agent Will Remove/Leave Sign at Property</span>
            </div>
        @endif
    </div>
@endif

@if ($order->order->repair && $order->order->repair->status == $order->order->repair::STATUS_COMPLETED)
    @php $listed = []; @endphp
    @if ($order->order->repair->accessories->isNotEmpty())
        @foreach ($order->order->repair->accessories as $orderAccessory)
            <div class="row px-3 mt-3 accessory-div">
                <div class="col-2 pt-4">
                    <input
                        type="checkbox"
                        class="form-control w-h-px-30 accessories-checkbox removal-items"
                        data-item-id="{{$orderAccessory->accessory->id}}"
                        checked
                    >
                </div>
                <div class="col-2 pl-0">
                    <img
                        src="{{url('/private/image/accessory/')}}/{{$orderAccessory->accessory->image}}"
                        alt="{{$orderAccessory->accessory->accessory_name}}"
                        style="max-width: 4.6rem; max-height: 4.8rem;"
                    >
                </div>
                <div class="col-7 pt-4">
                    <span class="font-px-16">{{$orderAccessory->accessory->accessory_name}}</span>
                </div>
            </div>
            @php array_push($listed, $orderAccessory->accessory->id); @endphp
        @endforeach
    @endif
    @if ($order->order->accessories->isNotEmpty())
        @foreach ($order->order->accessories as $orderAccessory)
            @if (! in_array($orderAccessory->accessory->id, $listed))
                <div class="row px-3 mt-3 accessory-div">
                    <div class="col-2 pt-4">
                        <input
                            type="checkbox"
                            class="form-control w-h-px-30 accessories-checkbox removal-items"
                            data-item-id="{{$orderAccessory->accessory->id}}"
                            checked
                        >
                    </div>
                    <div class="col-2 pl-0">
                        <img
                            src="{{url('/private/image/accessory/')}}/{{$orderAccessory->accessory->image}}"
                            alt="{{$orderAccessory->accessory->accessory_name}}"
                            style="max-width: 4.6rem; max-height: 4.8rem;"
                        >
                    </div>
                    <div class="col-7 pt-4">
                        <span class="font-px-16">{{$orderAccessory->accessory->accessory_name}}</span>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@else
    @if ($order->order->accessories->isNotEmpty())
        @foreach ($order->order->accessories as $orderAccessory)
            <div class="row px-3 mt-3 accessory-div">
                <div class="col-2 pt-4">
                    <input
                        type="checkbox"
                        class="form-control w-h-px-30 accessories-checkbox removal-items"
                        data-item-id="{{$orderAccessory->accessory->id}}"
                        checked
                    >
                </div>
                <div class="col-2 pl-0">
                    <img
                        src="{{url('/private/image/accessory/')}}/{{$orderAccessory->accessory->image}}"
                        alt="{{$orderAccessory->accessory->accessory_name}}"
                        style="max-width: 4.6rem; max-height: 4.8rem;"
                    >
                </div>
                <div class="col-7 pt-4">
                    <span class="font-px-16">{{$orderAccessory->accessory->accessory_name}}</span>
                </div>
            </div>
        @endforeach
    @endif
@endif

<!-- Pickup sign panels left at property -->
<div class="row mt-2">
    <div class="col-12 text-center">
        <button
            class="btn btn-primary mt-2 font-px-16 py-0"
            id="removalAddPanelsBtn"
        >
            Pick Up Sign Panels
        </button>
    </div>
</div>

<!-- Sign panels modal -->
<div class="modal fade" id="removalAddPanelsModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card height-px-400 overflow-y-auto">
            <div class="modal-header" style="display: block;">
                <div><h5>Pick Up Sign Panels</h5></div>
            </div>
            <div class="modal-body mb-5" style="margin-top: -5px;">
                <div id="pickupContainer">
                    <div class="d-flex justify-content-between" id="pickupDiv1">
                        <select
                            style="border: 1px solid #7a7575;"
                            name="pickup_panel_style[1]"
                            class="width-px-200 height-px-28 panel-list pt-2px"
                        >
                        </select>
                        <input
                            type="text"
                            name="pickup_panel_qty[1]"
                            class="text-center width-px-50 height-px-28 px-2 qty-box ml-2"
                            placeholder="QTY"
                            style="border: 1px solid #7a7575;"
                        />
                        <a
                            href="#"
                            id="addNewPickup1"
                            data-row="1"
                            class="text-primary font-px-12 font-weight-bold add-new-pickup ml-3 width-px-60"
                            title="Add new panel"
                            style="line-height: 15px";
                        >
                            NEW SIGN
                        </a>
                        <a href="#" id="addAnotherPickup" class="text-primary font-weight-bold ml-3 width-px-60">
                            <img src="{{url('images')}}/plus_icon.jpg" alt="ADD" class="width-px-27 height-px-28" title="Add another panel">
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-orange" type="button" id="removalSavePanelsBtn">SAVE</button>
                <button class="btn btn-secondary ml-4" type="button" data-dismiss="modal">CANCEL</button>
            </div>
        </div>
    </div>
</div>

<div id="pickupTmpl" style="display: none;">
    <div class="d-flex justify-content-between mt-2 to-append" id="pickupDivrowCount">
        <select
            style="border: 1px solid #7a7575;"
            name="pickup_panel_style[rowCount]"
            class="width-px-200 height-px-28 panel-list pt-2px"
        >
        </select>
        <input
            type="text"
            name="pickup_panel_qty[rowCount]"
            class="text-center width-px-50 height-px-28 px-2 qty-box ml-2"
            placeholder="QTY"
            style="border: 1px solid #7a7575;"
        />
        <a
            href="#"
            id="addNewPickuprowCount"
            data-row="rowCount"
            class="text-primary font-px-12 font-weight-bold add-new-pickup ml-3 width-px-60"
            title="Add new panel"
            style="line-height: 15px";
        >
            NEW SIGN
        </a>
        <a href="#" class="text-primary font-weight-bold ml-3 width-px-60 remove-pickup">
            <img src="{{url('images')}}/Cancel_Icon.png" alt="REMOVE" class="width-px-27 height-px-28" title="Add another panel">
        </a>
    </div>
</div>

<!-- Preload install and repair order history -->
<div class="modal fade" id="removalHistoryModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card height-px-700 overflow-y-auto">
            <div class="modal-header" style="display: block;">
                <div><h5>View History</h5></div>
            </div>
            <div class="modal-body height-px-650 overflow-y-auto" style="margin-top: -25px;">
                <div class="row px-3">
                    <div class="col-12">
                        <b>Date Installed:</b>
                        {{$order->order->date_completed->format('m/d/Y')}}
                    </div>
                </div>
                <div class="row px-3 mt-1">
                    <div class="col-12">
                        <label class="text-dark d-block w-100 mb-0">
                            <b>Agent Comments:</b>
                        </label>
                        <textarea class="form-control" rows="3" readonly>{{$order->order->comment}}</textarea>
                    </div>
                </div>
                <div class="row px-3 mt-1">
                    <div class="col-12">
                        <label class="text-dark d-block w-100 mb-0">
                            <b>Installer Comments:</b>
                        </label>
                        <textarea class="form-control" rows="3"  readonly>{{$order->order->installer_comments}}</textarea>
                    </div>
                </div>
                <div class="row px-3 mt-1">
                    <div class="col-12">
                        <label class="text-dark d-block w-100 mb-0">
                            <b>Installer Photos:</b>
                        </label>
                        <div class="d-flex justify-content-between">
                            @if ($order->order->photo1)
                                <a href="{{url('/private/image')}}/{{$order->order->photo1}}" target="_blank">
                                    <img style="max-width: 4.8rem; max-height: 5rem;" src="{{url('/private/image')}}/{{$order->order->photo1}}">
                                </a>
                            @endif
                            @if ($order->order->photo2)
                                <a href="{{url('/private/image')}}/{{$order->order->photo2}}" target="_blank">
                                    <img style="max-width: 4.8rem; max-height: 5rem;" src="{{url('/private/image')}}/{{$order->order->photo2}}">
                                </a>
                            @endif
                            @if ($order->order->photo3)
                                <a href="{{url('/private/image')}}/{{$order->order->photo3}}" target="_blank">
                                    <img style="max-width: 4.8rem; max-height: 5rem;" src="{{url('/private/image')}}/{{$order->order->photo3}}">
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if (
                    $order->order->repair
                    && $order->order->repair->status == $order->order->repair::STATUS_COMPLETED
                )
                    <div class="row px-3 mt-4">
                        <div class="col-12">
                            <b>Date Repaired:</b>
                            {{$order->order->repair->date_completed->format('m/d/Y')}}
                        </div>
                    </div>
                    <div class="row px-3 mt-1">
                        <div class="col-12">
                            <label class="text-dark d-block w-100 mb-0">
                                <b>Agent Comments:</b>
                            </label>
                            <textarea class="form-control" rows="3" readonly>{{$order->order->repair->comment}}</textarea>
                        </div>
                    </div>
                    <div class="row px-3 mt-1">
                        <div class="col-12">
                            <label class="text-dark d-block w-100 mb-0">
                                <b>Installer Comments:</b>
                            </label>
                            <textarea class="form-control" rows="3"  readonly>{{$order->order->repair->installer_comments}}</textarea>
                        </div>
                    </div>
                    <div class="row px-3 mt-1">
                    <div class="col-12">
                        <label class="text-dark d-block w-100 mb-0">
                            <b>Installer Photos:</b>
                        </label>
                        <div class="d-flex justify-content-between">
                            @if ($order->order->repair->photo1)
                                <a href="{{url('/private/image')}}/{{$order->order->repair->photo1}}" target="_blank">
                                    <img style="max-width: 4.8rem; max-height: 5rem;" src="{{url('/private/image')}}/{{$order->order->repair->photo1}}">
                                </a>
                            @endif
                            @if ($order->order->repair->photo2)
                                <a href="{{url('/private/image')}}/{{$order->order->repair->photo2}}" target="_blank">
                                    <img style="max-width: 4.8rem; max-height: 5rem;" src="{{url('/private/image')}}/{{$order->order->repair->photo2}}">
                                </a>
                            @endif
                            @if ($order->order->repair->photo3)
                                <a href="{{url('/private/image')}}/{{$order->order->repair->photo3}}" target="_blank">
                                    <img style="max-width: 4.8rem; max-height: 5rem;" src="{{url('/private/image')}}/{{$order->order->repair->photo3}}">
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary ml-4" type="button" data-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>

@if ($order->order->agent)
    <input type="hidden" id="orderHasAgent" value="yes">
    <input type="hidden" id="agentId" value="{{$order->order->agent_id}}">
@else
    <input type="hidden" id="orderHasAgent" value="no">
    <input type="hidden" id="officeId" value="{{$order->order->office_id}}">
@endif

