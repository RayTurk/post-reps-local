<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">4</span>
    <h5 class="pt-2">SIGN PANELS</h5>
</div>
<div class="px-4">
    <div class="row">
        <div class="col-12 col-md-12 col-lg-12">
            <label for="delivery_order_select_sign" class="text-primary d-block text-center"><span
                    class="blue-label">Sign Panel</span></label>
            <div class="list-container-delivery py-2 px-2 position-relative w-100" id="signPanelContainer">
                <div id="pickupContainer">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex min-width-190px">
                            <input
                                type="checkbox"
                                class="form-control w-h-px-25 mr-2 pickup-checkbox"
                                name="pickup_panel[1]"
                                data-row="1"
                            >
                            <label style="margin-left: -5px;" class="text-dark pt-1">Pick Up Sign Panels</label>
                        </div>
                        <div class="min-width-50px ml-2">
                            <input
                                type="text"
                                name="pickup_panel_qty[1]"
                                class="form-control text-center width-px-50 height-px-28 px-2 qty-box"
                                placeholder="QTY"
                                style="border: 1px solid #7a7575;"
                            >
                        </div>
                        <div class="d-flex justify-content-start min-width-340px ml-3">
                            <label class="text-dark pt-1">Panel Style: </label>
                            <select
                                style="border: 1px solid #7a7575;"
                                name="pickup_panel_style[1]"
                                class="form-control width-px-250 height-px-28 ml-1 panel-list pt-2px"
                                disabled
                            >
                            </select>
                        </div>
                        <div class="width-px-60 " style="line-height: 16px;">
                            <a href="#" id="addNewPickup1" data-row="1" class="text-primary font-weight-bold add-new-pickup" title="Add new panel">NEW SIGN</a>
                        </div>
                        <div class="width-px-60 ">
                            <a href="#" id="addAnotherPickup" class="text-primary font-weight-bold ml-2">
                                <img src="{{url('images')}}/plus_icon.jpg" alt="ADD" class="width-px-27 pt-1" title="Add another panel">
                            </a>
                        </div>
                    </div>
                </div>

                <div id="dropoffContainer">
                    <div class="d-flex justify-content-between mt-3">
                        <div class="d-flex min-width-190px">
                            <input
                                type="checkbox"
                                class="form-control w-h-px-25 mr-2 dropoff-checkbox"
                                name="dropoff_panel[1]"
                                data-row="1"
                            >
                            <label style="margin-left: -5px;" class="text-dark pt-1">Drop Off Sign Panels</label>
                        </div>
                        <div class="min-width-50px ml-2">
                            <input
                                type="text"
                                name="dropoff_panel_qty[1]"
                                class="form-control text-center width-px-50 height-px-28 px-2 qty-box"
                                placeholder="QTY"
                                style="border: 1px solid #7a7575;"
                            >
                        </div>
                        <div class="d-flex justify-content-start min-width-340px ml-3">
                            <label class="text-dark pt-1">Panel Style: </label>
                            <select
                                style="border: 1px solid #7a7575;"
                                name="dropoff_panel_style[1]"
                                class="form-control width-px-250 height-px-28 ml-1 panel-list pt-2px"
                                disabled
                            >
                            </select>
                        </div>
                        <div class="width-px-60 " style="line-height: 16px;">
                            <!-- <a href="#" id="addNewDropoff" class="text-primary font-weight-bold" title="Add new panel">NEW SIGN</a> -->
                        </div>
                        <div class="width-px-60 ">
                            <a href="#" id="addAnotherDropoff" class="text-primary font-weight-bold ml-2">
                                <img src="{{url('images')}}/plus_icon.jpg" alt="ADD" class="width-px-27 pt-1" title="Add another panel">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="pickupTmpl" style="display:none;">
    <div class="d-flex justify-content-between to-append">
        <div class="d-flex min-width-190px">
            <input
                type="checkbox"
                class="form-control w-h-px-25 mr-2 pickup-checkbox"
                name="pickup_panel[rowCount]"
                data-row="rowCount"
            >
            <label style="margin-left: -5px;" class="text-dark pt-1">Pick Up Sign Panels</label>
        </div>
        <div class="min-width-50px ml-2">
            <input
                type="text"
                name="pickup_panel_qty[rowCount]"
                class="form-control text-center width-px-50 height-px-28 px-2 qty-box"
                placeholder="QTY"
                style="border: 1px solid #7a7575;"
            >
        </div>
        <div class="d-flex justify-content-start min-width-340px ml-3">
            <label class="text-dark pt-1">Panel Style: </label>
            <select
                style="border: 1px solid #7a7575;"
                name="pickup_panel_style[rowCount]"
                class="form-control width-px-250 height-px-28 ml-1 panel-list pt-2px"
                disabled
            >
            </select>
        </div>
        <div class="width-px-60 " style="line-height: 16px;">
            <a href="#" id="addNewPickuprowCount" data-row="rowCount" class="text-primary font-weight-bold add-new-pickup" title="Add new panel">NEW SIGN</a>
        </div>
        <div class="width-px-60 ">
            <a href="#" class="text-primary font-weight-bold ml-2 remove-pickup">
                <img src="{{url('images')}}/Cancel_Icon.png" alt="REMOVE" class="width-px-27 pt-1" title="Remove panel">
            </a>
        </div>
    </div>
</div>

<div id="dropoffTmpl" style="display:none;">
    <div class="d-flex justify-content-between to-append">
        <div class="d-flex min-width-190px">
            <input
                type="checkbox"
                class="form-control w-h-px-25 mr-2 dropoff-checkbox"
                name="dropoff_panel[rowCount]"
                data-row="rowCount"
            >
            <label style="margin-left: -5px;" class="text-dark pt-1">Drop Off Sign Panels</label>
        </div>
        <div class="min-width-50px ml-2">
            <input
                type="text"
                name="dropoff_panel_qty[rowCount]"
                class="form-control text-center width-px-50 height-px-28 px-2 qty-box"
                placeholder="QTY"
                style="border: 1px solid #7a7575;"
            >
        </div>
        <div class="d-flex justify-content-start min-width-340px ml-3">
            <label class="text-dark pt-1">Panel Style: </label>
            <select
                style="border: 1px solid #7a7575;"
                name="dropoff_panel_style[rowCount]"
                class="form-control width-px-250 height-px-28 ml-1 panel-list pt-2px"
                disabled
            >
            </select>
        </div>
        <div class="width-px-60 " style="line-height: 16px;">
        </div>
        <div class="width-px-60 ">
            <a href="#" class="text-primary font-weight-bold ml-2 remove-dropoff">
                <img src="{{url('images')}}/Cancel_Icon.png" alt="REMOVE" class="width-px-27 pt-1" title="Remove panel">
            </a>
        </div>
    </div>
</div>
