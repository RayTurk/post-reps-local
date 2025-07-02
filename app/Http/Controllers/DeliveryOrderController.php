<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Models\ServiceSetting;
use App\Services\OfficeService;
use App\Models\User;
use App\Http\Requests\CreateDeliveryOrder;
use App\Models\DeliveryOrder;
use App\Models\Office;
use App\Models\Agent;
use App\Models\Order;
use Illuminate\Support\Facades\Session;
use App\Models\DeliveryOrderPanel;
use App\Jobs\RefundJob;
use App\Services\PaymentService;

class DeliveryOrderController extends Controller
{
    use HelperTrait;

    protected $orderService;
    protected $notificationService;
    protected $officeService;
    protected $paymentService;

    public function __construct(
        OrderService $orderService,
        OfficeService $officeService,
        NotificationService $notificationService,
        PaymentService $paymentService
    ) {
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
        $this->officeService = $officeService;
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $authUser = auth()->user();

        $serviceSettings = ServiceSetting::first();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $offices = $this->officeService->getAll();

            return view('orders.delivery.main', compact('serviceSettings', 'offices'));
        }

        if ($authUser->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($authUser->office->id);
            return view('orders.office.delivery.main', compact('agents', 'serviceSettings'));
        }

        if ($authUser->role == User::ROLE_AGENT) {
            return view('orders.agent.delivery.main', compact('serviceSettings'));
        }
    }

    public function datatable(Request $request)
    {
        return $this->orderService->deliveryOrdersDataTable();
    }

    public function addNewSign(Request $request)
    {
        $this->orderService->addNewDeliverySign($request);
    }

    public function deleteAll()
    {
        $this->orderService->deleteAllDeliveryOrders();

        return $this->responseJsonSuccess((object)[]);
    }

    public function sendEmail(DeliveryOrder $deliveryOrder)
    {
        try {
            $this->notificationService->deliveryOrderCreated($deliveryOrder);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        Session::flash("success", "Delivery order saved successfully.");
    }

    public function cancel(DeliveryOrder $deliveryOrder)
    {
        $authUser = auth()->user();

        if ($deliveryOrder->status == DeliveryOrder::STATUS_RECEIVED) {
            //Dispatch job to void the hold in Authorize.net
            \App\Jobs\VoidCardHoldJob::dispatch($deliveryOrder);
        }

        if ($authUser->role != User::ROLE_SUPER_ADMIN) {
            $deliveryOrder->status = DeliveryOrder::STATUS_CANCELLED;
            $deliveryOrder->save();

            //Dispatch job for refund if order is paid
            if ($deliveryOrder->fully_paid) {
                RefundJob::dispatch($deliveryOrder, Order::DELIVERY_ORDER, $this->orderService);
            }
        } else {
            if ($deliveryOrder->assigned_to) {
                //Unassign order
                $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
                $deliveryOrder->save();

                $request = new \stdClass();
                $request->orderType = 'delivery';
                $request->orderId = $deliveryOrder->id;

                $this->orderService->unassignOrder($request);
            } else {
                $deliveryOrder->status = DeliveryOrder::STATUS_CANCELLED;
                $deliveryOrder->save();
            }
        }

        return true;
    }

    public function getDeliveryZone(Request $request)
    {
        return $this->orderService->getDeliveryZone($request->zoneId);
    }

    public function getOrder(DeliveryOrder $deliveryOrder)
    {
        return $deliveryOrder->with('office')
            ->with('agent')
            ->with('panels')
            ->with('adjustments')
            ->where('id', $deliveryOrder->id)
            ->first();
    }

    public function store(CreateDeliveryOrder $request)
    {
        $data = $request->all();

        $data['delivery_order_desired_date'] = strtolower(trim($data['delivery_order_desired_date'])) == "asap" ? 1 : 2;

        if ($data['delivery_order_desired_date'] == 2) {
            //Need to check past date and return error to user
            $deliveryDate = date("Y-m-d", strtotime($data['delivery_order_custom_desired_date']));
            $today = date("Y-m-d");
            if ($deliveryDate < $today) {
                return $this->responseJsonError("Cannot create order for past dates!");
            }

            $data["delivery_order_custom_desired_date"] = date("Y-m-d", strtotime($data['delivery_order_custom_desired_date']));
        } else {
            $data["delivery_order_custom_desired_date"] = null;
        }

        if ($data['create_order'] == "false") {
            return $this->update(
                $this->orderService->findDeliveryOrderById($data['delivery_order_id']),
                $data,
                $request
            );
        }

        $marker = (object) ['lat' => null, 'lng' => null];
        if (isset($data['delivery_marker_position'])) {
            if ($data['delivery_marker_position'] !== "null") {
                $marker_position = json_decode($data['delivery_marker_position']);
                $marker->lat = $marker_position->lat;
                $marker->lng = $marker_position->lng;
            }
        }

        $deliveryOrder = $this->orderService->createDeliveryOrder([
            "user_id"      => auth()->id(),
            "office_id"          => $data['office_id'],
            "agent_id"          => $data['agent_id'] ? $data['agent_id'] : null,
            "address"            => $data['delivery_order_address'],
            "service_date_type"  => $data['delivery_order_desired_date'],
            "service_date"       => $data["delivery_order_custom_desired_date"],
            "delivery_fee"        => $data['delivery_order_fee'],
            "zone_fee"           => $data['delivery_order_zone_fee'],
            "rush_fee"              => $data['delivery_order_rush_fee'],
            "total"           => $data['total'],
            "comment"          => $data['delivery_order_comment'],
            "latitude"           => $marker->lat,
            "longitude"          => $marker->lng,
            "zone_id"            => $data['zone_id'],
        ]);

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['delivery_order_id'] = $deliveryOrder->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertDeliveryAdjustments($insertData);
            }
        }

        //Sign panels
        $insertData = [];
        $signPanels = json_decode($data['signPanels']);
        //return $signPanels;
        if (count($signPanels->pickup->panel)) {
            foreach ($signPanels->pickup->panel as $key => $val) {
                if ( ! is_null($val)) {
                    $insertData[$key]['delivery_order_id'] = $deliveryOrder->id;
                    $insertData[$key]['panel_id'] = $val;
                    $insertData[$key]['quantity'] = $signPanels->pickup->qty[$key];
                    $insertData[$key]['pickup_delivery'] = DeliveryOrderPanel::PICKUP;
                }
            }
            $this->orderService->massInsertDeliveryPanels($insertData);
        }
        $insertData = [];
        if (count($signPanels->dropoff->panel)) {
            foreach ($signPanels->dropoff->panel as $key => $val) {
                if ( ! is_null($val)) {
                    $insertData[$key]['delivery_order_id'] = $deliveryOrder->id;
                    $insertData[$key]['panel_id'] = $val;
                    $insertData[$key]['quantity'] = $signPanels->dropoff->qty[$key];
                    $insertData[$key]['pickup_delivery'] = DeliveryOrderPanel::DROPOFF;
                }
            }
            $this->orderService->massInsertDeliveryPanels($insertData);
        }

        //New signs
        if ($data['countNewSigns'] > 0) {
            $this->orderService->addNewDeliverySign($request, $deliveryOrder, $data['countNewSigns']);
        }

        $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
        $deliveryOrder->action_needed = false;
        $deliveryOrder->save();

        $needPayment = false;

        if ($deliveryOrder->office && ! $deliveryOrder->agent) {
            if ($deliveryOrder->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($deliveryOrder->total > 0) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_INCOMPLETE;
                    $deliveryOrder->action_needed = true;
                    $deliveryOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
                $deliveryOrder->save();
            }
        }

        if ($deliveryOrder->agent) {
            if ($deliveryOrder->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($deliveryOrder->total > 0) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_INCOMPLETE;
                    $deliveryOrder->action_needed = true;
                    $deliveryOrder->save();
                    $needPayment = true;
                }
            } elseif ($deliveryOrder->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY && $deliveryOrder->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($deliveryOrder->total > 0) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_INCOMPLETE;
                    $deliveryOrder->action_needed = true;
                    $deliveryOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
                $deliveryOrder->save();
            }
        }

        if ($deliveryOrder->total == 0) {
            $needPayment = false;
        }



        //set to_be_invoiced to true and send email
        if ( ! $needPayment) {
            $deliveryOrder->update([
                'to_be_invoiced' => true
            ]);
            try {
                $this->notificationService->deliveryOrderCreated($deliveryOrder);
            } catch (Throwable $e) {
                logger()->error($e->getMessage());
            }
        }

        $deliveryOrder->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($deliveryOrder->needPayment) {
            $billing = $this->paymentService->getBillingDetails($deliveryOrder);
        }

        Session::flash("success", "Delivery order created successfully.");

        return response()->json(compact('deliveryOrder', 'billing'));
    }

    public function update(DeliveryOrder $deliveryOrder, $data, $request)
    {
        $marker = (object) ['lat' => null, 'lng' => null];
        if (isset($data['delivery_marker_position'])) {
            if ($data['delivery_marker_position'] !== "null") {
                $marker_position = json_decode($data['delivery_marker_position']);
                $marker->lat = $marker_position->lat;
                $marker->lng = $marker_position->lng;
            }
        }

        $deliveryOrder->update([
            "office_id"          => $data['office_id'],
            "agent_id"          => $data['agent_id'] ? $data['agent_id'] : null,
            "address"            => $data['delivery_order_address'],
            "service_date_type"  => $data['delivery_order_desired_date'],
            "service_date"       => $data["delivery_order_custom_desired_date"],
            "delivery_fee"        => $data['delivery_order_fee'],
            "zone_fee"           => $data['delivery_order_zone_fee'],
            "rush_fee"              => $data['delivery_order_rush_fee'],
            "total"           => $data['total'],
            "comment"          => $data['delivery_order_comment'],
            "latitude"           => $marker->lat,
            "longitude"          => $marker->lng,
            "zone_id"            => $data['zone_id'],
        ]);

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            $this->orderService->deleteDeliveryAdjustments((int) $deliveryOrder->id);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['delivery_order_id'] = $deliveryOrder->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertDeliveryAdjustments($insertData);
            }
        }

        //Sign panels
        $insertData = [];
        $signPanels = json_decode($data['signPanels']);
        //return $signPanels;
        $this->orderService->deleteDeliveryPanels((int) $deliveryOrder->id);
        if (count($signPanels->pickup->panel)) {
            foreach ($signPanels->pickup->panel as $key => $val) {
                if ( ! is_null($val)) {
                    $insertData[$key]['delivery_order_id'] = $deliveryOrder->id;
                    $insertData[$key]['panel_id'] = $val;
                    $insertData[$key]['quantity'] = $signPanels->pickup->qty[$key];
                    $insertData[$key]['pickup_delivery'] = DeliveryOrderPanel::PICKUP;
                }
            }
            $this->orderService->massInsertDeliveryPanels($insertData);
        }
        $insertData = [];
        if (count($signPanels->dropoff->panel)) {
            foreach ($signPanels->dropoff->panel as $key => $val) {
                if ( ! is_null($val)) {
                    $insertData[$key]['delivery_order_id'] = $deliveryOrder->id;
                    $insertData[$key]['panel_id'] = $val;
                    $insertData[$key]['quantity'] = $signPanels->dropoff->qty[$key];
                    $insertData[$key]['pickup_delivery'] = DeliveryOrderPanel::DROPOFF;
                }
            }
            $this->orderService->massInsertDeliveryPanels($insertData);
        }

        //New signs
        if ($data['countNewSigns'] > 0) {
            $this->orderService->addNewDeliverySign($request, $deliveryOrder, $data['countNewSigns']);
        }

        $needPayment = false;

        $deliveryOrder->action_needed = false;
        $deliveryOrder->save();

        if ($deliveryOrder->office && ! $deliveryOrder->agent) {
            if (
                $deliveryOrder->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $deliveryOrder->status != DeliveryOrder::STATUS_RECEIVED
            ) {
                if ($deliveryOrder->total > 0) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_INCOMPLETE;
                    $deliveryOrder->action_needed = true;
                    $deliveryOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
                $deliveryOrder->save();
            }
        }

        if ($deliveryOrder->agent) {
            if (
                $deliveryOrder->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $deliveryOrder->status != DeliveryOrder::STATUS_RECEIVED
            ) {
                if ($deliveryOrder->total > 0) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_INCOMPLETE;
                    $deliveryOrder->action_needed = true;
                    $deliveryOrder->save();
                    $needPayment = true;
                }
            } elseif (
                $deliveryOrder->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY
                && $deliveryOrder->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $deliveryOrder->status != DeliveryOrder::STATUS_RECEIVED
            ) {
                if ($deliveryOrder->total > 0) {
                    $deliveryOrder->status = DeliveryOrder::STATUS_INCOMPLETE;
                    $deliveryOrder->action_needed = true;
                    $deliveryOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
                $deliveryOrder->save();
            }
        }

        if ($deliveryOrder->total == 0) {
            $needPayment = false;
            $deliveryOrder->status = DeliveryOrder::STATUS_RECEIVED;
            $deliveryOrder->fully_paid = true;
            $deliveryOrder->save();
        }

        $toBeInvoiced = $this->orderService->isInvoiced($deliveryOrder);
        if ( ! $needPayment && $toBeInvoiced) {
            $deliveryOrder->update([
                'to_be_invoiced' => true
            ]);
        }

        $deliveryOrder->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($deliveryOrder->needPayment) {
            $billing = $this->paymentService->getBillingDetails($deliveryOrder);
        }

        Session::flash("success", "Delivery order updated successfully.");

        return response()->json(compact('deliveryOrder', 'billing'));
    }

    public function markCompleted(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->status = DeliveryOrder::STATUS_COMPLETED;
        $deliveryOrder->save();

        return true;
    }
}
