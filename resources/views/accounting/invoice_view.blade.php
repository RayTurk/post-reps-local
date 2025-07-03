<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <title>Invoice</title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

        <noscript>
            <h3> You must have JavaScript enabled in order to use this website. Please
                enable JavaScript and then reload this page in order to continue.
            </h3>
            <meta HTTP-EQUIV="refresh" content=0; url="https://www.enable-javascript.com/">
        </noscript>

        <style>

            body {
                font-size: 16px;
                font-family: 'verdana';
            }

            /* INVOICE HEADER */
            .invoice-header {
                display: flex;
                flex-direction: row;
                justify-content: space-between;
            }

            .invoice-header .invoice-header-container {
                display: flex;
                flex-direction: row;
            }

            .invoice-header-text {
                margin-bottom: 0;
                text-align: end
            }

            table {
                border-collapse: collapse;
            }

            .invoice-header-table {
                border: 1px solid blue;
            }

            .invoice-header-table td, .invoice-header-table th {
                padding: 0px 15px;
                border-bottom: 1px solid blue;
            }

            .invoice-header-table thead {
                background-color: #2664AD;
                color: #fff;
            }

            /* BILL TO / INVOICE SUMMARY */
            .invoice-bill-summary-container {
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                margin-top: 1.2em;
            }

            .bill-to {
                flex: 1;
                max-width: 45%;
            }

            .bill-to .bill-to-header {
                text-align: center;
                padding: 5px 0;
                border: 1px solid blue;
                background-color: #2664AD;
                color: #fff;
                font-weight: bold;
            }

            .bill-to-content {
                padding: 1px 10px;
                border: 1px solid blue;
                border-top: 0;
                height: 8rem;
            }

            .bill-to-text .text {
                line-height: 25px;
                font-weight: 500;
                font-style: normal;
            }

            .invoice-summary {
                flex: 1;
                max-width: 45%;
            }

            .invoice-summary .invoice-summary-header {
                text-align: center;
                padding: 5px 0;
                border: 1px solid blue;
                background-color: #2664AD;
                color: #fff;
                font-weight: bold;
            }

            .summary-content {
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                padding: 0 10px;
                border: 1px solid blue;
                border-top: 0;
                height: 8rem;
            }

            .summary-content .text {
                line-height: 25px;
                font-weight: 500;
                font-style: normal;
            }

            /* CHARGE DETAILS */
            .charge-details-container {
                text-align: center;
                margin-top: 2em;
            }

            .charge-details-header {
                text-align: center;
                padding: 5px 0;
                border: 1px solid blue;
                background-color: #2664AD;
                color: #fff;
                font-weight: bold;
            }

            .charge-details-table {
                width: 100%;
            }

            .charge-details-table {
                border: 1px solid blue;
                border-top: 0;
            }

            .charge-details-table th {
                padding: 10px 10px;
                text-align: left;
                border-bottom: 1px solid blue;
            }

            .charge-details-table td {
                padding: 10px 10px 10px 10px;
                text-align: left;
                border-top: 1px solid #aaaaaa;
            }

            span {
                font-weight: bold;
                font-style: italic;
            }

            /* ADJUSTMENTS DETAILS */
            .adjustments-details-container {
                margin-top: 2em;
                text-align: center;
                /* Put the next section into another page, also can use before.*/
                /* page-break-after:always; */
            }

            .adjustments-details-header {
                text-align: center;
                padding: 5px 0;
                border: 1px solid blue;
                border-bottom: 0;
                background-color: #2664AD;
                color: #fff;
                font-weight: bold;
            }

            .adjustments-details-table{
                width: 100%;
                border: 1px solid blue;
            }

            .adjustments-details-table th {
                padding: 10px 10px;
                text-align: left;
                border-bottom: 1px solid blue;
            }

            .adjustments-details-table td {
                padding: 10px 10px 10px 10px;
                text-align: left;
            }

            /* PAYMENTS RECEIVED */
            .payments-received-container {
                margin-top: 2em;
                margin-bottom: 1em;
                text-align: center;
            }

            .payments-received-header {
                text-align: center;
                padding: 5px 0;
                border: 1px solid blue;
                border-bottom: 0;
                background-color: #2664AD;
                color: #fff;
                font-weight: bold;
            }

            .payments-received-table{
                width: 100%;
                border: 1px solid blue;
            }

            .payments-received-table th {
                padding: 10px 10px;
                text-align: left;
                border-bottom: 1px solid blue;
            }

            .payments-received-table td {
                padding: 10px 10px 10px 10px;
                text-align: left;
            }

            tbody.separated::before{
                content: '';
                display: block;
                height: 50px;
            }

            .text-right {
                text-align: right !important;
            }

            .agent-totals {
                height: 5rem;
                border-bottom: none;
            }

            .agent-totals-last {
                height: 2rem;
            }

            @page {
                margin: 25px 30px 18px 30px;
            }
        </style>

    </head>

    <body style="margin: auto; max-width: 1200px;">

        {{-- INVOICE HEADER --}}
        <div class="invoice-header" style="margin-top: -10px;">
            <div class="invoice-header-container">
                <div style="padding-top: 10px;">
                    <img src="{{ asset('/storage/images/logo.png') }}" alt="{{ config('app.name', 'Post Reps') }}" height="100">
                </div>
                <div class="">
                    <p class="" style="width: 9em;">
                        PostReps, LLC<br>
                        PO BOX 1594<br>
                        NAMPA, ID 83653<br>
                        www.postreps.com<br>
                        (208) 546-5546
                    </p>
                </div>
            </div>

            <div class="">
                <p class="invoice-header-text">Invoiced: {{$invoice->created_at->format('M jS, Y')}}</p>
                <table class="invoice-header-table">
                    <thead>
                        <tr class="">
                          <th scope="col" class="">INVOICE#</th>
                          <th scope="col" class="">DUE DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="">{{$invoice->invoice_number}}</td>
                            <td class="">{{$invoice->due_date->format('M jS, Y')}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BILL TO / INVOICE SUMMARY --}}
        <div class="invoice-bill-summary-container">
            <div class="bill-to">
                <div class="bill-to-header">BILL TO</div>
                <div class="bill-to-content">
                    <div class="bill-to-text">
                        @if ($invoice->agent_name)
                            <span class="text">{{$invoice->agent_name}}</span><br>
                            <span class="text">{{$invoice->agent_address}}</span><br>
                            <span class="text">{{$invoice->agent_city}}, {{$invoice->agent_state}}, {{$invoice->agent_zipcode}}</span><br>
                            <span class="text">{{$invoice->agent_phone}}</span>
                        @else
                            <span class="text">{{$invoice->office_name}}</span><br>
                            <span class="text">{{$invoice->office_address}}</span><br>
                            <span class="text">{{$invoice->office_city}}, {{$invoice->office_state}}, {{$invoice->office_zipcode}}</span><br>
                            <span class="text">{{$invoice->office_phone}}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="invoice-summary">
                <div class="invoice-summary-header">INVOICE SUMMARY</div>
                <div class="card-body border border-2 border-primary">
                    <div class="summary-content">
                    <div class="">
                        <span class="text">CHARGES</span><br>
                        <span class="text">ADJUSTMENTS</span><br>
                        <span class="text">PAID</span><br>
                        <hr class="text" style="visibility: hidden;">
                        <p class="text" style="font-weight: bold; font-size: 18px;">AMOUNT DUE</p>
                    </div>
                    <div class="">
                        <span class="text">$ {{number_format($invoice->invoice_lines->sum('amount'), 2, '.', ',')}}</span><br>
                        <span class="text">$ {{number_format($invoice->adjustments->sum("amount"), 2, '.', ',')}}</span><br>
                        <span class="text">$ {{number_format($invoice->payments->sum("total"), 2, '.', ',')}}</span><br>
                        <hr class="text" style="border-top: 2px solid #000;"/>
                        <p class="text" class="" style="font-weight: bold; font-size: 18px;">
                            <span class="text" class="" style="font-weight: bold; font-size: 18px;">
                                $ {{number_format($invoice->amount, 2, '.', ',')}}
                            </span>
                        </p>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHARGE DETAILS --}}
        <div class="charge-details-container">
            <div class="charge-details-card">
                <div class="charge-details-header">CHARGE DETAILS</div>
                <div class="">
                    <table class="charge-details-table">
                        <thead>
                          <tr class="">
                            <th style="width:15%;" scope="col" class="">ORDER #</th>
                            <th style="width:38%;" scope="col" class="">ORDER DETAILS</th>
                            <th style="width:29%;" scope="col" class="">AGENT</th>
                            <th style="width:9%;"scope="col" class="">DATE</th>
                            <th style="width:9%;" scope="col" class="">CHARGES</th>
                          </tr>
                        </thead>
                        <tbody>
                            @php
                                $agentTotal = 0;
                                $processedAgents = [];
                                $previousAgent = null;
                            @endphp
                            @foreach ($invoice->invoice_lines as $key => $invoiceLine)
                                @switch($invoiceLine->order_type)
                                    @case($invoiceLine::ORDER_TYPE_INSTALL)
                                        @php $order =  App\Models\Order::find($invoiceLine->order_id); @endphp
                                        @if ($previousAgent != null && ! in_array($order->agent_id, $processedAgents) && ! $loop->first)
                                            <tr class="agent-totals">
                                                <td class=""></td>
                                                <td class=""></td>
                                                <td colspan="2" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                                <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ {{number_format($agentTotal, 2, '.', ',')}}</td>
                                            </tr>
                                        @endif

                                        @php
                                            if (! in_array($order->agent_id, $processedAgents)) {
                                                array_push($processedAgents, $order->agent_id);
                                                $agentTotal = 0;
                                                $previousAgent = $order->agent_id;
                                            }

                                            $chargeDetails = $order->address;
                                            $lineTotal = number_format($order->total, 2, '.', ',');
                                            if ($invoiceLine->missing_items) {
                                                $chargeDetails = "$order->address: $invoiceLine->description";
                                                $lineTotal = number_format($invoiceLine->amount, 2, '.', ',');
                                            }
                                        @endphp

                                        <tr>
                                            <td>{{$order->order_number}}</td>
                                            <td><span class=""><u>INSTALL: </u></span> {{$chargeDetails}}</td>
                                            <td>
                                                {{$order->agent->user->name ?? ''}}<br>
                                                {{$order->agent->user->phone ?? ''}}
                                            </td>
                                            <td>{{$order->date_completed ? $order->date_completed->format('m/d/Y') : $order->updated_at->format('m/d/Y')}}</td>
                                            <td class="text-right" >$ {{$lineTotal}}</td>
                                        </tr>
                                        @break
                                    @case($invoiceLine::ORDER_TYPE_REPAIR)
                                        @php $order =  App\Models\RepairOrder::find($invoiceLine->order_id); @endphp
                                        @if ($previousAgent != null && ! in_array($order->order->agent_id, $processedAgents) && ! $loop->first)
                                            <tr class="agent-totals">
                                                <td class=""></td>
                                                <td class=""></td>
                                                <td colspan="2" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                                <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ {{number_format($agentTotal, 2, '.', ',')}}</td>
                                            </tr>
                                        @endif

                                        @php
                                            if (! in_array($order->order->agent_id, $processedAgents)) {
                                                array_push($processedAgents, $order->order->agent_id);
                                                $agentTotal = 0;
                                                $previousAgent = $order->order->agent_id;
                                            }

                                            $chargeDetails = $order->order->address;
                                            $lineTotal = number_format($order->total, 2, '.', ',');
                                            if ($invoiceLine->missing_items) {
                                                $chargeDetails = "{$order->order->address}: $invoiceLine->description";
                                                $lineTotal = number_format($invoiceLine->amount, 2, '.', ',');
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{$order->order_number}}</td>
                                            <td><span class=""><u>REPAIR: </u></span> {{$chargeDetails}}</td>
                                            <td>
                                                {{$order->order->agent->user->name ?? ''}}<br>
                                                {{$order->order->agent->user->phone ?? ''}}
                                            </td>
                                            <td>{{$order->date_completed ? $order->date_completed->format('m/d/Y') : $order->updated_at->format('m/d/Y')}}</td>
                                            <td class="text-right">$ {{$lineTotal}}</td>
                                        </tr>
                                        @break
                                    @case($invoiceLine::ORDER_TYPE_REMOVAL)
                                        @php $order =  App\Models\RemovalOrder::find($invoiceLine->order_id); @endphp
                                        @if ($previousAgent != null && ! in_array($order->order->agent_id, $processedAgents) && ! $loop->first)
                                            <tr class="agent-totals">
                                                <td class=""></td>
                                                <td class=""></td>
                                                <td colspan="2" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                                <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ {{number_format($agentTotal, 2, '.', ',')}}</td>
                                            </tr>
                                        @endif

                                        @php
                                            if (! in_array($order->order->agent_id, $processedAgents)) {
                                                array_push($processedAgents, $order->order->agent_id);
                                                $agentTotal = 0;
                                                $previousAgent = $order->order->agent_id;
                                            }

                                            $chargeDetails = $order->order->address;
                                            $lineTotal = number_format($order->total, 2, '.', ',');
                                            if ($invoiceLine->missing_items) {
                                                $chargeDetails = "{$order->order->address}: $invoiceLine->description";
                                                $lineTotal = number_format($invoiceLine->amount, 2, '.', ',');
                                            }
                                            info($chargeDetails)
                                        @endphp
                                        <tr>
                                            <td>{{$order->order_number}}</td>
                                            <td><span class=""><u>REMOVAL: </u></span> {{$chargeDetails}}</td>
                                            <td>
                                                {{$order->order->agent->user->name ?? ''}}<br>
                                                {{$order->order->agent->user->phone ?? ''}}
                                            </td>
                                            <td>{{$order->date_completed ? $order->date_completed->format('m/d/Y') : $order->updated_at->format('m/d/Y')}}</td>
                                            <td class="text-right">$ {{$lineTotal}}</td>
                                        </tr>
                                        @break
                                    @case($invoiceLine::ORDER_TYPE_DELIVERY)
                                        @php $order =  App\Models\DeliveryOrder::find($invoiceLine->order_id); @endphp
                                        @if ($previousAgent != null && ! in_array($order->agent_id, $processedAgents) && ! $loop->first)
                                            <tr class="agent-totals">
                                                <td class=""></td>
                                                <td class=""></td>
                                                <td colspan="2" class="text-right" style="padding-top: 0px; padding-bottom: 40px;">AGENT TOTALS:</td>
                                                <td class="text-right" style="padding-top: 0px; padding-bottom: 40px;">$ {{number_format($agentTotal, 2, '.', ',')}}</td>
                                            </tr>
                                        @endif

                                        @php
                                            if (! in_array($order->agent_id, $processedAgents)) {
                                                array_push($processedAgents, $order->agent_id);
                                                $agentTotal = 0;
                                                $previousAgent = $order->agent_id;
                                            }

                                            $chargeDetails = $order->address;
                                            $lineTotal = number_format($order->total, 2, '.', ',');
                                            if ($invoiceLine->missing_items) {
                                                $chargeDetails = "$order->address: $invoiceLine->description";
                                                $lineTotal = number_format($invoiceLine->amount, 2, '.', ',');
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{$order->order_number}}</td>
                                            <td><span class=""><u>DELIVERY: </u></span> {{$chargeDetails}}</td>
                                            <td>
                                                {{$order->agent->user->name ?? ''}}<br>
                                                {{$order->agent->user->phone ?? ''}}
                                            </td>
                                            <td>{{$order->date_completed ? $order->date_completed->format('m/d/Y') : $order->updated_at->format('m/d/Y')}}</td>
                                            <td class="text-right">$ {{$lineTotal}}</td>
                                        </tr>
                                        @break
                                    @default
                                @endswitch

                                @php
                                    if ($invoiceLine->missing_items) {
                                        $agentTotal += $invoiceLine->amount;
                                    } else {
                                        $agentTotal += $order->total;
                                    }
                                @endphp

                                @if ($loop->last)
                                    <tr class="agent-totals-last">
                                        <td class=""></td>
                                        <td class=""></td>
                                        <td colspan="2" class="text-right" >AGENT TOTALS:</td>
                                        <td class="text-right">$ {{number_format($agentTotal, 2, '.', ',')}}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ADJUSTMENTS DETAILS --}}
        @if ($invoice->adjustments->count())
        <div class="adjustments-details-container">
            <div class="adjustments-details-card">
                <div class="adjustments-details-header">ADJUSTMENTS DETAILS</div>
                <div class="">
                    <table class="adjustments-details-table">
                        <thead>
                          <tr class="">
                            <th scope="col" class="" style="width: 70%">COMMENT</th>
                            <th scope="col" class="" style="text-align: left">DATE</th>
                            <th scope="col" class="" style="text-align: end">CHARGES/CREDITS</th>
                          </tr>
                        </thead>
                        <tbody>

                            @foreach ($invoice->adjustments as $adjustment)
                                <tr>
                                    <td class="">{{$adjustment->description}}</td>
                                    <td style="text-align: end">{{$adjustment->created_at->format('m/d/Y')}}</td>
                                    <td style="text-align: end">$ {{ number_format($adjustment->amount, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- PAYMENT RECEIVED --}}
        <div class="payments-received-container">
            <div class="payments-received-card">
                <div class="payments-received-header">PAYMENT RECEIVED</div>
                <div class="">
                    <table class="payments-received-table">
                        <thead>
                          <tr class="">
                            <th scope="col" class="" style="width: 35%">PAYMENT TYPE</th>
                            <th scope="col" class="" style="width: 60%">COMMENT</th>
                            <th scope="col" class="" style="text-align: left">DATE</th>
                            <th scope="col" class="" style="text-align: end">AMOUNT</th>
                          </tr>
                        </thead>
                        <tbody>
                            @if ($invoice->payments->count())
                                @foreach ($invoice->payments as $payment)
                                    <tr>
                                        @if ($payment->payment_method == 0)
                                            <td class="">CHECK</td>
                                        @elseif($payment->payment_method == 1)
                                            <td class="">CC</td>
                                        @elseif($payment->payment_method == 2)
                                            <td class="">BALANCE</td>
                                        @endif
                                        @if ($invoice->fully_paid)
                                        <td class="">Payment Received</td>
                                        @else
                                        <td class="">Partial Payment Received</td>
                                        @endif
                                        <td style="text-align: end">{{$payment->created_at->format('m/d/Y')}}</td>
                                        <td style="text-align: end">$ {{ number_format($payment->total, 2, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4">No Payment Received</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
