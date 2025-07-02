<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Models\{Order, OrderAccessory, OrderAttachment, Zone, RepairOrder, RemovalOrder, DeliveryOrder};
use App\Models\{
    RepairOrderAccessory,
    RepairOrderAttachment,
    RepairOrderPayment,
    RemovalOrderPayment,
    Office,
    Agent,
    OrderAdjustment,
    RepairOrderAdjustment,
    RemovalOrderAdjustment,
    Panel,
    PanelAgent,
    DeliveryOrderPanel,
    DeliveryOrderPayment,
    DeliveryOrderAdjustment,
    Payment,
    User,
    Post,
    Accessory,
    AuthorizenetPaymentProfile,
    ZonePoints
};
// use Yajra\Datatables\Datatables;
use Yajra\DataTables\Facades\DataTables;
use DB;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\OrderEmails;
use Illuminate\Support\Facades\Http;

class OrderService
{
    use HelperTrait;

    protected $model;
    protected $orderAccessory;
    protected $orderAttachment;
    protected $notificationService;
    protected $deliveryOrder;

    public function __construct(
        Order $model,
        OrderAccessory $orderAccessory,
        OrderAttachment $orderAttachment,
        NotificationService $notificationService,
        DeliveryOrder $deliveryOrder
    ) {
        $this->model = $model;
        $this->orderAccessory = $orderAccessory;
        $this->orderAttachment = $orderAttachment;
        $this->notificationService = $notificationService;
        $this->deliveryOrder = $deliveryOrder;
    }

    public function create(array $attributes)
    {
        $attributes['order_number'] = $this->temp_id();
        $order =  $this->model->create($attributes);

        do {
            $order_number = $this->generateOrderNumber('I');
            $exists = $order->where('order_number', $order_number)->exists();
        } while ($exists) ;

        $order->order_number = $order_number;
        $order->save();
        return $order;
    }

    public function generateOrderNumber($orderType)
    {
        $yearLatter = Order::getYearLatter(now()->format("Y"));
        $monthChar = $this->getMonthCharFromAlphabet((int) now()->month);
        $order_number = $orderType . $yearLatter . $monthChar . mt_rand(1000, 9999);
        return $order_number;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function getAllOrderBy(string $orderBy, string $sortOrder = 'asc'): EloquentCollection
    {
        $getAllOrderBy = $this->model;

        if ($orderBy) {
            $getAllOrderBy = $getAllOrderBy->orderBy($orderBy, $sortOrder);
        }

        $getAllOrderBy = $getAllOrderBy->get();

        return $getAllOrderBy;
    }

    public function findById(int $id): Order
    {
        return $this->model->findOrFail($id);
    }

    public function dataTable()
    {
        $model = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->orderBy('updated_at', "DESC")
            ->select('orders.*', 'office.name as office_name', 'agent.name as agent_name');
        $search = strtolower($_GET['search']['value']);
        $status = ["received", "action needed", "scheduled", "installed"];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return Datatables::eloquent($model)->filter(function ($query) use ($search) {
                        switch ($search) {
                            case "received":
                                $query->where('orders.status', Order::STATUS_RECEIVED);
                                break;
                            case "action needed":
                                $query->where('orders.status', Order::STATUS_INCOMPLETE);
                                break;
                            case "scheduled":
                                $query->where('orders.status', Order::STATUS_SCHEDULED);
                                break;
                            case "installed":
                                $query->where('orders.status', Order::STATUS_COMPLETED);
                                break;
                        }
                        return $query;
                    })->toJson();
                }
            }
        }
        return $data = Datatables::eloquent($model)->toJson();
    }

    public function deleteAll()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $this->orderAccessory->truncate();
        $this->orderAttachment->truncate();
        $this->model->truncate();
        OrderAdjustment::truncate();
        Payment::truncate();

        RepairOrder::truncate();
        RepairOrderPayment::truncate();
        RepairOrderAccessory::truncate();
        RepairOrderAdjustment::truncate();
        RepairOrderAttachment::truncate();

        RemovalOrder::truncate();
        RemovalOrderPayment::truncate();
        RemovalOrderAdjustment::truncate();

        DeliveryOrder::truncate();
        DeliveryOrderPayment::truncate();
        DeliveryOrderPanel::truncate();
        DeliveryOrderAdjustment::truncate();

        \App\Models\Invoice::truncate();
        \App\Models\InvoicePayments::truncate();
        \App\Models\InvoiceAdjustments::truncate();
        \App\Models\InvoiceLine::truncate();

        //Reset users authorize.net profile ID
        DB::table('users')->update(['authorizenet_profile_id' => null]);

        DB::transaction( function() {
            //Return all IN FIELD items back to storage
            DB::statement('update panels set quantity = quantity + quantity_in_field where quantity_in_field > 0');
            DB::statement('update posts set quantity = quantity + quantity_in_field where quantity_in_field > 0');
            DB::statement('update accessories set quantity = quantity + quantity_in_field where quantity_in_field > 0');

            //Set IN FIELD to 0 for all items
            DB::table('posts')->where('quantity_in_field', '>', 0)->update(['quantity_in_field' => 0]);
            DB::table('panels')->where('quantity_in_field', '>', 0)->update(['quantity_in_field' => 0]);
            DB::table('accessories')->where('quantity_in_field', '>', 0)->update(['quantity_in_field' => 0]);
        });

        //Clear transaction summary table
        DB::table('transaction_summaries')->truncate();

        //Delete all Auhtorize.net payment profiles
        AuthorizenetPaymentProfile::truncate();

        //Delete all installer payments
        DB::table('installer_payments')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return true;
    }

    public function repairOrdersDatatable()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->whereDoesntHave('latest_repair', function (Builder $q) {
                $q->where('status', RepairOrder::STATUS_INCOMPLETE)
                ->orWhere('status', RepairOrder::STATUS_SCHEDULED)
                ->orWhere('status', RepairOrder::STATUS_RECEIVED);
            })
            ->whereDoesntHave('latest_removal', function (Builder $q) {
                $q->where('status', RemovalOrder::STATUS_INCOMPLETE)
                ->orWhere('status', RemovalOrder::STATUS_SCHEDULED)
                ->orWhere('status', RemovalOrder::STATUS_RECEIVED)
                ->orWhere('status', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->select('orders.status as status', DB::raw("NULL as repair_status"), 'orders.assigned_to as assigned_to', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id', 'agent.id as agent_user_id');

        $repairOrders = $this->model
            ->join('repair_orders', function ($join) {
                $join->on('orders.id', 'repair_orders.order_id')
                ->where('repair_orders.status', '<>', RepairOrder::STATUS_COMPLETED)
                ->where('repair_orders.status', '<>', RepairOrder::STATUS_CANCELLED);
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->select('repair_orders.status as status', 'repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id', 'agent.id as agent_user_id');

        //info($repairOrders->toSql()); die;

        $union = $installOrders->unionAll($repairOrders);
        //info($union->toSql()); die;

        $sql = DB::table(DB::raw("({$union->toSql()}) as sub"))
            ->mergeBindings($union->getQuery())
            ->select('repair_status', 'office_user_id', 'agent_user_id', 'assigned_to', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'order_number', 'office_name', 'agent_name');
        //info($query->toSql()); die;

        $role = auth()->user()->role;
        if ($role == User::ROLE_OFFICE) {
            $sql = $sql->whereraw('office_user_id = '. auth()->id());
        }
        if ($role == User::ROLE_AGENT) {
            $sql = $sql->whereraw('agent_user_id = '. auth()->id());
        }

        $search = strtolower($_GET['search']['value']);
        $status = ["received", "action needed", "scheduled", "installed", 'repaired', 'cancelled'];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return Datatables::of($sql)
                        ->filter(function ($query) use ($search) {
                            switch ($search) {
                                case "received":
                                    $query->whereRaw("status =". Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->whereRaw("status =". Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->whereRaw("status =". Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->whereRaw("status =". Order::STATUS_COMPLETED)
                                        ->whereRaw('order_type = install');
                                    break;
                            }

                            return $query;
                        })
                        ->orderColumn('orders.address', false)
                        ->order(function ($query) {
                            $query->orderBy('updated_at', 'desc');
                        })
                        ->make(true);
                }
            }

            return Datatables::of($sql)
                ->filter(function ($query) use ($search) {
                    $query->whereRaw("address like ?", ["%{$search}%"])
                        ->orWhereRaw("office_name like ?", ["%{$search}%"])
                        ->orWhereRaw("agent_name like ?", ["%{$search}%"])
                        ->orWhereRaw("desired_date like ?", ["%{$search}%"])
                        ->orWhereRaw("order_type like ?", ["%{$search}%"])
                        ->orWhereRaw("order_number like ?", ["%{$search}%"]);
                })
                ->orderColumn('orders.address', false)
                ->order(function ($query) {
                    $query->orderBy('updated_at', 'desc');
                })
                ->toJson();
        }

        return Datatables::of($sql)
            ->orderColumn('orders.address', false)
            ->order(function ($q) {
                $q->orderBy('updated_at', 'desc');
            })
            ->toJson();
    }

    public function removalOrdersDatatable()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->whereDoesntHave('latest_repair', function (Builder $q) {
                $q->where('status', RepairOrder::STATUS_INCOMPLETE)
                ->orWhere('status', RepairOrder::STATUS_SCHEDULED)
                ->orWhere('status', RepairOrder::STATUS_RECEIVED);
            })
            ->whereDoesntHave('latest_removal', function (Builder $q) {
                $q->where('status', RemovalOrder::STATUS_INCOMPLETE)
                ->orWhere('status', RemovalOrder::STATUS_SCHEDULED)
                ->orWhere('status', RemovalOrder::STATUS_RECEIVED)
                ->orWhere('status', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->select('orders.status as status', DB::raw("NULL as removal_status"), 'orders.assigned_to as assigned_to', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id', 'agent.id as agent_user_id');

        $removalOrders = $this->model
            ->join('removal_orders', function ($join) {
                $join->on('orders.id', 'removal_orders.order_id')
                ->where('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED)
                ->where('removal_orders.status', '<>', RemovalOrder::STATUS_CANCELLED);
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->select('removal_orders.status as status', 'removal_orders.status as removal_status', 'removal_orders.assigned_to as assigned_to', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id', 'agent.id as agent_user_id');

        //info($removalOrders->toSql()); die;

        $union = $installOrders->unionAll($removalOrders);
        //info($union->toSql()); die;

        $sql = DB::table(DB::raw("({$union->toSql()}) as sub"))
            ->mergeBindings($union->getQuery())
            ->select('removal_status', 'office_user_id', 'agent_user_id', 'assigned_to', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'order_number', 'office_name', 'agent_name');
        //info($query->toSql()); die;

        $role = auth()->user()->role;
        if ($role == User::ROLE_OFFICE) {
            $sql = $sql->whereraw('office_user_id = '. auth()->id());
        }
        if ($role == User::ROLE_AGENT) {
            $sql = $sql->whereraw('agent_user_id = '. auth()->id());
        }

        $search = strtolower($_GET['search']['value']);
        $status = ["received", "action needed", "scheduled", "installed", 'repaired', 'cancelled'];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return Datatables::of($sql)
                        ->filter(function ($query) use ($search) {
                            switch ($search) {
                                case "received":
                                    $query->whereRaw("status =". Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->whereRaw("status =". Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->whereRaw("status =". Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->whereRaw("status =". Order::STATUS_COMPLETED)
                                        ->whereRaw('order_type = install');
                                    break;
                            }

                            return $query;
                        })
                        ->orderColumn('orders.address', false)
                        ->order(function ($query) {
                            $query->orderBy('updated_at', 'desc');
                        })
                        ->make(true);
                }
            }

            return Datatables::of($sql)
                ->filter(function ($query) use ($search) {
                    $query->whereRaw("address like ?", ["%{$search}%"])
                        ->orWhereRaw("office_name like ?", ["%{$search}%"])
                        ->orWhereRaw("agent_name like ?", ["%{$search}%"])
                        ->orWhereRaw("desired_date like ?", ["%{$search}%"])
                        ->orWhereRaw("order_type like ?", ["%{$search}%"])
                        ->orWhereRaw("order_number like ?", ["%{$search}%"]);
                })
                ->orderColumn('orders.address', false)
                ->order(function ($query) {
                    $query->orderBy('updated_at', 'desc');
                })
                ->toJson();
        }

        return Datatables::of($sql)
            ->orderColumn('orders.address', false)
            ->order(function ($q) {
                $q->orderBy('updated_at', 'desc');
            })
            ->toJson();
    }

    public function deliveryOrdersDataTable()
    {
        $data = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->orderBy('updated_at', "DESC")
            ->where('status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->select('delivery_orders.*', 'office.name as office_name', 'agent.name as agent_name');
        $search = strtolower($_GET['search']['value']);
        $status = ["received", "incomplete", "scheduled"];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return Datatables::eloquent($data)->filter(function ($query) use ($search) {
                        switch ($search) {
                            case "received":
                                $query->where('delivery_orders.status', DeliveryOrder::STATUS_RECEIVED);
                                break;
                            case "incomplete":
                                $query->where('delivery_orders.status', DeliveryOrder::STATUS_INCOMPLETE);
                                break;
                            case "scheduled":
                                $query->where('delivery_orders.status', DeliveryOrder::STATUS_SCHEDULED);
                                break;
                        }
                        return $query;
                    })->toJson();
                }
            }
        }
        return Datatables::eloquent($data)->toJson();
    }

    public function datatableOrderStatus()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('orders.assigned_to as assigned_to', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('repair_orders.assigned_to as assigned_to', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.assigned_to as assigned_to', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.assigned_to as assigned_to', DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('updated_at');

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->distinct()
            ->select('assigned_to', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name');

        $search = strtolower($_GET['search']['value']);

        $status = ["received", "action needed", "scheduled", "installed", 'removed', 'repaired', 'delivered', 'cancelled'];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return $search;
                    return Datatables::of($sql)
                        ->filter(function ($query) use ($search) {
                            switch ($search) {
                                case "received":
                                    $query->where('status', Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->where('status', Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->where('status', Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->where('status', Order::STATUS_COMPLETED);
                                    break;
                                case "repaired":
                                    $query->where('status', Order::STATUS_COMPLETED);
                                    break;
                                case "removed":
                                    $query->where('status', Order::STATUS_COMPLETED);
                                    break;
                                case "delivered":
                                    $query->where('status', Order::STATUS_COMPLETED);
                                    break;
                                case "cancelled":
                                    $query->where('status', Order::STATUS_CANCELLED);
                                    break;
                            }
                            return $query;
                        })
                        ->orderColumn('orders.address', false)
                        ->make(true);
                }
            }
        }

        return Datatables::of($sql)->make(true);
    }

    public function datatableOrderStatusActive()
    {
        $removals = DB::raw("(select * from removal_orders where id in (select max(id) from removal_orders group by order_id)) removals");

        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->select('orders.action_needed as action_needed', DB::raw("'none' as repair_status"), 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->select('repair_orders.action_needed as action_needed', 'repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removals.action_needed as action_needed', DB::raw("'none' as repair_status"), 'removals.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'removal' as order_type"), 'removals.updated_at as updated_at', 'removals.id', 'orders.address', 'removals.service_date_type as desired_date_type', 'removals.service_date as desired_date', 'removals.status', 'removals.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.action_needed as action_needed', DB::raw("'none' as repair_status"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('action_needed');

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->distinct()
            ->select('action_needed', 'repair_status', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name');

        $search = strtolower($_GET['search']['value']);
        $status = ["received", "action needed", "scheduled", "installed", 'repaired', 'cancelled'];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return Datatables::of($sql)
                        ->filter(function ($query) use ($search) {
                            $query->where(function($q) {
                                $q->whereNull('removal_status')
                                ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED)
                                ->orWhere('repair_status', RepairOrder::STATUS_SCHEDULED);
                            })
                            ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
                            ->where('status', '<>', Order::STATUS_CANCELLED);

                            switch ($search) {
                                case "received":
                                    $query->where('status', Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->where('status', Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->where('status', Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->where('status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'install');
                                    break;
                                case "repaired":
                                    $query->where('status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'repair');
                                    break;
                            }

                            return $query;
                        })
                        ->orderColumn('orders.address', false)
                        ->order(function ($query) {
                            $query->orderByDesc('action_needed')
                                ->orderBy('updated_at', 'desc');
                        })
                        ->make(true);
                }
            }

            return Datatables::of($sql)
            ->filter(function ($query) use ($search) {
                $query->where(function($q) {
                    $q->whereNull('removal_status')
                    ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED)
                    ->orWhere('repair_status', RepairOrder::STATUS_SCHEDULED);
                })
                ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
                ->where('status', '<>', Order::STATUS_CANCELLED)
                ->where(function ($q) use ($search) {
                    $q->where('address', 'like', "%{$search}%")
                    ->orWhere('office_name', 'like', "%{$search}%")
                    ->orWhere('agent_name', 'like', "%{$search}%")
                    ->orWhere('desired_date', 'like', "%{$search}%")
                    ->orWhere('order_type', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
                });

                return $query;
            })
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderByDesc('action_needed')
                    ->orderBy('updated_at', 'desc');
            })
            ->make(true);
        }

        return Datatables::of($sql)
            ->filter(function ($query) {
                $query->where(function($q) {
                    $q->whereNull('removal_status')
                    ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED)
                    ->orWhere('repair_status', RepairOrder::STATUS_SCHEDULED);
                })
                ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
                ->where('status', '<>', Order::STATUS_CANCELLED);

                return $query;
            })
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderByDesc('action_needed')
                    ->orderBy('updated_at', 'desc');
            })
            ->make(true);
    }

    public function datatableOrderStatusHistory()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('updated_at');
            //return $union->toSql();

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name')
            ->where('status', '<>', Order::STATUS_SCHEDULED)
            ->where('status', '<>', Order::STATUS_RECEIVED)
            ->where('status', '<>', Order::STATUS_INCOMPLETE);

        $search = strtolower($_GET['search']['value']);
        $status = ["received", "action needed", "scheduled", "installed", 'removed', 'repaired', 'delivered', 'cancelled'];
        if (!empty($search)) {
            foreach ($status as $s) {
                if (str_starts_with($s, $search)) {
                    $search = $s;
                    return Datatables::of($sql)
                        ->filter(function ($query) use ($search) {
                            switch ($search) {
                                case "received":
                                    $query->where('status', Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->where('status', Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->where('status', Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->where('status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'install');
                                    break;
                                case "repaired":
                                    $query->where('status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'repair');
                                    break;
                                case "removed":
                                    $query->where('order_type', 'removal');
                                    break;
                                case "delivered":
                                    $query->where('order_type', 'delivery');
                                    break;
                            }

                            $query->where(function($q) {
                                $q->where('removal_status', RemovalOrder::STATUS_COMPLETED)
                                ->orWhere('delivery_status', DeliveryOrder::STATUS_COMPLETED)
                                ->orWhere('status', Order::STATUS_CANCELLED);
                            });

                            return $query;
                        })
                        ->orderColumn('orders.address', false)
                        ->order(function ($query) {
                            $query->orderBy('updated_at', 'desc');
                        })
                        ->make(true);
                }
            }

            return Datatables::of($sql)
            ->filter(function ($query) use ($search) {
                $query->where(function($q) {
                    $q->where('removal_status', RemovalOrder::STATUS_COMPLETED)
                    ->orWhere('delivery_status', DeliveryOrder::STATUS_COMPLETED)
                    ->orWhere('status', Order::STATUS_CANCELLED);
                })
                ->where(function ($q) use ($search) {
                    $q->where('address', 'like', "%{$search}%")
                    ->orWhere('office_name', 'like', "%{$search}%")
                    ->orWhere('agent_name', 'like', "%{$search}%")
                    ->orWhere('desired_date', 'like', "%{$search}%")
                    ->orWhere('order_type', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
                });

                return $query;
            })
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderBy('updated_at', 'desc');
            })
            ->make(true);
        }

        return Datatables::of($sql)
            ->filter(function ($query) {
                $query->where('removal_status', RemovalOrder::STATUS_COMPLETED)
                ->orWhere('delivery_status', DeliveryOrder::STATUS_COMPLETED)
                ->orWhere('status', Order::STATUS_CANCELLED);

                return $query;
            })
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderBy('updated_at', 'desc');
            })
            ->make(true);
    }

    public function createRepairOrder(array $attributes)
    {
        $attributes['order_number'] = $this->temp_id();
        $repairOrder =  RepairOrder::create($attributes);

        $exists = false;
        do {
            $order_number = $this->generateOrderNumber('S');
            $exists = $repairOrder->where('order_number', $order_number)->exists();
        } while ($exists);

        $repairOrder->order_number = $order_number;
        $repairOrder->save();

        return $repairOrder;
    }

    public function createRemovalOrder(array $attributes)
    {
        $attributes['order_number'] = $this->temp_id();
        $removalOrder =  RemovalOrder::create($attributes);

        $order_number = $this->generateOrderNumber('R');

        $exists = false;
        do {
            $order_number = $this->generateOrderNumber('R');
            $exists = $removalOrder->where('order_number', $order_number)->exists();
        } while ($exists);

        $removalOrder->order_number = $order_number;
        $removalOrder->save();

        return $removalOrder;
    }

    public function createDeliveryOrder(array $attributes)
    {
        $attributes['order_number'] = $this->temp_id();
        $deliveryOrder =  DeliveryOrder::create($attributes);

        $order_number = $this->generateOrderNumber('D');

        $exists = false;
        do {
            $order_number = $this->generateOrderNumber('D');
            $exists = $deliveryOrder->where('order_number', $order_number)->exists();
        } while ($exists);

        $deliveryOrder->order_number = $order_number;
        $deliveryOrder->save();

        return $deliveryOrder;
    }

    public function deleteAllRepairOrders()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        RepairOrderAccessory::truncate();
        RepairOrderAttachment::truncate();
        RepairOrder::truncate();
        RepairOrderPayment::truncate();
        RepairOrderAdjustment::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return true;
    }

    public function deleteAllRemovalOrders()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        RemovalOrder::truncate();
        RemovalOrderPayment::truncate();
        RemovalOrderAdjustment::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return true;
    }

    public function deleteAllDeliveryOrders()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        DeliveryOrder::truncate();
        DeliveryOrderPayment::truncate();
        DeliveryOrderAdjustment::truncate();
        DeliveryOrderPanel::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return true;
    }

    public function deleteAllOrderStatus()
    {
        $this->deleteAll();
        $this->deleteAllRepairOrders();
        $this->deleteAllRemovalOrders();
        $this->deleteAllDeliveryOrders();
    }

    public function findRepairOrderById($repairOrderId)
    {
        return RepairOrder::find($repairOrderId);
    }

    public function findRemovalOrderById($removalOrderId)
    {
        return RemovalOrder::find($removalOrderId);
    }

    public function findDeliveryOrderById($deliveryOrderId)
    {
        return DeliveryOrder::find($deliveryOrderId);
    }

    public function checkOrderSameAddress(
        string $address,
        string $lat,
        string $lng,
        Office $office,
        int $agentId,
        int $orderId
    ) {
        $query = $this->model->where('status', '<>', Order::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('office_id', $office->id);
            if ($agentId > 0) {
                $query = $query->where('agent_id', $agentId);
            }
            if ($orderId > 0) {
                $query = $query->where('id', '<>', $orderId);
            }
            $query = $query->where(function($q) use ($address, $lat, $lng) {
                $q->where('address', $address)
                ->orWhere( function($q2) use ($lat, $lng) {
                    $q2->where('latitude', $lat)
                    ->where('longitude', $lng);
                });
            })
            ->exists();

        return $query;
    }

    public function countPostsAtProperty(
        string $address,
        string $lat,
        string $lng,
        Office $office,
        Agent $agent
    ) {
        $query = $this->model->where('status', Order::STATUS_COMPLETED)
            ->where('office_id', $office->id);
            if ($agent) {
                $query = $query->where('agent_id', $agent->id);
            }
            $query = $query->where(function($q) use ($address, $lat, $lng) {
                $q->where('address', $address)
                ->orWhere( function($q2) use ($lat, $lng) {
                    $q2->where('latitude', $lat)
                    ->where('longitude', $lng);
                });
            })
            ->whereDoesntHave('removal', function (Builder $q) {
                $q->where('status', RemovalOrder::STATUS_COMPLETED)
                ->orWhere('status', RemovalOrder::STATUS_RECEIVED)
                ->orWhere('status', RemovalOrder::STATUS_SCHEDULED)
                ->orWhere('status', RemovalOrder::STATUS_INCOMPLETE);
            })
            ->where(function($q2) {
                $q2->whereHas('repair', function (Builder $q3) {
                    $q3->where('status', RepairOrder::STATUS_COMPLETED);
                })
                ->orWhereDoesntHave('repair');
            })
            ->count();

        return $query;
    }

    public function updateOrderSameAddress(Order $order)
    {
        $address = $order->address;
        $lat = $order->latitude;
        $lng = $order->longitude;

        $orders = $this->model->where('status', '<>', Order::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('office_id', $order->office->id);
        if ($order->agent) {
            $orders = $orders->where('agent_id', $order->agent->id);
        }
        $orders = $orders->where(function($q) use ($address, $lat, $lng) {
            $q->where('address', $address)
            ->orWhere( function($q2) use ($lat, $lng) {
                $q2->where('latitude', $lat)
                ->where('longitude', $lng);
            });
        })
        ->get();

        foreach ($orders as $duplicate) {
            $duplicate->desired_date_type = $order->desired_date_type;
            $duplicate->desired_date = $order->desired_date;
            $duplicate->save();
        }
    }

    public function getOthersOrdersSameProperty(Order $order)
    {
        $address = $order->address;
        $lat = $order->latitude;
        $lng = $order->longitude;

        $query = $this->model->where('status', Order::STATUS_COMPLETED)
            ->with('post')
            ->where('id', '<>', $order->id)
            ->where('office_id', $order->office_id);
            if ($order->agent_id) {
                $query = $query->where('agent_id', $order->agent_id);
            }
            $query = $query->where(function($q) use ($address, $lat, $lng) {
                $q->where('address', $address)
                ->orWhere( function($q2) use ($lat, $lng) {
                    $q2->where('latitude', $lat)
                    ->where('longitude', $lng);
                });
            })
            ->whereDoesntHave('removal', function (Builder $q) {
                $q->whereIn('status', [
                    RemovalOrder::STATUS_COMPLETED,
                    RemovalOrder::STATUS_SCHEDULED,
                    RemovalOrder::STATUS_RECEIVED
                ]);
            })
            ->where(function($q2) {
                $q2->whereHas('repair', function (Builder $q3) {
                    $q3->where('status', RepairOrder::STATUS_COMPLETED);
                })
                ->orWhereDoesntHave('repair');
            })
            ->get();

        return $query;
    }

    public function getOthersRemovalOrdersSameProperty(RemovalOrder $removalOrder)
    {
        return RemovalOrder::where('parent_removal_order', $removalOrder->id)
            ->get();
    }

    public function updateDates($removalOrder)
    {
        RemovalOrder::where('parent_removal_order', $removalOrder->id)
            ->orWhere('id', $removalOrder->parent_removal_order)
            ->update([
                'service_date' => $removalOrder->service_date,
                'service_date_type' => $removalOrder->service_date_type
            ]);
    }

    public function massInsertInstallAdjustments(array $data)
    {
        OrderAdjustment::insert($data);
    }

    public function deleteInstallAdjustments(int $orderId)
    {
        OrderAdjustment::where('order_id', $orderId)->delete();
    }

    public function massInsertRepairAdjustments(array $data)
    {
        RepairOrderAdjustment::insert($data);
    }

    public function deleteRepairAdjustments(int $orderId)
    {
        RepairOrderAdjustment::where('repair_order_id', $orderId)->delete();
    }

    public function massInsertRemovalAdjustments(array $data)
    {
        RemovalOrderAdjustment::insert($data);
    }

    public function deleteRemovalAdjustments(int $orderId)
    {
        RemovalOrderAdjustment::where('removal_order_id', $orderId)->delete();
    }

    public function massInsertDeliveryAdjustments(array $data)
    {
        DeliveryOrderAdjustment::insert($data);
    }

    public function deleteDeliveryAdjustments(int $orderId)
    {
        DeliveryOrderAdjustment::where('delivery_order_id', $orderId)->delete();
    }

    public function massInsertDeliveryPanels(array $data)
    {
        DeliveryOrderPanel::insert($data);
    }

    public function deleteDeliveryPanels(int $orderId)
    {
        DeliveryOrderPanel::where('delivery_order_id', $orderId)->delete();
    }

    public function addNewDeliverySign($request, $deliveryOrder, $countNewSigns)
    {
        //Delete previous entries
        DeliveryOrderPanel::where('delivery_order_id', $deliveryOrder->id)
            ->where('existing_new', DeliveryOrderPanel::NEW_PANEL)
            ->delete();

        for ($i=1; $i <= $countNewSigns; $i++) {
            $officeId = $request->office_id;
            $agentId = $request->agent_id;

            $office = Office::find($officeId);
            $panelName = $office->name_abbreviation ? $office->name_abbreviation : $office->user->name;
            $panelName .= ' - ';
            $agent = Agent::find($agentId);
            if ($agent) {
                $panelName .= $agent->user->name;
            }
            $date = now()->format('m/d/y');
            $panelName .= " {$date} - {$i}";

            $panel = Panel::create([
                "panel_name" => $panelName,
                "quantity" => 0,
                "price" => 0,
                "free_storage" => 0,
                "cost_per_unit" => 0,
                "frequency" => 0,
                "width" => 0,
                "height" => 0,
                "office_id" => $officeId,
                "status" => Panel::STATUS_INACTIVE,
                'image_path' => 'no_panel_image.png'
            ]);

            if ($agent) {
                PanelAgent::create([
                    'panel_id' => $panel->id,
                    'agent_id' => $agentId,
                    'is_primary' => true,
                ]);
            }

            DeliveryOrderPanel::create([
                'delivery_order_id' => $deliveryOrder->id,
                'panel_id' => $panel->id,
                'quantity' => 1,
                'pickup_delivery' => DeliveryOrderPanel::PICKUP,
                'existing_new' => DeliveryOrderPanel::NEW_PANEL
            ]);
        }
    }

    public function getDeliveryZone($zoneId)
    {
        return Zone::find($zoneId);
    }

    public function getOrdersNotAssigned()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select('orders.created_at as created_at', 'orders.latitude', 'orders.longitude', 'posts.post_name as post_name', 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select('repair_orders.created_at as created_at', 'orders.latitude', 'orders.longitude', 'posts.post_name as post_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.created_at as created_at', 'orders.latitude', 'orders.longitude', 'posts.post_name as description', 'removal_orders.assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            //->leftJoin('delivery_order_panels', 'delivery_orders.id', 'delivery_order_panels.delivery_order_id')
            ->select('delivery_orders.created_at as created_at', 'delivery_orders.latitude', 'delivery_orders.longitude',  DB::raw("0 as post_name"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('updated_at');
            //return $union->toSql();

        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay(1)->format('Y-m-d');

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('created_at', 'latitude', 'longitude', 'post_name', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name')
            ->where(function($q) {
                $q->whereNull('removal_status')
                ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_COMPLETED)
            ->where(function($q) use ($today, $tomorrow) {
                $q->where('desired_date_type', 1)
                    ->orWhereDate('desired_date', '<=' , $tomorrow);
            })
            ->whereNull('assigned_to')
            ->get();

        return $orders;
    }

    public function getAllRouteOrders(string $routeDate)
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'orders.stop_number as stop_number', 'orders.created_at as created_at', 'orders.latitude', 'orders.longitude', 'posts.post_name as post_name', 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'repair_orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'repair_orders.stop_number as stop_number', 'repair_orders.created_at as created_at', 'orders.latitude', 'orders.longitude', 'posts.post_name as post_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'removal_orders.assigned_to', 'installers.id')
            ->select('removal_orders.pickup_address as pickup_address', 'removal_orders.pickup_latitude as pickup_latitude', 'removal_orders.pickup_longitude as pickup_longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'removal_orders.stop_number as stop_number', 'removal_orders.created_at as created_at', 'orders.latitude', 'orders.longitude', 'posts.post_name as description', 'removal_orders.assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'delivery_orders.assigned_to', 'installers.id')
            //->leftJoin('delivery_order_panels', 'delivery_orders.id', 'delivery_order_panels.delivery_order_id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'delivery_orders.stop_number as stop_number', 'delivery_orders.created_at as created_at', 'delivery_orders.latitude', 'delivery_orders.longitude',  DB::raw("0 as post_name"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);
            //return $union->toSql();

        $today = now()->format('Y-m-d');
        //$tomorrow = now()->addDay(1)->format('Y-m-d');

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('pickup_address', 'pickup_latitude', 'pickup_longitude', 'installer_id', 'installer_name', 'routing_color', 'stop_number', 'created_at', 'latitude', 'longitude', 'post_name', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name')
            ->distinct()
            ->where(function($q) {
                $q->whereNull('removal_status')
                ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_INCOMPLETE)
            ->where(function($q) use ($routeDate, $today) {
                /*$q->where('desired_date_type', 1)
                    ->orWhereDate('desired_date', '=' , $routeDate);*/
                $q->where('desired_date_type', 1)
                    ->whereNull('desired_date')
                    ->orWhere(function($q2) use ($routeDate) {
                        $q2->where('desired_date_type', 2)
                        ->whereDate('desired_date', '=' , $routeDate);
                    })
                    ->orWhere(function($q2) use ($routeDate) {
                        $q2->where('desired_date_type', 1)
                        ->whereDate('desired_date', '=' , $routeDate);
                    })
                    ->orWhere(function($q2) use ($today) {
                        $q2->where('desired_date_type', 2)
                        ->whereDate('desired_date', '<' , $today);
                    });
            })
            ->orderBy('stop_number')
            ->get();

        return $orders;
    }

    public function getInstallerOrders(int $installerId, string $routeDate)
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'orders.stop_number as stop_number', 'posts.post_name as post_name', 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'repair_orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'repair_orders.stop_number as stop_number','posts.post_name as post_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as installers', 'removal_orders.assigned_to', 'installers.id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.pickup_address as pickup_address', 'removal_orders.pickup_latitude as pickup_latitude', 'removal_orders.pickup_longitude as pickup_longitude', 'orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'removal_orders.stop_number as stop_number', 'posts.post_name as description', 'removal_orders.assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'delivery_orders.assigned_to', 'installers.id')
            //->leftJoin('delivery_order_panels', 'delivery_orders.id', 'delivery_order_panels.delivery_order_id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'delivery_orders.latitude', 'delivery_orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'delivery_orders.stop_number as stop_number',  DB::raw("0 as post_name"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderBy('stop_number');
            //return $union->toSql();

        $today = now()->format('Y-m-d');
        //$tomorrow = now()->addDay(1)->format('Y-m-d');

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('pickup_address', 'pickup_latitude', 'pickup_longitude', 'latitude', 'longitude', 'installer_id', 'installer_name', 'routing_color', 'stop_number', 'post_name', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name')
            ->distinct()
            ->where(function($q) {
                $q->whereNull('removal_status')
                ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_INCOMPLETE)
            ->where(function($q) use ($routeDate, $today) {
                /*$q->where('desired_date_type', 1)
                    ->orWhereDate('desired_date', '=' , $routeDate);*/
                $q->where('desired_date_type', 1)
                ->whereNull('desired_date')
                ->orWhere(function($q2) use ($routeDate) {
                    $q2->where('desired_date_type', 2)
                    ->whereDate('desired_date', '=' , $routeDate);
                })
                ->orWhere(function($q2) use ($routeDate) {
                    $q2->where('desired_date_type', 1)
                    ->whereDate('desired_date', '=' , $routeDate);
                })
                ->orWhere(function($q2) use ($today) {
                    $q2->where('desired_date_type', 2)
                    ->whereDate('desired_date', '<' , $today);
                });
            })
            ->where(function($q) use ($installerId) {
                $q->where('assigned_to', $installerId)
                ->orWhereNull('assigned_to');
            })
            ->get();

        return $orders;
    }

    public function getAssignedInstallerOrders($installerId, $routeDate)
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'orders.stop_number as stop_number', 'posts.post_name as post_name', 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'repair_orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'repair_orders.stop_number as stop_number','posts.post_name as post_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as installers', 'removal_orders.assigned_to', 'installers.id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removal_orders.pickup_address as pickup_address', 'removal_orders.pickup_latitude as pickup_latitude', 'removal_orders.pickup_longitude as pickup_longitude', 'orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'removal_orders.stop_number as stop_number', 'posts.post_name as description', 'removal_orders.assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'delivery_orders.assigned_to', 'installers.id')
            //->leftJoin('delivery_order_panels', 'delivery_orders.id', 'delivery_order_panels.delivery_order_id')
            ->select(DB::raw("null as pickup_address"), DB::raw("null as pickup_latitude"), DB::raw("null as pickup_longitude"), 'delivery_orders.latitude', 'delivery_orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'delivery_orders.stop_number as stop_number',  DB::raw("0 as post_name"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);
           // return $union->toSql();

        /*$today = now()->format('Y-m-d');
        $tomorrow = now()->addDay(1)->format('Y-m-d');*/

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('pickup_address', 'pickup_latitude', 'pickup_longitude', 'latitude', 'longitude', 'installer_name', 'routing_color', 'stop_number', 'post_name', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name')
            ->distinct()
            ->where(function($q) {
                $q->whereNull('removal_status')
                ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('status', Order::STATUS_SCHEDULED)
            ->where('status', '<>', Order::STATUS_COMPLETED)
            ->where(function($q) use ($routeDate) {
                /*$q->where('desired_date_type', 1)
                    ->orWhereDate('desired_date', '=' , $routeDate);*/
                $q->where('desired_date_type', 1)
                ->whereNull('desired_date')
                ->orWhere(function($q2) use ($routeDate) {
                    $q2->where('desired_date_type', 2)
                    ->whereDate('desired_date', '=' , $routeDate);
                })
                ->orWhere(function($q2) use ($routeDate) {
                    $q2->where('desired_date_type', 1)
                    ->whereDate('desired_date', '=' , $routeDate);
                });
            })
            ->where('assigned_to', $installerId)
            ->orderBy('stop_number')
            ->get();

        return $orders;
    }

    public function assignOrder($request)
    {
        $installerId = $request->installerId;
        $orderType = $request->orderType;
        $orderId = $request->orderId;
        $routeDate = $request->route_date;

        //Need to get next stop number
        $nextStopNumber = 1;

        $orders = $this->getAssignedInstallerOrders($installerId, $routeDate);
        if (count($orders) > 0) {
            foreach ($orders as $order) {
                $nextStopNumber = $order->stop_number  + 1;
            }
        }

        //Assign and update status
        if ($orderType == 'install') {
            Order::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => Order::STATUS_SCHEDULED,
                'stop_number' => $nextStopNumber,
                'desired_date' => $routeDate
            ]);

            $order = Order::find($orderId);
            //OrderEmails::dispatch($order, $orderType);
        }

        if ($orderType == 'repair') {
            $order = RepairOrder::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => RepairOrder::STATUS_SCHEDULED,
                'stop_number' => $nextStopNumber,
                'service_date' => $routeDate
            ]);

            $order = RepairOrder::find($orderId);
            $order->office = $order->order->office;
            $order->agent = $order->order->agent;
            //OrderEmails::dispatch($order, $orderType);

        }
        if ($orderType == 'removal') {
            $order = RemovalOrder::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => RemovalOrder::STATUS_SCHEDULED,
                'stop_number' => $nextStopNumber,
                'service_date' => $routeDate
            ]);

            $order = RemovalOrder::find($orderId);
            $order->office = $order->order->office;
            $order->agent = $order->order->agent;
            //OrderEmails::dispatch($order, $orderType);
        }
        if ($orderType == 'delivery') {
            $order = DeliveryOrder::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => DeliveryOrder::STATUS_SCHEDULED,
                'stop_number' => $nextStopNumber,
                'service_date' => $routeDate
            ]);

            $order = DeliveryOrder::find($orderId);
            //OrderEmails::dispatch($order, $orderType);
        }

        return $order;
    }

    public function updateAssignedOrder($request)
    {
        $installerId = $request->installerId;
        $orderType = $request->orderType;
        $orderId = $request->orderId;
        $stopNumber = $request->stopNumber;
        $newStopNumber = $request->stopNumber;
        $oldInstallerId = 0;
        $routeDate = $request->route_date;

        //Assign and update status
        if ($orderType == 'install') {
            $installOrder = Order::find($orderId);
            $oldStopNumber = $installOrder->stop_number;

            //Need to update stop numbers for previous installer
            //if a different installer was selected
            if ($installOrder->assigned_to != $installerId ) {
                $oldInstallerId = $installOrder->assigned_to;

                //Need to check if the new installer has any order assigned
                $orders = $this->getAssignedInstallerOrders($installerId, $routeDate);
                if (count($orders) > 0) {
                    //Increment stop number including the stop being replaced
                    $this->increaseStopNumberInclusive($installerId, $stopNumber, $routeDate);
                } else { //If installer has no order assigned
                    //It will be the first stop
                    $stopNumber = 1;
                }
            } else {
                //If stop number changed then reorder
                if ($oldStopNumber != $newStopNumber) {
                    $this->reorderStopNumber($installerId, $oldStopNumber, $newStopNumber, $routeDate);
                }
            }

            Order::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => Order::STATUS_SCHEDULED,
                'stop_number' => $stopNumber
            ]);
        }

        if ($orderType == 'repair') {
            //Need to get next stop number if a different installer was selected
            $repairOrder = RepairOrder::find($orderId);
            $oldStopNumber = $repairOrder->stop_number;

            if ($repairOrder->assigned_to != $installerId ) {
                $oldInstallerId = $repairOrder->assigned_to;

                //Need to check if the new installer has any order assigned
                $orders = $this->getAssignedInstallerOrders($installerId, $routeDate);
                if (count($orders) > 0) {
                    //Increment stop number including the stop being replaced
                    $this->increaseStopNumberInclusive($installerId, $stopNumber, $routeDate);
                } else { //If installer has no order assigned
                    //It will be the first stop
                    $stopNumber = 1;
                }
            } else {
                //If stop number changed then reorder
                if ($oldStopNumber != $newStopNumber) {
                    $this->reorderStopNumber($installerId, $oldStopNumber, $newStopNumber, $routeDate);
                }
            }

            RepairOrder::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => RepairOrder::STATUS_SCHEDULED,
                'stop_number' => $stopNumber
            ]);
        }

        if ($orderType == 'removal') {
            //Need to get next stop number if a different installer was selected
            $removalOrder = RemovalOrder::find($orderId);
            $oldStopNumber = $removalOrder->stop_number;

            if ($removalOrder->assigned_to != $installerId ) {
                $oldInstallerId = $removalOrder->assigned_to;

                //Need to check if the new installer has any order assigned
                $orders = $this->getAssignedInstallerOrders($installerId, $routeDate);
                if (count($orders) > 0) {
                    //Increment stop number including the stop being replaced
                    $this->increaseStopNumberInclusive($installerId, $stopNumber, $routeDate);
                } else { //If installer has no order assigned
                    //It will be the first stop
                    $stopNumber = 1;
                }
            } else {
                //If stop number changed then reorder
                if ($oldStopNumber != $newStopNumber) {
                    $this->reorderStopNumber($installerId, $oldStopNumber, $newStopNumber, $routeDate);
                }
            }

            RemovalOrder::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => RemovalOrder::STATUS_SCHEDULED,
                'stop_number' => $stopNumber
            ]);
        }

        if ($orderType == 'delivery') {
            //Need to get next stop number if a different installer was selected
            $deliveryOrder = DeliveryOrder::find($orderId);
            $oldStopNumber = $deliveryOrder->stop_number;

            if ($deliveryOrder->assigned_to != $installerId ) {
                $oldInstallerId = $deliveryOrder->assigned_to;

                //Need to check if the new installer has any order assigned
                $orders = $this->getAssignedInstallerOrders($installerId, $routeDate);
                if (count($orders) > 0) {
                    //Increment stop number including the stop being replaced
                    $this->increaseStopNumberInclusive($installerId, $stopNumber, $routeDate);
                } else { //If installer has no order assigned
                    //It will be the first stop
                    $stopNumber = 1;
                }
            } else {
                //If stop number changed then reorder
                if ($oldStopNumber != $newStopNumber) {
                    $this->reorderStopNumber($installerId, $oldStopNumber, $newStopNumber, $routeDate);
                }
            }

            DeliveryOrder::where('id', $orderId)->update([
                'assigned_to' => $installerId,
                'status' => DeliveryOrder::STATUS_SCHEDULED,
                'stop_number' => $stopNumber
            ]);
        }

        //Reorder stop number for previous installer
        if ($oldInstallerId > 0) {
            $this->decreaseStopNumber($oldInstallerId, $oldStopNumber, $routeDate);
        }

        return $stopNumber;
    }

    public function unassignOrder($request)
    {
        $orderType = $request->orderType;
        $orderId = $request->orderId;

        //Assign and update status
        if ($orderType == 'install') {
            //Need to decrease stop numbers
            $order = Order::find($orderId);
            $routeDate = $request->route_date ?? $order->desired_date;

            $this->decreaseStopNumber($order->assigned_to, $order->stop_number, $routeDate);

            DB::table('orders')->where('id', $orderId)->update([
                'assigned_to' => null,
                'status' => Order::STATUS_RECEIVED,
                'stop_number' => 1,
                'desired_date' => $order->desired_date_type == 1 ? null : $order->desired_date
            ]);
        }
        if ($orderType == 'repair') {
            $order = RepairOrder::find($orderId);
            $routeDate = $request->route_date ?? $order->service_date;

            $this->decreaseStopNumber($order->assigned_to, $order->stop_number, $routeDate);

            DB::table('repair_orders')->where('id', $orderId)->update([
                'assigned_to' => null,
                'status' => Order::STATUS_RECEIVED,
                'stop_number' => 1,
                'service_date' => $order->service_date_type == 1 ? null : $order->service_date
            ]);
        }
        if ($orderType == 'removal') {
            $order = RemovalOrder::find($orderId);
            $routeDate = $request->route_date ?? $order->service_date;

            $this->decreaseStopNumber($order->assigned_to, $order->stop_number, $routeDate);

            DB::table('removal_orders')->where('id', $orderId)->update([
                'assigned_to' => null,
                'status' => Order::STATUS_RECEIVED,
                'stop_number' => 1,
                'service_date' => $order->service_date_type == 1 ? null : $order->service_date
            ]);
        }
        if ($orderType == 'delivery') {
            $order = DeliveryOrder::find($orderId);
            $routeDate = $request->route_date ?? $order->service_date;

            $this->decreaseStopNumber($order->assigned_to, $order->stop_number, $routeDate);

            DB::table('delivery_orders')->where('id', $orderId)->update([
                'assigned_to' => null,
                'status' => Order::STATUS_RECEIVED,
                'stop_number' => 1,
                'service_date' => $order->service_date_type == 1 ? null : $order->service_date
            ]);
        }

        return true;
    }

    public function getInstallerOrderByStopNumber($installerId, $stopNumber)
    {
        $order = Order::where('assigned_to', $installerId)->where('stop_number', $stopNumber)->first();
        if ($order) {
            return $order;
        }

        $order = RepairOrder::where('assigned_to', $installerId)->where('stop_number', $stopNumber)->first();
        if ($order) {
            return $order;
        }

        $order = RemovalOrder::where('assigned_to', $installerId)->where('stop_number', $stopNumber)->first();
        if ($order) {
            return $order;
        }

        $order = DeliveryOrder::where('assigned_to', $installerId)->where('stop_number', $stopNumber)->first();
        if ($order) {
            return $order;
        }
    }

    public function reorderStopNumber($installerId, $oldStopNumber, $newStopNumber, $routeDate)
    {
        //If new stop is less than current stop then increase by one all the
        //other stops > current stop
        if ($newStopNumber < $oldStopNumber) {
            $this->reorderStopIncrease($installerId, $oldStopNumber, $newStopNumber, $routeDate);
        }

        //If new stop is greater than current stop then decrease by one all the
        //other stops < current stop
        if ($newStopNumber > $oldStopNumber) {
            $this->reorderStopDecrease($installerId, $oldStopNumber, $newStopNumber, $routeDate);
        }
    }

    public function decreaseStopNumber($installerId, $stopNumber, $routeDate): void
    {
        DB::table('orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', $stopNumber)
            ->where('desired_date', '=', $routeDate)
            ->decrement('stop_number');

        DB::table('repair_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', $stopNumber)
            ->where('service_date', '=', $routeDate)
            ->decrement('stop_number');

        DB::table('removal_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', $stopNumber)
            ->where('service_date', '=', $routeDate)
            ->decrement('stop_number');

        DB::table('delivery_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', $stopNumber)
            ->where('service_date', '=', $routeDate)
            ->decrement('stop_number');
    }

    public function increaseStopNumberInclusive($installerId, $stopNumber, $routeDate): void
    {
        DB::table('orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $stopNumber)
            ->where('desired_date', '=', $routeDate)
            ->increment('stop_number');

        DB::table('repair_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $stopNumber)
            ->where('service_date', '=', $routeDate)
            ->increment('stop_number');

        DB::table('removal_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $stopNumber)
            ->where('service_date', '=', $routeDate)
            ->increment('stop_number');

        DB::table('delivery_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $stopNumber)
            ->where('service_date', '=', $routeDate)
            ->increment('stop_number');
    }

    public function reorderStopIncrease($installerId, $oldStopNumber, $newStopNumber, $routeDate)
    {
        //Use DB::table so timestamps are not touched
        DB::table('orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $newStopNumber)
            ->where('stop_number', '<=', $oldStopNumber)
            ->where('desired_date', '=', $routeDate)
            ->increment('stop_number');

        DB::table('repair_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $newStopNumber)
            ->where('stop_number', '<=', $oldStopNumber)
            ->where('service_date', '=', $routeDate)
            ->increment('stop_number');

        DB::table('removal_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $newStopNumber)
            ->where('stop_number', '<=', $oldStopNumber)
            ->where('service_date', '=', $routeDate)
            ->increment('stop_number');

        DB::table('delivery_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>=', $newStopNumber)
            ->where('stop_number', '<=', $oldStopNumber)
            ->where('service_date', '=', $routeDate)
            ->increment('stop_number');
    }

    public function reorderStopDecrease($installerId, $oldStopNumber, $newStopNumber, $routeDate)
    {
        DB::table('orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', 1)
            ->where('stop_number', '>', $oldStopNumber)
            ->where('stop_number', '<=', $newStopNumber)
            ->where('desired_date', '=', $routeDate)
            ->decrement('stop_number');

        DB::table('repair_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', 1)
            ->where('stop_number', '>', $oldStopNumber)
            ->where('stop_number', '<=', $newStopNumber)
            ->where('service_date', '=', $routeDate)
            ->decrement('stop_number');

        DB::table('removal_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', 1)
            ->where('stop_number', '>', $oldStopNumber)
            ->where('stop_number', '<=', $newStopNumber)
            ->where('service_date', '=', $routeDate)
            ->decrement('stop_number');

        DB::table('delivery_orders')->where('assigned_to', $installerId)
            ->where('stop_number', '>', 1)
            ->where('stop_number', '>', $oldStopNumber)
            ->where('stop_number', '<=', $newStopNumber)
            ->where('service_date', '=', $routeDate)
            ->decrement('stop_number');
    }

    public function removeStops($installerId)
    {
        if ($installerId == 0) {
            $this->removeAllStops();
        } else {
            $this->removeInstallerStops($installerId);
        }

        return true;
    }

    public function removeAllStops()
    {
        DB::table('orders')->where('status', Order::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => Order::STATUS_RECEIVED
        ]);

        DB::table('repair_orders')->where('status', RepairOrder::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => RepairOrder::STATUS_RECEIVED
        ]);

        DB::table('removal_orders')->where('status', RemovalOrder::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => RemovalOrder::STATUS_RECEIVED
        ]);

        DB::table('delivery_orders')->where('status', DeliveryOrder::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => DeliveryOrder::STATUS_RECEIVED
        ]);
    }

    public function removeInstallerStops($installerId)
    {
        DB::table('orders')->where('assigned_to', $installerId)
        ->where('status', Order::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => Order::STATUS_RECEIVED
        ]);

        DB::table('repair_orders')->where('assigned_to', $installerId)
        ->where('status', RepairOrder::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => RepairOrder::STATUS_RECEIVED
        ]);

        DB::table('removal_orders')->where('assigned_to', $installerId)
        ->where('status', RemovalOrder::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => RemovalOrder::STATUS_RECEIVED
        ]);

        DB::table('delivery_orders')->where('assigned_to', $installerId)
        ->where('status', DeliveryOrder::STATUS_SCHEDULED)
        ->update([
            'assigned_to' => null,
            'stop_number' => 1,
            'status' => DeliveryOrder::STATUS_RECEIVED
        ]);
    }

    public function getInstallerNextStop($installerId, $routeDate)
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select('orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'orders.stop_number as stop_number', 'posts.post_name as post_name', 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'repair_orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select('orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'repair_orders.stop_number as stop_number','posts.post_name as post_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as installers', 'removal_orders.assigned_to', 'installers.id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('orders.latitude', 'orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'removal_orders.stop_number as stop_number', 'posts.post_name as description', 'removal_orders.assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'delivery_orders.assigned_to', 'installers.id')
            ->select('delivery_orders.latitude', 'delivery_orders.longitude', 'installers.id as installer_id', 'installers.name as installer_name', 'installers.routing_color', 'delivery_orders.stop_number as stop_number',  DB::raw("0 as post_name"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name');


        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        /*$today = now()->format('Y-m-d');
        $tomorrow = now()->addDay(1)->format('Y-m-d');*/

        $order = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('latitude', 'longitude', 'installer_id', 'installer_name', 'routing_color', 'stop_number', 'post_name', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name')
            ->where(function($q) {
                $q->whereNull('removal_status')
                ->orWhere('removal_status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->where('delivery_status', '<>', DeliveryOrder::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('status', '<>', Order::STATUS_COMPLETED)
            ->where('status', '<>', Order::STATUS_INCOMPLETE)
            ->where(function($q) use ($routeDate) {
                /*$q->where('desired_date_type', 1)
                    ->orWhereDate('desired_date', '=' , $routeDate);*/
                $q->where('desired_date_type', 1)
                ->whereNull('desired_date')
                ->orWhere(function($q2) use ($routeDate) {
                    $q2->where('desired_date_type', 2)
                    ->whereDate('desired_date', '=' , $routeDate);
                })
                ->orWhere(function($q2) use ($routeDate) {
                    $q2->where('desired_date_type', 1)
                    ->whereDate('desired_date', '=' , $routeDate);
                });
            })
            ->where('assigned_to', $installerId)
            ->orderBy('stop_number')
            ->first();

        return $order;
    }

    public function getDirection($origin, $destination)
    {
        $googleKey = env('GOOGLE_API_KEY');
        $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$origin}&destination={$destination}&key={$googleKey}";
        return Http::get($url);
    }

    public function getInstallerOrderDetails($orderId, $orderType, $installerId)
    {
        $order = collect();

        if ($orderType == 'install') {
            $order = Order::where('id', $orderId)
            ->where('assigned_to', $installerId)
            ->where('status', Order::STATUS_SCHEDULED)
            ->first();
        }

        if ($orderType == 'repair') {
            $order = RepairOrder::where('id', $orderId)
            ->where('assigned_to', $installerId)
            ->where('status', RepairOrder::STATUS_SCHEDULED)
            ->first();
        }

        if ($orderType == 'removal') {
            $order = RemovalOrder::where('id', $orderId)
            ->where('assigned_to', $installerId)
            ->where('status', RemovalOrder::STATUS_SCHEDULED)
            ->first();
        }

        if ($orderType == 'delivery') {
            $order = DeliveryOrder::where('id', $orderId)
            ->where('assigned_to', $installerId)
            ->where('status', DeliveryOrder::STATUS_SCHEDULED)
            ->first();

            $pickup_delivery = '';
            foreach ($order->panels as $orderPanel) {
                if ($orderPanel->pickup_delivery == DeliveryOrder::PICKUP) {
                    $pickup_delivery = 'Pickup/';
                }
                if ($orderPanel->pickup_delivery == DeliveryOrder::DELIVERY) {
                    $pickup_delivery .= 'Delivery';
                }
            }

            $pickup_delivery = rtrim($pickup_delivery, '/');
            $order->pickup_delivery = $pickup_delivery;
        }

        return $order;
    }

    public function updatePostInventoryOut($postId, $qty) {
        $post = Post::find($postId);
        if ($post) {
            if ($post->quantity > 0) {
                $post->quantity = $post->quantity - $qty;
            }
            //$post->quantity_in_field = $post->quantity_in_field + $qty;
            $post->save();
        }
    }

    public function updatePostInventoryIn($postId, $qty) {
        $post = Post::find($postId);
        if ($post) {
            $post->quantity = $post->quantity + $qty;
            /*if ($post->quantity_in_field > 0) {
                $post->quantity_in_field = $post->quantity_in_field - $qty;
            }*/
            $post->save();
        }
    }

    public function updatePanelInventoryOut($panelId, $qty) {
        $panel = Panel::find($panelId);
        if ($panel) {
            if ($panel->quantity > 0) {
                $panel->quantity = $panel->quantity - $qty;
            }
            //$panel->quantity_in_field = $panel->quantity_in_field + $qty;
            $panel->save();
        }
    }

    public function updatePanelInventoryIn($panelId, $qty) {
        $panel = Panel::find($panelId);
        if ($panel) {
            $panel->quantity = $panel->quantity + $qty;
            /*if ($panel->quantity_in_field > 0) {
                $panel->quantity_in_field = $panel->quantity_in_field - $qty;
            }*/
            $panel->save();
        }
    }

    public function updateAccessoryInventoryOut($accessoryId, $qty) {
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            if ($accessory->quantity > 0) {
                $accessory->quantity = $accessory->quantity - $qty;
            }
            //$accessory->quantity_in_field = $accessory->quantity_in_field + $qty;
            $accessory->save();
        }
    }

    public function updateAccessoryInventoryIn($accessoryId, $qty) {
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            $accessory->quantity = $accessory->quantity + $qty;
            /*if ($accessory->quantity_in_field > 0) {
                $accessory->quantity_in_field = $accessory->quantity_in_field - $qty;
            }*/
            $accessory->save();
        }
    }

    public function zeroOutPostInventory($postId) {
        $post = Post::find($postId);
        if ($post) {
            $post->quantity = 0;
            $post->save();
        }
    }

    public function zeroOutPanelInventory($panelId)
    {
        $panel = Panel::find($panelId);
        if ($panel) {
            $panel->quantity = 0;
            $panel->save();
        }
    }

    public function zeroOutAccessoryInventory($accessoryId)
    {
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            $accessory->quantity = 0;
            $accessory->save();
        }
    }

    public function getPostPoints($postId)
    {
        $post = Post::find($postId);
        $points = 0;
        if ($post) {
            $points = $post->point_value;
        }

        return $points;
    }

    public function getPriceMissingPost($postId)
    {
        $post = Post::find($postId);
        $price = 0;
        if ($post) {
            $price = $post->loss_damage;
        }

        return $price;
    }

    public function getPostName($postId)
    {
        $post = Post::find($postId);
        $name = '';
        if ($post) {
            $name = $post->post_name;
        }

        return $name;
    }

    public function getAccessoryPoints($accessoryId)
    {
        $accessory = Accessory::find($accessoryId);
        $points = 0;
        if ($accessory) {
            $points = $accessory->point_value;
        }

        return $points;
    }

    public function getAccessoryPrice($accessoryId)
    {
        $accessory = Accessory::find($accessoryId);
        $price = 0;
        if ($accessory) {
            $price = $accessory->price;
        }

        return $price;
    }

    public function getPriceMissingAccessory($accessoryId)
    {
        $price = 0;
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            $price = $accessory->loss_damage;
        }

        return $price;
    }

    public function getAccessoryName($accessoryId)
    {
        $name = '';
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            $name = $accessory->accessory_name;
        }

        return $name;
    }

    public function getSavedPaymentProfile($order): array
    {
        $payProfile = [];

        if ($order->office) {
            $billTo['first_name'] = $order->office->user->first_name;
            $billTo['last_name'] = $order->office->user->last_name;
            $billTo['address'] = $order->office->user->address;
            $billTo['city'] = $order->office->user->city;
            $billTo['state'] = $order->office->user->state;
            $billTo['zipcode'] = $order->office->user->zipcode;
            $billTo['email'] = $order->office->user->email;
            $billTo['userId'] = $order->office->user->id;
        }

        if ($order->agent) {
            $billTo['first_name'] = $order->agent->user->first_name;
            $billTo['last_name'] = $order->agent->user->last_name;
            $billTo['address'] = $order->agent->user->address;
            $billTo['city'] = $order->agent->user->city;
            $billTo['state'] = $order->agent->user->state;
            $billTo['zipcode'] = $order->agent->user->zipcode;
            $billTo['email'] = $order->agent->user->email;

            if ($order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                $billTo['userId'] = $order->office->user->id;
            } else{
                $billTo['userId'] = $order->agent->user->id;
            }
        }

        //Authorize payment through customer profile
        $cardOwner = User::find($billTo['userId']);
        if ($cardOwner) {
            $payProfile['customer_profile'] = $cardOwner->authorizenet_profile_id;
            $payProfile['card_profile'] = $cardOwner->latestPaymentProfile()->first()->payment_profile_id;
            $payProfile['userId'] = $billTo['userId'];
        }

        return $payProfile;
    }

    public function storePanels($officeId, $agentId, array $signPanels) {
        foreach ($signPanels['panel'] as $key => $panelId) {
            $panel = Panel::find($panelId);
            if ($panel) {
                $panel->quantity = $panel->quantity + $signPanels['qty'][$key];
                $panel->save();
            } else { //New panel
                $this->addNewSign($officeId, $agentId, $key);
            }
        }
    }

    public function addNewSign($officeId, $agentId, $counter)
    {
        $office = Office::find($officeId);
        $panelName = $office->name_abbreviation ? $office->name_abbreviation : $office->user->name;
        $panelName .= ' - ';
        $agent = Agent::find($agentId);
        if ($agent) {
            $panelName .= $agent->user->name;
        }
        $date = now()->format('m/d/y');
        $panelName .= " {$date} - {$counter}";

        //Item id
        $monthChar = $this->getMonthCharFromAlphabet((int) now()->month);
        $lastItemNumber = Panel::max('item_id_number') ?? 0;
        $itemNumber = ++$lastItemNumber;
        $year = sprintf('%03d', now()->format('y'));
        $counter = sprintf('%05d', $itemNumber);
        $itemCode = "S{$year}{$monthChar}{$counter}";

        $panel = Panel::create([
            "panel_name" => $panelName,
            "quantity" => 1,
            "price" => 0,
            "free_storage" => 0,
            "cost_per_unit" => 0,
            "frequency" => 0,
            "width" => 0,
            "height" => 0,
            "office_id" => $officeId,
            "status" => Panel::STATUS_ACTIVE,
            'item_id_number' => $itemNumber,
            'item_id_code' => $itemCode,
            'image_path' => 'no_panel_image.png'
        ]);

        if ($agent) {
            PanelAgent::create([
                'panel_id' => $panel->id,
                'agent_id' => $agentId,
                'is_primary' => true,
            ]);
        }
    }

    public function getOrderByTypeAndId($orderId, $orderType)
    {
        $order = collect();

        if ($orderType == 'install') {
            $order = Order::where('id', $orderId)
            ->with('office')
            ->with('agent')
            ->with('post')
            ->with('panel')
            ->with('accessories')
            ->with('attachments')
            ->with('installer')
            ->first();
        }

        if ($orderType == 'repair') {
            $order = RepairOrder::where('id', $orderId)
            ->with('order')
            ->with('panel')
            ->with('accessories')
            ->with('installer')
            ->first();
        }

        if ($orderType == 'removal') {
            $order = RemovalOrder::where('id', $orderId)
            ->with('order')
            ->with('installer')
            ->first();
        }

        if ($orderType == 'delivery') {
            $order = DeliveryOrder::where('id', $orderId)
            ->with('office')
            ->with('agent')
            ->with('pickups')
            ->with('dropoffs')
            ->with('installer')
            ->first();
        }

        return $order;
    }

    public function getOrdersCompleted()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'orders.assigned_to as assigned_to', DB::raw("'install' as order_type"), 'orders.id', 'orders.address', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'orders.rating', 'orders.feedback', 'orders.feedback_date', 'orders.feedback_published');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'repair_orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'repair' as order_type"), 'repair_orders.id', 'orders.address', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'repair_orders.rating', 'repair_orders.feedback', 'repair_orders.feedback_date', 'repair_orders.feedback_published');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as installers', 'removal_orders.assigned_to', 'installers.id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'removal_orders.assigned_to', DB::raw("'removal' as order_type"), 'removal_orders.id', 'orders.address', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'removal_orders.rating', 'removal_orders.feedback', 'removal_orders.feedback_date', 'removal_orders.feedback_published');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'delivery_orders.assigned_to', 'installers.id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'delivery_orders.assigned_to as assigned_to', DB::raw("'delivery' as order_type"), 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'delivery_orders.rating', 'delivery_orders.feedback', 'delivery_orders.feedback_date', 'delivery_orders.feedback_published');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('installer_name', 'assigned_to', 'order_type', 'id', 'address', 'status', 'order_number', 'office_name', 'agent_name', 'rating', 'feedback', 'feedback_date', 'feedback_published')
            ->where('status', Order::STATUS_COMPLETED)
            ->orderByDesc('feedback_date')
            ->whereNotNull('rating')
            ->get();

        return $orders;
    }

    public function findCompletedByTypeAndId($orderType, $orderId)
    {
        if ($orderType == 'install') {
            $order = Order::where('id', $orderId)
            ->where('status', Order::STATUS_COMPLETED)
            ->first();
        }

        if ($orderType == 'repair') {
            $order = RepairOrder::where('id', $orderId)
            ->where('status', RepairOrder::STATUS_COMPLETED)
            ->first();
        }

        if ($orderType == 'removal') {
            $order = RemovalOrder::where('id', $orderId)
            ->where('status', RemovalOrder::STATUS_COMPLETED)
            ->first();
        }

        if ($orderType == 'delivery') {
            $order = DeliveryOrder::where('id', $orderId)
            ->where('status', DeliveryOrder::STATUS_COMPLETED)
            ->first();
        }

        return $order;
    }

    public function getCompletedPublishedAndRated()
    {
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'orders.assigned_to as assigned_to', DB::raw("'install' as order_type"), 'orders.id', 'orders.address', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'agent.first_name as agent_first_name', 'agent.last_name as agent_last_name', 'orders.rating', 'orders.feedback', 'orders.feedback_date', 'orders.feedback_published');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'repair_orders.assigned_to', 'installers.id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'repair_orders.assigned_to as assigned_to', DB::raw("'repair' as order_type"), 'repair_orders.id', 'orders.address', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'agent.first_name as agent_first_name', 'agent.last_name as agent_last_name', 'repair_orders.rating', 'repair_orders.feedback', 'repair_orders.feedback_date', 'repair_orders.feedback_published');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as installers', 'removal_orders.assigned_to', 'installers.id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'removal_orders.assigned_to', DB::raw("'removal' as order_type"), 'removal_orders.id', 'orders.address', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'agent.first_name as agent_first_name', 'agent.last_name as agent_last_name', 'removal_orders.rating', 'removal_orders.feedback', 'removal_orders.feedback_date', 'removal_orders.feedback_published');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('users as installers', 'delivery_orders.assigned_to', 'installers.id')
            ->select('installers.id as installer_id', 'installers.name as installer_name', 'delivery_orders.assigned_to as assigned_to', DB::raw("'delivery' as order_type"), 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'agent.first_name as agent_first_name', 'agent.last_name as agent_last_name', 'delivery_orders.rating', 'delivery_orders.feedback', 'delivery_orders.feedback_date', 'delivery_orders.feedback_published');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $orders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('installer_name', 'assigned_to', 'order_type', 'id', 'address', 'status', 'order_number', 'office_name', 'agent_name', 'agent_first_name', 'agent_last_name', 'rating', 'feedback', 'feedback_date', 'feedback_published')
            ->where('status', Order::STATUS_COMPLETED)
            ->whereNotNull('rating')
            ->whereNotNull('feedback')
            ->whereNotNull('feedback_date')
            ->where('feedback_published', '1')
            ->get();

        return $orders;
    }

    public function generatePanelItemId($panelId)
    {
        $panel = Panel::find($panelId);
        if ($panel) {
            //Check if panel inactive, activate and generate item id
            if ($panel->status == Panel::STATUS_INACTIVE) {
                $data = $panel->generateItemCodeNumber();

                $panel->status = Panel::STATUS_ACTIVE;
                $panel->item_id_number = $data['item_id_number'];
                $panel->item_id_code = $data['item_id_code'];
                $panel->save();
            }
        }
    }

    public function getInstallerPullList($installerId, $routeDate)//: array
    {
        $pullList = [];

        $postsPullList = $this->getInstallerPostsPullList($installerId, $routeDate);
        $signsPullList = $this->getInstallerSignsPullList($installerId, $routeDate);
        $accessoriesPullList = $this->getInstallerAccessoriesPullList($installerId, $routeDate);

        $pullList['postsPullList'] = $postsPullList;
        $pullList['signsPullList'] = $signsPullList;
        $pullList['accessoriesPullList'] = $accessoriesPullList;

        return $pullList;
    }

    public function getInstallerPostsPullList($installerId, $routeDate)//: array
    {
        $postsPullList = [];

        /*$today = now()->format('Y-m-d');
        $tomorrow = now()->addDay(1)->format('Y-m-d');*/

        //Get posts and qty
        $installOrders = $this->model
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->where(function($q) {
                $q->whereNull('removal_orders.status')
                ->orWhere('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('orders.status', '<>', Order::STATUS_CANCELLED)
            ->where('orders.status', '<>', Order::STATUS_COMPLETED)
            ->where('orders.status', '<>', Order::STATUS_INCOMPLETE)
            ->whereDate('orders.desired_date', '=' , $routeDate)
            ->where('orders.assigned_to', $installerId)
            ->select('posts.id as post_id', 'posts.post_name', 'posts.image_path as image_path', DB::raw('count(posts.id) as post_qty'))
            ->groupBy('posts.id', 'posts.post_name', 'posts.image_path')
            ->get();

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->where('removal_orders.status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('removal_orders.service_date', '=', $routeDate)
            ->where('removal_orders.assigned_to', $installerId)
            ->select('posts.id as post_id')
            ->get();
            //dd($removalOrders);
        $postIds = [];
        foreach ($removalOrders as $removalOrder) {
            array_push($postIds, $removalOrder->post_id);
        }
        $counts = array_count_values($postIds);

        foreach ($installOrders as $key => $installOrder) {
            $postsPullList[$key]['post_name'] = $installOrder->post_name;
            $postsPullList[$key]['post_qty'] = $installOrder->post_qty;
            $postsPullList[$key]['image_path'] = $installOrder->image_path;

            $postsPullList[$key]['removal_qty'] = 0;
            if (isset($counts[$installOrder->post_id])) {
                $postsPullList[$key]['removal_qty'] = $counts[$installOrder->post_id];
            }

            //Inventory for the post
            $postsPullList[$key]['inventory'] = $this->getPostInventory((int) $installOrder->post_id);
        }

        return $postsPullList;
    }

    public function getInstallerSignsPullList($installerId, $routeDate)//: array
    {
        $signsPullList = [];

        //$tomorrow = now()->addDay(1)->format('Y-m-d');

        //Get posts and qty
        $installOrders = $this->model
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->leftJoin('panels', 'orders.panel_id', 'panels.id')
            ->where(function($q) {
                $q->whereNull('removal_orders.status')
                ->orWhere('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where(function($q) {
                $q->where('orders.status', Order::STATUS_SCHEDULED)
                    ->orWhere('repair_orders.status', RepairOrder::STATUS_SCHEDULED);
            })
            ->where(function($q) use ($routeDate) {
                $q->whereDate('orders.desired_date', '=', $routeDate)
                    ->orWhereDate('repair_orders.service_date', '=', $routeDate);
            })
            ->where(function($q) use ($installerId) {
                $q->where('orders.assigned_to', $installerId)
                ->orWhere('repair_orders.assigned_to', $installerId);
            })
            ->select('orders.id as install_order_id', 'panels.id_number as panel_id_number', 'repair_orders.panel_id as repair_panel_id', 'orders.id as order_id', 'panels.id as panel_id', 'panels.panel_name', 'panels.image_path as image_path', DB::raw('count(panels.id) as panel_qty'))
            ->groupBy('panels.id_number', 'repair_orders.panel_id', 'orders.id', 'panels.id', 'panels.panel_name', 'panels.image_path')
            ->orderBy('panels.id_number')
            ->get();
            //dd($installOrders);

        $panelIds = [];
        foreach ($installOrders as $installOrder) {
            if (isset($installOrder->repair_panel_id) && !is_null($installOrder->repair_panel_id)) {
                array_push($panelIds, $installOrder->repair_panel_id);
            } else {
                if (! is_null($installOrder->panel_id)) {
                    array_push($panelIds, $installOrder->panel_id);
                }
            }
        }
        //dd($panelIds);
        $counts = array_count_values($panelIds);

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->leftJoin('panels', 'orders.panel_id', 'panels.id')
            ->where('removal_orders.status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('removal_orders.service_date', '=', $routeDate)
            ->where('removal_orders.assigned_to', $installerId)
            ->select('panels.id_number as panel_id_number', 'panels.id as panel_id', 'repair_orders.panel_id as repair_panel_id')
            ->orderBy('panels.id_number')
            ->get();

        //dd($removalOrders);
        $removalPanelIds = [];
        foreach ($removalOrders as $removalOrder) {
            if (isset($removalOrder->repair_panel_id) && !is_null($removalOrder->repair_panel_id)) {
                array_push($removalPanelIds, $removalOrder->repair_panel_id);
            } else {
                if (!is_null($removalOrder->panel_id)) {
                    array_push($removalPanelIds, $removalOrder->panel_id);
                }
            }
        }
        $countRemovals = array_count_values($removalPanelIds);

        //dd($counts);
        foreach ($installOrders as $key => $installOrder) {
            $panelId = null;
            if (isset($installOrder->panel_id) || isset($installOrder->repair_panel_id)) {
                $order = Order::find($installOrder->install_order_id);
                if ($order->repair) {
                    if (!is_null($installOrder->repair_panel_id)) {
                        $panelId = $installOrder->repair_panel_id;
                        $panel = Panel::find($panelId);

                        if (isset($counts[$installOrder->repair_panel_id])) {
                            $signsPullList[$panelId]['panel_qty'] = $counts[$panelId];
                        }

                        $signsPullList[$panelId]['panel_name'] = $panel->panel_name;
                        $signsPullList[$panelId]['image_path'] = $panel->image_path;
                        $signsPullList[$panelId]['panel_id_number'] = $panel->id_number;
                    }
                } else {
                    $panelId = $installOrder->panel_id;
                    $panel = Panel::find($panelId);

                    if (isset($counts[$installOrder->panel_id])) {
                        $signsPullList[$panelId]['panel_qty'] = $counts[$panelId];
                    }

                    $signsPullList[$panelId]['panel_name'] = $panel->panel_name;
                    $signsPullList[$panelId]['image_path'] = $panel->image_path;
                    $signsPullList[$panelId]['panel_id_number'] = $panel->id_number;
                }

                //Get removals qty
                if ($panelId) {
                    $signsPullList[$panelId]['removal_qty'] = 0;
                    if (isset($installOrder->repair_panel_id) && !is_null($installOrder->repair_panel_id)) {
                        if (isset($countRemovals[$installOrder->repair_panel_id])) {
                            $signsPullList[$panelId]['removal_qty'] = $countRemovals[$installOrder->repair_panel_id];
                        }
                    } else {
                        if (isset($countRemovals[$installOrder->panel_id])) {
                            $signsPullList[$panelId]['removal_qty'] = $countRemovals[$installOrder->panel_id];
                        }
                    }
                }

                //Inventory for panel
                $signsPullList[$panelId]['inventory'] = $this->getPanelInventory((int) $installOrder->panel_id);
            }
        }

        //List panels from delivery orders
        //Get posts and qty
        $deliveryOrders = $this->deliveryOrder
            ->leftJoin('delivery_order_panels', 'delivery_orders.id', 'delivery_order_panels.delivery_order_id')
            ->join('panels', 'delivery_order_panels.panel_id', 'panels.id')
            ->where('delivery_orders.status', DeliveryOrder::STATUS_SCHEDULED)
            ->whereDate('delivery_orders.service_date', '=' , $routeDate)
            ->where('delivery_orders.assigned_to', $installerId)
            ->select('panels.id_number as panel_id_number', 'panels.id as panel_id', 'panels.panel_name', 'panels.image_path as image_path', DB::raw('count(panels.id) as panel_qty'))
            ->groupBy('panels.id_number', 'panels.id', 'panels.panel_name', 'panels.image_path')
            ->orderBy('panels.id_number')
            ->get();
            //dd($deliveryOrders);

        if ($deliveryOrders->isNotEmpty()) {
            foreach ($deliveryOrders as $key => $deliveryOrder) {
                $panelId = $deliveryOrder->panel_id;

                //Add panel qty to any existing installl/repair
                if ( isset($signsPullList[$panelId]['panel_qty']) ) {
                    $signsPullList[$panelId]['panel_qty'] = $signsPullList[$panelId]['panel_qty'] + $deliveryOrder->panel_qty;
                } else {
                    $signsPullList[$panelId]['panel_qty'] = $deliveryOrder->panel_qty;
                }

                $signsPullList[$panelId]['panel_name'] = $deliveryOrder->panel_name;
                $signsPullList[$panelId]['image_path'] = $deliveryOrder->image_path;
                $signsPullList[$panelId]['panel_id_number'] = $deliveryOrder->panel_id_number;

                $signsPullList[$panelId]['removal_qty'] = 0;
                if (isset($countRemovals[$deliveryOrder->panel_id])) {
                    $signsPullList[$panelId]['removal_qty'] = $countRemovals[$deliveryOrder->panel_id];
                }

                //Inventory for panel
                $signsPullList[$panelId]['inventory'] = $this->getPanelInventory((int) $deliveryOrder->panel_id);
            }
        }

        return $signsPullList;
    }

    public function getInstallerAccessoriesPullList($installerId, $routeDate)//: array
    {
        $accessoriesPullList = [];

        //$tomorrow = now()->addDay(1)->format('Y-m-d');

        //Get posts and qty
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->where(function($q) {
                $q->whereNull('removal_orders.status')
                ->orWhere('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where(function($q) {
                $q->where('orders.status', Order::STATUS_SCHEDULED)
                    ->orWhere('repair_orders.status', RepairOrder::STATUS_SCHEDULED);
            })
            ->where(function($q) use ($routeDate) {
                $q->whereDate('orders.desired_date', '=' , $routeDate)
                    ->orWhereDate('repair_orders.service_date', '=' , $routeDate);
            })
            ->where(function($q) use ($installerId) {
                $q->where('orders.assigned_to', $installerId)
                ->orWhere('repair_orders.assigned_to', $installerId);
            })
            //->where('orders.assigned_to', $installerId)
            ->select('orders.status as status', 'orders.id as order_id', 'office.name as office_name', 'agent.name as agent_name')
            ->get();
            //dd($installOrders);

        $accessoriesIds = [];
        foreach ($installOrders as $key => $installOrder) {
            $order = Order::find($installOrder->order_id);

            if ($order->repair) {
                $orderAccessories = $order->repair->accessories;
            } else {
                $orderAccessories = $order->accessories;
            }

            //Get all accessories Ids
            foreach ($orderAccessories as $orderAccessory) {
                if ($order->repair) {
                    if ($orderAccessory->repair_order->status !== RepairOrder::STATUS_COMPLETED) {
                        array_push($accessoriesIds, $orderAccessory->accessory_id);
                    }
                } else {
                    if ($orderAccessory->order->status !== Order::STATUS_COMPLETED) {
                        array_push($accessoriesIds, $orderAccessory->accessory_id);
                    }
                }
            }
        }

        //Count accessories
        $counts = array_count_values($accessoriesIds);

        //Take unique values from array of accessories Ids
        $accessoriesIds = array_unique($accessoriesIds);
        //dd($accessoriesIds);

        $removalOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->where('removal_orders.status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('removal_orders.service_date', '=' , $routeDate)
            ->where('removal_orders.assigned_to', $installerId)
            ->select('orders.id as order_id', 'office.name as office_name', 'agent.name as agent_name')
            ->get();

        $removalAccessoriesIds = [];
        foreach ($removalOrders as $removalOrder) {
            $order = Order::find($removalOrder->order_id);
            if ($order->repair) {
                $orderAccessories = $order->repair->accessories;
            } else {
                $orderAccessories = $order->accessories;
            }

            foreach ($orderAccessories as $orderAccessory) {
                array_push($removalAccessoriesIds, $orderAccessory->accessory_id);
            }
        }

        $countRemovals = array_count_values($removalAccessoriesIds);

        foreach ($accessoriesIds as $accessoryId) {
            $accessory = Accessory::find($accessoryId);

            $accessoriesPullList[$accessoryId]['accessory_name'] = $accessory->accessory_name;
            $accessoriesPullList[$accessoryId]['accessory_qty'] = $counts[$accessoryId];
            $accessoriesPullList[$accessoryId]['image_path'] = $accessory->image;

            //Removals
            $accessoriesPullList[$accessoryId]['removal_qty'] = 0;
            if (isset($countRemovals[$accessoryId])) {
                $accessoriesPullList[$accessoryId]['removal_qty'] = $countRemovals[$accessoryId];
            }

            //Check if need to display agent/office in pull list
            $accessoriesPullList[$accessoryId]['show_agent_office'] = false;
            $accessoriesPullList[$accessoryId]['agent_office_list'] = [];
            if ($accessory->pull_list) {
                $accessoriesPullList[$accessoryId]['show_agent_office'] = true;

                $processedAccessories = [];
                $processedRemovalAccessories = [];
                $processedNames = [];
                foreach ($installOrders as $key => $installOrder) {
                    $order = Order::find($installOrder->order_id);

                    if ($order->repair) {
                        $orderAccessories = $order->repair->accessories;
                    } else {
                        $orderAccessories = $order->accessories;
                    }

                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_qty'] = 0;
                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_id'] = $accessoryId;

                    foreach ($orderAccessories as $orderAccessory) {
                        if ($orderAccessory->accessory_id == $accessoryId) {
                            //array_push($processedAccessories, $orderAccessory->accessory_id);

                            if ($order->agent) {
                                $name = $order->agent->user->name;
                                $nameKey = array_search($name, $processedNames);
                                if ($nameKey !== false) {
                                    $key = $nameKey;
                                }
                                $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['name'] = $name;
                                if ($order->status != Order::STATUS_COMPLETED) {
                                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_qty']++;
                                }
                            } else {
                                $name = $order->office->user->name;
                                $nameKey = array_search($name, $processedNames);
                                if ($nameKey !== false) {
                                    $key = $nameKey;
                                }
                                $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['name'] = $name;
                                if ($order->status != Order::STATUS_COMPLETED) {
                                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_qty']++;
                                }
                            }

                            if ($nameKey === false) {
                                $processedNames[$key] = $name;
                            }
                        }
                    }
                }
            }
        }

        foreach ($accessoriesPullList as $pullList) {
            foreach ($pullList['agent_office_list'] as $key => $agentOfficeList) {
                //dd($agentOfficeList);
                $previousAgent = '';
                $previous= '';
                if (isset($agentOfficeList['accessory_id']) && isset($agentOfficeList['name'])) {
                    $name = $agentOfficeList['name'];
                    $accessoryId = $agentOfficeList['accessory_id'];
                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['removal_qty'] = 0;

                    foreach ($removalOrders as $removalOrder) {
                        $install = Order::find($removalOrder->order_id);

                        if ($install->repair) {
                            $removalOrderAccessories = $install->repair->accessories;
                        } else {
                            $removalOrderAccessories = $install->accessories;
                        }
                        foreach ($removalOrderAccessories as $removalOrderAccessory) {
                            if ($removalOrderAccessory->accessory_id == $accessoryId) {
                                if ($removalOrder->agent_name) {
                                    if ($removalOrder->agent_name == $name) {
                                        $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['removal_qty']++;
                                    }
                                } else {
                                    if ($removalOrder->office_name == $name) {
                                        $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['removal_qty']++;
                                    }
                                }
                                $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['name'] = $name;
                                $previousName = $name;
                            }
                        }
                    }
                }
            }
        }

        //dd($accessoriesPullList);
        return $accessoriesPullList;
    }

    public function isInvoiced($order)
    {
        $invoiced = false;

        if (isset($order->order)) {
            $order->office = $order->order->office;
            $order->agent = $order->order->agent;
        }

        $officePayMethod = $order->office->payment_method;
        if ($officePayMethod == Office::PAYMENT_METHOD_INVOICE) {
            $invoiced = true;
        }
        if ($order->agent) {
            $agentPayMethod = $order->agent->payment_method;
            if ($agentPayMethod == Agent::PAYMENT_METHOD_INVOICE) {
                $invoiced = true;
            } else {
                $invoiced = false;
                if ($agentPayMethod == Agent::PAYMENT_METHOD_OFFICE_PAY) {
                    if ($officePayMethod == Office::PAYMENT_METHOD_INVOICE) {
                        $invoiced = true;
                    }
                }
            }
        }

        return $invoiced;
    }

    public function getOrderPayer($order, $orderType)
    {
        if (
            $orderType == Order::REPAIR_ORDER || $orderType == 'repair'
            || $orderType == Order::REMOVAL_ORDER || $orderType == 'removal'
        ) {
            $order->office = $order->order->office;
            $order->agent = $order->order->agent;
        }

        $payer = $order->office->user;

        if ($order->agent && $order->agent->payment_method != Agent::PAYMENT_METHOD_OFFICE_PAY) {
            $payer = $order->agent->user;
        }

        return $payer;
    }

    public function getPaymentProfile($orderId, $orderType)
    {
        $paymentProfile = null;

        switch ($orderType) {
            case 'install':
                $orderType =  Order::INSTALL_ORDER;
                break;
            case 'repair':
                $orderType =  Order::REPAIR_ORDER;
                break;
            case 'removal':
                $orderType =  Order::REMOVAL_ORDER;
                break;
            case 'delivery':
                $orderType =  Order::DELIVERY_ORDER;
                break;
        }

        $profile = AuthorizenetPaymentProfile::where('order_id', $orderId)
            ->where('order_type', $orderType)
            ->first();

        if ($profile) {
            $paymentProfile = $profile->payment_profile_id;
        }

        return $paymentProfile;
    }

    public function getOrderTypeFromString($orderType)
    {
        switch ($orderType) {
            case 'install':
                return Order::INSTALL_ORDER;
                break;
            case 'repair':
                return Order::REPAIR_ORDER;
                break;
            case 'removal':
                return Order::REMOVAL_ORDER;
                break;
            case 'delivery':
                return Order::DELIVERY_ORDER;
                break;
        }
    }

    public function findByIdAndType($orderId, $orderType)
    {
        if ($orderType == 'install' || $orderType == Order::INSTALL_ORDER) {
            //Need to decrease stop numbers
            $order = Order::find($orderId);
        }

        if ($orderType == 'repair' || $orderType == Order::REPAIR_ORDER) {
            $order = RepairOrder::find($orderId);
        }

        if ($orderType == 'removal' || $orderType == Order::REMOVAL_ORDER) {
            $order = RemovalOrder::find($orderId);
        }

        if ($orderType == 'delivery' || $orderType == Order::DELIVERY_ORDER) {
            $order = DeliveryOrder::find($orderId);
        }

        return $order;
    }

    public function recalculatePostInFieldInstall($postId)
    {
        //Find all installed (not removed) orders
        $postFieldQty = $this->model
            ->where('status', Order::STATUS_COMPLETED)
            ->where('post_id', $postId)
            ->whereDoesntHave('removal_completed')
            ->count();

        //info("Post IN FIELD: $postFieldQty");
        $post = Post::find($postId);
        if ($post) {
            $post->quantity_in_field = $postFieldQty;
            $post->save();
        }
    }

    public function recalculatePanelInFieldInstall($panelId)
    {
        //Find all installed (not removed) orders
        $panelFieldQty = $this->model
            ->where('status', Order::STATUS_COMPLETED)
            ->where('panel_id', $panelId)
            ->whereDoesntHave('removal_completed')
            ->count();

        //info("Panel IN FIELD: $panelFieldQty");
        $panel = Panel::find($panelId);
        if ($panel) {
            $panel->quantity_in_field = $panelFieldQty;
            $panel->save();
        }
    }

    public function recalculatePanelInFieldRepair($panelId, $repairOrder)
    {
        //Get Install order from repair order
        $installOrder = $repairOrder->order;

        if ($installOrder->panel_id == $repairOrder->panel_id) {
            //Do nothing since panel didn't change and is not missing/damaged
            //This is just a swap of same panel
        } else {
            //decrement IN FIELD for old panel
            $this->updatePanelInventoryInfield($installOrder->panel_id, -1);
            //increment IN STORAGE for old panel
            $this->updatePanelInventoryInStorage($installOrder->panel_id, 1);

            //increment IN FIELD for new panel
            $this->updatePanelInventoryInfield($panelId, 1);
            //decrement IN STORAGE for new panel
            $this->updatePanelInventoryInStorage($panelId, -1);
        }
    }

    public function recalculatePanelInventoryRemoval($panelId, $removalOrder)
    {
        //Decrement IN Field for panel
        //The panelId here is either from post or from repair order
        $this->updatePanelInventoryInfield($panelId, -1);

        $this->updatePanelInventoryInStorage($panelId, 1);
    }

    public function recalculateAccessoryInFieldInstall($accessoryId)
    {
        //Get orders using the accessory
        $ordersAccessories = $this->model
            ->join('order_accessories', function($join) use ($accessoryId){
                $join->on('orders.id', 'order_accessories.order_id')
                ->where('accessory_id', $accessoryId);
            })
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->get('orders.id');

        if ($ordersAccessories->isNotEmpty()) {
            //count in if order was not removed
            $accessoryFieldQty = 0;
            foreach ($ordersAccessories as $orderAccessory) {
                $order = $this->model->find($orderAccessory->id);

                if (! $order->removal_completed && ! $order->repair_completed) {
                    $accessoryFieldQty++;
                }
            }

            //info("Accessory IN FIELD: $accessoryFieldQty");
            $accessory = Accessory::find($accessoryId);
            if ($accessory) {
                $accessory->quantity_in_field = $accessoryFieldQty;
                $accessory->save();
            }
        }
    }

    public function recalculateAccessoryInFieldRepair($accessoryId, $repairOrder)
    {
        $accessoryFieldQty = 0;
        $installOrder = $repairOrder->order;
        $originalAccessories = $installOrder->accessories->pluck('accessory_id')->all();

        //Only update accessory inventory if changed in repair order
        if ( ! in_array($accessoryId, $originalAccessories)) {
            //decrement IN STORAGE for new accessory
            $this->updateAccessoryInventoryInStorage($accessoryId, -1);

            //increment IN FIELD for new accessory
            $this->updateAccessoryInventoryInField($accessoryId, 1);
        }
    }

    public function recalculateAccessoryInventoryRemoval($accessoryId)
    {
        //increment IN STORAGE for accessory
        $this->updateAccessoryInventoryInStorage($accessoryId, 1);

        //decrement IN FIELD for accessory
        $this->updateAccessoryInventoryInField($accessoryId, -1);
    }

    public function updatePanelInventoryInfield(int $panelId, int $qty)
    {
        $panel = Panel::find($panelId);
        if ($panel) {
            $panel->quantity_in_field = $panel->quantity_in_field + $qty;
            if ($panel->quantity_in_field >= 0) {
                $panel->save();
            }
        }
    }

    public function updatePanelInventoryInStorage(int $panelId, int $qty)
    {
        $panel = Panel::find($panelId);
        if ($panel) {
            $panel->quantity = $panel->quantity + $qty;
            if ($panel->quantity >= 0) {
                $panel->save();
            }
        }
    }

    public function updatePostInventoryInField(int $postId, int $qty) {
        $post = Post::find($postId);
        if ($post) {
            $post->quantity_in_field = $post->quantity_in_field + $qty;
            if ($post->quantity_in_field >= 0) {
                $post->save();
            }
        }
    }

    public function updatePostInventoryInStorage(int $postId, int $qty) {
        $post = Post::find($postId);
        if ($post) {
            $post->quantity = $post->quantity + $qty;
            if ($post->quantity >= 0) {
                $post->save();
            }
        }
    }

    public function updateAccessoryInventoryInField($accessoryId, $qty) {
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            $accessory->quantity_in_field = $accessory->quantity_in_field + $qty;
            if ($accessory->quantity_in_field >= 0) {
                $accessory->save();
            }
        }
    }

    public function updateAccessoryInventoryInStorage($accessoryId, $qty) {
        $accessory = Accessory::find($accessoryId);
        if ($accessory) {
            $accessory->quantity = $accessory->quantity + $qty;
            if ($accessory->quantity >= 0) {
                $accessory->save();
            }
        }
    }

    public function getAllAuthorizeNetCustomerProfiles()
    {
        return User::whereNotNull('authorizenet_profile_id')
            ->get(['users.authorizenet_profile_id']);
    }

    public function checkIfZonePointsAwarded(int $zoneId, int $installerId)
    {
        $today = now()->format('Y-m-d');

        return ZonePoints::where('user_id', $installerId)
            ->where('zone_id', $zoneId)
            ->whereDate('created_at', $today)
            ->first();
    }

    public function storeZonePoints(array $data)
    {
        ZonePoints::create($data);
    }

    public function calculateTotalAfterAdjust($orderId, $orderType)
    {
        if ($orderType == 'install' || $orderType == Order::INSTALL_ORDER) {
            //Need to decrease stop numbers
            $order = Order::find($orderId);
        }

        if ($orderType == 'repair' || $orderType == Order::REPAIR_ORDER) {
            $order = RepairOrder::find($orderId);
        }

        if ($orderType == 'removal' || $orderType == Order::REMOVAL_ORDER) {
            $order = RemovalOrder::find($orderId);
        }

        if ($orderType == 'delivery' || $orderType == Order::DELIVERY_ORDER) {
            $order = DeliveryOrder::find($orderId);
        }

        $charges = $order->adjustments->sum('charge');
        $discounts = $order->adjustments->sum('discount');
        $payments = $order->payments->sum('amount');

        $total = $order->total + $charges - $discounts - $payments;
        if ($total == 0) {
            $order->fully_paid = true;
        }

        $order->total = $total;
        $order->save();
    }

    public function getDataForRenewalFee()
    {
        return $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->join('posts', function ($join) {
                $join->on('orders.post_id', 'posts.id')
                    ->where('posts.time_days', '>', 0);
            })
            ->whereDoesntHave('latest_removal', function (Builder $q) {
                $q->where('status', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->get([
                'orders.post_renewal_fee', 'orders.date_completed',
                'orders.post_id', 'orders.id as order_id', 'office.id as office_user_id',
                'agent.id as agent_user_id',
                'posts.renewal_fee', 'posts.time_days', 'posts.post_name'
            ]);
    }

    public function countOrdersByDate() {
        $tomorrow = now()->addDay()->format('y-m-d');
        $tenDayshead= now()->addDays(10)->format('y-m-d');

        $installOrders = $this->model
            ->whereBetween('orders.desired_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('orders.status', Order::STATUS_RECEIVED)
                ->orWhere('orders.status', Order::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(desired_date) as `total_orders`'), 'orders.desired_date as service_date')->groupBy('service_date');

        $repairOrders = $this->model
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->whereBetween('repair_orders.service_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('repair_orders.status', RepairOrder::STATUS_RECEIVED)
                ->orWhere('repair_orders.status', RepairOrder::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(service_date) as `total_orders`'), 'repair_orders.service_date')->groupBy('service_date');

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->whereBetween('removal_orders.service_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('removal_orders.status', RemovalOrder::STATUS_RECEIVED)
                ->orWhere('removal_orders.status', RemovalOrder::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(service_date) as `total_orders`'), 'removal_orders.service_date')->groupBy('service_date');

        $deliveryOrders = DeliveryOrder::whereBetween('delivery_orders.service_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('delivery_orders.status', DeliveryOrder::STATUS_RECEIVED)
                ->orWhere('delivery_orders.status', DeliveryOrder::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(service_date) as `total_orders`'), 'delivery_orders.service_date')->groupBy('service_date');

        return $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->get()
            ->groupBy('service_date');
    }

    public function getPostInventory(int $postId)
    {
        $post = Post::find($postId);

        $inventory = 0;
        if ($post) {
            $inventory = $post->quantity;
        }

        return $inventory;
    }

    public function getPanelInventory(int $panelId)
    {
        $panel = Panel::find($panelId);

        $inventory = 0;
        if ($panel) {
            $inventory = $panel->quantity;
        }

        return $inventory;
    }

    public function getAllPullLists($routeDate): array
    {
        $pullList = [];

        $postsPullList = $this->getAllPostsPullList($routeDate);
        $signsPullList = $this->getAllSignsPullList($routeDate);
        $accessoriesPullList = $this->getAllAccessoriesPullList($routeDate);

        $pullList['postsPullList'] = $postsPullList;
        $pullList['signsPullList'] = $signsPullList;
        $pullList['accessoriesPullList'] = $accessoriesPullList;

        return $pullList;
    }

    public function getAllPostsPullList($routeDate): array
    {
        $postsPullList = [];

        //Get posts and qty
        $installOrders = $this->model
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->where(function($q) {
                $q->whereNull('removal_orders.status')
                ->orWhere('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where('orders.status', '<>', Order::STATUS_CANCELLED)
            ->where('orders.status', '<>', Order::STATUS_COMPLETED)
            ->where('orders.status', '<>', Order::STATUS_INCOMPLETE)
            ->whereDate('orders.desired_date', '=' , $routeDate)
            ->whereNotNull('orders.assigned_to')
            ->select('posts.id as post_id', 'posts.post_name', 'posts.image_path as image_path', DB::raw('count(posts.id) as post_qty'))
            ->groupBy('posts.id', 'posts.post_name', 'posts.image_path')
            ->get();

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('posts', 'orders.post_id', 'posts.id')
            ->where('removal_orders.status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('removal_orders.service_date', '=', $routeDate)
            ->whereNotNull('removal_orders.assigned_to')
            ->select('posts.id as post_id')
            ->get();
            //dd($removalOrders);
        $postIds = [];
        foreach ($removalOrders as $removalOrder) {
            array_push($postIds, $removalOrder->post_id);
        }
        $counts = array_count_values($postIds);

        foreach ($installOrders as $key => $installOrder) {
            $postsPullList[$key]['post_name'] = $installOrder->post_name;
            $postsPullList[$key]['post_qty'] = $installOrder->post_qty;
            $postsPullList[$key]['image_path'] = $installOrder->image_path;

            $postsPullList[$key]['removal_qty'] = 0;
            if (isset($counts[$installOrder->post_id])) {
                $postsPullList[$key]['removal_qty'] = $counts[$installOrder->post_id];
            }

            //Inventory for the post
            $postsPullList[$key]['inventory'] = $this->getPostInventory((int) $installOrder->post_id);
        }

        return $postsPullList;
    }

    public function getAllSignsPullList($routeDate)//: array
    {
        $signsPullList = [];

        //$tomorrow = now()->addDay(1)->format('Y-m-d');

        //Get posts and qty
        $installOrders = $this->model
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->leftJoin('panels', 'orders.panel_id', 'panels.id')
            ->where(function($q) {
                $q->whereNull('removal_orders.status')
                ->orWhere('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where(function($q) {
                $q->where('orders.status', Order::STATUS_SCHEDULED)
                    ->orWhere('repair_orders.status', RepairOrder::STATUS_SCHEDULED);
            })
            ->where(function($q) use ($routeDate) {
                $q->whereDate('orders.desired_date', '=', $routeDate)
                    ->orWhereDate('repair_orders.service_date', '=', $routeDate);
            })
            ->where(function($q) {
                $q->whereNotNull('orders.assigned_to')
                ->orWhereNotNull('repair_orders.assigned_to');
            })
            ->select('orders.id as install_order_id', 'panels.id_number as panel_id_number', 'repair_orders.panel_id as repair_panel_id', 'orders.id as order_id', 'panels.id as panel_id', 'panels.panel_name', 'panels.image_path as image_path', DB::raw('count(panels.id) as panel_qty'))
            ->groupBy('panels.id_number', 'repair_orders.panel_id', 'orders.id', 'panels.id', 'panels.panel_name', 'panels.image_path')
            ->orderBy('panels.id_number')
            ->get();
            //dd($installOrders);

        $panelIds = [];
        foreach ($installOrders as $installOrder) {
            if (isset($installOrder->repair_panel_id) && !is_null($installOrder->repair_panel_id)) {
                array_push($panelIds, $installOrder->repair_panel_id);
            } else {
                if (! is_null($installOrder->panel_id)) {
                    array_push($panelIds, $installOrder->panel_id);
                }
            }
        }
        //dd($panelIds);
        $counts = array_count_values($panelIds);

        $removalOrders = $this->model
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->leftJoin('panels', 'orders.panel_id', 'panels.id')
            ->where('removal_orders.status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('removal_orders.service_date', '=', $routeDate)
            ->whereNotNull('removal_orders.assigned_to')
            ->select('panels.id_number as panel_id_number', 'panels.id as panel_id', 'repair_orders.panel_id as repair_panel_id')
            ->orderBy('panels.id_number')
            ->get();

        //dd($removalOrders);
        $removalPanelIds = [];
        foreach ($removalOrders as $removalOrder) {
            if (isset($removalOrder->repair_panel_id) && !is_null($removalOrder->repair_panel_id)) {
                array_push($removalPanelIds, $removalOrder->repair_panel_id);
            } else {
                if (!is_null($removalOrder->panel_id)) {
                    array_push($removalPanelIds, $removalOrder->panel_id);
                }
            }
        }
        $countRemovals = array_count_values($removalPanelIds);

        //dd($counts);
        foreach ($installOrders as $key => $installOrder) {
            $panelId = null;
            if (isset($installOrder->panel_id) || isset($installOrder->repair_panel_id)) {
                $order = Order::find($installOrder->install_order_id);
                if ($order->repair) {
                    if (!is_null($installOrder->repair_panel_id)) {
                        $panelId = $installOrder->repair_panel_id;
                        $panel = Panel::find($panelId);

                        if (isset($counts[$installOrder->repair_panel_id])) {
                            $signsPullList[$panelId]['panel_qty'] = $counts[$panelId];
                        }

                        $signsPullList[$panelId]['panel_name'] = $panel->panel_name;
                        $signsPullList[$panelId]['image_path'] = $panel->image_path;
                        $signsPullList[$panelId]['panel_id_number'] = $panel->id_number;
                    }
                } else {
                    $panelId = $installOrder->panel_id;
                    $panel = Panel::find($panelId);

                    if (isset($counts[$installOrder->panel_id])) {
                        $signsPullList[$panelId]['panel_qty'] = $counts[$panelId];
                    }

                    $signsPullList[$panelId]['panel_name'] = $panel->panel_name;
                    $signsPullList[$panelId]['image_path'] = $panel->image_path;
                    $signsPullList[$panelId]['panel_id_number'] = $panel->id_number;
                }

                //Get removals qty
                if ($panelId) {
                    $signsPullList[$panelId]['removal_qty'] = 0;
                    if (isset($installOrder->repair_panel_id) && !is_null($installOrder->repair_panel_id)) {
                        if (isset($countRemovals[$installOrder->repair_panel_id])) {
                            $signsPullList[$panelId]['removal_qty'] = $countRemovals[$installOrder->repair_panel_id];
                        }
                    } else {
                        if (isset($countRemovals[$installOrder->panel_id])) {
                            $signsPullList[$panelId]['removal_qty'] = $countRemovals[$installOrder->panel_id];
                        }
                    }
                }

                //Inventory for panel
                $signsPullList[$panelId]['inventory'] = $this->getPanelInventory((int) $installOrder->panel_id);
            }
        }

        //List panels from delivery orders
        //Get posts and qty
        $deliveryOrders = $this->deliveryOrder
            ->leftJoin('delivery_order_panels', 'delivery_orders.id', 'delivery_order_panels.delivery_order_id')
            ->join('panels', 'delivery_order_panels.panel_id', 'panels.id')
            ->where('delivery_orders.status', DeliveryOrder::STATUS_SCHEDULED)
            ->whereDate('delivery_orders.service_date', '=' , $routeDate)
            ->whereNotNull('delivery_orders.assigned_to')
            ->select('panels.id_number as panel_id_number', 'panels.id as panel_id', 'panels.panel_name', 'panels.image_path as image_path', DB::raw('count(panels.id) as panel_qty'))
            ->groupBy('panels.id_number', 'panels.id', 'panels.panel_name', 'panels.image_path')
            ->orderBy('panels.id_number')
            ->get();
            //dd($deliveryOrders);

        if ($deliveryOrders->isNotEmpty()) {
            foreach ($deliveryOrders as $key => $deliveryOrder) {
                $panelId = $deliveryOrder->panel_id;

                //Add panel qty to any existing installl/repair
                if ( isset($signsPullList[$panelId]['panel_qty']) ) {
                    $signsPullList[$panelId]['panel_qty'] = $signsPullList[$panelId]['panel_qty'] + $deliveryOrder->panel_qty;
                } else {
                    $signsPullList[$panelId]['panel_qty'] = $deliveryOrder->panel_qty;
                }

                $signsPullList[$panelId]['panel_name'] = $deliveryOrder->panel_name;
                $signsPullList[$panelId]['image_path'] = $deliveryOrder->image_path;
                $signsPullList[$panelId]['panel_id_number'] = $deliveryOrder->panel_id_number;

                $signsPullList[$panelId]['removal_qty'] = 0;
                if (isset($countRemovals[$deliveryOrder->panel_id])) {
                    $signsPullList[$panelId]['removal_qty'] = $countRemovals[$deliveryOrder->panel_id];
                }

                //Inventory for panel
                $signsPullList[$panelId]['inventory'] = $this->getPanelInventory((int) $deliveryOrder->panel_id);
            }
        }

        return $signsPullList;
    }

    public function getAllAccessoriesPullList($routeDate): array
    {
        $accessoriesPullList = [];

        //Get posts and qty
        $installOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->where(function($q) {
                $q->whereNull('removal_orders.status')
                ->orWhere('removal_orders.status', '<>', RemovalOrder::STATUS_COMPLETED);
            })
            ->where(function($q) {
                $q->where('orders.status', Order::STATUS_SCHEDULED)
                    ->orWhere('repair_orders.status', RepairOrder::STATUS_SCHEDULED);
            })
            ->where(function($q) use ($routeDate) {
                $q->whereDate('orders.desired_date', '=' , $routeDate)
                    ->orWhereDate('repair_orders.service_date', '=' , $routeDate);
            })
            ->where(function($q) {
                $q->whereNotNull('orders.assigned_to')
                ->orWhereNotNull('repair_orders.assigned_to');
            })
            //->where('orders.assigned_to', $installerId)
            ->select('orders.status as status', 'orders.id as order_id', 'office.name as office_name', 'agent.name as agent_name')
            ->get();
            //dd($installOrders);

        $accessoriesIds = [];
        foreach ($installOrders as $key => $installOrder) {
            $order = Order::find($installOrder->order_id);

            if ($order->repair) {
                $orderAccessories = $order->repair->accessories;
            } else {
                $orderAccessories = $order->accessories;
            }

            //Get all accessories Ids
            foreach ($orderAccessories as $orderAccessory) {
                if ($order->repair) {
                    if ($orderAccessory->repair_order->status !== RepairOrder::STATUS_COMPLETED) {
                        array_push($accessoriesIds, $orderAccessory->accessory_id);
                    }
                } else {
                    if ($orderAccessory->order->status !== Order::STATUS_COMPLETED) {
                        array_push($accessoriesIds, $orderAccessory->accessory_id);
                    }
                }
            }
        }

        //Count accessories
        $counts = array_count_values($accessoriesIds);

        //Take unique values from array of accessories Ids
        $accessoriesIds = array_unique($accessoriesIds);
        //dd($accessoriesIds);

        $removalOrders = $this->model
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->where('removal_orders.status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('removal_orders.service_date', '=' , $routeDate)
            ->whereNotNull('removal_orders.assigned_to')
            ->select('orders.id as order_id', 'office.name as office_name', 'agent.name as agent_name')
            ->get();

        $removalAccessoriesIds = [];
        foreach ($removalOrders as $removalOrder) {
            $order = Order::find($removalOrder->order_id);
            if ($order->repair) {
                $orderAccessories = $order->repair->accessories;
            } else {
                $orderAccessories = $order->accessories;
            }

            foreach ($orderAccessories as $orderAccessory) {
                array_push($removalAccessoriesIds, $orderAccessory->accessory_id);
            }
        }

        $countRemovals = array_count_values($removalAccessoriesIds);

        foreach ($accessoriesIds as $accessoryId) {
            $accessory = Accessory::find($accessoryId);

            $accessoriesPullList[$accessoryId]['accessory_name'] = $accessory->accessory_name;
            $accessoriesPullList[$accessoryId]['accessory_qty'] = $counts[$accessoryId];
            $accessoriesPullList[$accessoryId]['image_path'] = $accessory->image;

            //Removals
            $accessoriesPullList[$accessoryId]['removal_qty'] = 0;
            if (isset($countRemovals[$accessoryId])) {
                $accessoriesPullList[$accessoryId]['removal_qty'] = $countRemovals[$accessoryId];
            }

            //Check if need to display agent/office in pull list
            $accessoriesPullList[$accessoryId]['show_agent_office'] = false;
            $accessoriesPullList[$accessoryId]['agent_office_list'] = [];
            if ($accessory->pull_list) {
                $accessoriesPullList[$accessoryId]['show_agent_office'] = true;

                $processedAccessories = [];
                $processedRemovalAccessories = [];
                $processedNames = [];
                foreach ($installOrders as $key => $installOrder) {
                    $order = Order::find($installOrder->order_id);

                    if ($order->repair) {
                        $orderAccessories = $order->repair->accessories;
                    } else {
                        $orderAccessories = $order->accessories;
                    }

                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_qty'] = 0;
                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_id'] = $accessoryId;

                    foreach ($orderAccessories as $orderAccessory) {
                        if ($orderAccessory->accessory_id == $accessoryId) {
                            //array_push($processedAccessories, $orderAccessory->accessory_id);

                            if ($order->agent) {
                                $name = $order->agent->user->name;
                                $nameKey = array_search($name, $processedNames);
                                if ($nameKey !== false) {
                                    $key = $nameKey;
                                }
                                $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['name'] = $name;
                                if ($order->status != Order::STATUS_COMPLETED) {
                                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_qty']++;
                                }
                            } else {
                                $name = $order->office->user->name;
                                $nameKey = array_search($name, $processedNames);
                                if ($nameKey !== false) {
                                    $key = $nameKey;
                                }
                                $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['name'] = $name;
                                if ($order->status != Order::STATUS_COMPLETED) {
                                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['accessory_qty']++;
                                }
                            }

                            if ($nameKey === false) {
                                $processedNames[$key] = $name;
                            }
                        }
                    }
                }
            }
        }

        foreach ($accessoriesPullList as $pullList) {
            foreach ($pullList['agent_office_list'] as $key => $agentOfficeList) {
                //dd($agentOfficeList);
                $previousAgent = '';
                $previous= '';
                if (isset($agentOfficeList['accessory_id']) && isset($agentOfficeList['name'])) {
                    $name = $agentOfficeList['name'];
                    $accessoryId = $agentOfficeList['accessory_id'];
                    $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['removal_qty'] = 0;

                    foreach ($removalOrders as $removalOrder) {
                        $install = Order::find($removalOrder->order_id);

                        if ($install->repair) {
                            $removalOrderAccessories = $install->repair->accessories;
                        } else {
                            $removalOrderAccessories = $install->accessories;
                        }
                        foreach ($removalOrderAccessories as $removalOrderAccessory) {
                            if ($removalOrderAccessory->accessory_id == $accessoryId) {
                                if ($removalOrder->agent_name) {
                                    if ($removalOrder->agent_name == $name) {
                                        $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['removal_qty']++;
                                    }
                                } else {
                                    if ($removalOrder->office_name == $name) {
                                        $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['removal_qty']++;
                                    }
                                }
                                $accessoriesPullList[$accessoryId]['agent_office_list'][$key]['name'] = $name;
                                $previousName = $name;
                            }
                        }
                    }
                }
            }
        }

        //dd($accessoriesPullList);
        return $accessoriesPullList;
    }

    public function countOrdersByServiceDate() {
        $tomorrow = now()->addDay()->format('y-m-d');
        $tenDayshead= now()->addDays(10)->format('y-m-d');

        $installOrders = Order::whereBetween('orders.desired_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('orders.status', Order::STATUS_RECEIVED)
                ->orWhere('orders.status', Order::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(desired_date) as `total_orders`'), 'orders.desired_date as service_date')->groupBy('service_date');

        $repairOrders = Order::join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->whereBetween('repair_orders.service_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('repair_orders.status', RepairOrder::STATUS_RECEIVED)
                ->orWhere('repair_orders.status', RepairOrder::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(service_date) as `total_orders`'), 'repair_orders.service_date as service_date')->groupBy('service_date');

        $removalOrders = Order::join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->whereBetween('removal_orders.service_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('removal_orders.status', RemovalOrder::STATUS_RECEIVED)
                ->orWhere('removal_orders.status', RemovalOrder::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(service_date) as `total_orders`'), 'removal_orders.service_date as service_date')->groupBy('service_date');

        $deliveryOrders = DeliveryOrder::whereBetween('delivery_orders.service_date', [$tomorrow, $tenDayshead])
            ->where( function ($q) {
                $q->where('delivery_orders.status', DeliveryOrder::STATUS_RECEIVED)
                ->orWhere('delivery_orders.status', DeliveryOrder::STATUS_SCHEDULED);
            })
            ->select(DB::raw('count(service_date) as `total_orders`'), 'delivery_orders.service_date as service_date')->groupBy('service_date');

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $results = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->mergeBindings($union->getQuery())
            ->select('service_date', DB::raw('sum(total_orders) as `total_orders`'))
            ->groupBy('service_date')
            ->pluck('total_orders', 'service_date');

        return $results;
    }

    public function getUnfinishedOrdersForPreviousDay()
    {
        $yesterday = now()->subDay()->format('Y-m-d');

        $installOrders = $this->model->where('status', Order::STATUS_SCHEDULED)
            ->whereDate('desired_date', '<=', $yesterday)
            ->select('stop_number', 'assigned_to as installer_id', 'id', 'desired_date as service_date', DB::raw("'install' as order_type"));

        $repairOrders = RepairOrder::where('status', RepairOrder::STATUS_SCHEDULED)
            ->whereDate('service_date', '<=', $yesterday)
            ->select('stop_number', 'assigned_to as installer_id', 'id', 'service_date', DB::raw("'repair' as order_type"));

        $removalOrders = RemovalOrder::where('status', RemovalOrder::STATUS_SCHEDULED)
            ->whereDate('service_date', '<=', $yesterday)
            ->select('stop_number', 'assigned_to as installer_id', 'id', 'service_date', DB::raw("'removal' as order_type"));

        $deliveryOrders = DeliveryOrder::where('status', DeliveryOrder::STATUS_SCHEDULED)
            ->whereDate('service_date', '<=', $yesterday)
            ->select('stop_number', 'assigned_to as installer_id', 'id', 'service_date', DB::raw("'delivery' as order_type"));

        $union = $installOrders
            ->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $unfinishedOrders = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->mergeBindings($union->getQuery())
            ->select('installer_id', 'id', 'service_date', 'order_type')
            ->orderByDesc('stop_number')
            ->orderBy('service_date')
            ->get();

        return $unfinishedOrders;
    }

    public function changeUnfinishedOrderServiceDate(string $serviceDate, object $unfinishedOrder)
    {
        switch ($unfinishedOrder->order_type) {
            case 'install':
                $order =  Order::find($unfinishedOrder->id);
                $order->desired_date = $serviceDate;
                $order->save();
                break;
            case 'repair':
                $order =  RepairOrder::find($unfinishedOrder->id);
                $order->service_date = $serviceDate;
                $order->save();
                break;
            case 'removal':
                $order =  RemovalOrder::find($unfinishedOrder->id);
                $order->service_date = $serviceDate;
                $order->save();
                break;
            case 'delivery':
                $order =  DeliveryOrder::find($unfinishedOrder->id);
                $order->service_date = $serviceDate;
                $order->save();
                break;
        }
    }

    public function changeUnfinishedOrderStopNumber($stopNumber, object $unfinishedOrder)
    {
        switch ($unfinishedOrder->order_type) {
            case 'install':
                $order =  Order::find($unfinishedOrder->id);
                $order->stop_number = $stopNumber;
                $order->save();
                break;
            case 'repair':
                $order =  RepairOrder::find($unfinishedOrder->id);
                $order->stop_number = $stopNumber;
                $order->save();
                break;
            case 'removal':
                $order =  RemovalOrder::find($unfinishedOrder->id);
                $order->stop_number = $stopNumber;
                $order->save();
                break;
            case 'delivery':
                $order =  DeliveryOrder::find($unfinishedOrder->id);
                $order->stop_number = $stopNumber;
                $order->save();
                break;
        }
    }

    public function checkOrderPickupSameAddress(
        string $address,
        string $lat,
        string $lng,
        Office $office,
        int $agentId,
        int $orderId
    ) {
        $query = $this->model->where('status', '<>', Order::STATUS_CANCELLED)
            ->where('office_id', $office->id);
            if ($agentId > 0) {
                $query = $query->where('agent_id', $agentId);
            }
            if ($orderId > 0) {
                $query = $query->where('id', $orderId);
            }
            $query = $query->where(function($q) use ($address, $lat, $lng) {
                $q->where('address', $address)
                ->orWhere( function($q2) use ($lat, $lng) {
                    $q2->where('latitude', $lat)
                    ->where('longitude', $lng);
                });
            })
            ->exists();

        return $query;
    }
}
