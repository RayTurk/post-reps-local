<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnpaidInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $invoice;
    public $attachment;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, string $firstName, string $attachment)
    {
        $this->invoice = $invoice;
        $this->firstName = $firstName;
        $this->attachment = $attachment;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('mail.accounting.unpaid_invoices.email')
            ->subject("PostReps Invoice notification")
            ->attach($this->attachment);
    }
}
