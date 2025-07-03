<div class="row px-3 mt-3">
    @if ($order->platMapFiles->isNotEmpty())
        @foreach ($order->platMapFiles as $file)
            <div class="col-1">
                <i class="fa fa-paperclip text-orange font-px-30" aria-hidden="true"></i>
            </div>
            <div class="col-11">
                Attachment<br>
                <a
                    href="{{url('/private/document/file')}}/{{$file->name}}"
                    style="color: blue;"
                    target="_blank"
                >
                    {{substr($file->name, 0, 40)}}
                </a>
            </div>
        @endforeach
    @endif
    @if ($order->attachmentsWithoutPlatMap->isNotEmpty())
        @foreach ($order->attachmentsWithoutPlatMap as $file)
            <div class="col-1">
                <i class="fa fa-paperclip text-orange font-px-30" aria-hidden="true"></i>
            </div>
            <div class="col-11">
                Attachment<br>
                <a
                    href="{{url('/private/document/file')}}/{{$file->name}}"
                    style="color: blue;"
                    target="_blank"
                >
                    {{substr($file->name, 0, 40)}}
                </a>
            </div>
        @endforeach
    @endif
    @if ($order->attachmentsWithoutPlatMap->isEmpty() && $order->platMapFiles->isEmpty())
        <div class="col-1">
            <i class="fa fa-paperclip font-px-30" aria-hidden="true"></i>
        </div>
        <div class="col-11">
            No Attachments
        </div>
    @endif
</div>


<div class="row px-3 mt-3">
    <div class="col-2 pt-4">
        <input
            type="checkbox"
            class="form-control w-h-px-30 installation-items"
            id="postCheckbox"
            data-item-id="{{$order->post->id}}"
        >
        <span
            class="fa fa-times out-of-inventory d-none"
            data-item-name="{{$order->post->post_name}}"
            data-item-id="{{$order->post->id}}"
        ></span>
    </div>
    <div class="col-2 pl-0">
        <img
            src="{{url('/private/image/post/')}}/{{$order->post->image_path}}"
            alt="{{$order->post->post_name}}"
            style="max-width: 4.6rem; max-height: 4.8rem;"
        >
    </div>
    <div class="col-7 pt-4">
        <span class="font-px-16">{{$order->post->post_name}}</span>
    </div>
</div>

@if ($order->panel)
    <div class="row px-3 mt-3">
        <div class="col-2 pt-4">
            <input
                type="checkbox"
                class="form-control w-h-px-30 installation-items"
                id="panelCheckbox"
                data-item-id="{{$order->panel->id}}"
            >
            <span
                class="fa fa-times out-of-inventory d-none"
                data-item-name="{{$order->panel->panel_name}}"
                data-item-id="{{$order->panel->id}}"
            ></span>
        </div>
        <div class="col-2 pl-0">
            <img
                src="{{url('/private/image/panel/')}}/{{$order->panel->image_path}}"
                alt="{{$order->panel->panel_name}}"
                style="max-width: 4.6rem; max-height: 4.8rem;"
            >
        </div>
        <div class="col-7 pt-4">
            <span class="font-px-16">{{$order->panel->panel_name}}</span>
            <span class="font-px-14 font-weight-bold">
                @if ($order->panel->id_number)
                    ID#{{$order->panel->id_number}}
                @endif
            </span>
        </div>
    </div>
@else
    @if ($order->agent_own_sign)
        <div class="row px-3 mt-3">
            <div class="col-2 py-2">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 installation-items"
                    disabled
                >
            </div>
            <div class="col-9 py-2">
                <span class="font-px-16">Agent will Hang Own Sign</span>
            </div>
        </div>
    @endif
    @if ($order->sign_at_property)
        <div class="row px-3 mt-3">
            <div class="col-2 py-2">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 installation-items"
                    disabled
                >
            </div>
            <div class="col-9 py-2">
                <span class="font-px-16">Sign Left at Property</span>
            </div>
        </div>
    @endif
@endif

@php $selectedAccessories = []; @endphp

@if ($order->accessories->isNotEmpty())
    @foreach ($order->accessories as $orderAccessory)
        @php array_push($selectedAccessories, $orderAccessory->accessory->id); @endphp

        <div class="row px-3 mt-3 accessory-div">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 accessories-checkbox installation-items"
                    data-item-id="{{$orderAccessory->accessory->id}}"
                >
                <span
                    class="fa fa-times out-of-inventory d-none"
                    data-item-name="{{$orderAccessory->accessory->accessory_name}}"
                    data-item-id="{{$orderAccessory->accessory->id}}"
                ></span>
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
                @if ($order->agent)
                    @if ($orderAccessory->accessory->getAgentInventoryList($order->agent_id, $order->office_id)->count())
                        <br>
                        <select class="height-px-30 width-px-200 accessories-item" data-accessory-name="{{$orderAccessory->accessory->accessory_name}}">
                            <option value="0"></option>
                            <option value="0">Inventory Not Listed</option>
                            @foreach ($orderAccessory->accessory->getAgentInventoryList($order->agent_id, $order->office_id)->get() as $item)
                                <option value="{{$item->id}}">{{$item->item_id}}</option>
                            @endforeach
                        </select><br>
                    @endif
                @else
                    @if ($orderAccessory->accessory->getOfficeInventoryList($order->office->id))
                        <select class="height-px-30 width-px-200 accessories-item" data-accessory-name="{{$orderAccessory->accessory->accessory_name}}">
                            <option value="0"></option>
                            <option value="0">Inventory Not Listed</option>
                            @foreach ($orderAccessory->accessory->inventories as $item)
                                @if ($order->office_id == $item->office_id)
                                    <option value="{{$item->id}}">{{$item->item_id}}</option>
                                @endif
                            @endforeach
                        </select><br>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
@endif

<!-- Need to preload office/agent accessories in case installer needs to add new ones to the order -->
@if ($order->agent)
    @php
        $agentService = new \App\Services\AgentService($order->agent);
        $agentAccessories = $agentService->getAgentAcessories($order->agent);
    @endphp
    @foreach ($agentAccessories as $agentAccessory)
        <div class="row px-3 mt-3 d-none hidden-accessories accessory-div" data-accessory-id="{{$agentAccessory->id}}">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 accessories-checkbox installation-items"
                    data-item-id="{{$agentAccessory->id}}"
                >
                <span
                    class="fa fa-times out-of-inventory d-none"
                    data-item-name="{{$agentAccessory->accessory_name}}"
                    data-item-id="{{$agentAccessory->id}}"
                ></span>
            </div>
            <div class="col-2 pl-0">
                <img
                    src="{{url('/private/image/accessory/')}}/{{$agentAccessory->image}}"
                    alt="{{$agentAccessory->accessory_name}}"
                    style="max-width: 4.6rem; max-height: 4.8rem;"
                >
            </div>
            <div class="col-7 pt-4">
                <span class="font-px-16">{{$agentAccessory->accessory_name}}</span>
                @if ($agentAccessory->getAgentInventoryList($order->agent_id, $order->office_id)->count())
                    <br>
                    <select class="height-px-30 width-px-200 accessories-item" data-accessory-name="{{$agentAccessory->accessory_name}}">
                        <option value="0"></option>
                        <option value="0">Inventory Not Listed</option>
                        @foreach ($agentAccessory->getAgentInventoryList($order->agent_id, $order->office_id)->get() as $item)
                            <option value="{{$item->id}}">{{$item->item_id}}</option>
                        @endforeach
                    </select><br>
                @endif
            </div>
            <div class="col-1 pt-4 pl-0">
                <i
                    class="fa fa-times-circle text-danger font-px-25 remove-accessory"
                    data-accessory-id="{{$agentAccessory->id}}"
                ></i>
            </div>
        </div>
    @endforeach
@else
    @php
        $officeService = new \App\Services\OfficeService($order->office);
        $officeAccessories = $officeService->getOfficeAccessories($order->office);
    @endphp
    @foreach ($officeAccessories as $officeAccessory)
        <div class="row px-3 mt-3 d-none hidden-accessories accessory-div" data-accessory-id="{{$officeAccessory->id}}">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 accessories-checkbox installation-items"
                    data-item-id="{{$officeAccessory->id}}"
                >
                <span
                    class="fa fa-times out-of-inventory d-none"
                    data-item-name="{{$officeAccessory->accessory_name}}"
                    data-item-id="{{$officeAccessory->id}}"
                ></span>
            </div>
            <div class="col-2 pl-0">
                <img
                    src="{{url('/private/image/accessory/')}}/{{$officeAccessory->image}}"
                    alt="{{$officeAccessory->accessory_name}}"
                    style="max-width: 4.6rem; max-height: 4.8rem;"
                >
            </div>
            <div class="col-7 pt-4">
                <span class="font-px-16">{{$officeAccessory->accessory_name}}</span>
                @if ($officeAccessory->getOfficeInventoryList($order->office->id)->count())
                    <br>
                    <select class="height-px-30 width-px-200 accessories-item" data-accessory-name="{{$officeAccessory->accessory_name}}">
                        <option value="0"></option>
                        <option value="0">Inventory Not Listed</option>
                        @foreach ($officeAccessory->getOfficeInventoryList($order->office->id)->get() as $item)
                            <option value="{{$item->id}}">{{$item->item_id}}</option>
                        @endforeach
                    </select><br>
                @endif
            </div>
            <div class="col-1 pt-4 pl-0">
                <i
                    class="fa fa-times-circle text-danger font-px-25 remove-accessory"
                    data-accessory-id="{{$officeAccessory->id}}"
                ></i>
            </div>
        </div>
    @endforeach
@endif

@if (
    ($order->agent && $order->agent->accessory_agents->count())
    || $order->office->accessory_offices->count()
)
    <div class="row mt-1">
        <div class="col-12 text-center">
            <button
                class="btn btn-primary mt-2 font-px-16 py-0"
                id="installOpenAccessoriesModal"
            >
                Add Accessory
            </button>
        </div>
    </div>
@endif

<!-- Pickup sign panels left at property -->
<div class="row mt-2">
    <div class="col-12 text-center">
        <button
            class="btn btn-primary mt-2 font-px-16 py-0"
            id="installAddPanelsBtn"
        >
            Pick Up Sign Panels
        </button>
    </div>
</div>

<!-- Need to preload office/agent accessories in modal -->
<div class="modal fade" id="installAccessoriesModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card">
            <div class="modal-header" style="display: block;">
                <div><h5>Add Accessory</h5></div>
                <div>
                    <input class="w-100" type="text" id="installSearchAccessory" placeholder="Search..." autocomplete="off">
                </div>
            </div>
            <div class="modal-body mb-5 height-px-350 overflow-y-auto" style="margin-top: -25px;">
                @if ($order->agent)
                    @php
                        $agentService = new \App\Services\AgentService($order->agent);
                        $agentAccessories = $agentService->getAgentAcessories($order->agent);
                    @endphp
                    @foreach ($agentAccessories as $agentAccessory)
                        <div
                            class="row px-3 mt-3 modal-accessories"
                            data-accessory-id="{{$agentAccessory->id}}"
                            data-accessory-name="{{$agentAccessory->accessory_name}}"
                        >
                            <div class="col-2 pt-4">
                                <input type="checkbox" class="form-control w-h-px-30 modal-accessories-checkbox" data-accessory-id="{{$agentAccessory->id}}">
                                <span class="fa fa-times out-of-inventory d-none"></span>
                            </div>
                            <div class="col-2 pl-0">
                                <img
                                    src="{{url('/private/image/accessory/')}}/{{$agentAccessory->image}}"
                                    alt="{{$agentAccessory->accessory_name}}"
                                    style="max-width: 4.6rem; max-height: 4.8rem;"
                                >
                            </div>
                            <div class="col-7 pt-4">
                                <span class="font-px-16">{{$agentAccessory->accessory_name}}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    @php
                        $officeService = new \App\Services\OfficeService($order->office);
                        $officeAccessories = $officeService->getOfficeAccessories($order->office);
                    @endphp
                    @foreach ($officeAccessories as $officeAccessory)
                        <div
                            class="row px-3 mt-3 modal-accessories"
                            data-accessory-id="{{$officeAccessory->id}}"
                            data-accessory-name="{{$officeAccessory->accessory_name}}"
                        >
                            <div class="col-2 pt-4">
                            <input type="checkbox" class="form-control w-h-px-30 modal-accessories-checkbox" data-accessory-id="{{$officeAccessory->id}}">
                                <span class="fa fa-times out-of-inventory d-none"></span>
                            </div>
                            <div class="col-2 pl-0">
                                <img
                                    src="{{url('/private/image/accessory/')}}/{{$officeAccessory->image}}"
                                    alt="{{$officeAccessory->accessory_name}}"
                                    style="max-width: 4.6rem; max-height: 4.8rem;"
                                >
                            </div>
                            <div class="col-7 pt-4">
                                <span class="font-px-16">{{$officeAccessory->accessory_name}}</span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="modal-footer">
                <button class="btn btn-orange" type="button" id="installAddAccessoryBtn">ADD</button>
                <button class="btn btn-secondary ml-4" type="button" data-dismiss="modal">CANCEL</button>
            </div>
        </div>
    </div>
</div>

<!-- Sign panels modal -->
<div class="modal fade" id="installAddPanelsModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
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
                <button class="btn btn-orange" type="button" id="installSavePanelsBtn">SAVE</button>
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

@if ($order->agent)
    <input type="hidden" id="orderHasAgent" value="yes">
    <input type="hidden" id="agentId" value="{{$order->agent_id}}">
@else
    <input type="hidden" id="orderHasAgent" value="no">
    <input type="hidden" id="officeId" value="{{$order->office_id}}">
@endif
