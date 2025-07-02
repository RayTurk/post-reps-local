<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderIncomplete extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    protected $orderType;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, $subject)
    {
        $this->order = $order;
        $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.order_incomplete', ['order' => $this->order])->subject($this->subject);
    }
}
