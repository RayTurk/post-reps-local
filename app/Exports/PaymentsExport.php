<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\InvoicePayments;
use App\Http\Traits\HelperTrait;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PaymentsExport implements FromView
{

    use HelperTrait;
    public $payments;

    public function __construct($office_id = "", $agent_id = "", $fromDate, $toDate)
    {
        $from = Carbon::createFromFormat('m/d/Y', $fromDate)->format("Y-m-d");
        $to = Carbon::createFromFormat('m/d/Y', $toDate)->format("Y-m-d");
        $this->payments = InvoicePayments::query()
        ->with(['invoice.office.user', 'invoice.agent.user',])
        ->orderByDesc('created_at')
        ->when($office_id, function ($query) use ($office_id) {
            $query->whereHas('invoice', function ($query) use ($office_id) {
                $query->where('office_id', $office_id);
            });
        })
        ->when($agent_id, function ($query) use ($agent_id) {
            $query->whereHas('invoice', function ($query) use ($agent_id) {
                $query->where('agent_id', $agent_id);
            });
        })
        ->whereBetween('updated_at', [$from, $to])
        ->get();

    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        return view("exports.payments", ['self' => $this ]);
    }
}