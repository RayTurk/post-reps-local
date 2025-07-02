<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\RepairOrder;

class RepairOrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    protected $repairOrder;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(RepairOrder $repairOrder)
    {
        $this->repairOrder = $repairOrder;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.repair_order.created', ['repairOrder' => $this->repairOrder])->subject("PostReps Repair");
    }
}
