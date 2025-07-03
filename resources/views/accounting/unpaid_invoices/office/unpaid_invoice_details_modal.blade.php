<style>
    .agent-totals {
        height: 5rem;
        border-bottom: none;
    }

    .agent-totals-last {
        height: 2rem;
    }

    .text-right {
        text-align: right !important;
    }
</style>

<div class="modal fade" id="invoiceDetails" tabindex="-1" aria-labelledby="invoiceDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-body auth-card">

                <div class="d-flex justify-content-between">
                    <div class="font-weight-bold h5">Invoice #: <span id="invoiceNumber">E22E28C</span></div>
                    <div class="font-weight-bold h5">Invoiced:  <span class="font-weight-normal" id="invoiceDate">May 6th, 2022</span ></div>
                    <div class="font-weight-bold h5">Due date: <span class="font-weight-normal" id="invoiceDueDate">May 16th</span ></div>
                </div>

                <div class="d-flex flex-lg-row flex-column justify-content-around">

                    <div class="mt-4">
                        <p class="font-weight-bold h5">BILL TO</p>
                        <span class="d-block" id="officeAgentName">Brenda & Sons</span>
                        <span class="d-block" id="officeAgentAddress">68 Saint Joseph St</span>
                        <span class="d-block" id="officeAgentState">Warwick, RI, 02886</span>
                        <span class="d-block" id="officeAgentPhone">(774) 255-4436</span>
                    </div>

                    <div class="mt-4">
                        <p class="font-weight-bold h5">INVOICE SUMMARY</p>
                        <div class="d-flex">
                            <div class="">
                                <span class="d-block">CHARGES</span>
                                <span class="d-block">ADJUSTMENTS</span>
                                <span class="d-block">PAID</span>
                                <span class="d-block">AMOUNT DUE</span>
                            </div>
                            <div class="ml-5">
                                <span class="d-block" id="charges">$ 170.00</span>
                                <span class="d-block" id="adjustments">$ 0.00</span>
                                <span class="d-block" id="paid">$ 0.00</span>
                                <span class="d-block" id="amountDue">$ 170</span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-5 text-center">
                    <p class="font-weight-bold h5">CHARGE DETAILS</p>
                    <div class="table-responsive">
                        <table class="table" id="invoiceDetailsTable">
                            <thead class="auth-card">
                                <tr class="">
                                  <th style="width:15%;" scope="col" class="">ORDER #</th>
                                  <th style="width:38%;" scope="col" class="">ORDER DETAILS</th>
                                  <th style="width:29%;" scope="col" class="">AGENT</th>
                                  <th style="width:9%;"scope="col" class="">DATE</th>
                                  <th style="width:9%;" scope="col" class="">CHARGES</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <p class="font-weight-bold h5">ADJUSTMENTS</p>
                    <div class="table-responsive">
                        <table class="table" id="invoiceAdjustmentsDetailsTable">
                            <thead>
                                <tr class="">
                                  <th scope="col" class="" style="width: 70%">COMMENT</th>
                                  <th scope="col" class="" style="text-align: center">DATE</th>
                                  <th scope="col" class="" style="text-align: end">CHARGES/CREDITS</th>
                                </tr>
                              </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <p class="font-weight-bold h5">PAYMENTS RECEIVED</p>
                    <div class="table-responsive">
                        <table class="table" id="invoicePaymentsDetailsTable">
                            <thead>
                                <tr class="">
                                  <th scope="col" class="" style="width: 35%">PAYMENT TYPE</th>
                                  <th scope="col" class="" style="width: 35%">COMMENT</th>
                                  <th scope="col" class="" style="text-align: end">DATE</th>
                                  <th scope="col" class="" style="text-align: end">AMOUNT</th>
                                </tr>
                              </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer auth-card">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
