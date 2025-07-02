<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RemoveCardJob;
use App\Models\Agent;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\RemovalOrder;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoicePayments;
use App\Models\Accessory;
use App\Models\AgentEmailSettings;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Database\Eloquent\Builder;

class AgentService
{
    protected $model;
    protected $paymentModel;

    public function __construct(Agent $model)
    {
        $this->model = $model;
        $this->paymentModel = new Payment();
    }

    public function create(array $attributes)
    {
        if (! $attributes['agent_office']) {
            $attributes['agent_office'] = 92; //Default No Office
        }

        return $this->model->create($attributes);
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

    public function findById(int $id): Agent
    {
        return $this->model->findOrFail($id);
    }

    public function getOne($id)
    {
        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state', 'users.region_id', 'users.first_name', 'users.last_name'];
        $officeColumns = ['agents.*'];
        return  $this->model->join('users', 'users.id', 'agents.user_id')
            ->where('agents.id', $id)
            ->select(...$officeColumns, ...$userColumns)
            ->first();
    }

    public function datatable()
    {
        $userColumns = ['users.name', 'users.first_name', 'users.last_name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $agentsColumns = ['agents.*'];
        $officeColumns = ['office_users.name as office_name', 'office_users.phone as office_phone'];
        $query = $this->model
            ->join('users', 'users.id', 'agents.user_id')
            ->leftJoin('offices', 'offices.id', 'agents.agent_office')
            ->leftJoin('users as office_users', 'office_users.id', 'offices.user_id')
            ->orderBy('users.name')
            ->select(...$agentsColumns, ...$userColumns, ...$officeColumns);


        $search = $_GET['search']['value'];
        $isNoOffice = str_starts_with(strtolower($search), 'no of');
        if ($isNoOffice) {
            $response = DataTables::eloquent($query)->filter(function ($query) {
                $query->where('office_users.name', null);
                return $query;
            })->toJson();
            session(['agents' => $response]);
            return $response;
        } else {
            $response = DataTables::eloquent($query)->toJson();
            session(['agents' => $response]);
            return  $response;
        }
    }

    public function post_agents()
    {
        $post_id = (int) ($_GET['agent_id'] ?? 0);
        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $postAgentsColumns = ['post_agents.agent_id', 'post_agents.price as post_agent_price', 'post_agents.access', 'post_agents.locked'];
        $agentsColumns = ['agents.*'];

        $query = $this->model->join('users', 'users.id', 'agents.user_id')
            ->leftJoin('post_agents', function ($join) use ($post_id) {
                $join->on('agents.id', 'post_agents.agent_id')
                    ->where('post_agents.post_id', $post_id);
            })
            ->where('agents.inactive', 0)
            ->orderBy('users.name')
            ->select(...$agentsColumns, ...$userColumns, ...$postAgentsColumns);


        return DataTables::eloquent($query)->toJson();
    }

    public function accessory_agents()
    {
        $accessory_id = (int) ($_GET['accessory_id'] ?? 0);
        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $accessoryAgentsColumns = ['accessory_agents.agent_id', 'accessory_agents.price as post_agent_price', 'accessory_agents.access', 'accessory_agents.locked'];
        $agentsColumns = ['agents.*'];

        $query = $this->model->join('users', 'users.id', 'agents.user_id')
            ->leftJoin('accessory_agents', function ($join) use ($accessory_id) {
                $join->on('agents.id', 'accessory_agents.agent_id')
                    ->where('accessory_agents.accessory_id', $accessory_id);
            })
            ->where('agents.inactive', 0)
            ->orderBy('users.name')
            ->select(...$agentsColumns, ...$userColumns, ...$accessoryAgentsColumns);


        return DataTables::eloquent($query)->toJson();
    }

    public function destroyAll()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $this->model->truncate();
        (new UserService(new User))->deleteWhereRole(User::ROLE_AGENT);

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        return true;
    }

    public function datatableRecentOrders()
    {
        $removals = DB::raw("(select * from removal_orders where id in (select max(id) from removal_orders group by order_id)) removal_orders");

        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removal_orders.order_id');
            })
            ->select(DB::raw("'none' as repair_status"), 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removal_orders.order_id');
            })
            ->select('repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $removalOrders = Order::query()
            ->join($removals, function ($join) {
                $join->on('orders.id', 'removal_orders.order_id');
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as repair_status"), 'removal_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as repair_status"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('repair_status', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'office_user_id', 'agent_user_id');

        // $search = strtolower($_GET['search']['value']);
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
                            ->where('status', '<>', Order::STATUS_CANCELLED)
                            ->where('agent_user_id', auth()->id);

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
                            $query->orderBy('updated_at', 'desc');
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
                ->where('agent_user_id', auth()->id())
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
                $query->orderBy('updated_at', 'desc');
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
                ->where('agent_user_id', auth()->id())
                ->where('delivery_status', '<>', DeliveryOrder::STATUS_COMPLETED)
                ->where('status', '<>', Order::STATUS_CANCELLED);

                return $query;
            })
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderBy('updated_at', 'desc');
            })
            ->make(true);
    }

    public function repairOrdersDatatable()
    {
        $data = Order::query()
            ->with('repair')
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDoesntHave('removal', function (Builder $query) {
                $query->where('status', '<>', RemovalOrder::STATUS_CANCELLED);
            })
            ->whereDoesntHave('repair_completed')
            ->orderByDesc('updated_at')
            ->where('agent_id', auth()->user()->agent->id)
            ->select('orders.*');

        /*return $data = Datatables::eloquent($data)
        ->addColumn('repair_status', function (Order $order) {
            return $order->repair->status;
        })
        ->addColumn('repair_order_number', function (Order $order){
            return $order->repair->order_number;
        })
        ->toJson();*/

        return Datatables::eloquent($data)->toJson();
    }

    public function removalOrdersDatatable()
    {
        $data = Order::query()
            ->with('removal')
            ->where('status', Order::STATUS_COMPLETED)
            ->whereDoesntHave('repair', function (Builder $query) {
                $query->where('status', '<>', RepairOrder::STATUS_CANCELLED)
                    ->where('status', '<>', RepairOrder::STATUS_COMPLETED);
            })
            ->whereDoesntHave('removal_completed')
            ->orderByDesc('updated_at')
            ->where('agent_id', auth()->user()->agent->id)
            ->select(['orders.*']);

        /*return Datatables::eloquent($data)
        ->addColumn('removal_status', function (Order $order) {
            return $order->removal->status;
        })
        ->addColumn('removal_order_number', function (Order $order){
            return $order->removal->order_number;
        })
        ->toJson();*/

        return Datatables::eloquent($data)->toJson();
    }

    public function deliveryOrdersDatatable()
    {
        $data = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
        ->join('users as office', 'office.id', 'offices.user_id')
        ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
        ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
        ->orderBy('updated_at', "DESC")
        ->where('status', '<>', DeliveryOrder::STATUS_COMPLETED)
        ->where('agent_id', auth()->user()->agent->id)
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
                                $query->where('delivery_orders.status', DeliveryOrder::STATUS_RECEIVED)
                                    ->where('agent_id', auth()->user()->agent->id);
                                break;
                            case "incomplete":
                                $query->where('delivery_orders.status', DeliveryOrder::STATUS_INCOMPLETE)
                                    ->where('agent_id', auth()->user()->agent->id);
                                break;
                            case "scheduled":
                                $query->where('delivery_orders.status', DeliveryOrder::STATUS_SCHEDULED)
                                    ->where('agent_id', auth()->user()->agent->id);
                                break;
                        }
                        return $query;
                    })->toJson();
                }
            }
        }
        return Datatables::eloquent($data)->toJson();
    }

    public function datatableOrderStatusActive()
    {
		$removals = DB::raw("(select * from removal_orders where id in (select max(id) from removal_orders group by order_id)) removals");

        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->select('orders.action_needed as action_needed', DB::raw("'none' as repair_status"), 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->select('repair_orders.action_needed as action_needed', 'repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $removalOrders = Order::query()
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removals.action_needed as action_needed', DB::raw("'none' as repair_status"), 'removals.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'removal' as order_type"), 'removals.updated_at as updated_at', 'removals.id', 'orders.address', 'removals.service_date_type as desired_date_type', 'removals.service_date as desired_date', 'removals.status', 'removals.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.action_needed as action_needed', DB::raw("'none' as repair_status"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('action_needed');

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('action_needed', 'repair_status', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'office_user_id', 'agent_user_id');

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
                            ->where('agent_user_id', auth()->user()->id)
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
                ->where('agent_user_id', auth()->user()->id)
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
                ->where('agent_user_id', auth()->user()->id)
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
        $installOrders = Order::query()
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $removalOrders = Order::query()
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id', 'agents.user_id as agent_user_id');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('updated_at');
            //return $union->toSql();

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'office_user_id', 'agent_user_id')
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
                            })->where('agent_user_id', auth()->user()->id);

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
                })
                ->where('agent_user_id', auth()->user()->id);

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
                $query->where(function ($q) {
                    $q->where('removal_status', RemovalOrder::STATUS_COMPLETED)
                    ->orWhere('delivery_status', DeliveryOrder::STATUS_COMPLETED)
                    ->orWhere('status', Order::STATUS_CANCELLED);
                })
                ->where('agent_user_id', auth()->user()->id);
                // $query->where('removal_status', RemovalOrder::STATUS_COMPLETED)
                // ->orWhere('delivery_status', DeliveryOrder::STATUS_COMPLETED)
                // ->orWhere('status', Order::STATUS_CANCELLED);

                return $query;
            })
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderBy('updated_at', 'desc');
            })
            ->make(true);
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
        ->select('invoices.*', 'office.name as office_name', 'agent.name as agent_name')
        ->where('agent_id', auth()->user()->agent->id)
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

    public function paymentsDatatable()
    {

        $search = strtolower($_GET['search']['value']);
        $limit = intval($_GET['length']);
        $start = intval($_GET['start']);

        try {
            $date = Carbon::createFromDate($search)->toDateString();
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            $date = "";
        }

        $payments = InvoicePayments::query()
            ->with('invoice')
            ->latest()
            ->whereHas('invoice', function ($query) {
                return $query->where('agent_id', auth()->user()->agent->id);
            });

        if (!empty($search)) {
            $payments = InvoicePayments::query()
            ->offset($start)
            ->limit($limit)
            ->with('invoice')
            ->latest()
            ->whereHas('invoice', function ($query) {
                return $query->where('agent_id', auth()->user()->agent->id);
            })
            ->where('check_number', "$search")
            ->orWhere('card_last_four', "$search")
            ->orWhereDate('created_at', 'LIKE', "$date")
            ->orWhereHas("invoice", function ($query) use ($search, $date) {
                return $query->where('invoice_number', 'LIKE', ["%{$search}%"])->orWhereDate('created_at', 'LIKE', "$date");
            })
            ->orWhereHas("invoice.office.user", function ($query) use ($search) {
                return $query->where('name', 'LIKE', ["%{$search}%"]);
            })
            ->orWhereHas("invoice.agent.user", function ($query) use ($search) {
                return $query->where('name', 'LIKE', ["%{$search}%"]);
            })
            ->get();

            return Datatables::collection($payments)->filter(function ($instance) {
                $instance->collection = $instance->collection->filter(function ($row) {
                    return $row['invoice']['agent_id'] == auth()->user()->agent->id ? true : false;
                });
            })->skipPaging()->setFilteredRecords(InvoicePayments::count())->setTotalRecords(InvoicePayments::count())->toJson();
        }

        return Datatables::of($payments)->make();
    }

    public function getAccessoriesOrderByListingorderAndName()
    {
        $data = Accessory::with('accessory_offices')
            ->with('accessory_agents')
            ->where('status', 1)
            ->orderBy('listing_order', 'asc')
            ->orderBy('accessory_name', 'asc')
            ->whereNotNull("listing_order")
            ->with('office_access')
            ->get();
        $nulls = Accessory::whereNull('listing_order')
            ->with('office_access')
            ->where('status', 1)
            ->get();
        foreach ($nulls as $n) {
            $data->push($n);
        }
        return $data;
    }

    public function getAgentAcessories(Agent $agent)
    {
        $accessories = $this->getAccessoriesOrderByListingorderAndName()
            ->filter(function ($accessory) use ($agent) {
                 //Must add accessories that agents office has access to
                $checkAccess = $accessory->accessory_offices->where('office_id', $agent->agent_office)->first();
                if ($checkAccess) {
                    if ($checkAccess->access == true) {
                        if ($checkAccess->locked == true) {
                            $accessory->price = $checkAccess->price;
                        }

                        return $accessory;
                    }
                }

                $checkAccess = $accessory->accessory_agents->where('agent_id', $agent->id)->first();
                if ($checkAccess) {
                    if ($checkAccess->access == true) {
                        if ($checkAccess->locked == true) {
                            $accessory->price = $checkAccess->price;
                        }

                        return $accessory;
                    }
                } else {
                    if ($accessory->default == 1) {
                        return $accessory;
                    }
                }

                return null;
            });

        return $accessories;
    }

    public function emailSettingsDatatable($id)
    {
        $data = AgentEmailSettings::where('agent_id', $id)->orderBy('created_at', 'asc')->get();
        return Datatables::of($data)->make();
    }

    public function addNewEmail($data)
    {
        $emailCount = AgentEmailSettings::where('agent_id', $data['agent_id'])->count();
        if (isset($data['user_email'])) {
            //if the user's principal email does not exist in the table, add it.
            $userEmailExist = AgentEmailSettings::where('email', $data['user_email'])->first();

            if (!$userEmailExist) {
                return AgentEmailSettings::create([
                    'agent_id' => $data['agent_id'],
                    'email' => $data['user_email'],
                    'order' => true,
                    'accounting' => true,
                ]);
            }
        }

        if ($emailCount >= 5) {
            throw new \Exception("You can only add up to 5 email accounts per user.");
        }

        return AgentEmailSettings::create([
            'agent_id' => $data['agent_id'],
            'email' => $data['email'],
            'order' => $data['order'],
            'accounting' => $data['accounting'],
        ]);
    }

    public function updateNotification($data)
    {
        $emailSetting = AgentEmailSettings::where('agent_id', $data['agent_id'])->where('email', $data['email'])->first();

        $emailSetting->order = $data['order'];
        $emailSetting->accounting = $data['accounting'];

        return $emailSetting->save();
    }

    public function removeEmail($data)
    {
        return AgentEmailSettings::where('agent_id', $data['agent_id'])->where('email', $data['email'])->delete();
    }

    public function getAgentSavedCards($user)
    {
        //Get only unique rows because it's taking too long to load cards.
        $storedPaymentProfiles = (new PaymentService($this->paymentModel))->getUniquePaymentProfiles((int) $user->id);
        $authorizeNetCustomerId = $user->authorizenet_profile_id;

        $returnData = [];
        if ($storedPaymentProfiles->isNotEmpty()) {
            foreach ($storedPaymentProfiles as $storedPaymentProfile) {
                $paymentProfile = (new AuthorizeNetService())->getPaymentProfile(
                    $authorizeNetCustomerId,
                    $storedPaymentProfile->payment_profile_id
                );
                //info($paymentProfile);
                if (isset($paymentProfile['paymentProfile'])) {
                    $cardinfo = $paymentProfile['paymentProfile']['payment']['creditCard'];

                    if ((new Carbon($cardinfo['expirationDate']))->endOfMonth() < (new Carbon(now()))->endOfDay()) {
                        RemoveCardJob::dispatch($paymentProfile['paymentProfile']['customerPaymentProfileId'], $authorizeNetCustomerId);
                        continue;
                    }

                    $returnData[$storedPaymentProfile->payment_profile_id]['cardNumber'] = str_replace('XXXX', 'XXXX-XXXXX-XXXX-', $cardinfo['cardNumber']);
                    $returnData[$storedPaymentProfile->payment_profile_id]['cardType'] = $cardinfo['cardType'];
                    $expDateArray = explode('-', $cardinfo['expirationDate']);
                    $expDate = "{$expDateArray[1]}/{$expDateArray[0]}";
                    $returnData[$storedPaymentProfile->payment_profile_id]['expDate'] = $expDate;

                    $authPaymentProfile = (new PaymentService($this->paymentModel))->findByPaymentProfileIdAndUserId(
                        "$storedPaymentProfile->payment_profile_id",
                        (int) $user->id
                    );

                    $returnData[$storedPaymentProfile->payment_profile_id]['visibleToAgents'] = false;
                    if ($authPaymentProfile->office_card_visible_agents) {
                        $returnData[$storedPaymentProfile->payment_profile_id]['visibleToAgents'] = true;
                    }
                }
            }
        }

        return $returnData;
    }
}
