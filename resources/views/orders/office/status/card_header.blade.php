<div class="card-header  d-flex justify-content-between align-items-center">
    <h6>ORDERS STATUS</h6>
    <div class="d-flex align-items-center" style="gap: 3px">
        <span>Show</span>
        <select class="form-control showOrderStatusEntries" id="showOrderStatusEntries">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span>Entries</span>
    </div>
<!--     <div class="d-flex align-items-center" style="gap: 3px;">
        <span>Filter</span>
        <select class="form-control" id="filterOrders" style="width: 150px !important;">
            <option value="">All</option>
            <option value="Received">Received</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div> -->
    {{-- <div>
        <a id="deleteAllOrdersStatus" class="btn btn-danger">Delete All Orders</a>
    </div> --}}
    <div>
        <input type="text" class="form-control searchOrders" id="searchOrders" placeholder="search...">
    </div>
</div>
