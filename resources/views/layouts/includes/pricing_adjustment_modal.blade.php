<!-- Modal -->
<div class="modal fade" id="installPriceAdjustmentModal" data-keyboard="true" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content auth-card">
            <div class="modal-header d-flex justify-content-center">
                <h4 class="text-orange">PRICING ADJUSTMENTS</h4>
                <!-- <button type="button" class="close order-hold" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button> -->
            </div>
            <div class="modal-body">
                <div class="row" style="margin-top: -25px;">
                    <div class="col-12 col-md-1">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="card_name" class="text-dark">
                            <strong>DESCRIPTION</strong>
                        </label>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="card_name" class="text-dark">
                            <strong>CHARGES</strong>
                        </label>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="card_name" class="text-dark">
                            <strong>DISCOUNTS</strong>
                        </label>
                    </div>
                </div>
                <div class="row px-3" id="rowContainerInstallAdjustments">

                </div>
                <div class="row">
                    <div class="col-12 col-md-1">
                    </div>
                    <div class="col-12 col-md-11">
                        <button type="button" class="btn btn-success btn-sm" id="addAdjustmentInstallBtn">
                            Add Another Adjustment
                        </button>
                    </div>
                </div>
                <div class="row mt-5">
                    <div class="col-12 mb-2 d-flex justify-content-around">
                        <button class="btn btn-orange rounded-pill mx-auto d-block width-px-100 text-white font-weight-bold"
                                id="savePricingAdjustmentInstallBtn" type="button"
                        >SAVE
                        </button>

                        <button type="button" class="btn btn-primary rounded-pill mx-auto d-block width-px-100" id="closeInstallPriceAdjustmentModalBtn">
                            <strong class="text-white">CANCEL</strong>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="rowTmplInstallAdjustment" style="display:none;">
    <div class="row mb-2">
        <div class="col-12 col-md-1 text-right remove-price-adjustment-row">
            <a class="link text-danger mx-1">
                <img src="{{url('/images/Cancel_Icon.png')}}" title="Remove" alt="Remove" class="width-px-35">
            </a>
        </div>
        <div class="col-12 col-md-5">
            <input type="text" name="install_price_adjustment_description[rowCount]" class="form-control">
        </div>
        <div class="col-12 col-md-3 input-group">
            <div class="input-group-prepend">
                <span class="input-group-text px-2" id="dollar-symbol">$</span>
            </div>
            <input type="number" step="0.01" name="install_price_adjustment_charge[rowCount]" class="form-control charges text-right" aria-describedby="dollar-symbol">
        </div>
        <div class="col-12 col-md-3 input-group">
            <div class="input-group-prepend">
                <span class="input-group-text px-2" id="dollar-symbol">$</span>
            </div>
            <input type="number" step="0.01" name="install_price_adjustment_discount[rowCount]" class="form-control discounts text-right" aria-describedby="dollar-symbol">
        </div>
    </div>
</div>
