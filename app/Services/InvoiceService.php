<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\HelperTrait;
use Carbon\Carbon;
use DB;
use App\Models\{
    Agent,
    DeliveryOrder,
    Invoice,
    InvoiceLine,
    InvoicePayments,
    Office,
    Order,
    RemovalOrder,
    RepairOrder,
    ServiceSetting,
    InvoiceAdjustments,
    InvoiceEmailHistory,
    TransactionSummary
};

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Schema;
use Spatie\Browsershot\Browsershot;

class InvoiceService
{
    use HelperTrait;

    protected $model;

    public function __construct(Invoice $model)
    {
        $this->model = $model;
    }

    public function findById($id): Invoice
    {
        return $this->model->findOrFail($id);
    }

    public function findInvoiceByNumber(string $invoiceNumber)
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    public function generateInvoice($invoiceData, $sendEmail = false)
    {
        $total = 0;

        $invoice = Invoice::create([
            'office_id' => $invoiceData['office_id'],
            'agent_id' => $invoiceData['agent_id'] ?? null,
            'invoice_number' => $invoiceData['invoice_number'] ?? $this->generateInvoiceNumber(),
            'due_date' => $invoiceData['due_date'],
            'visible' => $invoiceData['visible'] ?? true,
            'missing_items' => $invoiceData['missing_items'] ?? false,
            'amount' => 0,
            'invoice_type' => $invoiceData['invoice_type'] ?? null,
        ]);

        if (isset($invoiceData['line_items'])) {
            $lineItems = $invoiceData['line_items'];
            foreach($lineItems as $lineItem) {
                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'order_id' => $lineItem['order_id'],
                    'order_type' => $lineItem['order_type'],
                    'visible' => $lineItem['visible'] ?? true,
                    'description' => $lineItem['description'] ?? null,
                    'missing_items' => $lineItem['missing_items'] ?? false,
                    'amount' => $lineItem['amount']
                ]);

                $total = $total + $lineItem['amount'];
            }
        }

        if (isset($invoiceData['adjustments'])) {
            $adjustments = $invoiceData['adjustments'];
            foreach($adjustments as $adjustment) {
                InvoiceAdjustments::create([
                    'invoice_id' => $invoice->id,
                    'description' => $adjustment['description'],
                    'amount' => $adjustment['amount']
                ]);

                $total = $total + $adjustment['amount'];
            }
        }

        $invoice->amount = $total;
        $invoice->save();

        if ($sendEmail) {
            //Send email with pdf
            (new \App\Services\NotificationService())->sendUnpaidInvoiceReminder($invoice->id);
        }

        return $invoice;
    }

    public function createInvoiceLine(array $lineItem)
    {
        DB::transaction(function() use($lineItem) {
            InvoiceLine::create([
                'invoice_id' =>$lineItem['invoice_id'],
                'order_id' => $lineItem['order_id'],
                'order_type' => $lineItem['order_type'],
                'visible' => $lineItem['visible'] ?? true,
                'description' => $lineItem['description'] ?? null,
                'missing_items' => $lineItem['missing_items'] ?? false,
                'amount' => $lineItem['amount']
            ]);

            $invoice = Invoice::find($lineItem['invoice_id']);
            $invoice->amount = $invoice->total();

            if ($invoice->amount == 0) {
                $invoice->fully_paid = true;
            }

            $invoice->save();
        });
    }

    public function createInvoiceAdjustment(array $adjustment)
    {
        DB::transaction(function() use($adjustment) {
            InvoiceAdjustments::create([
                'invoice_id' => $adjustment['invoice_id'],
                'description' => $adjustment['description'],
                'amount' => $adjustment['amount'],
                'type' => $adjustment['type']
            ]);

            $invoice = Invoice::find($adjustment['invoice_id']);
            $invoice->amount = $invoice->total();
            $invoice->save();
        });
    }

    public function createInvoicePayment(array $payment)
    {
        DB::transaction(function() use($payment) {
            $invoicePayment = InvoicePayments::create([
                'invoice_id' => $payment['invoice_id'],
                'payment_method' => $payment['payment_method'],
                'total' => $payment['total'],
                'check_number' => $payment['check_number'] ?? null,
                'comments' => $payment['comments'] ?? null,
                'reversed' => $payment['reversed'] ?? false,
                'card_type' => $payment['card_type'] ?? null,
                'card_last_four' => $payment['card_last_four'] ?? null,
                'transaction_id' => $payment['transaction_id'] ?? null,
                'payment_profile' => $payment['payment_profile'] ?? null
            ]);

            $invoice = Invoice::find($payment['invoice_id']);
            $invoice->amount = $invoice->total();

            if ($invoice->amount == 0) {
                $invoice->fully_paid = true;
            }

            $invoice->save();

            $invoicePayment->invoice_balance = $invoice->amount;
            $invoicePayment->save();
        });
    }

    public function getInvoiceLineByOrder($orderId, $orderType)
    {
        return InvoiceLine::where('order_id', $orderId)
            ->where('order_type', $orderType)
            ->where('missing_items', false)
            ->first();
    }

    public function getInvoiceYears()
    {
        $result = Order::select(DB::raw('YEAR(created_at) as year'))->distinct()->get();
        return $result->pluck('year');
    }

    public function generateInvoiceNumber()
    {
        $monthChar = $this->getMonthCharFromAlphabet((int) now()->month);
        $year = now()->format('y');

        $rnd = mt_rand(1111, 9999);
        $random = array_map('intval', str_split("$rnd"));
        $random[0] = $random[0] == 0 ? 2 : $random[0];
        $random[3] = $random[3] == 0 ? 3 : $random[3];
        $fl =  $this->getMonthCharFromAlphabet((int) $random[0]);
        $sl =  $this->getMonthCharFromAlphabet((int) $random[3]);
        $fd = $random[1];
        $sd = $random[2];

        $invoiceNumber = "{$monthChar}{$year}{$fl}{$fd}{$sd}{$sl}";

        return $invoiceNumber;
    }

    public function countUnpaidInvoices($year)
    {
        return Invoice::where('fully_paid', false)->whereYear('created_at', $year)
            ->where('visible', true)
            ->count();
    }

    public function sumUnpaidInvoices($year)
    {
        return Invoice::where('fully_paid', false)->whereYear('created_at', $year)
        ->where('visible', true)
        ->sum('amount');
    }

    public function countPastDueInvoices($year)
    {
        $today = Carbon::today();

        return Invoice::where('fully_paid', false)
            ->whereDate('due_date', '<', $today)
            ->whereYear('created_at', $year)
            ->where('visible', true)
            ->count();
    }

    public function sumPastDueInvoices($year)
    {
        $today = Carbon::today();

        return Invoice::where('fully_paid', false)
            ->whereDate('due_date', '<', $today)
            ->whereYear('created_at', $year)
            ->where('visible', true)
            ->sum('amount');
    }

    public function datatableUnpaidInvoices()
    {
        $unpaidInvoices = Invoice::query()
            ->join('offices', 'offices.id', 'invoices.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'invoices.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->where('invoices.fully_paid', false)
            ->where('visible', true)
            ->where('amount', '>', '0.00')
            ->latest()
            ->select('invoices.*', 'office.name as office_name', 'agent.name as agent_name')
            ->get()
            ->map(function ($invoice) {
                $office_name = $invoice->office->user->name;
                if ($invoice->agent_id) {
                    $agent_name =$invoice->agent->user->name;
                    $invoice->agent_name = $agent_name;
                }
                $invoice->office_name = $office_name;

                $invoice->history = '';
                foreach ($invoice->invoice_email_histories as $history) {
                    $invoice->history .= "Sent: {$history->created_at->format('m/d/Y g:i A')}\n";
                }

                return $invoice;
            });

        return Datatables::of($unpaidInvoices)->make();
    }

    public function datatableCreateInvoices()
    {
        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('orders.fully_paid as fully_paid', 'orders.status as order_status', 'orders.invoiced', 'orders.created_at as created_at', DB::raw("'install' as order_type"), 'orders.total as order_total', 'orders.id', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'orders.to_be_invoiced');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('repair_orders.fully_paid as fully_paid', 'repair_orders.status as order_status', 'repair_orders.invoiced', 'repair_orders.created_at as created_at', DB::raw("'repair' as order_type"), 'repair_orders.total as order_total', 'repair_orders.id', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'repair_orders.to_be_invoiced');

        $removalOrders = Order::query()
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.fully_paid as fully_paid', 'removal_orders.status as order_status', 'removal_orders.invoiced', 'removal_orders.created_at as created_at', DB::raw("'removal' as order_type"), 'removal_orders.total as order_total', 'removal_orders.id', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'removal_orders.to_be_invoiced');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.fully_paid as fully_paid', 'delivery_orders.status as order_status', 'delivery_orders.invoiced', 'delivery_orders.created_at as created_at', DB::raw("'delivery' as order_type"), 'delivery_orders.total as order_total', 'delivery_orders.id', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'delivery_orders.to_be_invoiced');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('fully_paid', 'order_status', 'created_at', 'order_total', 'id as order_id', 'order_type', 'order_number', 'office_name', 'agent_name', 'to_be_invoiced')
            ->where('invoiced', false)
            ->where('fully_paid', false)
            ->orderByDesc('created_at')
            ->where('order_status', Order::STATUS_COMPLETED)
            ->where('order_total', '>', 0)
            ->where('to_be_invoiced', true);
            // ->where(function ($query) {
            //     $query->whereNotNull('agent_payment_method')
            //     ->where('agent_payment_method', Agent::PAYMENT_METHOD_INVOICE)
            //     ->orWhere(function ($q) {
            //         $q->where('agent_payment_method', Agent::PAYMENT_METHOD_OFFICE_PAY)
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     })
            //     ->orWhere(function ($q) {
            //         $q->whereNull('agent_payment_method')
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     });
            // });
        return Datatables::of($sql)->make();
    }

    public function generateInvoiceForAllAccounts($data, $sendEmail)
    {
        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('orders.desired_date as service_date', 'orders.updated_at as updated_at', 'orders.status as order_status', 'orders.invoiced', 'orders.created_at as created_at', DB::raw("'1' as order_type"), 'orders.total as order_total', 'orders.id', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'orders.to_be_invoiced');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('repair_orders.service_date as service_date', 'repair_orders.updated_at as updated_at', 'repair_orders.status as order_status', 'repair_orders.invoiced', 'repair_orders.created_at as created_at', DB::raw("'2' as order_type"), 'repair_orders.total as order_total', 'repair_orders.id', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'repair_orders.to_be_invoiced');

        $removalOrders = Order::query()
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.service_date as service_date', 'removal_orders.updated_at as updated_at', 'removal_orders.status as order_status', 'removal_orders.invoiced', 'removal_orders.created_at as created_at', DB::raw("'3' as order_type"), 'removal_orders.total as order_total', 'removal_orders.id', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'removal_orders.to_be_invoiced');

        $deliveryOrders = DeliveryOrder::query()
            ->join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.service_date as service_date', 'delivery_orders.updated_at as updated_at', 'delivery_orders.status as order_status', 'delivery_orders.invoiced', 'delivery_orders.created_at as created_at', DB::raw("'4' as order_type"), 'delivery_orders.total as order_total', 'delivery_orders.id', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'delivery_orders.to_be_invoiced');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $from = Carbon::createFromFormat('m/d/Y', $data['from_date'])->format("Y-m-d");
        $to = Carbon::createFromFormat('m/d/Y', $data['to_date'])->format("Y-m-d");

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('service_date', 'agent_payment_method', 'updated_at', 'order_status', 'created_at', 'order_total', 'id as order_id', 'order_type', 'order_number', 'office_name', 'agent_name', 'to_be_invoiced')
            ->where('invoiced', false)
            ->where('order_status', Order::STATUS_COMPLETED)
            // ->where(function ($query) {
            //     $query->whereNotNull('agent_payment_method')
            //     ->where('agent_payment_method', Agent::PAYMENT_METHOD_INVOICE)
            //     ->orWhere(function ($q) {
            //         $q->where('agent_payment_method', Agent::PAYMENT_METHOD_OFFICE_PAY)
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     })
            //     ->orWhere(function ($q) {
            //         $q->whereNull('agent_payment_method')
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     });
            // })
            ->where('to_be_invoiced', true)
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->orderBy('agent_name')
            ->orderBy('service_date')
            ->groupBy(['office_name', 'agent_name', 'service_date', 'updated_at', 'agent_payment_method', 'order_status', 'created_at', 'order_total', 'order_id', 'order_type', 'order_number', 'to_be_invoiced'])
            ->get();
            //dd($orders);

        $invoicedCount = $this->generateInvoiceFromOrders($orders, 'all', $sendEmail);

        return $invoicedCount;
    }

    public function generateInvoiceForOffice($data, $sendEmail)
    {
        $office = Office::query()
            //->where("payment_method", Office::PAYMENT_METHOD_INVOICE)
            ->find($data["create_invoice_office"]);

        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('orders.desired_date as service_date', 'offices.id as office_id', 'orders.updated_at as updated_at', 'orders.status as order_status', 'orders.invoiced', 'orders.created_at as created_at', DB::raw("'1' as order_type"), 'orders.total as order_total', 'orders.id', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'orders.to_be_invoiced');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('repair_orders.service_date as service_date', 'offices.id as office_id', 'repair_orders.updated_at as updated_at', 'repair_orders.status as order_status', 'repair_orders.invoiced', 'repair_orders.created_at as created_at', DB::raw("'2' as order_type"), 'repair_orders.total as order_total', 'repair_orders.id', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'repair_orders.to_be_invoiced');

        $removalOrders = Order::query()
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.service_date as service_date', 'offices.id as office_id', 'removal_orders.updated_at as updated_at', 'removal_orders.status as order_status', 'removal_orders.invoiced', 'removal_orders.created_at as created_at', DB::raw("'3' as order_type"), 'removal_orders.total as order_total', 'removal_orders.id', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'removal_orders.to_be_invoiced');

        $deliveryOrders = DeliveryOrder::query()
            ->join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.service_date as service_date', 'offices.id as office_id', 'delivery_orders.updated_at as updated_at', 'delivery_orders.status as order_status', 'delivery_orders.invoiced', 'delivery_orders.created_at as created_at', DB::raw("'4' as order_type"), 'delivery_orders.total as order_total', 'delivery_orders.id', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'delivery_orders.to_be_invoiced');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $from = Carbon::createFromFormat('m/d/Y', $data['from_date'])->format("Y-m-d");
        $to = Carbon::createFromFormat('m/d/Y', $data['to_date'])->format("Y-m-d");

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('service_date', 'agent_payment_method', 'office_id', 'updated_at', 'order_status', 'created_at', 'order_total', 'id as order_id', 'order_type', 'order_number', 'office_name', 'agent_name', 'to_be_invoiced')
            ->where('office_id', $office->id)
            ->where('invoiced', false)
            ->where('order_status', Order::STATUS_COMPLETED)
            // ->where(function ($query) {
            //     $query->where(function ($q) {
            //         $q->where('agent_payment_method', Agent::PAYMENT_METHOD_OFFICE_PAY)
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     })
            //     ->orWhere(function ($q) {
            //         $q->whereNull('agent_payment_method')
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     });
            // })
            ->where('to_be_invoiced', true)
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->orderBy('agent_name')
            ->orderBy('service_date')
            ->orderByDesc('updated_at')
            ->groupBy(['office_name', 'agent_name', 'service_date', 'agent_payment_method', 'office_id', 'updated_at', 'order_status', 'created_at', 'order_total', 'order_id', 'order_type', 'order_number', 'to_be_invoiced'])
            ->get();

            $invoicedCount = $this->generateInvoiceFromOrders($orders, 'office', $sendEmail);

            return $invoicedCount;
    }

    public function generateInvoiceForAgent($data, $sendEmail)
    {
        $agent = Agent::query()
            //->where("payment_method", Agent::PAYMENT_METHOD_INVOICE)
            ->find($data["create_invoice_agent"]);

        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('orders.desired_date as service_date', 'agents.id as agent_id', 'orders.updated_at as updated_at', 'orders.status as order_status', 'orders.invoiced', 'orders.created_at as created_at', DB::raw("'1' as order_type"), 'orders.total as order_total', 'orders.id', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'orders.to_be_invoiced');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('repair_orders.service_date as service_date', 'agents.id as agent_id', 'repair_orders.updated_at as updated_at', 'repair_orders.status as order_status', 'repair_orders.invoiced', 'repair_orders.created_at as created_at', DB::raw("'2' as order_type"), 'repair_orders.total as order_total', 'repair_orders.id', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'repair_orders.to_be_invoiced');

        $removalOrders = Order::query()
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.service_date as service_date', 'agents.id as agent_id', 'removal_orders.updated_at as updated_at', 'removal_orders.status as order_status', 'removal_orders.invoiced', 'removal_orders.created_at as created_at', DB::raw("'3' as order_type"), 'removal_orders.total as order_total', 'removal_orders.id', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'removal_orders.to_be_invoiced');

        $deliveryOrders = DeliveryOrder::query()
            ->join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.service_date as service_date', 'agents.id as agent_id', 'delivery_orders.updated_at as updated_at', 'delivery_orders.status as order_status', 'delivery_orders.invoiced', 'delivery_orders.created_at as created_at', DB::raw("'4' as order_type"), 'delivery_orders.total as order_total', 'delivery_orders.id', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.payment_method as office_payment_method', 'agents.payment_method as agent_payment_method', 'delivery_orders.to_be_invoiced');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $from = Carbon::createFromFormat('m/d/Y', $data['from_date'])->format("Y-m-d");
        $to = Carbon::createFromFormat('m/d/Y', $data['to_date'])->format("Y-m-d");

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('service_date', 'agent_payment_method', 'agent_id', 'updated_at', 'order_status', 'created_at', 'order_total', 'id as order_id', 'order_type', 'order_number', 'office_name', 'agent_name', 'to_be_invoiced')
            ->where('agent_id', $agent->id)
            ->where('invoiced', false)
            ->where('order_status', Order::STATUS_COMPLETED)
            // ->where(function ($query) {
            //     $query->where('agent_payment_method', Agent::PAYMENT_METHOD_INVOICE)
            //     ->orWhere(function ($q) {
            //         $q->where('agent_payment_method', Agent::PAYMENT_METHOD_OFFICE_PAY)
            //         ->where('office_payment_method', Office::PAYMENT_METHOD_INVOICE);
            //     });
            // })
            ->where('to_be_invoiced', true)
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->orderBy('agent_name')
            ->orderBy('service_date')
            ->groupBy(['office_name', 'agent_name', 'service_date', 'agent_payment_method', 'agent_id', 'updated_at', 'order_status', 'created_at', 'order_total', 'order_id', 'order_type', 'order_number', 'to_be_invoiced'])
            ->get();

            $invoicedCount = $this->generateInvoiceFromOrders($orders, 'agent', $sendEmail);

            return $invoicedCount;
    }

    public function generateInvoiceFromOrders($orders, $accounts, $sendEmail)
    {
        //dd($orders);

        $invoicedCount = 0;

        $defaultDueDate = ServiceSetting::first()->default_invoice_due_date_days;
        $invoiceDueDate = now()->addDays($defaultDueDate);

        $lineIndex = 1;
        foreach ($orders as $order) {
            if ($order->order_total > 0) {
                //If agent being invoiced and pay method is office_pay
                //then don't create invoice until office is invoiced
                if ($accounts == 'agent') {
                    if ($order->agent_payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                        break;
                    }
                }

                switch ($order->order_type) {
                    case InvoiceLine::ORDER_TYPE_INSTALL:
                        $installOrder = Order::find($order->order_id);

                        $officeId = $installOrder->office_id;
                        $agentId = $installOrder->agent_id;
                        if ($accounts == 'office' || $order->agent_payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                            $agentId = null;
                        }

                        //If order exists in invoice_lines table and is not visible
                        $invoiceLine = $this->getInvoiceLineByOrder($order->order_id, $order->order_type);
                        if ($invoiceLine) {
                            $invoiceLine->visible = true;
                            $invoiceLine->save();

                            $invoice = $invoiceLine->invoice;
                            $invoice->visible = true;
                            $invoice->due_date = $invoiceDueDate;
                            $invoice->save();
                        } else {
                            $index = $officeId;
                            /*if ( ! $agentId && $accounts != 'all') {
                                $index = $index + 1;
                            }*/
                            if ($agentId && $order->agent_payment_method == Agent::PAYMENT_METHOD_INVOICE) {
                                $index = $agentId;
                            }

                            $invoiceData[$index]['invoice_number'] = $this->generateInvoiceNumber().'-0';
                            $invoiceData[$index]['office_id'] = $officeId;
                            $invoiceData[$index]['agent_id'] = $agentId;
                            $invoiceData[$index]['due_date'] = $invoiceDueDate;
                            $invoiceData[$index]['visible'] = true;

                            //Line items
                            $invoiceData[$index]['line_items'][$lineIndex]["order_id"] = $order->order_id;
                            $invoiceData[$index]['line_items'][$lineIndex]["order_type"] = $order->order_type;
                            $invoiceData[$index]['line_items'][$lineIndex]['visible'] = true;
                            $invoiceData[$index]['line_items'][$lineIndex]['amount'] = $order->order_total;
                            $lineIndex++;
                        }

                        $installOrder->invoiced = true;
                        $installOrder->save();

                        break;

                    case InvoiceLine::ORDER_TYPE_REPAIR:
                        $repairOrder = RepairOrder::find($order->order_id);

                        $officeId = $repairOrder->order->office_id;
                        $agentId = $repairOrder->order->agent_id;
                        if ($accounts == 'office' || $order->agent_payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                            $agentId = null;
                        }

                        $invoiceLine = $this->getInvoiceLineByOrder($order->order_id, $order->order_type);
                        if ($invoiceLine) {
                            $invoiceLine->visible = true;
                            $invoiceLine->save();

                            $invoice = $invoiceLine->invoice;
                            $invoice->visible = true;
                            $invoice->due_date = $invoiceDueDate;
                            $invoice->save();
                        } else {
                            $index = $officeId;
                            /*if ( ! $agentId && $accounts != 'all') {
                                $index = $index + 1;
                            }*/
                            if ($agentId && $order->agent_payment_method == Agent::PAYMENT_METHOD_INVOICE) {
                                $index = $agentId;
                            }

                            $invoiceData[$index]['invoice_number'] = $this->generateInvoiceNumber().'-0';
                            $invoiceData[$index]['office_id'] = $officeId;
                            $invoiceData[$index]['agent_id'] = $agentId;
                            $invoiceData[$index]['due_date'] = $invoiceDueDate;
                            $invoiceData[$index]['visible'] = true;

                            //Line items
                            $invoiceData[$index]['line_items'][$lineIndex]["order_id"] = $order->order_id;
                            $invoiceData[$index]['line_items'][$lineIndex]["order_type"] = $order->order_type;
                            $invoiceData[$index]['line_items'][$lineIndex]['visible'] = true;
                            $invoiceData[$index]['line_items'][$lineIndex]['amount'] = $order->order_total;
                            $lineIndex++;
                        }

                        $repairOrder->invoiced = true;
                        $repairOrder->save();

                        break;

                    case InvoiceLine::ORDER_TYPE_REMOVAL:
                        $removalOrder = RemovalOrder::find($order->order_id);

                        $officeId = $removalOrder->order->office_id;
                        $agentId = $removalOrder->order->agent_id;
                        if ($accounts == 'office' || $order->agent_payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                            $agentId = null;
                        }

                        $invoiceLine = $this->getInvoiceLineByOrder($order->order_id, $order->order_type);
                        if ($invoiceLine) {
                            $invoiceLine->visible = true;
                            $invoiceLine->save();

                            $invoice = $invoiceLine->invoice;
                            $invoice->visible = true;
                            $invoice->due_date = $invoiceDueDate;
                            $invoice->save();
                        } else {
                            $index = $officeId;
                            /*if ( ! $agentId && $accounts != 'all') {
                                $index = $index + 1;
                            }*/
                            if ($agentId && $order->agent_payment_method == Agent::PAYMENT_METHOD_INVOICE) {
                                $index = $agentId;
                            }

                            $invoiceData[$index]['invoice_number'] = $this->generateInvoiceNumber().'-0';
                            $invoiceData[$index]['office_id'] = $officeId;
                            $invoiceData[$index]['agent_id'] = $agentId;
                            $invoiceData[$index]['due_date'] = $invoiceDueDate;
                            $invoiceData[$index]['visible'] = true;

                            //Line items
                            $invoiceData[$index]['line_items'][$lineIndex]["order_id"] = $order->order_id;
                            $invoiceData[$index]['line_items'][$lineIndex]["order_type"] = $order->order_type;
                            $invoiceData[$index]['line_items'][$lineIndex]['visible'] = true;
                            $invoiceData[$index]['line_items'][$lineIndex]['amount'] = $order->order_total;
                            $lineIndex++;
                        }

                        $removalOrder->invoiced = true;
                        $removalOrder->save();

                        break;

                    case InvoiceLine::ORDER_TYPE_DELIVERY:
                        $deliveryOrder = DeliveryOrder::find($order->order_id);

                        $officeId = $deliveryOrder->office_id;
                        $agentId = $deliveryOrder->agent_id;
                        if ($accounts == 'office' || $order->agent_payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                            $agentId = null;
                        }

                        //If order exists in invoice_lines table and is not visible
                        $invoiceLine = $this->getInvoiceLineByOrder($order->order_id, $order->order_type);
                        if ($invoiceLine) {
                            $invoiceLine->visible = true;
                            $invoiceLine->save();

                            $invoice = $invoiceLine->invoice;
                            $invoice->visible = true;
                            $invoice->due_date = $invoiceDueDate;
                            $invoice->save();
                        } else {
                            $index = $officeId;
                            /*if ( ! $agentId && $accounts != 'all') {
                                $index = $index + 1;
                            }*/
                            if ($agentId && $order->agent_payment_method == Agent::PAYMENT_METHOD_INVOICE) {
                                $index = $agentId;
                            }

                            $invoiceData[$index]['invoice_number'] = $this->generateInvoiceNumber().'-0';
                            $invoiceData[$index]['office_id'] = $officeId;
                            $invoiceData[$index]['agent_id'] = $agentId;
                            $invoiceData[$index]['due_date'] = $invoiceDueDate;
                            $invoiceData[$index]['visible'] = true;

                            //Line items
                            $invoiceData[$index]['line_items'][$lineIndex]["order_id"] = $order->order_id;
                            $invoiceData[$index]['line_items'][$lineIndex]["order_type"] = $order->order_type;
                            $invoiceData[$index]['line_items'][$lineIndex]['visible'] = true;
                            $invoiceData[$index]['line_items'][$lineIndex]['amount'] = $order->order_total;
                            $lineIndex++;
                        }

                        $deliveryOrder->invoiced = true;
                        $deliveryOrder->save();

                        break;

                    default:
                        # code...
                        break;
                }
            }
        }
        //dd($invoiceData);
        if (isset($invoiceData)) {
            foreach($invoiceData as $data) {
                $this->generateInvoice($data, $sendEmail);

                $invoicedCount++;
            }
        }

        //Include hidden invoices for missing items
        $invoicesForMissingItems = $this->getInvoicesForMissingItems();
        if ($invoicesForMissingItems->isNotEmpty()) {
            foreach($invoicesForMissingItems as $invoice) {
                $invoice->invoice_lines()->update(['visible' => true]);
                $invoice->visible = true;
                $invoice->save();

                if ($sendEmail) {
                    //Send email with pdf
                    (new \App\Services\NotificationService())->sendUnpaidInvoiceReminder($invoice->id);
                }

                $invoicedCount++;
            }
        }

        return $invoicedCount;
    }

    public function paymentsDatatable()
    {
        $search = strtolower($_GET['search']['value']);

        try {
            $date = Carbon::createFromDate($search)->toDateString();
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            $date = "";
        }

        $payments = InvoicePayments::query()
            ->with('invoice')
            ->latest();

        if (!empty($search)) {
            $payments = InvoicePayments::query()
            ->with('invoice')
            ->where('check_number', "$search")
            ->orWhere('card_last_four', "$search")
            ->orWhereDate('created_at', 'LIKE', "$date")
            ->orWhereHas("invoice", function ($query) use ($search, $date) {
                return $query->where('invoice_number',  'LIKE', ["%{$search}%"])->orWhereDate('created_at', 'LIKE', "$date");
            })
            ->orWhereHas("invoice.office.user", function ($query) use ($search) {
                return $query->where('name', 'LIKE', ["%{$search}%"]);
            })
            ->orWhereHas("invoice.agent.user", function ($query) use ($search) {
                return $query->where('name', 'LIKE', ["%{$search}%"]);
            })
            ->latest();

            return Datatables::of($payments)->make();
        }

        return Datatables::of($payments)->make();
    }

    public function countInvoiceLateFees($invoiceId)
    {
        return InvoiceAdjustments::where('invoice_id', $invoiceId)
            ->where('type', InvoiceAdjustments::TYPE_LATE_FEE)
            ->count();
    }

    public function invoiceView($id)
    {

        $invoice = Invoice::query()
            ->with('invoice_lines', 'adjustments', 'payments')
            ->where('invoices.id', $id)
            ->join('offices', 'offices.id', 'invoices.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'invoices.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            // ->where('invoices.fully_paid', false)
            //->where('invoices.visible', true)
            ->select(
                'invoices.*',
                'office.name as office_name',
                'agent.name as agent_name',
                'office.address as office_address',
                'agent.address as agent_address',
                'office.city as office_city',
                'agent.city as agent_city',
                'office.state as office_state',
                'agent.state as agent_state',
                'office.zipcode as office_zipcode',
                'agent.zipcode as agent_zipcode',
                'office.phone as office_phone',
                'agent.phone as agent_phone',
            )->first();

        return $invoice;
    }

    public function getInvoicePayer($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if ($invoice->agent_id) {
            $payer = $invoice->agent->user;
            $payer->payment_method = $invoice->agent->payment_method;
        } else {
            $payer = $invoice->office->user;
            $payer->payment_method = $invoice->office->payment_method;
        }

        return $payer;
    }

    public function reverseBalancePayment($paymentId)
    {
        DB::transaction(function() use($paymentId) {
            $invoicePayment = InvoicePayments::find($paymentId);

            $invoice = $invoicePayment->invoice;
            $payer = $this->getInvoicePayer($invoice->id);

            $payer->balance = $payer->balance + $invoicePayment->total;
            $payer->save();

            $invoicePayment->delete();

            $invoice->amount = $invoice->total();
            $invoice->fully_paid = false;
            $invoice->save();
        });
    }

    public function reverseCheckPayment($paymentId)
    {
        DB::transaction(function() use($paymentId) {
            $invoicePayment = InvoicePayments::find($paymentId);

            $invoice = $invoicePayment->invoice;

            $invoicePayment->delete();

            $invoice->amount = $invoice->total();
            $invoice->fully_paid = false;
            $invoice->save();
        });
    }

    public function findInvoicePaymentById($paymentId)
    {
        return InvoicePayments::find($paymentId);;
    }

    public function reverseCardPayment(InvoicePayments $invoicePayment)
    {
        DB::transaction(function() use($invoicePayment) {
            $invoice = $invoicePayment->invoice;

            $invoicePayment->delete();

            $invoice->amount = $invoice->total();
            $invoice->fully_paid = false;
            $invoice->save();
        });
    }

    public function reverseCardPaymentPartial(InvoicePayments $invoicePayment, float $refundAmount)
    {
        DB::transaction(function() use($invoicePayment, $refundAmount) {
            $invoice = $invoicePayment->invoice;

            $refundAmount = abs($refundAmount);

            //Waiting for Bryan to decide what to do here
            /*$invoicePayment->total = $invoicePayment->total - $refundAmount;
            $invoicePayment->save();*/

            //Record refund as adjustment
            $adjustment = [
                'invoice_id' => $invoice->id,
                'description' => 'Refund',
                'amount' => $refundAmount * (-1),
                'type' => InvoiceAdjustments::TYPE_REGULAR
            ];
            $this->createInvoiceAdjustment($adjustment);
        });
    }

    public function findEmailSentHistoryByInvoiceId($id)
    {
        $emailHistory = InvoiceEmailHistory::query()
            ->where('invoice_id', $id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function($history) {
                $history->sent .= "Sent: {$history->created_at->format('m/d/Y g:i A')}\n";

                return $history;
            });

        return $emailHistory;
    }

    public function removeAgentFromInvoice($agentId, $invoiceId)
    {
        $invoice = $this->findById($invoiceId);
        $invoiceLines = $invoice->invoice_lines;

        //Need to delete all invoice lineS with orders belonging to agent
        foreach ($invoiceLines as $invoiceLine) {
            switch ($invoiceLine->order_type) {
                case InvoiceLine::ORDER_TYPE_INSTALL:
                    $installOrder = Order::find($invoiceLine->order_id);
                    if ($installOrder->agent_id == $agentId) {
                        $invoiceLine->delete();
                        $installOrder->invoiced = false;
                        $installOrder->save();
                    }
                    break;
                case InvoiceLine::ORDER_TYPE_REPAIR:
                    $repairOrder = RepairOrder::find($invoiceLine->order_id);
                    if ($repairOrder->order->agent_id == $agentId) {
                        $invoiceLine->delete();
                        $repairOrder->invoiced = false;
                        $repairOrder->save();
                    }
                    break;
                case InvoiceLine::ORDER_TYPE_REMOVAL:
                    $removalOrder = RemovalOrder::find($invoiceLine->order_id);
                    if ($removalOrder->order->agent_id == $agentId) {
                        $invoiceLine->delete();
                        $removalOrder->invoiced = false;
                        $removalOrder->save();
                    }
                    break;
                case InvoiceLine::ORDER_TYPE_DELIVERY:
                    $deliveryOrder = DeliveryOrder::find($invoiceLine->order_id);
                    if ($deliveryOrder->agent_id == $agentId) {
                        $invoiceLine->delete();
                        $deliveryOrder->invoiced = false;
                        $deliveryOrder->save();
                    }
                    break;
            }
        }

        //Refresh model to reset cached relationships so that count() works correctly
        //otherwise even after deleting the invoice line it still counts he cached relationship
        //and doesn't detect that the lines were removed
        $invoice->refresh();

        //Delete invoice if it belongs to agent
        //and there are no more agents in the invoice (no invoice lines)
        if (
            $invoice->agent_id == $agentId
            && ! $invoice->payments->count()
            //&& ! $invoice->adjustments->count()
            && ! $invoice->invoice_lines->count()
        ) {
            Schema::disableForeignKeyConstraints();
            $invoice->adjustments()->delete();
            $invoice->delete();
            Schema::enableForeignKeyConstraints();
        } else {
            //Update invoice balance
            $invoice->amount = $invoice->total();
            $invoice->save();
        }
    }

    public function getRecentTransactionSummary($limit)
    {
        return TransactionSummary::where('type', TransactionSummary::RECENT)
        ->selectRaw('settlement_date, SUM(amount) as total')
        ->orderByDesc('settlement_date')
        ->groupBy('settlement_date')
        ->take($limit)
        ->get();
    }

    public function getFutureTransactionSummary($limit)
    {
        return TransactionSummary::where('type', TransactionSummary::FUTURE)
        ->selectRaw('settlement_date, SUM(amount) as total')
        ->orderByDesc('settlement_date')
        ->groupBy('settlement_date')
        ->take($limit)
        ->get();
    }

    public function getInvoicesForMissingItems()
    {
        return $this->model->where('missing_items', true)
            ->where('visible', false)
            ->get();
    }

    public function incrementInvoiceNumber($invoiceNumber)
    {
        if (empty($invoiceNumber)) {
            $invoiceNumber = $this->generateInvoiceNumber().'-0';

            return $invoiceNumber;
        }

        $sequence = (int) substr($invoiceNumber, -1);
        $baseInvoiceNumber = substr($invoiceNumber, 0, 7);

        //Need to check invoices table and get latest invoice number
        $lastInvoice = $this->model
            ->whereRaw("LEFT(invoice_number, 7) = ?", [$baseInvoiceNumber])
            ->latest()->first();

        if ($lastInvoice) {
            $sequence = (int) substr($lastInvoice->invoice_number, -1);
        }

        $increment = ++$sequence ;

        return "$baseInvoiceNumber-$increment";;
    }

    public function invoiceTypeSingleOrder()
    {
        return Invoice::INVOICE_TYPE_SINGLE_ORDER;
    }

    public function invoicePaymentTypeCard()
    {
        return InvoicePayments::CREDIT_CARD;
    }

    public function latestFee(int $invoiceId)
    {
        return InvoiceAdjustments::where('invoice_id', $invoiceId)
            ->where('type', InvoiceAdjustments::TYPE_LATE_FEE)
            ->latest()
            ->first();
    }

    /****Returns the pdf file path ***/
    public function generatePdf(int $invoiceId): string
    {
        $invoice = $this->invoiceView($invoiceId);

        $data = compact('invoice');
        $fileName = "inv_{$invoice->invoice_number}.pdf";
        $html = view('accounting.invoice_view', $data)->render();

        //Save the PDF in storage folder.
        Browsershot::html($html)
            ->format('A4')
            ->margins(0, 5, 0, 5)
            ->showBackground()
            ->savePdf($fileName);

        return public_path() . '/' . $fileName;
    }

    public function makeInvoiceFullyPaid($invoice)
    {
        $invoice->fully_paid = true;
        $invoice->save();
    }

    public function findLastInvoicePayment(int $invoiceId)
    {
        return InvoicePayments::where('invoice_id', $invoiceId)
            ->latest()
            ->first();
    }

    public function findLastInvoiceLine(int $invoiceId)
    {
        return InvoiceLine::where('invoice_id', $invoiceId)
            ->latest()
            ->first();
    }

    public function updateInvoiceLineAmount(InvoiceLine $invoiceLine, float $amount)
    {
        DB::transaction(function() use($invoiceLine, $amount) {

            $invoiceLine->amount = $amount;
            $invoiceLine->save();

            $invoice = Invoice::find($invoiceLine->invoice_id);
            $invoice->amount = $invoice->total();

            if ($invoice->amount == 0) {
                $invoice->fully_paid = true;
            }

            $invoice->save();
        });
    }
}
