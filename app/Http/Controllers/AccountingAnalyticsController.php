<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Http\Traits\HelperTrait;
use App\Models\ServiceSetting;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\AuthorizeNetService;

class AccountingAnalyticsController extends Controller
{
    protected $invoiceService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $authorizeNetService;
    protected $paymentService;

    public function __construct(
        InvoiceService $invoiceService,
        NotificationService $notificationService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService,
        AuthorizeNetService $authorizeNetService,
        PaymentService $paymentService
    ) {
        $this->invoiceService = $invoiceService;
        $this->notificationService = $notificationService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->authorizeNetService = $authorizeNetService;
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $yearSelected = $request->year_selected;
        //dd($yearSelected);
        $offices = $this->officeService->getAll();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();

        $serviceSettings = $service_settings = ServiceSetting::first();

        $invoiceYears = $this->invoiceService->getInvoiceYears();
        $countUnpaidInvoices = $this->invoiceService->countUnpaidInvoices($yearSelected);
        $sumUnpaidInvoices = $this->invoiceService->sumUnpaidInvoices($yearSelected);
        $countPastDueInvoices = $this->invoiceService->countPastDueInvoices($yearSelected);
        $sumPastDueInvoices = $this->invoiceService->sumPastDueInvoices($yearSelected);
        $countPaymentsCurrentMonth = $this->paymentService->countPaymentsCurrentMonth($yearSelected);
        $sumPaymentsCurrentMonth = $this->paymentService->sumPaymentsCurrentMonth($yearSelected);
        $countPaymentsYtd = $this->paymentService->countPaymentsYtd($yearSelected);
        $sumPaymentsYtd = $this->paymentService->sumPaymentsYtd($yearSelected);
        $chartData = $this->paymentService->getMonthlyPayments($yearSelected);

        $data = compact('chartData', 'sumPaymentsYtd', 'countPaymentsYtd', 'sumPaymentsCurrentMonth', 'countPaymentsCurrentMonth', 'sumPastDueInvoices', 'countPastDueInvoices', 'yearSelected', 'invoiceYears', 'sumUnpaidInvoices', 'countUnpaidInvoices', 'offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

        return view('accounting.analytics.index', $data);
    }
}
