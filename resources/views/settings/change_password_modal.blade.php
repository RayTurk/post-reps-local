<div class="modal fade" tabindex="-1" id="changePasswordModal" aria-labelledby="changePasswordModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content auth-card">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group text-center">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" id="newPassword">
                    </div>
                    <div class="form-group text-center">
                      <label for="passworConfirm">Confirm Password</label>
                      <input type="password" class="form-control" id="confirmPassword">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-orange font-weight-bold" id="changePasswordBtn">Change</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
