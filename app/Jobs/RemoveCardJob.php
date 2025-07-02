<?php

namespace App\Jobs;

use App\Services\AuthorizeNetService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $authorizeNetService;
    protected $customerPaymentProfileId;
    protected $authorizenetProfileId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($customerPaymentProfileId, $authorizenetProfileId)
    {
        $this->authorizeNetService = new AuthorizeNetService();
        $this->customerPaymentProfileId = $customerPaymentProfileId;
        $this->authorizenetProfileId = $authorizenetProfileId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = auth()->user();
        $authorizenetProfileId = $user->authorizenet_profile_id ?? $this->authorizenetProfileId;
        $this->authorizeNetService->removeCard($authorizenetProfileId, $this->customerPaymentProfileId);
    }
}
