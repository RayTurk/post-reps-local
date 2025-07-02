<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RemoveCardJob;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Order;
use App\Models\RepairOrder;
use App\Models\RemovalOrder;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\InvoicePayments;
use App\Models\Accessory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Agent;
use App\Models\OfficeEmailSettings;
use App\Models\Payment;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Contracts\DataTable;
use Carbon\Carbon;

class OfficeService
{
    protected $model;
    protected $orderService;
    protected $paymentModel;

    public function __construct(Office $model)
    {
        $this->model = $model;
        $this->paymentModel = new Payment();
    }

    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    public function getAll()
    {
        return $this->model->with('user')->get()->sortBy('user.name', SORT_REGULAR, false);
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

    public function findById(int $id): Office
    {
        return $this->model->findOrFail($id);
    }

    public function getOne($id)
    {
        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state', 'users.region_id'];
        $officeColumns = ['offices.*'];
        return  $this->model->join('users', 'users.id', 'offices.user_id')->select(...$officeColumns, ...$userColumns)->where('offices.id', $id)->first();
    }

    public function datatable()
    {
        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $officeColumns = ['offices.*', DB::raw('(SELECT count(*) FROM agents WHERE offices.id = agents.agent_office) as agents_count')];
        $query = $this->model->join('users', 'users.id', 'offices.user_id')
            ->orderBy('users.name')
            ->select(...$officeColumns, ...$userColumns);
        return DataTables::of($query)->make(true);
    }

    public function datatable_public()
    {
        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $officeColumns = ['offices.*', DB::raw('(SELECT count(*) FROM agents WHERE offices.id = agents.agent_office) as agents_count')];
        $query = $this->model->join('users', 'users.id', 'offices.user_id')
            ->where('offices.inactive', 0)
            ->where('offices.private', 0)
            ->select(...$officeColumns, ...$userColumns);

        return DataTables::of($query)->make(true);
    }

    public function destroyAll()
    {
        // delete images;
        $images = $this->model->select(DB::raw('CONCAT("/private/images/",logo_image) as logo_image'))->pluck('logo_image')->toArray();
        Storage::delete($images);
        return (new UserService(new User))->deleteWhereRole(User::ROLE_OFFICE);
    }

    public function post_offices()
    {
        $post_id = (int) ($_GET['post_id'] ?? 0);

        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $postOfficesColumns = ['post_offices.office_id', 'post_offices.locked', 'post_offices.price as post_office_price', 'post_offices.access', 'post_offices.price'];
        $officeColumns = ['offices.*'];

        $query = $this->model->join('users', 'users.id', 'offices.user_id')
            ->leftJoin('post_offices', function ($join) use ($post_id) {
                $join->on('offices.id', 'post_offices.office_id')
                    ->where('post_offices.post_id', $post_id);
            })
            ->where('offices.inactive', 0)
            ->orderBy('users.name')
            ->select(...$officeColumns, ...$userColumns, ...$postOfficesColumns);

        return DataTables::of($query)->make(true);
    }

    public function accessory_offices()
    {
        $accessory_id = (int) ($_GET['accessory_id'] ?? 0);

        $userColumns = ['users.name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $accessoryOfficesColumns = ['accessory_offices.office_id', 'accessory_offices.locked', 'accessory_offices.price as post_office_price', 'accessory_offices.access', 'accessory_offices.price'];
        $officeColumns = ['offices.*'];

        $query = $this->model->join('users', 'users.id', 'offices.user_id')
            ->leftJoin('accessory_offices', function ($join) use ($accessory_id) {
                $join->on('offices.id', 'accessory_offices.office_id')
                    ->where('accessory_offices.accessory_id', $accessory_id);
            })
            ->where('offices.inactive', 0)
            ->orderBy('users.name')
            ->select(...$officeColumns, ...$userColumns, ...$accessoryOfficesColumns);

        return DataTables::of($query)->make(true);
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
            ->select(DB::raw("'none' as repair_status"), 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removal_orders.order_id');
            })
            ->select('repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $removalOrders = Order::query()
            ->join($removals, function ($join) {
                $join->on('orders.id', 'removal_orders.order_id');
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as repair_status"), 'removal_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as repair_status"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders);

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('repair_status', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'office_user_id');

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
                            ->where('office_user_id', auth()->id())
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
                ->where('office_user_id', auth()->id())
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
                ->where('office_user_id', auth()->id())
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
        $installOrders = Order::join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->select('orders.status as install_status', 'repair_orders.status as install_repair_status', DB::raw("-1 as repair_status"), 'orders.assigned_to as assigned_to', 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id');

        $installCompleted = Order::STATUS_COMPLETED;
        $repairOrders = Order::join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("$installCompleted as install_status"), DB::raw("-1 as install_repair_status"),'repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id');

        $union = $installOrders->unionAll($repairOrders);

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->distinct()
            ->select('office_user_id', 'install_status', 'install_repair_status', 'repair_status', 'assigned_to', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'order_number', 'office_name', 'agent_name')
            ->where('install_status', Order::STATUS_COMPLETED)
            ->where('repair_status', '<>', RepairOrder::STATUS_CANCELLED)
            ->where('repair_status', '<>', RepairOrder::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->where('install_repair_status', RepairOrder::STATUS_COMPLETED)
                    ->orWhere('install_repair_status', RepairOrder::STATUS_CANCELLED)
                    ->orWhereNull('install_repair_status');
            })
            ->where(function ($q) {
                $q->where('removal_status', RemovalOrder::STATUS_CANCELLED)
                    ->orWhereNull('removal_status');
            })
            ->where('office_user_id', auth()->id());

        //info($sql->toSql());

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
                                    $query->where('repair_status', Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->where('repair_status', Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->where('repair_status', Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->where('install_status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'install');
                                    break;
                                case "repaired":
                                    $query->where('repair_status', Order::STATUS_COMPLETED)
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
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderBy('updated_at', 'desc');
            })
            ->make(true);
    }

    public function removalOrdersDatatable()
    {
        $installOrders = Order::join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->select('orders.status as install_status', 'removal_orders.status as install_removal_status', 'repair_orders.status as repair_status', 'orders.assigned_to as assigned_to', DB::raw("-1 as removal_status"), DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id');

        $installCompleted = Order::STATUS_COMPLETED;
        $removalOrders = Order::join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->select(DB::raw("$installCompleted as install_status"), DB::raw("-1 as install_removal_status"), 'repair_orders.status as repair_status', 'removal_orders.assigned_to as assigned_to', 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date',  'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'office.id as office_user_id');

        $union = $installOrders->unionAll($removalOrders);

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->distinct()
            ->select('office_user_id', 'install_status', 'install_removal_status', 'repair_status', 'assigned_to', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'order_number', 'office_name', 'agent_name')
            ->where('install_status', Order::STATUS_COMPLETED)
            ->where('removal_status', '<>', RemovalOrder::STATUS_CANCELLED)
            ->where('removal_status', '<>', RemovalOrder::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->where('install_removal_status', '<>', RemovalOrder::STATUS_RECEIVED)
                    ->where('install_removal_status', '<>', RemovalOrder::STATUS_INCOMPLETE)
                    ->where('install_removal_status', '<>', RemovalOrder::STATUS_SCHEDULED)
                    ->orWhereNull('install_removal_status');
            })
            ->where(function ($q) {
                $q->where('repair_status', RepairOrder::STATUS_CANCELLED)
                    ->orWhere('repair_status', RepairOrder::STATUS_COMPLETED)
                    ->orWhereNull('repair_status');
            })
            ->where('office_user_id', auth()->id());

        //info($sql->toSql());

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
                                    $query->where('removal_status', Order::STATUS_RECEIVED);
                                    break;
                                case "action needed":
                                    $query->where('removal_status', Order::STATUS_INCOMPLETE);
                                    break;
                                case "scheduled":
                                    $query->where('removal_status', Order::STATUS_SCHEDULED);
                                    break;
                                case "installed":
                                    $query->where('install_status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'install');
                                    break;
                                case "repaired":
                                    $query->where('removal_status', Order::STATUS_COMPLETED)
                                        ->where('order_type', 'removal');
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
            ->orderColumn('orders.address', false)
            ->order(function ($query) {
                $query->orderBy('updated_at', 'desc');
            })
            ->make(true);
    }

    public function deliveryOrdersDataTable()
    {
        $data = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->orderBy('updated_at', "DESC")
            ->where('status', '<>', DeliveryOrder::STATUS_COMPLETED)
            ->where('office_id', auth()->user()->office->id)
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
            ->select('orders.action_needed as action_needed', DB::raw("'none' as repair_status"), 'orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->select('repair_orders.action_needed as action_needed', 'repair_orders.status as repair_status', 'repair_orders.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $removalOrders = Order::query()
            ->leftJoin($removals, function ($join) {
                $join->on('orders.id', 'removals.order_id');
            })
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('removals.action_needed as action_needed', DB::raw("'none' as repair_status"), 'removals.assigned_to as assigned_to', DB::raw("'none' as delivery_status"), 'removals.status as removal_status', DB::raw("'removal' as order_type"), 'removals.updated_at as updated_at', 'removals.id', 'orders.address', 'removals.service_date_type as desired_date_type', 'removals.service_date as desired_date', 'removals.status', 'removals.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.action_needed as action_needed', DB::raw("'none' as repair_status"), 'delivery_orders.assigned_to as assigned_to', 'delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('action_needed');

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('action_needed', 'repair_status', 'assigned_to', 'delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'office_user_id');

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
                            ->where('office_user_id', auth()->user()->id)
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
                ->where('office_user_id', auth()->user()->id)
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
                ->where('office_user_id', auth()->user()->id)
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
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $repairOrders = Order::query()
            ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->leftJoin('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $removalOrders = Order::query()
            ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
            ->join('offices', 'offices.id', 'orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select(DB::raw("'none' as delivery_status"), 'removal_orders.status as removal_status', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $deliveryOrders = DeliveryOrder::join('offices', 'offices.id', 'delivery_orders.office_id')
            ->join('users as office', 'office.id', 'offices.user_id')
            ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
            ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
            ->select('delivery_orders.status as delivery_status', DB::raw("'none' as removal_status"), DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'offices.user_id as office_user_id');

        $union = $installOrders->unionAll($repairOrders)
            ->unionAll($removalOrders)
            ->unionAll($deliveryOrders)
            ->orderByDesc('updated_at');
            //return $union->toSql();

        $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
            ->select('delivery_status', 'removal_status', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'office_user_id')
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
                            })->where('office_user_id', auth()->user()->id);

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
                ->where('office_user_id', auth()->user()->id);

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
                ->where('office_user_id', auth()->user()->id);
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
        ->where('office_id', auth()->user()->office->id)
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
                return $query->where('office_id', auth()->user()->office->id);
            });

        if (!empty($search)) {
            $payments = InvoicePayments::query()
            ->offset($start)
            ->limit($limit)
            ->with('invoice')
            ->latest()
            ->whereHas('invoice', function ($query) {
                return $query->where('office_id', auth()->user()->office->id);
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
                    return $row['invoice']['office_id'] == auth()->user()->office->id ? true : false;
                });
            })->skipPaging()->setFilteredRecords(InvoicePayments::count())->setTotalRecords(InvoicePayments::count())->toJson();
        }

        return Datatables::of($payments)->make();
    }

    public function getAgents($officeId)
    {
        return Agent::join('users', 'agents.user_id', 'users.id')
            ->where('agents.agent_office', $officeId)
            ->where('agents.inactive', Agent::STATUS_ACTIVE)
            ->orderByRaw("CONCAT(users.last_name, ' ', users.first_name)")
            ->get(['agents.id', 'users.first_name', 'users.last_name']);
    }

    public function officeAgentsdatatable()
    {
        $userColumns = ['users.name', 'users.first_name', 'users.last_name', 'users.email', 'users.zipcode', 'users.address', 'users.city', 'users.phone', 'users.state'];
        $agentsColumns = ['agents.*'];
        $officeColumns = ['office_users.name as office_name', 'office_users.phone as office_phone'];
        $query = Agent::query()
            ->join('users', 'users.id', 'agents.user_id')
            ->leftJoin('offices', 'offices.id', 'agents.agent_office')
            ->leftJoin('users as office_users', 'office_users.id', 'offices.user_id')
            ->orderBy('users.name')
            ->select(...$agentsColumns, ...$userColumns, ...$officeColumns)
            ->where('agents.agent_office', auth()->user()->office->id);

        $response = DataTables::eloquent($query)->toJson();
        return  $response;
    }

    public function updateOfficeAgent($data, $agent)
    {
        DB::transaction(function () use ($agent, $data) {
            $agent->user->update($data);

            $agent->update($data);
        });
    }

    public function removeOfficeAgent($agent)
    {
        DB::transaction(function () use ($agent) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
                $agent->update([
                    'agent_office' => 92,
                    'payment_method' => $agent::PAYMENT_METHOD_DEFAULT
                ]);
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        });
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

    public function getOfficeAccessories(Office $office)
    {
        $accessories = $this->getAccessoriesOrderByListingorderAndName()
            ->filter(function ($accessory) use ($office) {
                $checkAccess = $accessory->accessory_offices->where('office_id', $office->id)->first();

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
        $data = OfficeEmailSettings::where('office_id', $id)->orderBy('created_at', 'asc')->get();
        return Datatables::of($data)->make();
    }

    public function addNewEmail($data)
    {
        $emailCount = OfficeEmailSettings::where('office_id', $data['office_id'])->count();
        if (isset($data['user_email'])) {
            //if the user's principal email does not exist in the table, add it.
            $userEmailExist = OfficeEmailSettings::where('email', $data['user_email'])->first();

            if (!$userEmailExist) {
                return OfficeEmailSettings::create([
                    'office_id' => $data['office_id'],
                    'email' => $data['user_email'],
                    'order' => true,
                    'accounting' => true,
                ]);
            }
        }

        if ($emailCount >= 5) {
            throw new \Exception("You can only add up to 5 email accounts per user.");
        }

        return OfficeEmailSettings::create([
            'office_id' => $data['office_id'],
            'email' => $data['email'],
            'order' => $data['order'],
            'accounting' => $data['accounting'],
        ]);
    }

    public function updateNotification($data)
    {
        $emailSetting = OfficeEmailSettings::where('office_id', $data['office_id'])->where('email', $data['email'])->first();

        $emailSetting->order = $data['order'];
        $emailSetting->accounting = $data['accounting'];

        return $emailSetting->save();
    }

    public function removeEmail($data)
    {
        return OfficeEmailSettings::where('office_id', $data['office_id'])->where('email', $data['email'])->delete();
    }

    public function getOfficeSavedCards($user)
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
