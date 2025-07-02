<div class="row px-3 mt-3">
    @if ($order->attachments->count())
        @foreach ($order->attachments as $file)
            <div class="col-1">
                <i class="fa fa-paperclip text-orange font-px-30" aria-hidden="true"></i>
            </div>
            <div class="col-11">
                Attachment<br>
                <a
                    href="{{url('/private/document/file')}}/{{$file->file_name}}"
                    style="color: blue;"
                    target="_blank"
                >
                    {{substr($file->file_name, 0, 40)}}
                </a>
            </div>
        @endforeach
    @else
        <div class="col-1">
            <i class="fa fa-paperclip font-px-30" aria-hidden="true"></i>
        </div>
        <div class="col-11">
            No Attachments
        </div>
    @endif
</div>

@if ($order->replace_repair_post)
    <div class="row px-3 mt-2">
        <div class="col-2 pt-4">
            <input
                type="checkbox"
                class="form-control w-h-px-30 repair-items not-item"
                id="replaceRepairPostCheckbox"
            >
        </div>
        <div class="col-7 pt-4 pl-0">
            <span class="font-px-16">Replace/Repair Post</span>
        </div>
    </div>
@endif

@if ($order->relocate_post)
    <div class="row px-3">
        <div class="col-2 pt-4">
            <input
                type="checkbox"
                class="form-control w-h-px-30 repair-items not-item"
                id="relocatePostCheckbox"
            >
        </div>
        <div class="col-7 pt-4 pl-0">
            <span class="font-px-16">Relocate Post</span>
        </div>
    </div>
@endif

@if ($order->panel_id)
    <div class="row px-3 mt-3">
        <div class="col-2 pt-4">
            <input
                type="checkbox"
                class="form-control w-h-px-30 repair-items"
                id="panelCheckbox"
                data-item-id="{{$order->panel->id}}"
            >
            <span
                class="fa fa-times repair-out-of-inventory d-none"
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
@endif

@if ($order->accessories->count())
    @foreach ($order->accessories as $repairOrderAccessory)
        <div class="row px-3 mt-3 accessory-div">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 accessories-checkbox repair-items to-remove"
                    data-item-id="{{$repairOrderAccessory->accessory->id}}"
                >
                <span
                    class="fa fa-times repair-out-of-inventory d-none"
                    data-item-name="{{$repairOrderAccessory->accessory->accessory_name}}"
                    data-item-id="{{$repairOrderAccessory->accessory->id}}"
                ></span>
            </div>
            <div class="col-2 pl-0">
                <img
                    src="{{url('/private/image/accessory/')}}/{{$repairOrderAccessory->accessory->image}}"
                    alt="{{$repairOrderAccessory->accessory->accessory_name}}"
                    style="max-width: 4.6rem; max-height: 4.8rem;"
                >
            </div>
            <div class="col-6 pt-4">
                <span class="font-px-16">{{$repairOrderAccessory->accessory->accessory_name}}</span>
                @if ($order->order->agent)
                    @if ($repairOrderAccessory->accessory->getAgentInventoryList($order->order->agent_id, $order->order->office_id)->count())
                        <br>
                        <select class="height-px-30 width-px-180 accessories-item" data-accessory-name="{{$repairOrderAccessory->accessory->accessory_name}}">
                            <option value="0"></option>
                            <option value="0">Inventory Not Listed</option>
                            @foreach ($repairOrderAccessory->accessory->getAgentInventoryList($order->order->agent_id, $order->order->office_id)->get() as $item)
                                <option value="{{$item->id}}">{{$item->item_id}}</option>
                            @endforeach
                        </select><br>
                    @endif
                @else
                    @if ($repairOrderAccessory->accessory->getOfficeInventoryList($order->order->office_id))
                        <select class="height-px-30 width-px-180 accessories-item" data-accessory-name="{{$repairOrderAccessory->accessory->accessory_name}}">
                            <option value="0"></option>
                            <option value="0">Inventory Not Listed</option>
                            @foreach ($repairOrderAccessory->accessory->inventories as $item)
                                @if ($order->order->office_id == $item->office_id)
                                    <option value="{{$item->id}}">{{$item->item_id}}</option>
                                @endif
                            @endforeach
                        </select><br>
                    @endif
                @endif
            </div>
            @if ($repairOrderAccessory->action == $repairOrderAccessory::ACTION_REMOVE)
                <div class="col-2 pt-4">
                    <span
                        class="fa fa-minus-square repair-remove-accessory-icon"
                        data-item-name="{{$repairOrderAccessory->accessory->accessory_name}}"
                        data-item-id="{{$repairOrderAccessory->accessory_id}}"
                        style="display: block; margin-left: 5%; font-size: 30px;"
                    ></span>
                </div>
            @endif
            @if ($repairOrderAccessory->action == $repairOrderAccessory::ACTION_ADD_REPLACE)
                <div class="col-2 pt-4">
                    <span
                        class="fa fa-plus-square repair-add-accessory-icon"
                        data-item-name="{{$repairOrderAccessory->accessory->accessory_name}}"
                        data-item-id="{{$repairOrderAccessory->accessory_id}}"
                        style="display: block; margin-left: 5%; font-size: 30px;"
                    ></span>
                </div>
            @endif
        </div>
    @endforeach
@endif

<!-- Need to preload office/agent accessories in case repairer needs to add new ones to the order -->
@if ($order->order->agent)
    @php
        $agentService = new \App\Services\AgentService($order->order->agent);
        $agentAccessories = $agentService->getAgentAcessories($order->order->agent);
    @endphp
    @foreach ($agentAccessories as $agentAccessory)
        <div class="row px-3 mt-3 d-none hidden-accessories accessory-div" data-accessory-id="{{$agentAccessory->id}}">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 accessories-checkbox repair-items"
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
                @if ($agentAccessory->getAgentInventoryList($order->order->agent_id, $order->order->office_id)->count())
                    <br>
                    <select class="height-px-30 width-px-200 accessories-item" data-accessory-name="{{$agentAccessory->accessory_name}}">
                        <option value="0"></option>
                        <option value="0">Inventory Not Listed</option>
                        @foreach ($agentAccessory->getAgentInventoryList($order->order->agent_id, $order->order->office_id)->get() as $item)
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
        $officeService = new \App\Services\OfficeService($order->order->office);
        $officeAccessories = $officeService->getOfficeAccessories($order->order->office);
    @endphp
    @foreach ($officeAccessories as $officeAccessory)
        <div class="row px-3 mt-3 d-none hidden-accessories accessory-div" data-accessory-id="{{$officeAccessory->id}}">
            <div class="col-2 pt-4">
                <input
                    type="checkbox"
                    class="form-control w-h-px-30 accessories-checkbox repair-items"
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
                @if ($officeAccessory->getOfficeInventoryList($order->order->office->id)->count())
                    <br>
                    <select class="height-px-30 width-px-200 accessories-item" data-accessory-name="{{$officeAccessory->accessory_name}}">
                        <option value="0"></option>
                        <option value="0">Inventory Not Listed</option>
                        @foreach ($officeAccessory->getOfficeInventoryList($order->order->office->id)->get() as $item)
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
    ($order->order->agent && $order->order->agent->accessory_agents->count())
    || $order->order->office->accessory_offices->count()
)
    <div class="row mt-1">
        <div class="col-12 text-center">
            <button
                class="btn btn-primary mt-2 font-px-16 py-0"
                id="repairOpenAccessoriesModal"
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
            id="repairAddPanelsBtn"
        >
            Pick Up Sign Panels
        </button>
    </div>
</div>

<!-- Need to preload office/agent accessories in modal -->
<div class="modal fade" id="repairAccessoriesModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card">
            <div class="modal-header" style="display: block;">
                <div><h5>Add Accessory</h5></div>
                <div>
                    <input class="w-100" type="text" id="repairSearchAccessory" placeholder="Search..." autocomplete="off">
                </div>
            </div>
            <div class="modal-body mb-5 height-px-350 overflow-y-auto" style="margin-top: -25px;">
                @if ($order->order->agent)
                    @php
                        $agentService = new \App\Services\AgentService($order->order->agent);
                        $agentAccessories = $agentService->getAgentAcessories($order->order->agent);
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
                        $officeService = new \App\Services\OfficeService($order->order->office);
                        $officeAccessories = $officeService->getOfficeAccessories($order->order->office);
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
                <button class="btn btn-orange" type="button" id="repairAddAccessoryBtn">ADD</button>
                <button class="btn btn-secondary ml-4" type="button" data-dismiss="modal">CANCEL</button>
            </div>
        </div>
    </div>
</div>

<!-- Sign panels modal -->
<div class="modal fade" id="repairAddPanelsModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
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
                <button class="btn btn-orange" type="button" id="repairSavePanelsBtn">SAVE</button>
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

<!-- Preload install order history -->
<div class="modal fade" id="repairHistoryModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalError" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content auth-card height-px-500 overflow-y-auto">
            <div class="modal-header" style="display: block;">
                <div><h5>View History</h5></div>
            </div>
            <div class="modal-body" style="margin-top: -25px;">
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
