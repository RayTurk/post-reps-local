@component('mail::message')

<style>
    p {
        color: #000;
    }
</style>

# Hello,

<p>
    This is a reminder that you have an UNPAID invoice.
    Please see details below.
</p>

<p>
    Invoice Date: <span>{{ $invoice->created_at->format('m/d/Y') }}</span>
</p>

<p>
    Invoice #: <span>{{ $invoice->invoice_number }}</span>
</p>

<p>
    Amount Due: <span>$ {{ $invoice->amount }}</span>
</p>

<p>
    For further information, You can verify the status and invoice details by
    clicking the link below. Please be sure to make your payment on time. 
	Any unpaid balance not received by the due date is subject to additional 
	late fees as listed in our terms and conditions. All terms 
	and conditions are subject to chage without warning. 
</p>

 <p>Regards,</p>
 <p>The PostReps Team</p>
 <p>(208)546-5546</p>

<a href="{{url('/accounting/unpaid/invoices')}}" target="_blank">Click here for invoice details.</a>

@endcomponent
