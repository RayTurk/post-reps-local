<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\DeliveryOrder;

class DeliveryOrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    protected $deliveryOrder;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(DeliveryOrder $deliveryOrder)
    {
        $this->deliveryOrder = $deliveryOrder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.delivery_order.created', ['deliveryOrder' => $this->deliveryOrder])->subject("PostReps Delivery");
    }
}
