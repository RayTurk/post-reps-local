<!-- Modal -->
<div class="modal fade" id="paymentsModal" tabindex="-1" aria-labelledby="paymentsModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content auth-card">
            <div class="modal-header text-center">
                <h5 class="modal-title font-weight-bold w-100" id="paymentsModal">INSTALLER PAYMENTS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div class="w-100">
                    Installer
                    <select class="form-control-sm text-center w-25" name="installer_payment_select" id="installerPaymentSelect">
                        <option value=""></option>
                        @foreach ($installers as $installer)
                            <option value="{{ $installer->id }}">{{ $installer->name }}</option>
                        @endforeach
                    </select>
                    <a href="#" class="underline ml-2" data-toggle="modal" data-target="#addPaymentModal" id="addPaymentButton">Add Payment</a>
                    <input type="text" class="form-control-sm ml-2 installerPaymentsInput w-25" name="" id="" placeholder="Search...">
                </div>
                <div class="font-weight-bold mt-2 mb-4">
                    <p>Total Due: <span id="total_due_points"></span></p>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover text-center w-100" id="paymentsTable" {{--style="margin: 0 auto; border-collapse: separate; border-spacing: 1em;"--}}>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
