<div class="row">
                            <div class="col-md-3">
                            </div>
                            <div class="col-md-2 text-center">
                                NUMBER OF INVOICES
                            </div>
                            <div class="col-md-2 pl-4">
                                BALANCE TOTALS
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3 text-right">
                                UNPAID INVOICES
                            </div>
                            <div class="col-md-2 text-center">
                                <input
                                    type="text"
                                    class="border-none width-px-150 py-0 font-px-18 text-center bg-white" readonly
                                    value="{{$countUnpaidInvoices}}"
                                >
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                        class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                        value="{{$sumUnpaidInvoices}}" readonly
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 text-right">
                                PAST DUE INVOICES
                            </div>
                            <div class="col-md-2 text-center">
                                <input type="text"
                                class="border-none width-px-150 py-0 font-px-18 text-center bg-white"
                                readonly value="{{$countPastDueInvoices}}">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                        class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                        value="{{$sumPastDueInvoices}}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 text-right">
                                PAYMENTS RCVD MONTH
                            </div>
                            <div class="col-md-2 text-center">
                                <input type="text"
                                    class="border-none width-px-150 py-0 font-px-18 text-center bg-white"
                                    readonly value="{{$countPaymentsCurrentMonth}}">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                    class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                    value="{{$sumPaymentsCurrentMonth}}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 text-right">
                                PAYMENTS RCVD YTD
                            </div>
                            <div class="col-md-2 text-center">
                                <input type="text"
                                class="border-none width-px-150 py-0 font-px-18 text-center bg-white"
                                readonly value="{{$countPaymentsYtd}}">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="input-group width-px-150">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                    class="border-none width-px-130 py-0 font-px-18 bg-white text-right"
                                    value="{{$sumPaymentsYtd}}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5 px-5">
                            <div class="col-md-12 px-5 text-center">
                                <canvas id="analyticsChart"
                                    style="max-width: 100%; max-height: 350px; background-color: white"
                                    class="px-4 pt-1 pb-2"
                                    data-payments="{{json_encode($chartData)}}"
                                >
                                </canvas>
                            </div>
                        </div>
