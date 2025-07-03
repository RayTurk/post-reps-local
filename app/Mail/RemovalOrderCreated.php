<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\RemovalOrder;

class RemovalOrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    protected $removalOrder;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(RemovalOrder $removalOrder)
    {
        $this->removalOrder = $removalOrder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.removal_order.created', ['removalOrder' => $this->removalOrder])->subject("PostReps Removal");
    }
}
