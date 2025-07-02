<div class="text-orange-dark d-flex align-items-center gap-px-4">
    <span class="cnumber">4</span>
    <h5 class="pt-2">SIGNPOST AND ACCESSORIES</h5>
</div>
<div class="px-4">
    <div class="row">

        <div class="col-12 col-md-4 col-lg-4 mt-2">
            <label for="removal_order_select_post" class="text-primary text-center d-block text-center">
                <span class="blue-label">Post</span>
            </label>
            <div >
                <input type="text" class="form-control w-100" id="removalOrderPost" disabled>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-4 mt-2">
            <label for="removal_order_select_sign" class="text-primary d-block text-center"><span
                    class="blue-label">Sign Panel</span></label>
            <div class="list-container list-container-signs position-relative" style="height: 6rem;">
                <div class="form-check d-flex justify-content-between">
                    <input type="radio" name="removal_order_panel" value="0"
                        class="form-check-input"
                        id="add_to_inventory"
                        onchange="window.onSignPanelChange(event)"
                        checked
                    >
                    <label class="form-check-label text-dark" for="add_to_inventory">Add to Inventory</label>
                </div>
                <div class="form-check d-flex justify-content-between">
                    <input type="radio" name="removal_order_panel" value="1"
                        class="form-check-input"
                        id="agent_remove_leave_sign"
                        onchange="window.onSignPanelChange(event)"
                    >
                    <label class="form-check-label text-dark" for="agent_remove_leave_sign">
                        Agent Will Remove/Leave Sign at Property
                    </label>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-4 mt-2">
            <label for="removal_order_select_accessories" class="text-primary d-block text-center"><span
                    class="blue-label">Accessories</span></label>
            <div class="list-container list-container-accessories-removal disabled" style="height: 6rem;">

            </div>
        </div>
    </div>
</div>
