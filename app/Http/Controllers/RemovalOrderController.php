<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\FileService;
use App\Http\Requests\CreateRemovalOrder;
use App\Services\OrderAccessoryService;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use App\Models\ServiceSetting;
use App\Models\{RemovalOrder, RemovalOrderAttachment, RemovalOrderAccessory};
use App\Models\Agent;
use App\Models\Office;
use App\Jobs\RefundJob;
use App\Models\User;
use App\Services\PaymentService;

class RemovalOrderController extends Controller
{
    use HelperTrait;

    protected $orderService;
    protected $installPostAgentService;
    protected $fileService;
    protected $orderAttachmentService;
    protected $orderAccessoryService;
    protected $paymentService;
    protected $notificationService;

    public function __construct(
        OrderService $orderService,
        FileService $fileService,
        NotificationService $notificationService,
        OrderAccessoryService $orderAccessoryService,
        PaymentService $paymentService
    ) {
        $this->orderService = $orderService;
        $this->fileService = $fileService;
        $this->notificationService = $notificationService;
        $this->orderAccessoryService = $orderAccessoryService;
        $this->paymentService = $paymentService;
    }

    public function loadPage()
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $serviceSettings = ServiceSetting::first();

            return view('orders.removal.main', compact('serviceSettings'));
        }

        if ($authUser->role == User::ROLE_OFFICE) {
            $serviceSettings = ServiceSetting::first();

            return view('orders.office.removal.main', compact('serviceSettings'));
        }

        if ($authUser->role == User::ROLE_AGENT) {
            $serviceSettings = ServiceSetting::first();

            return view('orders.agent.removal.main', compact('serviceSettings'));
        }
    }

    public function removalOrdersDatatable()
    {
        return $this->orderService->removalOrdersDatatable();
    }

    public function deleteAll()
    {
        $this->orderService->deleteAllRemovalOrders();

        return $this->responseJsonSuccess((object)[]);
    }

    public function getOrderForRemovalModal(Order $order)
    {
        return $order->with('accessories')
            ->with('office')
            ->with('agent')
            ->with('post')
            ->with('adjustments')
            ->where('id', $order->id)
            ->first();
    }

    public function getRemovalZone(Order $order)
    {
        return $this->responseJsonSuccess($order->zone);
    }

    public function sendEmail(RemovalOrder $removalOrder)
    {
        try {
            $this->notificationService->removalOrderCreated($removalOrder);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        Session::flash("success", "Removal order saved successfully.");
    }

    public function cancel(RemovalOrder $removalOrder)
    {
        $authUser = auth()->user();

        if ($removalOrder->status == RemovalOrder::STATUS_RECEIVED) {
            //Dispatch job to void the hold in Authorize.net
            \App\Jobs\VoidCardHoldJob::dispatch($removalOrder);
        }

        //Remove any additional address
        if ($removalOrder->pickup_address) {
            $removalOrder->pickup_address = null;
            $removalOrder->pickup_latitude = null;
            $removalOrder->pickup_longitude = null;
        }

        if ($authUser->role != User::ROLE_SUPER_ADMIN) {
            $removalOrder->status = RemovalOrder::STATUS_CANCELLED;
            $removalOrder->save();

            //Dispatch job for refund if order is paid
            if ($removalOrder->fully_paid) {
                RefundJob::dispatch($removalOrder, Order::REMOVAL_ORDER, $this->orderService);
            }
        } else {
            if ($removalOrder->assigned_to) {
                //Unassign order
                $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
                $removalOrder->save();

                $request = new \stdClass();
                $request->orderType = 'removal';
                $request->orderId = $removalOrder->id;

                $this->orderService->unassignOrder($request);
            } else {
                $removalOrder->status = Order::STATUS_CANCELLED;
                $removalOrder->save();
            }
        }

        return true;
    }

    public function store(CreateRemovalOrder $request)
    {
        $data = $request->all();
        $data['removal_order_desired_date'] = strtolower($data['removal_order_desired_date']) == "asap" ? 1 : 2;

        if ($data['removal_order_desired_date'] == 2) {
            //Need to check past date and return error to user
            $removalDate = date("Y-m-d", strtotime($data['removal_order_custom_desired_date']));
            $today = date("Y-m-d");
            if ($removalDate < $today) {
                return $this->responseJsonError("Cannot create order for past dates!");
            }

            $data["removal_order_custom_desired_date"] = date("Y-m-d", strtotime($data['removal_order_custom_desired_date']));
        } else {
            $data["removal_order_custom_desired_date"] = null;
        }

        if ($data['create_order'] == "false") {
            return $this->update(
                $this->orderService->findRemovalOrderById($data['removal_order_id']),
                $data
            );
        }

        $serviceSettings = ServiceSetting::first();
        $total = $data['total'];
        if ($data['multiplePosts'] == "true") {
            $total = $serviceSettings->removal_fee;
        }

        $removalOrder = $this->orderService->createRemovalOrder([
            "order_id"            => $data['order_id'],
            "user_id"      => auth()->id(),
            "service_date_type"  => $data['removal_order_desired_date'],
            "service_date"       => $data["removal_order_custom_desired_date"],
            "sign_panel"           => $data['sign_panel'] == 'undefined' ? null : $data['sign_panel'],
            "removal_fee"        => $serviceSettings->removal_fee,
            "zone_fee"           => $data['removal_order_zone_fee'],
            "rush_fee"              => $data['removal_order_rush_fee'],
            "total"           => $total,
            "comment"          => $data['removal_order_comment'],
            "pickup_address" => $data['removal_order_pickup_address'] ?? null,
            "pickup_latitude" => $data['removal_order_pickup_latitude'] ?? null,
            "pickup_longitude" => $data['removal_order_pickup_longitude'] ?? null,
        ]);

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['removal_order_id'] = $removalOrder->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertRemovalAdjustments($insertData);
            }
        }

        $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
        $removalOrder->action_needed = false;
        $removalOrder->save();

        $needPayment = false;

        if ($removalOrder->order->office && ! $removalOrder->order->agent) {
            if ($removalOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($removalOrder->total > 0) {
                    $removalOrder->status = RemovalOrder::STATUS_INCOMPLETE;
                    $removalOrder->action_needed = true;
                    $removalOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
                $removalOrder->save();
            }
        }

        if ($removalOrder->order->agent) {
            if ($removalOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($removalOrder->total > 0) {
                    $removalOrder->status = RemovalOrder::STATUS_INCOMPLETE;
                    $removalOrder->action_needed = true;
                    $removalOrder->save();
                    $needPayment = true;
                }
            } elseif ($removalOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY && $removalOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($removalOrder->total > 0) {
                    $removalOrder->status = RemovalOrder::STATUS_INCOMPLETE;
                    $removalOrder->action_needed = true;
                    $removalOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
                $removalOrder->save();
            }
        }

        //Multiple posts
        if ($data['multiplePosts'] == "true") {
            $order = $this->orderService->findById($data['order_id']);
            $othersOrders = $this->orderService->getOthersOrdersSameProperty($order);
            foreach ($othersOrders as $otherOrder) {
                $newOrder = $this->orderService->createRemovalOrder([
                    "order_id"            => $otherOrder->id,
                    "user_id"      => auth()->id(),
                    "service_date_type"  => $data['removal_order_desired_date'],
                    "service_date"       => $data["removal_order_custom_desired_date"],
                    "sign_panel"           => $data['sign_panel'] == 'undefined' ? null : $data['sign_panel'],
                    "removal_fee"        => $serviceSettings->removal_fee * $serviceSettings->discount_extra_post_removal / 100,
                    "zone_fee"           => 0,
                    "rush_fee"              => 0,
                    "total"           => $serviceSettings->removal_fee * $serviceSettings->discount_extra_post_removal / 100,
                    "comment"          => $data['removal_order_comment'],
                    'parent_removal_order' => $removalOrder->id
                ]);

                $newOrder->status = $removalOrder->status;
                $newOrder->save();
            }
        }

        if ($removalOrder->total == 0) {
            $needPayment = false;
        }

        //set to_be_invoiced to true and send email
        if ( ! $needPayment) {
            $removalOrder->update([
                'to_be_invoiced' => true
            ]);
            try {
                $this->notificationService->removalOrderCreated($removalOrder);
            } catch (Throwable $e) {
                logger()->error($e->getMessage());
            }
        }

        $removalOrder->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($removalOrder->needPayment) {
            $billing = $this->paymentService->getBillingDetails($removalOrder->order);
        }

        Session::flash("success", "Removal order created successfully.");

        $removalOrder->order = $removalOrder->order;
        $removalOrder->office = $removalOrder->order->office;
        $removalOrder->agent = $removalOrder->order->agent;

        return response()->json(compact('removalOrder', 'billing'));
    }

    public function update(RemovalOrder $removalOrder, $data)
    {
        $removalOrder->update([
            "order_id"            => $data['order_id'],
            "user_id"      => auth()->id(),
            "service_date_type"  => $data['removal_order_desired_date'],
            "service_date"       => $data["removal_order_custom_desired_date"],
            "sign_panel"           => $data['sign_panel'] == 'undefined' ? null : $data['sign_panel'],
            "removal_fee"        => $data['removal_order_fee'],
            "zone_fee"           => $data['removal_order_zone_fee'],
            "rush_fee"              => $data['removal_order_rush_fee'],
            "total"           => $data['total'],
            "comment"          => $data['removal_order_comment'],
            "pickup_address" => $data['removal_order_pickup_address'] ?? null,
            "pickup_latitude" => $data['removal_order_pickup_latitude'] ?? null,
            "pickup_longitude" => $data['removal_order_pickup_longitude'] ?? null,
        ]);

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            $this->orderService->deleteRemovalAdjustments((int) $removalOrder->id);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['removal_order_id'] = $removalOrder->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertRemovalAdjustments($insertData);
            }
        }

        $needPayment = false;

        $removalOrder->action_needed = false;
        $removalOrder->save();

        if ($removalOrder->order->office && ! $removalOrder->order->agent) {
            if (
                $removalOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $removalOrder->status != RemovalOrder::STATUS_RECEIVED
            ) {
                if ($removalOrder->total > 0) {
                    $removalOrder->status = RemovalOrder::STATUS_INCOMPLETE;
                    $removalOrder->action_needed = true;
                    $removalOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $removalOrder->status = Order::STATUS_RECEIVED;
                $removalOrder->save();
            }
        }

        if ($removalOrder->order->agent) {
            if (
                $removalOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $removalOrder->status != RemovalOrder::STATUS_RECEIVED
            ) {
                if ($removalOrder->total > 0) {
                    $removalOrder->status = RemovalOrder::STATUS_INCOMPLETE;
                    $removalOrder->action_needed = true;
                    $removalOrder->save();
                    $needPayment = true;
                }
            } elseif (
                $removalOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY
                && $removalOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $removalOrder->status != RemovalOrder::STATUS_RECEIVED
            ) {
                if ($removalOrder->total > 0) {
                    $removalOrder->status = RemovalOrder::STATUS_INCOMPLETE;
                    $removalOrder->action_needed = true;
                    $removalOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
                $removalOrder->save();
            }
        }

        //Update date for all related removal orders
        $this->orderService->updateDates($removalOrder);

        if ($removalOrder->total == 0) {
            $needPayment = false;
            $removalOrder->status = RemovalOrder::STATUS_RECEIVED;
            $removalOrder->fully_paid = true;
            $removalOrder->save();
        }

        $toBeInvoiced = $this->orderService->isInvoiced($removalOrder);
        if ( ! $needPayment && $toBeInvoiced) {
            $removalOrder->refresh();
            $removalOrder->update([
                'to_be_invoiced' => true
            ]);
        }

        $removalOrder->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($removalOrder->needPayment) {
            $billing = $this->paymentService->getBillingDetails($removalOrder->order);
        }

        Session::flash("success", "Removal order updated successfully.");

        $removalOrder->order = $removalOrder->order;
        $removalOrder->office = $removalOrder->order->office;
        $removalOrder->agent = $removalOrder->order->agent;

        $removalOrder->editOrder = true;

        return response()->json(compact('removalOrder', 'billing'));
    }

    public function getOrder(RemovalOrder $removalOrder)
    {
        return $removalOrder->with('order')
            ->with('adjustments')
            ->where('id', $removalOrder->id)
            ->first();
    }

    public function countPostsAtProperty(string $address, string $lat, string $lng, Office $office, Agent $agent)
    {
        $totalPosts = $this->orderService->countPostsAtProperty($address, $lat, $lng, $office, $agent);

        return $totalPosts;
    }

    public function getOthersOrdersSameProperty(Order $order)
    {
        return $this->orderService->getOthersOrdersSameProperty($order);
    }

    public function markCompleted(RemovalOrder $removalOrder)
    {
        $removalOrder->status = RemovalOrder::STATUS_COMPLETED;
        $removalOrder->save();

        return true;
    }

    public function checkOrderSameAddress(string $address, string $lat, string $lng, Office $office, $agentId, $orderId)
    {
        $hasOrderSameAddress = $this->orderService->checkOrderPickupSameAddress(
            $address,
            $lat,
            $lng,
            $office,
            $agentId,
            (int) $orderId
        );

        if ($hasOrderSameAddress) {
            return $hasOrderSameAddress;
        }

        return '404';
    }
}
