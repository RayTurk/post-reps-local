<table>
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Office / Agent</th>
            <th>Invoice Date</th>
            <th>Date Paid</th>
            <th>Paid</th>
            <th>Payment method</th>
            <th>Check Number</th>
            <th>CC Last Four</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($self->payments as $payment)
            @php
                if ($payment->payment_method == 0) {
                    $payment->payment_method = "CHECK";
                }else if ($payment->payment_method == 1) {
                    $payment->payment_method = "CREDIT CARD";
                }else if ($payment->payment_method == 2) {
                    $payment->payment_method = "BALANCE";
                }                
            @endphp
            <tr>
                <td>{{$payment->invoice->invoice_number}}</td>
                <td>{{$payment->invoice->office->user->name}} {{$payment->invoice->agent ? "- ".$payment->invoice->agent->user->name : ""}}</td>
                <td>{{$payment->invoice->created_at->format('m/d/Y')}}</td>
                <td>{{$payment->created_at->format('m/d/Y')}}</td>
                <td>$ {{$payment->total}}</td>
                <td>{{$payment->payment_method}}</td>
                <td>{{$payment->check_number}}</td>
                <td>{{$payment->card_last_four}}</td>
            </tr>
        @endforeach
    </tbody>
</table>
