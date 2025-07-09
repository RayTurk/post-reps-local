<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RemoveCardJob;
use App\Models\{
  InvoicePayments,
  Payment,
  RepairOrderPayment,
  RemovalOrderPayment,
  DeliveryOrderPayment,
  InstallerPayment,
  Order,
  User,
  Agent,
  Office,
  AuthorizenetPaymentProfile,
  CardRejectionCounter,
};
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Carbon\Carbon;
use DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DeliveryOrder;

class PaymentService
{
  protected $model;
  public $total_points = 0;
  public $paid_points = 0;
  public $due_points = 0;

  public function __construct(Payment $model)
  {
    $this->model = $model;
  }

  public function create(array $attributes)
  {
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

  public function findById(int $id): Payment
  {
    return $this->model->findOrFail($id);
  }
  public function getCustomerCards($customer_id)
  {
    $cards = Payment::where('customer_id', $customer_id)->get()->map(function ($p) {
      $cardLastNumber = decrypt($p->card_last_four_digits);
      $p->lastNumber = $cardLastNumber;
      $p->cardNumber = "XXXX-XXXX-XXXX-$cardLastNumber exp$p->expdate";
      return $p;
    })
      // ->groupBy('lastNumber')
    ;
    return $cards;
  }

  public function createRepairPayment(array $attributes)
  {
    return RepairOrderPayment::create($attributes);
  }

  public function createRemovalPayment(array $attributes)
  {
    return RemovalOrderPayment::create($attributes);
  }

  public function createDeliveryPayment(array $attributes)
  {
    return DeliveryOrderPayment::create($attributes);
  }

  public function countPaymentsCurrentMonth($year)
  {
    $month = now()->month;

    /*$installPayments = Payment::whereMonth('created_at', $month)
        ->count();

        $repairPayments = RepairOrderPayment::whereMonth('created_at', $month)
        ->count();

        $removalPayments = RemovalOrderPayment::whereMonth('created_at', $month)
        ->count();

        $deliveryPayments = DeliveryOrderPayment::whereMonth('created_at', $month)
        ->count();*/

    $invoicePayments = InvoicePayments::whereMonth('created_at', $month)
      ->whereYear('created_at', $year)
      ->count();

    //$countPayments = $invoicePayments + $installPayments + $repairPayments + $removalPayments + $deliveryPayments;
    $countPayments = $invoicePayments;

    return $countPayments;
  }

  public function sumPaymentsCurrentMonth($year)
  {
    $month = now()->month;

    /*$installPayments = Payment::whereMonth('created_at', $month)
        ->sum('amount');

        $repairPayments = RepairOrderPayment::whereMonth('created_at', $month)
        ->sum('amount');

        $removalPayments = RemovalOrderPayment::whereMonth('created_at', $month)
        ->sum('amount');

        $deliveryPayments = DeliveryOrderPayment::whereMonth('created_at', $month)
        ->sum('amount');*/

    $invoicePayments = InvoicePayments::whereMonth('created_at', $month)
      ->whereYear('created_at', $year)
      ->sum('total');

    //$totalPayments = $invoicePayments + $installPayments + $repairPayments + $removalPayments + $deliveryPayments;
    $totalPayments = $invoicePayments;

    return $totalPayments;
  }

  public function countPaymentsYtd($year)
  {
    $today = Carbon::today();

    /*$installPayments = Payment::whereDate('created_at', '<=', $today)
        ->count();

        $repairPayments = RepairOrderPayment::whereDate('created_at', '<=', $today)
        ->count();

        $removalPayments = RemovalOrderPayment::whereDate('created_at', '<=', $today)
        ->count();

        $deliveryPayments = DeliveryOrderPayment::whereDate('created_at', '<=', $today)
        ->count();*/

    $invoicePayments = InvoicePayments::whereDate('created_at', '<=', $today)
      ->whereYear('created_at', $year)
      ->count();

    //$countPayments = $invoicePayments + $installPayments + $repairPayments + $removalPayments + $deliveryPayments;
    $countPayments = $invoicePayments;

    return $countPayments;
  }

  public function sumPaymentsYtd($year)
  {
    $today = Carbon::today();

    /*$installPayments = Payment::whereDate('created_at', '<=', $today)
        ->sum('amount');

        $repairPayments = RepairOrderPayment::whereDate('created_at', '<=', $today)
        ->sum('amount');

        $removalPayments = RemovalOrderPayment::whereDate('created_at', '<=', $today)
        ->sum('amount');

        $deliveryPayments = DeliveryOrderPayment::whereDate('created_at', '<=', $today)
        ->sum('amount');*/

    $invoicePayments = InvoicePayments::whereDate('created_at', '<=', $today)
      ->whereYear('created_at', $year)
      ->sum('total');

    //$totalPayments = $invoicePayments + $installPayments + $repairPayments + $removalPayments + $deliveryPayments;

    $totalPayments = $invoicePayments;

    return $totalPayments;
  }

  public function getMonthlyPayments($year)
  {
    /*$installPayments = Payment::select('created_at', 'amount', DB::raw("0 as invoice_type"));

        $repairPayments = RepairOrderPayment::select('created_at', 'amount', DB::raw("0 as invoice_type"));

        $removalPayments = RemovalOrderPayment::select('created_at', 'amount', DB::raw("0 as invoice_type"));

        $deliveryPayments = DeliveryOrderPayment::select('created_at', 'amount', DB::raw("0 as invoice_type"));

        $invoicePayments = InvoicePayments::select('created_at', 'total as amount',);

        $union = $installPayments->unionAll($repairPayments)
        ->unionAll($removalPayments)
        ->unionAll($deliveryPayments)
        ->unionAll($invoicePayments);*/

    /*$startOfYear = now()->startOfYear();
        $endOfYear = now()->endOfYear();
        $monthlyPayments = InvoicePayments::whereBetween('created_at', [$startOfYear, $endOfYear])
            ->orderBy('created_at')
            ->selectRaw('UPPER(DATE_FORMAT(created_at, "%b")) as month, SUM(total) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        */

    $monthlyPayments = InvoicePayments::whereYear('created_at', $year)
      ->selectRaw('UPPER(DATE_FORMAT(created_at, "%b")) as month, SUM(total) as total')
      ->groupBy('month')
      ->pluck('total', 'month')
      ->toArray();

    return $monthlyPayments;
  }

  public function installerPointsDatatable()
  {
    $installerPayments = new InstallerPayment();

    $installOrders = Order::query()
      ->join('users as installer', 'orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'orders.office_id')
      ->join('users as office', 'office.id', 'offices.user_id')
      ->leftJoin('agents', 'agents.id', 'orders.agent_id')
      ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
      ->select(DB::raw("(orders.post_points + orders.accessory_points + orders.zone_points + orders.install_points + orders.points_adjustment) as installer_points"), 'orders.assigned_to as assigned_to', DB::raw("'install' as order_type"), 'orders.updated_at as updated_at', 'orders.id', 'orders.address', 'orders.desired_date_type', 'orders.desired_date', 'orders.status', 'orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'orders.date_completed', 'installer.name as installer_name', 'installer.balance as installer_balance', 'orders.installer_comments as installer_comments');

    $repairOrders = Order::query()
      ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
      ->join('users as installer', 'repair_orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'orders.office_id')
      ->join('users as office', 'office.id', 'offices.user_id')
      ->leftJoin('agents', 'agents.id', 'orders.agent_id')
      ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
      ->select(DB::raw("(repair_orders.post_points + repair_orders.accessory_points + repair_orders.zone_points + repair_orders.repair_points + repair_orders.points_adjustment) as installer_points"), 'repair_orders.assigned_to as assigned_to', DB::raw("'repair' as order_type"), 'repair_orders.updated_at as updated_at', 'repair_orders.id', 'orders.address', 'repair_orders.service_date_type as desired_date_type', 'repair_orders.service_date as desired_date', 'repair_orders.status', 'repair_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'repair_orders.date_completed', 'installer.name as installer_name', 'installer.balance as installer_balance', 'repair_orders.installer_comments as installer_comments');

    $removalOrders = Order::query()
      ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
      ->join('users as installer', 'removal_orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'orders.office_id')
      ->join('users as office', 'office.id', 'offices.user_id')
      ->leftJoin('agents', 'agents.id', 'orders.agent_id')
      ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
      ->select(DB::raw("(removal_orders.post_points + removal_orders.accessory_points + removal_orders.zone_points + removal_orders.removal_points + removal_orders.points_adjustment) as installer_points"), 'removal_orders.assigned_to as assigned_to', DB::raw("'removal' as order_type"), 'removal_orders.updated_at as updated_at', 'removal_orders.id', 'orders.address', 'removal_orders.service_date_type as desired_date_type', 'removal_orders.service_date as desired_date', 'removal_orders.status', 'removal_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'removal_orders.date_completed', 'installer.name as installer_name', 'installer.balance as installer_balance', 'removal_orders.installer_comments as installer_comments');

    $deliveryOrders = DeliveryOrder::query()
      ->join('users as installer', 'delivery_orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'delivery_orders.office_id')
      ->join('users as office', 'office.id', 'offices.user_id')
      ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
      ->leftJoin('users as agent', 'agent.id', 'agents.user_id')
      ->select(DB::raw("(delivery_orders.post_points + delivery_orders.accessory_points + delivery_orders.zone_points + delivery_orders.delivery_points + delivery_orders.points_adjustment) as installer_points"), 'delivery_orders.assigned_to as assigned_to', DB::raw("'delivery' as order_type"), 'delivery_orders.updated_at as updated_at', 'delivery_orders.id', 'delivery_orders.address', 'delivery_orders.service_date_type as desired_date_type', 'delivery_orders.service_date as desired_date', 'delivery_orders.status', 'delivery_orders.order_number', 'office.name as office_name', 'agent.name as agent_name', 'delivery_orders.date_completed', 'installer.name as installer_name', 'installer.balance as installer_balance', 'delivery_orders.installer_comments as installer_comments');

    $union = $installOrders->unionAll($repairOrders)
      ->unionAll($removalOrders)
      ->unionAll($deliveryOrders)
      ->orderByDesc('date_completed');

    $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
      ->where('status', Order::STATUS_COMPLETED)
      ->select('assigned_to', 'order_type', 'updated_at', 'id', 'address', 'desired_date_type', 'desired_date', 'status', 'order_number', 'office_name', 'agent_name', 'date_completed', 'installer_name', 'installer_balance', 'installer_comments', 'installer_points');

    $search = strtolower($_GET['search']['value']);
    $selectedInstaller = strtolower($_GET['installer_name']);
    if (!empty($selectedInstaller) && $selectedInstaller != "all installers") {
      return Datatables::of($sql)
        ->filter(function ($query) use ($selectedInstaller) {
          $query->where(function ($q) use ($selectedInstaller) {
            $query->where('users.name', $selectedInstaller);
          });

          return $query;
        })
        ->filter(function ($query) use ($search) {
          $query->where(function ($q) use ($search) {
            $q->where('address', 'like', "%{$search}%")
              ->orWhere('office_name', 'like', "%{$search}%")
              ->orWhere('agent_name', 'like', "%{$search}%")
              ->orWhere('installer_name', 'like', "%{$search}%")
              ->orWhere('order_type', 'like', "%{$search}%")
              ->orWhere('order_number', 'like', "%{$search}%");
          });

          return $query;
        })
        ->with('paid', function () use ($installerPayments, $selectedInstaller) {
          $payments_made =  $installerPayments::whereHas('user', function ($query) use ($selectedInstaller) {
            $query->where('users.name', $selectedInstaller);
          })
            ->where('canceled', false)
            ->sum('amount');
          return $this->paid_points = $payments_made;
        })
        ->with('due', function () use ($installerPayments, $sql, $selectedInstaller) {
          $installers_points = $sql->where('installer_name', 'like', "%{$selectedInstaller}%")->get()->sum('installer_points');
          $payments_made =  $installerPayments::whereHas('user', function ($query) use ($selectedInstaller) {
            $query->where('users.name', $selectedInstaller);
          })
            ->where('canceled', false)
            ->sum('amount');
          return $this->due_points = $installers_points - $payments_made;
        })
        ->with('total', function () {
          return $this->total_points = $this->paid_points + $this->due_points;
        })
        ->toJson();
    }

    return Datatables::of($sql)
      ->with('paid', function () use ($installerPayments) {
        return $this->paid_points =  $installerPayments::where('canceled', false)->get()->sum('amount');
      })
      ->with('due', function () use ($installerPayments, $sql) {
        $installers_points = $sql->get()->sum('installer_points');
        $total_paid = $installerPayments::where('canceled', false)->get()->sum('amount');
        return $this->due_points = $installers_points - $total_paid;
      })
      ->with('total', function () {
        return $this->total_points = $this->paid_points + $this->due_points;
      })
      ->toJson();
  }

  public function getTotalDue()
  {
    $installerPayments = new InstallerPayment();

    $installOrders = Order::query()
      ->select('orders.status', DB::raw("(orders.post_points + orders.accessory_points + orders.zone_points + orders.install_points + orders.points_adjustment) as installer_points"));

    $repairOrders = Order::query()
      ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
      ->select('repair_orders.status', DB::raw("(repair_orders.post_points + repair_orders.accessory_points + repair_orders.zone_points + repair_orders.repair_points + repair_orders.points_adjustment) as installer_points"));

    $removalOrders = Order::query()
      ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
      ->select('removal_orders.status', DB::raw("(removal_orders.post_points + removal_orders.accessory_points + removal_orders.zone_points + removal_orders.removal_points + removal_orders.points_adjustment) as installer_points"));

    $deliveryOrders = DeliveryOrder::query()
      ->select('delivery_orders.status', DB::raw("(delivery_orders.post_points + delivery_orders.accessory_points + delivery_orders.zone_points + delivery_orders.delivery_points + delivery_orders.points_adjustment) as installer_points"));

    $union = $installOrders->unionAll($repairOrders)
      ->unionAll($removalOrders)
      ->unionAll($deliveryOrders);

    $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
      ->where('status', Order::STATUS_COMPLETED)
      ->select('installer_points');

    return $sql->get()->sum('installer_points') - $installerPayments::where('canceled', false)->get()->sum('amount');
  }

  public function getAllInstallerPoints($name)
  {

    $installOrders = Order::query()
      ->join('users as installer', 'orders.assigned_to', 'installer.id')
      ->select('orders.status', DB::raw("(orders.post_points + orders.accessory_points + orders.zone_points + orders.install_points + orders.points_adjustment) as installer_points"), 'installer.name as installer_name');

    $repairOrders = Order::query()
      ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
      ->join('users as installer', 'repair_orders.assigned_to', 'installer.id')
      ->select('repair_orders.status', DB::raw("(repair_orders.post_points + repair_orders.accessory_points + repair_orders.zone_points + repair_orders.repair_points + repair_orders.points_adjustment) as installer_points"), 'installer.name as installer_name');

    $removalOrders = Order::query()
      ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
      ->join('users as installer', 'removal_orders.assigned_to', 'installer.id')
      ->select('removal_orders.status', DB::raw("(removal_orders.post_points + removal_orders.accessory_points + removal_orders.zone_points + removal_orders.removal_points + removal_orders.points_adjustment) as installer_points"), 'installer.name as installer_name');

    $deliveryOrders = DeliveryOrder::query()
      ->join('users as installer', 'delivery_orders.assigned_to', 'installer.id')
      ->select('delivery_orders.status', DB::raw("(delivery_orders.post_points + delivery_orders.accessory_points + delivery_orders.zone_points + delivery_orders.delivery_points + delivery_orders.points_adjustment) as installer_points"), 'installer.name as installer_name');

    $union = $installOrders->unionAll($repairOrders)
      ->unionAll($removalOrders)
      ->unionAll($deliveryOrders);

    $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
      ->where('status', Order::STATUS_COMPLETED)
      ->where('installer_name', 'like', "%{$name}%")
      ->select('installer_points');

    return $sql->get()->sum('installer_points');
  }

  public function installerPaymentsDatatable()
  {
    $sql = InstallerPayment::query()
      ->with('user')
      ->latest();

    $search = strtolower($_GET['search']['value']);
    $selectedInstaller = strtolower($_GET['installer_name']);
    if (!empty($selectedInstaller)) {
      $dt = Datatables::of($sql)
        ->filter(function ($query) use ($selectedInstaller) {
          $query->whereHas('user', function ($q) use ($selectedInstaller) {
            $q->where('users.name', $selectedInstaller);
          });

          return $query;
        });
      if ($search) {
        $dt = $dt->filter(function ($query) use ($search) {
          $query->where('check_number', 'like', "%{$search}%")
            ->orWhere('amount', 'like', "%{$search}%");

          return $query;
        });
      }
      $dt = $dt->with('total_due', function () use ($selectedInstaller) {
        $payments_made =  InstallerPayment::whereHas('user', function ($query) use ($selectedInstaller) {
          $query->where('users.name', $selectedInstaller);
        })
          ->where('canceled', false)
          ->sum('amount');
        return $this->getAllInstallerPoints($selectedInstaller) - $payments_made;
      })
        ->toJson();

      return $dt;
    }

    return Datatables::of($sql)
      ->with('total_due', function () {
        return $this->getTotalDue();
      })
      ->toJson();
  }

  public function getBillingDetails($order)
  {
    $billing = [];
    $authUser = auth()->user();
    $agent = $order->agent;
    $office = $order->office;

    $billing['name'] = $office->user->name;
    $billing['email'] = $office->user->email;
    $billing['address'] = $office->user->address;
    $billing['city'] = $office->user->city;
    $billing['state'] = $office->user->state;
    $billing['zipcode'] = $office->user->zipcode;
    $billing['account'] = 'office';
    $billing['cardOwner'] = $office->user;
    $billing['card_shared_with'] = null;
    $billing['office'] = [$office->user->id];

    //If office admin is logged in then run the rules for card visibility and assigment
    if ($authUser->role == User::ROLE_OFFICE) {
      info("getBillingDetails logged in as office {$office->user->name}");
      //Assign card to office by default
      if ($agent) {
        //If both office and agent pay at time of order then card will be visible
        //for both office and agent but card will be assigned to agent.
        if (
          $agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
          && $office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
        ) {
          $billing['name'] = $agent->user->name;
          $billing['email'] = $agent->user->email;
          $billing['address'] = $agent->user->address;
          $billing['city'] = $agent->user->city;
          $billing['state'] = $agent->user->state;
          $billing['zipcode'] = $agent->user->zipcode;
          $billing['account'] = 'agent';
          $billing['cardOwner'] = $agent->user;
          //Card belongs to agent but will be visible to office
          $billing['card_shared_with'] = $office->user->id;
        }

        //If office is invoiced and agent pays at time of order
        //card will be visible and assigned to agent only
        if (
          $agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
          && $office->payment_method == Office::PAYMENT_METHOD_INVOICE
        ) {
          $billing['name'] = $agent->user->name;
          $billing['email'] = $agent->user->email;
          $billing['address'] = $agent->user->address;
          $billing['city'] = $agent->user->city;
          $billing['state'] = $agent->user->state;
          $billing['zipcode'] = $agent->user->zipcode;
          $billing['account'] = 'agent';
          $billing['cardOwner'] = $agent->user;
          //Card belongs to agent and won't be visible to office
          $billing['card_shared_with'] = null;
        }
      }
    }

    //Same rules apply to when agent and superadmin are logged in
    if ($authUser->role == User::ROLE_AGENT || $authUser->role == User::ROLE_SUPER_ADMIN) {
      info("getBillingDetails logged in as agent or Admin");
      if ($agent) {
        //If both office and agent pay at time of order then card will be visible
        //and assigend to agent only
        if (
          $agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
          && $office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
        ) {
          $billing['name'] = $agent->user->name;
          $billing['email'] = $agent->user->email;
          $billing['address'] = $agent->user->address;
          $billing['city'] = $agent->user->city;
          $billing['state'] = $agent->user->state;
          $billing['zipcode'] = $agent->user->zipcode;
          $billing['account'] = 'agent';
          $billing['cardOwner'] = $agent->user;
          //Card belongs to agent and won't be visible to office
          $billing['card_shared_with'] = null;
        }

        //If office pay at time of order and agent office pay then card will be visible
        // to both office and agents and assigned to agent
        if (
          $agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY
          && $office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
        ) {
          $billing['name'] = $agent->user->name;
          $billing['email'] = $agent->user->email;
          $billing['address'] = $agent->user->address;
          $billing['city'] = $agent->user->city;
          $billing['state'] = $agent->user->state;
          $billing['zipcode'] = $agent->user->zipcode;
          $billing['account'] = 'agent';
          $billing['cardOwner'] = $agent->user;
          //Card belongs to agent but will be visible to office
          $billing['card_shared_with'] = $office->user->id;
        }

        //If office is invoiced and agent pays at time of order
        //card will be visible and assigned to agent only
        if (
          $agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
          && $office->payment_method == Office::PAYMENT_METHOD_INVOICE
        ) {
          $billing['name'] = $agent->user->name;
          $billing['email'] = $agent->user->email;
          $billing['address'] = $agent->user->address;
          $billing['city'] = $agent->user->city;
          $billing['state'] = $agent->user->state;
          $billing['zipcode'] = $agent->user->zipcode;
          $billing['account'] = 'agent';
          $billing['cardOwner'] = $agent->user;
          //Card belongs to agent and won't be visible to office
          $billing['card_shared_with'] = null;
        }
      }
    }

    return $billing;
  }

  public function getPaymentProfilesSharedByOfficeAndAgent(string $agentUserId, string $officeUserId)
  {
    return AuthorizenetPaymentProfile::where('user_id', $agentUserId)
      ->where('card_shared_with', $officeUserId)
      ->distinct()
      ->select('payment_profile_id', 'authorizenet_profile_id')
      ->get();
  }
  public function getOfficePaymentProfilesSharedWithAllAgents(
    int $officeUserId,
    int $officePayMethod,
    int $agentPayMethod
  ) {
    $authUser = auth()->user();

    if (
      $authUser->role == User::ROLE_SUPER_ADMIN
      && $officePayMethod == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
      && $agentPayMethod == Agent::PAYMENT_METHOD_OFFICE_PAY
    ) {
      return AuthorizenetPaymentProfile::where('user_id', $officeUserId)
        ->distinct()
        ->select('payment_profile_id', 'authorizenet_profile_id')
        ->get();
    } else {
      return AuthorizenetPaymentProfile::where('user_id', $officeUserId)
        ->distinct()
        ->where('office_card_visible_agents', true)
        ->select('payment_profile_id', 'authorizenet_profile_id')
        ->get();
    }
  }

  public function findPaymentProfileById(int $id)
  {
    return AuthorizenetPaymentProfile::find($id);
  }

  public function officeToggleCardVisibility(int $paymentProfileId, int $visibility, int $userId)
  {
    AuthorizenetPaymentProfile::where('payment_profile_id', $paymentProfileId)
      ->where('user_id', $userId)
      ->update(['office_card_visible_agents' => $visibility]);
  }

  public function getUniquePaymentProfiles(int $userId)
  {
    return AuthorizenetPaymentProfile::where('user_id', $userId)
      ->distinct()
      ->latest()
      ->select('payment_profile_id', 'authorizenet_profile_id', 'office_card_visible_agents')
      ->get();
  }

  public function getUserPaymentProfiles(int $userId)
  {
    return AuthorizenetPaymentProfile::where('user_id', $userId)
      ->get();
  }

  public function findByPaymentProfileIdAndUserId(string $AuthPaymentProfileId, int $userId)
  {
    return AuthorizenetPaymentProfile::where('payment_profile_id', $AuthPaymentProfileId)
      ->where('user_id', $userId)
      ->first();
  }

  public function handleCardRejected($office_id, $agent_id, $payment, $customerPaymentProfileId, $authorizenetProfileId)
  {
    $cardLastFour = substr($payment['transactionResponse']['accountNumber'], -4);

    // Add the record of the rejected card to db
    CardRejectionCounter::create([
      'office_id' => $office_id,
      'agent_id' => $agent_id,
      'card_last_four' => $cardLastFour,
    ]);

    // Check if there is 3 consecutive values, if true remove that card from the system
    $cardRejections = CardRejectionCounter::where('card_last_four', $cardLastFour)
      ->where('office_id', $office_id)
      ->when(!empty($agent_id), function ($query) use ($agent_id) {
        $query->where('agent_id', $agent_id);
      })->count();

    // 3 consecutive rejections
    if ($cardRejections >= 3) {
      RemoveCardJob::dispatch($customerPaymentProfileId, $authorizenetProfileId);
      $payment['messages']['resultCode'] = "Error";
      $payment['messages']['message'][1]['text'] = "card declined";
      $payment['transactionResponse']['errors'][0]['errorText'] = "The card xxxx-xxxx-xxxx-$cardLastFour has been declined 3 or more times and will be removed from the system. Please enter a new card number to place your order. If you continue to have issues, please contact the PostReps office.";

      return $payment;
    }

    return  $payment;
  }

  public function resetCardRejectionCounter($cardLastFour, $office_id, $agent_id)
  {
    CardRejectionCounter::where('office_id', $office_id)->when(!empty($agent_id), fn($query) => $query->where('agent_id', $agent_id))->where('card_last_four', $cardLastFour)->delete();
  }

  public function createAuthorizenetPaymentProfile(array $data)
  {
    AuthorizenetPaymentProfile::create(
      [
        'user_id' => $data['user_id'],
        'authorizenet_profile_id' => $data['authorizenet_profile_id'],
        'payment_profile_id' => $data['payment_profile_id'],
        'order_id' => $data['order_id'],
        'order_type' => $data['order_type'],
      ]
    );
  }

  /**
   * Get payment profiles that an office can charge for an agent
   * This includes:
   * 1. Cards owned by the agent that are shared with the office
   * 2. The agent's own cards (if office has permission)
   *
   * @param int $agentUserId
   * @param int $officeUserId
   * @return Collection
   */
  public function getPaymentProfilesForOfficeToCharge(int $agentUserId, int $officeUserId)
  {
    return AuthorizenetPaymentProfile::where(function ($query) use ($agentUserId, $officeUserId) {
      // Get agent's cards that are shared with this office
      $query->where('user_id', $agentUserId)
        ->where('card_shared_with', $officeUserId);
    })
      ->orWhere(function ($query) use ($agentUserId) {
        // Get agent's own cards (office can see all agent cards they manage)
        $query->where('user_id', $agentUserId)
          ->whereNull('card_shared_with');
      })
      ->distinct()
      ->select('payment_profile_id', 'authorizenet_profile_id', 'user_id')
      ->get();
  }
}
