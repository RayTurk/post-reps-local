<div class="" id="service-global-settings">
    <div class="row mt-2">
        <div class="col-md-3 text-right">
            LATE FEES
        </div>
        <div class="text-center">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text p-0 px-1">$</span>
                </div>
                <input type="number" value="{{ $serviceSettings->late_fee_amount }}" name="late_fee_amount" step="any" class="form-control form-control-sm text-right">
            </div>
        </div>

        <div class=" text-center ml-5">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text p-0 px-1">%</span>
                </div>
                <input type="number" value="{{ $serviceSettings->late_fee_percent }}" name="late_fee_percent" step="any" class="form-control form-control-sm text-right">
            </div>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-md-3 text-right">
            GRACE PERIOD
        </div>
        <div class="text-center">
            <input type="number" value="{{ $serviceSettings->grace_period_days }}" name="grace_period_days" step="any" class="form-control form-control-sm text-center">
        </div>
        <div class="ml-4">(DAYS)</div>
    </div>
    <div class="row mt-5">
        <div class="col-md-3 text-right">
            DEFAULT INVOICE DUE DATE
        </div>
        <div class="text-center">
            <input type="number" value="{{ $serviceSettings->default_invoice_due_date_days }}" name="default_invoice_due_date_days" step="any" class="form-control form-control-sm text-center">
        </div>
        <div class="ml-4">(DAYS)</div>
    </div>

    <div class="row mt-5">
        <div class="col-md-3 text-right">
            CONVENIENCE FEE
        </div>
        <div class="text-center">
            <input type="number" value="{{ $serviceSettings->convenience_fee }}" name="convenience_fee" step="any" class="form-control form-control-sm text-center">
        </div>
        <div class="ml-4">%</div>
    </div>
</div>
