<div class="modal fade" tabindex="-1" id="addEmailNotificationSettingsModalAgent" aria-labelledby="addEmailNotificationSettingsModalAgent" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 class="modal-title">Add New Email</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="newAgentEmailForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="form-group">
                                <label for="email"><b>Email</b></label>
                                <input type="email" id="newEmailAgent" class="form-control @error('email') is-invalid @enderror" name="email" required>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div class="form-group text-center">
                                    <label for="orderNotificationAgent" class="d-block">Order Notifications</label>
                                    <input type="checkbox" name="order" id="orderNotificationAgent" class="m-0 mx-1 scale-1_5">
                                </div>
                                <div class="form-group text-center">
                                    <label for="accountingNotificationAgent" class="d-block">Accounting Notification</label>
                                    <input type="checkbox" name="accounting" id="accountingNotificationAgent" class="m-0 mx-1 scale-1_5">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="submit" class="btn px-5 py-0 bg-primary text-white rounded-pill font-weight-bold" id="saveNewEmailBtn">Save</button>
                    <button type="button" class="btn px-5 py-0 btn-orange text-white rounded-pill font-weight-bold" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>