<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\InvoiceService;
use Spatie\Browsershot\Browsershot;

class InvoiceViewController extends Controller
{

    use HelperTrait;

    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        //
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

    public function generatePDF($id)
    {
        $invoice = $this->invoiceService->invoiceView($id);

        $data = compact('invoice');
        $fileName = "inv_{$invoice->invoice_number}.pdf";

        // Creating the PDF into the storage folder.
        Browsershot::html(view('accounting.invoice_view', $data))
            ->format('A4')
            ->margins(0, 5, 0, 5)
            ->showBackground()
            ->savePdf($fileName);

        // Downloading the file from the storage folding to Downloads folder and after that delete it from the storage.
        return response()->download(public_path() . '/' . $fileName)->deleteFileAfterSend(true);
    }
}
