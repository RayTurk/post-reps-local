<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInvoice;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\InvoiceService;
use App\Services\OfficeService;
use App\Models\ServiceSetting;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;

class AccountingCreateInvoicesController extends Controller
{
    use HelperTrait;

    protected $invoiceService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;

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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $offices = $this->officeService->getAll();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();

        $serviceSettings = $service_settings = ServiceSetting::first();

        $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

        return view('accounting.create_invoices.index', $data);
    }

    public function datatable()
    {
        return $this->invoiceService->datatableCreateInvoices();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateInvoice $request)
    {
        //dd($request->all());

        $sendEmail = false;
        if (isset($request->send_invoice_email)) {
            $sendEmail = true;
        }

        if ( ! $request->process_all_accounts ) {

            //There is an office in the request but not an agent so the invoice will be created for the office only within the date range
            if (isset($request->create_invoice_office) && ! isset($request->create_invoice_agent)) {
                if ($request->create_invoice_office) {
                    //try {
                        $invoicedCount = $this->invoiceService->generateInvoiceForOffice($request->validated(), $sendEmail);
                        return $this->backWithSuccess($invoicedCount . " invoices were generated successfully.");
                    //} catch (\Exception $ex) {
                        //logger()->error($ex->getMessage().' line:: ' . $ex->getLine());
                        return $this->backWithError($this->serverErrorMessage());
                    //}
                }
            }

            // There is an agent in the request so the invoice will be created for the agent only within the date range
            if (isset($request->create_invoice_office) && isset($request->create_invoice_agent)) {
                if ($request->create_invoice_agent) {
                    //try {
                        $invoicedCount = $this->invoiceService->generateInvoiceForAgent($request->validated(), $sendEmail);
                        return $this->backWithSuccess($invoicedCount . " invoices were generated successfully.");
                    //} catch (\Exception $ex) {
                        //logger()->error($ex->getMessage().' line:: ' . $ex->getLine());
                        return $this->backWithError($this->serverErrorMessage());
                    //}
                }
            }
        }

        // The process all account option is selected so the invoices will be created for all the accounts orders
        //try {
            $invoicedCount = $this->invoiceService->generateInvoiceForAllAccounts($request->validated(), $sendEmail);
            return $this->backWithSuccess($invoicedCount . " invoices were generated successfully.");
        //} catch (\Exception $ex) {
            //logger()->error($ex->getMessage().' line:: ' . $ex->getLine());
            return $this->backWithError($this->serverErrorMessage());
        //}
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
