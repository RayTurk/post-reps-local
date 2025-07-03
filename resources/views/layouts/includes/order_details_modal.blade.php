<div class="modal fade" id="orderDetailsModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 id="orderDetailsHeader">Order Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="orderDetailsModalBody">
                <div class="row">
                    <div class="col-md-6">
                        <b>Date/Time Created:</b> <span created_at></span>
                    </div>
                    <div class="col-md-6">
                        <b>Order Total:</b> <span order_total></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <b>Office:</b> <span office></span>
                    </div>
                    <div class="col-md-6">
                        <b>Agent:</b> <span agent></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <b>Address:</b> <span address></span>
                    </div>
                    <div class="col-md-6">
                        <b>Property Type:</b> <span property_type></span>
                    </div>
                </div>
                <div class="row mt-2" id="removalOrderPickupAddress" style="display: none">
                    <div class="col-md-6">
                        <b>Pickup Address:</b> <span pickup_address></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <b>Service Date:</b> <span service_date></span>
                    </div>
                    <div class="col-md-6">
                        <b>Status:</b> <span status></span>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4 text-center">
                        <b>Post:</b> <span post></span><br>
                        <span post_image></span>
                    </div>
                    <div class="col-md-4 text-center">
                        <b>Sign Panel:</b> <span panel></span><br>
                        <span panel_image></span>
                    </div>
                    <div class="col-md-4 text-center">
                        <b>Accessories:</b> <span accessories></span></span><br>
                        <span accessories_images></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <b>Comments:</b> <span comments></span>
                    </div>
                </div>
                <div class="row mt-2 d-none" id="OrderDetailsInstallerName">
                    <div class="col-md-12">
                        <b>Installer Name:</b> <span installer_name></span>
                    </div>
                </div>
                <div class="row mt-2 d-none" id="OrderDetailsInstallerDateCompleted">
                    <div class="col-md-12">
                        <b>Date/Time Completed:</b> <span date_completed></span>
                    </div>
                </div>
                <div class="row mt-2 d-none" id="OrderDetailsInstallerComments">
                    <div class="col-md-12">
                        <b>Installer Comments:</b> <span installer_comments></span>
                    </div>
                </div>
                <div class="row mt-2" >
                    <div class="col-md-6 d-none" id="OrderDetailsAttachments">
                        <b>Order Attachments:</b><br> <span attachments></span>
                    </div>
                    <div class="col-md-6 d-none" id="OrderDetailsInstallerPhotos">
                        <b>Installer Photos:</b><br> <span installer_photos></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deliveryDetailsModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 id="deliveryDetailsHeader">Delivery Order Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="deliveryDetailsModalBody">
                <div class="row">
                    <div class="col-md-6">
                        <b>Date/Time Created:</b> <span created_at></span>
                    </div>
                    <div class="col-md-6">
                        <b>Order Total:</b> <span order_total></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <b>Office:</b> <span office></span>
                    </div>
                    <div class="col-md-6">
                        <b>Agent:</b> <span agent></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <b>Address:</b> <span address></span>
                    </div>
                    <div class="col-md-3">
                        <b>Service Date:</b> <span service_date></span>
                    </div>
                    <div class="col-md-3">
                        <b>Status:</b> <span status></span>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <b>Pick Up:</b><br> <span pickups></span>
                    </div>
                    <div class="col-md-6">
                        <b>Drop off:</b><br> <span dropoffs></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <b>Comments:</b> <span comments></span>
                    </div>
                </div>
                <div class="row mt-2 d-none" id="deliveryDetailsInstallerName">
                    <div class="col-md-12">
                        <b>Installer Name:</b> <span installer_name></span>
                    </div>
                </div>
                <div class="row mt-2 d-none" id="deliveryDetailsInstallerDateCompleted">
                    <div class="col-md-12">
                        <b>Date/Time Completed:</b> <span date_completed></span>
                    </div>
                </div>
                <div class="row mt-2 d-none" id="deliveryDetailsInstallerComments">
                    <div class="col-md-12">
                        <b>Installer Comments:</b> <span installer_comments></span>
                    </div>
                </div>
                <div class="row mt-2" >
                    <div class="col-md-12 d-none" id="deliveryDetailsInstallerPhotos">
                        <b>Installer Photos:</b><br> <span installer_photos></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>