<?php

namespace App\Http\Controllers;

use App\Exports\AgentsExport;
use App\Exports\PaymentsExport;
use App\Imports\AgentsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportImportController extends Controller
{
    //
    public function currentDate()
    {
        return date('_Y_m_d');
    }
    public function agents()
    {
        $agents_dt = session('agents');
        $agents = $agents_dt->getData()->data;

        return Excel::download(new AgentsExport($agents), "agents{$this->currentDate()}.xlsx");
    }

    public function importAgents(Request $request)
    {
        if ($request->hasFile('file')) {

            Excel::import(new AgentsImport,$request->file('file'));
            return back();
        }
    }

    public function exportPaymentsToCsv($office_id, $agent_id, $fromDate, $toDate)
    {

        if((new PaymentsExport($office_id, $agent_id, $fromDate, $toDate))->payments->isEmpty()) {
            return back()->with('error', "No payment data found for the selected date range.");

        }

        return Excel::download(new PaymentsExport($office_id, $agent_id, $fromDate, $toDate), "payments{$this->currentDate()}.csv", \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportPaymentsToExcel($office_id, $agent_id, $fromDate, $toDate)
    {

        if((new PaymentsExport($office_id, $agent_id, $fromDate, $toDate))->payments->isEmpty()) {
            return back()->with('error', "No payment data found for the selected date range.");
        }

        return Excel::download(new PaymentsExport($office_id, $agent_id, $fromDate, $toDate), "payments{$this->currentDate()}.xlsx", \Maatwebsite\Excel\Excel::XLSX);
    }

    public function paymentsToCsv(Request $request)
    {
        $office_id = $request->export_to_csv_office;
        $agent_id = $request->export_to_csv_agent ? $request->export_to_csv_agent : "";
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        return $this->exportPaymentsToCsv($office_id, $agent_id, $fromDate, $toDate);
    }

    public function paymentsToExcel(Request $request)
    {
        $office_id = $request->export_to_csv_office ? $request->export_to_csv_office : "";
        $agent_id = $request->export_to_csv_agent ? $request->export_to_csv_agent : "";
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        return $this->exportPaymentsToExcel($office_id, $agent_id, $fromDate, $toDate);        
    }

    public function paymentsToCsvOffice(Request $request)
    {
        $office_id = auth()->user()->office->id;
        $agent_id = "";
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        return $this->exportPaymentsToCsv($office_id, $agent_id, $fromDate, $toDate);
    }

    public function paymentsToExcelOffice(Request $request)
    {
        $office_id = auth()->user()->office->id;
        $agent_id = "";
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        return $this->exportPaymentsToExcel($office_id, $agent_id, $fromDate, $toDate);
    }

    public function paymentsToCsvAgent(Request $request)
    {
        $office_id = "";
        $agent_id = auth()->user()->agent->id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        return $this->exportPaymentsToCsv($office_id, $agent_id, $fromDate, $toDate);
    }

    public function paymentsToExcelAgent(Request $request)
    {
        $office_id = "";
        $agent_id = auth()->user()->agent->id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        return $this->exportPaymentsToExcel($office_id, $agent_id, $fromDate, $toDate);
    }
}
