<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActionNeededEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $orderNumber;
    public $address;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $orderNumber, $address)
    {
        $this->orderNumber = $orderNumber;
        $this->subject = $subject;
        $this->address = $address;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        info('Sending action needed email for address ' . $this->address);
        return $this->view('mail.action_needed',
            ['orderNumber' => $this->orderNumber, 'address' => $this->address]
        )->subject($this->subject);
    }
}
