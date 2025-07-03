<div class="row">
                            <div class="col-md-3 col-3">
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                NUMBER OF INVOICES
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                BALANCE TOTALS
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3 col-3 text-right pt-0">
                                UNPAID INVOICES
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <input
                                    type="text"
                                    class="border-none width-px-90 py-0 font-px-18 text-center bg-white" readonly
                                    value="{{$countUnpaidInvoices}}"
                                >
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <div class="input-group width-px-120">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                        class="border-none width-px-90 py-0 font-px-18 bg-white text-right"
                                        value="{{$sumUnpaidInvoices}}" readonly
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 col-3 text-right">
                                PAST DUE INVOICES
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <input type="text"
                                class="border-none width-px-90 py-0 font-px-18 text-center bg-white"
                                readonly value="{{$countPastDueInvoices}}">
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <div class="input-group width-px-120">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                        class="border-none width-px-90 py-0 font-px-18 bg-white text-right"
                                        value="{{$sumPastDueInvoices}}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 col-3 text-right">
                                PAYMENTS RCVD MONTH
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <input type="text"
                                    class="border-none width-px-90 py-0 font-px-18 text-center bg-white"
                                    readonly value="{{$countPaymentsCurrentMonth}}">
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <div class="input-group width-px-120">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                    class="border-none width-px-90 py-0 font-px-18 bg-white text-right"
                                    value="{{$sumPaymentsCurrentMonth}}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3 col-3 text-right">
                                PAYMENTS RCVD YTD
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <input type="text"
                                class="border-none width-px-90 py-0 font-px-18 text-center bg-white"
                                readonly value="{{$countPaymentsYtd}}">
                            </div>
                            <div class="col-md-2 col-4 text-center">
                                <div class="input-group width-px-120">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text p-0 px-1" id="basic-addon1">$</span>
                                    </div>
                                    <input type="number"
                                    class="border-none width-px-90 py-0 font-px-18 bg-white text-right"
                                    value="{{$sumPaymentsYtd}}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-12 text-center">
                                <canvas id="analyticsChartMobile"
                                    style="max-width: 100%; max-height: 400px; background-color: white"
                                    class="px-4 py-3"
                                    data-payments="{{json_encode($chartData)}}"
                                >
                                </canvas>
                            </div>
                        </div>
