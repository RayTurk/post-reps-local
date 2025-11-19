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
    });

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

    $invoicePayments = InvoicePayments::whereMonth('created_at', $month)
      ->whereYear('created_at', $year)
      ->count();

    $countPayments = $invoicePayments;

    return $countPayments;
  }

  public function sumPaymentsCurrentMonth($year)
  {
    $month = now()->month;

    $invoicePayments = InvoicePayments::whereMonth('created_at', $month)
      ->whereYear('created_at', $year)
      ->sum('total');

    $totalPayments = $invoicePayments;

    return $totalPayments;
  }

  public function countPaymentsYtd($year)
  {
    $today = Carbon::today();

    $invoicePayments = InvoicePayments::whereDate('created_at', '<=', $today)
      ->whereYear('created_at', $year)
      ->count();

    $countPayments = $invoicePayments;

    return $countPayments;
  }

  public function sumPaymentsYtd($year)
  {
    $today = Carbon::today();

    $invoicePayments = InvoicePayments::whereDate('created_at', '<=', $today)
      ->whereYear('created_at', $year)
      ->sum('total');

    $totalPayments = $invoicePayments;

    return $totalPayments;
  }

  public function getMonthlyPayments($year)
  {
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
      ->join('office_user as office', 'offices.user_id', 'office.id')
      ->leftJoin('agents', 'agents.id', 'orders.agent_id')
      ->leftJoin('users as agent', 'agents.user_id', 'agent.id')
      ->select(
        'orders.id',
        'orders.order_number',
        'orders.address',
        'orders.date_completed',
        'orders.status',
        'office.name as office_name',
        'agent.name as agent_name',
        DB::raw("'install' as order_type"),
        DB::raw("(orders.post_points + orders.accessory_points + orders.zone_points + orders.install_points + orders.points_adjustment) as installer_points"),
        'installer.name as installer_name',
        'installer.comments as installer_comments',
        'orders.installer_balance'
      );

    $repairOrders = Order::query()
      ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
      ->join('users as installer', 'repair_orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'orders.office_id')
      ->join('office_user as office', 'offices.user_id', 'office.id')
      ->leftJoin('agents', 'agents.id', 'orders.agent_id')
      ->leftJoin('users as agent', 'agents.user_id', 'agent.id')
      ->select(
        'repair_orders.id',
        'orders.order_number',
        'orders.address',
        'repair_orders.date_completed',
        'repair_orders.status',
        'office.name as office_name',
        'agent.name as agent_name',
        DB::raw("'repair' as order_type"),
        DB::raw("(repair_orders.post_points + repair_orders.accessory_points + repair_orders.zone_points + repair_orders.repair_points + repair_orders.points_adjustment) as installer_points"),
        'installer.name as installer_name',
        'installer.comments as installer_comments',
        'repair_orders.installer_balance'
      );

    $removalOrders = Order::query()
      ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
      ->join('users as installer', 'removal_orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'orders.office_id')
      ->join('office_user as office', 'offices.user_id', 'office.id')
      ->leftJoin('agents', 'agents.id', 'orders.agent_id')
      ->leftJoin('users as agent', 'agents.user_id', 'agent.id')
      ->select(
        'removal_orders.id',
        'orders.order_number',
        'orders.address',
        'removal_orders.date_completed',
        'removal_orders.status',
        'office.name as office_name',
        'agent.name as agent_name',
        DB::raw("'removal' as order_type"),
        DB::raw("(removal_orders.post_points + removal_orders.accessory_points + removal_orders.zone_points + removal_orders.removal_points + removal_orders.points_adjustment) as installer_points"),
        'installer.name as installer_name',
        'installer.comments as installer_comments',
        'removal_orders.installer_balance'
      );

    $deliveryOrders = DeliveryOrder::query()
      ->join('users as installer', 'delivery_orders.assigned_to', 'installer.id')
      ->join('offices', 'offices.id', 'delivery_orders.office_id')
      ->join('office_user as office', 'offices.user_id', 'office.id')
      ->leftJoin('agents', 'agents.id', 'delivery_orders.agent_id')
      ->leftJoin('users as agent', 'agents.user_id', 'agent.id')
      ->select(
        'delivery_orders.id',
        'delivery_orders.order_number',
        'delivery_orders.address',
        'delivery_orders.date_completed',
        'delivery_orders.status',
        'office.name as office_name',
        'agent.name as agent_name',
        DB::raw("'delivery' as order_type"),
        DB::raw("(delivery_orders.post_points + delivery_orders.accessory_points + delivery_orders.zone_points + delivery_orders.delivery_points + delivery_orders.points_adjustment) as installer_points"),
        'installer.name as installer_name',
        'installer.comments as installer_comments',
        'delivery_orders.installer_balance'
      );

    $union = $installOrders->unionAll($repairOrders)
      ->unionAll($removalOrders)
      ->unionAll($deliveryOrders);

    $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
      ->where('status', Order::STATUS_COMPLETED)
      ->select('id', 'order_number', 'address', 'office_name', 'agent_name', 'date_completed', 'installer_name', 'installer_balance', 'installer_comments', 'installer_points', 'order_type');

    $search = strtolower($_GET['search']['value']);
    $selectedInstaller = strtolower($_GET['installer_name']);
    if (!empty($selectedInstaller) && $selectedInstaller != "all installers") {
      return Datatables::of($sql)
        ->filter(function ($query) use ($selectedInstaller) {
          $query->where(function ($q) use ($selectedInstaller) {
            $query->where('installer_name', $selectedInstaller);
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
      ->with('paid', function () {
        return $this->getTotalPaid();
      })
      ->with('due', function () {
        return $this->getTotalDue();
      })
      ->with('total', function () {
        return $this->total_points = $this->paid_points + $this->due_points;
      })
      ->toJson();
  }

  public function getTotalPaid()
  {
    return $this->paid_points = InstallerPayment::where('canceled', false)->sum('amount');
  }

  public function getTotalDue()
  {
    $installer_points = $this->getTotalInstallerPoints();
    $paid_points = $this->getTotalPaid();

    return $this->due_points = $installer_points - $paid_points;
  }

  public function getTotalInstallerPoints()
  {
    $installOrders = Order::query()
      ->select(DB::raw("(orders.post_points + orders.accessory_points + orders.zone_points + orders.install_points + orders.points_adjustment) as installer_points"));

    $repairOrders = Order::query()
      ->join('repair_orders', 'orders.id', 'repair_orders.order_id')
      ->select(DB::raw("(repair_orders.post_points + repair_orders.accessory_points + repair_orders.zone_points + repair_orders.repair_points + repair_orders.points_adjustment) as installer_points"));

    $removalOrders = Order::query()
      ->join('removal_orders', 'orders.id', 'removal_orders.order_id')
      ->select(DB::raw("(removal_orders.post_points + removal_orders.accessory_points + removal_orders.zone_points + removal_orders.removal_points + removal_orders.points_adjustment) as installer_points"));

    $deliveryOrders = DeliveryOrder::query()
      ->select(DB::raw("(delivery_orders.post_points + delivery_orders.accessory_points + delivery_orders.zone_points + delivery_orders.delivery_points + delivery_orders.points_adjustment) as installer_points"));

    $union = $installOrders->unionAll($repairOrders)
      ->unionAll($removalOrders)
      ->unionAll($deliveryOrders);

    $sql = DB::table(DB::raw("({$union->toSql()}) as x"))
      ->select('installer_points');

    return $this->total_points = $sql->get()->sum('installer_points');
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
    if (!empty($selectedInstaller) && $selectedInstaller != "all installers") {
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

  /**
   * Get payment profiles that an office can use to charge an agent
   *
   * This retrieves cards that belong to the agent AND either:
   * 1. Were explicitly shared with the office (card_shared_with = office user id)
   * 2. Have no sharing restriction (card_shared_with is null)
   *
   * @param int $agentUserId - The agent's user ID
   * @param int $officeUserId - The office user ID
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getPaymentProfilesForOfficeToCharge(int $agentUserId, int $officeUserId)
  {
    return AuthorizenetPaymentProfile::where('user_id', $agentUserId)
      ->where(function ($query) use ($officeUserId) {
        $query->whereNull('card_shared_with')
          ->orWhere('card_shared_with', $officeUserId);
      })
      ->distinct()
      ->select('payment_profile_id', 'authorizenet_profile_id', 'user_id')
      ->get();
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
    }

    return $payment;
  }
}
