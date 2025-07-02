<div class="" id="service-global-settings-mobile">
    <div class="">
        <div class="">
            LATE FEES
        </div>
        <div class="text-center mt-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text p-0 px-1">$</span>
                </div>
                <input type="number" value="{{ $serviceSettings->late_fee_amount }}" name="late_fee_amount" step="any" class="form-control text-right">
            </div>
        </div>

        <div class="text-center mt-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text p-0 px-1">%</span>
                </div>
                <input type="number" value="{{ $serviceSettings->late_fee_percent }}" name="late_fee_percent" step="any" class="form-control text-right">
            </div>
        </div>
    </div>
    <div class="mt-5">
        <div class="mb-3">
            GRACE PERIOD
            <input type="number" value="{{ $serviceSettings->grace_period_days }}" name="grace_period_days" step="any" class="d-inline w-25 form-control text-center">
            (DAYS)
        </div>
        <div class="mb-3">
            DEFAULT INVOICE DUE DATE
            <input type="number" value="{{ $serviceSettings->default_invoice_due_date_days }}" name="default_invoice_due_date_days" step="any" class="d-inline w-25 form-control text-center">
            (DAYS)
        </div>
        <div class="">
            CONVENIENCE FEE
            <input type="number" value="{{ $serviceSettings->convenience_fee }}" name="convenience_fee" step="any" class="d-inline w-25 form-control text-center">
            %
        </div>
    </div>
</div>
