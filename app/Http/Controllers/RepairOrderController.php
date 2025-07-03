<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\FileService;
use App\Http\Requests\CreateRepairOrder;
use App\Services\OrderAccessoryService;
use App\Services\OrderAttachmentService;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use App\Models\ServiceSetting;
use App\Models\{RepairOrder, RepairOrderAttachment, RepairOrderAccessory};
use App\Models\Agent;
use App\Models\Office;
use App\Models\User;
use App\Jobs\RefundJob;
use App\Services\PaymentService;

class RepairOrderController extends Controller
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
        OrderAttachmentService $orderAttachmentService,
        OrderAccessoryService $orderAccessoryService,
        PaymentService $paymentService
    ) {
        $this->orderService = $orderService;
        $this->fileService = $fileService;
        $this->notificationService = $notificationService;
        $this->orderAttachmentService = $orderAttachmentService;
        $this->orderAccessoryService = $orderAccessoryService;
        $this->paymentService = $paymentService;
    }

    public function loadRepairPage()
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {

            $serviceSettings = ServiceSetting::first();

            return view('orders.repair.main', compact('serviceSettings'));
        }

        if ($authUser->role == User::ROLE_OFFICE) {

            $serviceSettings = ServiceSetting::first();

            return view('orders.office.repair.main', compact('serviceSettings'));
        }

        if ($authUser->role == User::ROLE_AGENT) {

            $serviceSettings = ServiceSetting::first();

            return view('orders.agent.repair.main', compact('serviceSettings'));
        }
    }

    public function repairOrdersDatatable()
    {
        return $this->orderService->repairOrdersDatatable();
    }

    public function getOrderForRepairModal(Order $order)
    {
        return $order->with('accessories')
            ->with('files')
            ->with('office')
            ->with('agent')
            ->with('post')
            ->with('panel')
            ->where('id', $order->id)
            ->first();
    }

    public function getRepairZone(Order $order)
    {
        return $this->responseJsonSuccess($order->zone);
    }

    public function store(CreateRepairOrder $request)
    {
        $data = $request->all();

        // handle files
        $files = [];
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'file')) {
                $request->file = $request->{$key};
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    if (!$uploadImg['success']) {
                        return $this->responseJsonError($uploadImg['msg']);
                    }

                    $files[] = [
                        'fileName' => $uploadImg['fileName']
                    ];
                } else if (in_array($mime, $this->fileService->allowedDocMimes)) {
                    $uploadFile = $this->fileService->uploadFile($request);
                    if (!$uploadFile['success']) {
                        return $this->responseJsonError($uploadFile['msg']);
                    }

                    $files[] = [
                        'fileName' => $uploadFile['fileName']
                    ];
                } else {
                    return $this->responseJsonError("Invalid file format! Accepted file formats: PDF, GIF, PNG, JPG.");
                }
            }
        }

        $data['repair_order_desired_date'] = strtolower($data['repair_order_desired_date']) == "asap" ? 1 : 2;

        if ($data['repair_order_desired_date'] == 2) {
            //Need to check past date and return error to user
            $repairDate = date("Y-m-d", strtotime($data['repair_order_custom_desired_date']));
            $today = date("Y-m-d");
            if ($repairDate < $today) {
                return $this->responseJsonError("Cannot create order for past dates!");
            }

            $data["repair_order_custom_desired_date"] = date("Y-m-d", strtotime($data['repair_order_custom_desired_date']));
        } else {
            $data["repair_order_custom_desired_date"] = null;
        }

        if ($data['create_order'] == "false") {
            return $this->update(
                $this->orderService->findRepairOrderById($data['repair_order_id']),
                $data,
                $request
            );
        }

        $repairOrder = $this->orderService->createRepairOrder([
            "order_id"            => $data['order_id'],
            "user_id"      => auth()->id(),
            "service_date_type"  => $data['repair_order_desired_date'],
            "service_date"       => $data["repair_order_custom_desired_date"],
            "replace_repair_post"          => $data['repair_replace_post'],
            "relocate_post"            => $data['relocate_post'],
            "panel_id"           => $data['panel_id'] == 'undefined' ? null : $data['panel_id'],
            "repair_trip_fee"            => $data['repair_trip_fee'],
            "repair_fee"        => $data['repair_order_fee'],
            "zone_fee"           => $data['repair_order_zone_fee'],
            "rush_fee"              => $data['repair_order_rush_fee'],
            "total"           => $data['total'],
            "comment"          => $data['repair_order_comment']
        ]);

        $data['repair_order_select_accessories'] = json_decode($data['repair_order_select_accessories']);
        $accessories = $data['repair_order_select_accessories'];
        if (count($accessories)) {
            RepairOrderAccessory::where('repair_order_id', $repairOrder->id)->delete();

            foreach ($accessories as $key => $row) {
                $this->orderAccessoryService->storeRepairOrderAccessories([
                    'repair_order_id' => $repairOrder->id,
                    'accessory_id' => $row->accessory_id,
                    'action' => $row->action
                ]);
            }
        }

        if (count($files)) {
            foreach ($files as $file) {
                $this->orderAttachmentService->storeRepairOrderAttachments([
                    'repair_order_id' => $repairOrder->id,
                    'file_name' => $file['fileName'],
                ]);
            }
        }

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['repair_order_id'] = $repairOrder->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertRepairAdjustments($insertData);
            }
        }

        $repairOrder->status = RepairOrder::STATUS_RECEIVED;
        $repairOrder->action_needed = false;
        $repairOrder->save();

        $needPayment = false;

        if ($repairOrder->order->office && ! $repairOrder->order->agent) {
            if ($repairOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($repairOrder->total > 0) {
                    $repairOrder->status = RepairOrder::STATUS_INCOMPLETE;
                    $repairOrder->action_needed = true;
                    $repairOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $repairOrder->status = Order::STATUS_RECEIVED;
                $repairOrder->save();
            }
        }

        if ($repairOrder->order->agent) {
            if ($repairOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($repairOrder->total > 0) {
                    $repairOrder->status = RepairOrder::STATUS_INCOMPLETE;
                    $repairOrder->action_needed = true;
                    $repairOrder->save();
                    $needPayment = true;
                }
            } elseif ($repairOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY && $repairOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($repairOrder->total > 0) {
                    $repairOrder->status = Order::STATUS_INCOMPLETE;
                    $repairOrder->action_needed = true;
                    $repairOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $repairOrder->status = Order::STATUS_RECEIVED;
                $repairOrder->save();
            }
        }

        if ($repairOrder->total == 0) {
            $needPayment = false;
        }

        //set to_be_invoiced to true and send email
        if ( ! $needPayment) {
            $repairOrder->update([
                'to_be_invoiced' => true
            ]);
            try {
                $this->notificationService->repairOrderCreated($repairOrder);
            } catch (Throwable $e) {
                logger()->error($e->getMessage());
            }
        }

        $repairOrder->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($repairOrder->needPayment) {
            $billing = $this->paymentService->getBillingDetails($repairOrder->order);
        }

        Session::flash("success", "Repair order created successfully.");

        $repairOrder->order = $repairOrder->order;
        $repairOrder->office = $repairOrder->order->office;
        $repairOrder->agent = $repairOrder->order->agent;

        return response()->json(compact('repairOrder', 'billing'));
    }

    public function update(RepairOrder $repairOrder, $data, $request)
    {
        // handle files
        $files = [];
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'file')) {
                $request->file = $request->{$key};
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    if (!$uploadImg['success']) {
                        return $this->responseJsonError($uploadImg['msg']);
                    }

                    $files[] = [
                        'fileName' => $uploadImg['fileName']
                    ];
                } else if (in_array($mime, $this->fileService->allowedDocMimes)) {
                    $uploadFile = $this->fileService->uploadFile($request);
                    if (!$uploadFile['success']) {
                        return $this->responseJsonError($uploadFile['msg']);
                    }

                    $files[] = [
                        'fileName' => $uploadFile['fileName']
                    ];
                } else {
                    return $this->responseJsonError("Invalid file format! Accepted file formats: PDF, GIF, PNG, JPG.");
                }
            }
        }

        $repairOrder->update([
            "service_date_type"  => $data['repair_order_desired_date'],
            "service_date"       => $data["repair_order_custom_desired_date"],
            "replace_repair_post"          => $data['repair_replace_post'],
            "relocate_post"            => $data['relocate_post'],
            "panel_id"           => $data['panel_id'] == 'undefined' ? null : $data['panel_id'],
            "repair_trip_fee"            => $data['repair_trip_fee'],
            "repair_fee"        => $data['repair_order_fee'],
            "zone_fee"           => $data['repair_order_zone_fee'],
            "rush_fee"              => $data['repair_order_rush_fee'],
            "total"           => $data['total'],
            "comment"          => $data['repair_order_comment']
        ]);

        $data['repair_order_select_accessories'] = json_decode($data['repair_order_select_accessories']);
        $accessories = $data['repair_order_select_accessories'];
        if (count($accessories)) {
            RepairOrderAccessory::where('repair_order_id', $repairOrder->id)->delete();

            foreach ($accessories as $key => $row) {
                $this->orderAccessoryService->storeRepairOrderAccessories([
                    'repair_order_id' => $repairOrder->id,
                    'accessory_id' => $row->accessory_id,
                    'action' => $row->action
                ]);
            }
        }

        if (count($files)) {
            foreach ($files as $file) {
                $this->orderAttachmentService->storeRepairOrderAttachments([
                    'repair_order_id' => $repairOrder->id,
                    'file_name' => $file['fileName'],
                ]);
            }
        }

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            $this->orderService->deleteRepairAdjustments((int) $repairOrder->id);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['repair_order_id'] = $repairOrder->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertRepairAdjustments($insertData);
            }
        }

        $needPayment = false;

        $repairOrder->action_needed = false;
        $repairOrder->save();

        if ($repairOrder->order->office && ! $repairOrder->order->agent) {
            if (
                $repairOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $repairOrder->status != RepairOrder::STATUS_RECEIVED
            ) {
                if ($repairOrder->total > 0) {
                    $repairOrder->status = RepairOrder::STATUS_INCOMPLETE;
                    $repairOrder->action_needed = true;
                    $repairOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $repairOrder->status = RepairOrder::STATUS_RECEIVED;
                $repairOrder->save();
            }
        }

        if ($repairOrder->order->agent) {
            if (
                $repairOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $repairOrder->status != RepairOrder::STATUS_RECEIVED
            ) {
                if ($repairOrder->total > 0) {
                    $repairOrder->status = RepairOrder::STATUS_INCOMPLETE;
                    $repairOrder->action_needed = true;
                    $repairOrder->save();
                    $needPayment = true;
                }
            } elseif (
                $repairOrder->order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY
                && $repairOrder->order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $repairOrder->status != RepairOrder::STATUS_RECEIVED
            ) {
                if ($repairOrder->total > 0) {
                    $repairOrder->status = RepairOrder::STATUS_INCOMPLETE;
                    $repairOrder->action_needed = true;
                    $repairOrder->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $repairOrder->status = RepairOrder::STATUS_RECEIVED;
                $repairOrder->save();
            }
        }

        if ($repairOrder->total == 0) {
            $needPayment = false;
            $repairOrder->status = RepairOrder::STATUS_RECEIVED;
            $repairOrder->fully_paid = true;
            $repairOrder->save();
        }

        $toBeInvoiced = $this->orderService->isInvoiced($repairOrder);
        if ( ! $needPayment && $toBeInvoiced) {
            $repairOrder->refresh();
            $repairOrder->update([
                'to_be_invoiced' => true
            ]);
        }

        $repairOrder->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($repairOrder->needPayment) {
            $billing = $this->paymentService->getBillingDetails($repairOrder->order);
        }

        $repairOrder->order = $repairOrder->order;
        $repairOrder->office = $repairOrder->order->office;
        $repairOrder->agent = $repairOrder->order->agent;

        $repairOrder->editOrder = true;

        Session::flash("success", "Repair order updated successfully.");

        return response()->json(compact('repairOrder', 'billing'));
    }

    public function getOrder(RepairOrder $repairOrder)
    {
        return $repairOrder->with('order')
            ->with('attachments')
            ->with('accessories')
            ->with('panel')
            ->with('adjustments')
            ->where('id', $repairOrder->id)
            ->first();
    }

    public function sendEmail(RepairOrder $repairOrder)
    {
        try {
            $this->notificationService->repairOrderCreated($repairOrder);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        Session::flash("success", "Repair order saved successfully.");
    }

    public function deleteAll()
    {
        $this->orderService->deleteAllRepairOrders();

        return $this->responseJsonSuccess((object)[]);
    }

    public function cancel(RepairOrder $repairOrder)
    {
        $authUser = auth()->user();

        if ($repairOrder->status == RepairOrder::STATUS_RECEIVED) {
            //Dispatch job to void the hold in Authorize.net
            \App\Jobs\VoidCardHoldJob::dispatch($repairOrder);
        }

        if ($authUser->role != User::ROLE_SUPER_ADMIN) {
            $repairOrder->status = RepairOrder::STATUS_CANCELLED;
            $repairOrder->save();

            //Dispatch job for refund if order is paid
            if ($repairOrder->fully_paid) {
                RefundJob::dispatch($repairOrder, Order::REPAIR_ORDER, $this->orderService);
            }
        } else {
            if ($repairOrder->assigned_to) {
                //Unassign order
                $repairOrder->status = RepairOrder::STATUS_RECEIVED;
                $repairOrder->save();

                $request = new \stdClass();
                $request->orderType = 'repair';
                $request->orderId = $repairOrder->id;

                $this->orderService->unassignOrder($request);
            } else {
                $repairOrder->status = RepairOrder::STATUS_CANCELLED;
                $repairOrder->save();
            }
        }

        return true;
    }

    public function deleteFile($fileId)
    {
        $file = RepairOrderAttachment::findOrFail($fileId);
        $file->delete();

        return response()->json(true);
    }

    public function markCompleted(RepairOrder $repairOrder)
    {
        $repairOrder->status = RepairOrder::STATUS_COMPLETED;
        $repairOrder->save();

        return true;
    }
}
