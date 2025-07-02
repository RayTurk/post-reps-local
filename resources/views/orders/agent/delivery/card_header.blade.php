<div class="card-header  d-flex justify-content-between align-items-center">
    <div>
        <button
            class="btn btn-primary width-100-px font-weight-bold"
            style="min-width: 120px !important; width: 120px !important;"
            onclick="window.createDeliveryOrder()"
        >
            Create Order
        </button>
    </div>
    <div class="d-flex align-items-center" style="gap: 3px">
        <span>Show</span>
        <select class="form-control showDeliveryOrderEntries" id="showDeliveryOrderEntries">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>Entries</span>
    </div>
    {{-- <div>
        <button id="deleteAllDeliveryOrders" class="btn btn-danger">Delete All Orders</button>
    </div> --}}
    <div>
        <input type="text" class="form-control deliveryOrderSearchInput" id="deliveryOrderSearchInput" placeholder="search...">
    </div>
</div>
