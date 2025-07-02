<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Models\{ServiceSetting, User};

class AccountingTransactionSummaryController extends Controller
{
    protected $invoiceService;

    public function __construct(
        InvoiceService $invoiceService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService
    ) {
        $this->invoiceService = $invoiceService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
    }


    public function index($limit = 10)
    {
        $offices = $this->officeService->getAll();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();

        $serviceSettings = $service_settings = ServiceSetting::first();

        $recentTransactions = $this->invoiceService->getRecentTransactionSummary($limit);
        $futureTransactions = $this->invoiceService->getFutureTransactionSummary($limit);

        $data = compact('futureTransactions', 'recentTransactions', 'offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

        return view('accounting.transaction_summary.index', $data);
    }
}
