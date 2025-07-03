<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrder;
use App\Http\Traits\HelperTrait;
use App\Models\Agent;
use App\Models\{Order, RepairOrder, RemovalOrder, DeliveryOrder, Post, Panel, Accessory, Zone};
use App\Models\Office;
use App\Models\User;
use App\Models\{OrderAccessory, RepairOrderAccessory, InvoicePayments};
use App\Models\OrderAttachment;
use App\Services\InstallPostAgentService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\OrderAccessoryService;
use App\Services\OrderAttachmentService;
use Illuminate\Support\Facades\Session;
use App\Models\ServiceSetting;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PaymentService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Services\UserService;
use App\Services\AuthorizeNetService;
use App\Jobs\{OrderCompletedEmail, RefundJob, UpdateInventoryInFieldInstallJob};
use App\Models\{Invoice, InvoiceLine};
use App\Services\InvoiceService;
use App\Services\RefundQueueService;
use Illuminate\Support\Facades\Auth;
use App;

class OrderController extends Controller
{

    use HelperTrait;

    protected $orderService;

    protected $installPostAgentService;

    protected $fileService;

    protected $orderAttachmentService;

    protected $orderAccessoryService;

    protected $notificationService;

    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $userService;
    protected $authorizeNetService;
    protected $paymentService;
    protected $invoiceService;
    protected $refundQueueService;

    public function __construct(
        OrderService $orderService,
        InstallPostAgentService $installPostAgentService,
        FileService $fileService,
        OrderAttachmentService $orderAttachmentService,
        OrderAccessoryService $orderAccessoryService,
        NotificationService $notificationService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService,
        UserService $userService,
        AuthorizeNetService $authorizeNetService,
        PaymentService $paymentService,
        InvoiceService $invoiceService,
        RefundQueueService $refundQueueService
    ) {
        $this->orderService = $orderService;
        $this->installPostAgentService = $installPostAgentService;
        $this->fileService = $fileService;
        $this->orderAttachmentService = $orderAttachmentService;
        $this->orderAccessoryService = $orderAccessoryService;
        $this->notificationService = $notificationService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->userService = $userService;
        $this->authorizeNetService = $authorizeNetService;
        $this->paymentService = $paymentService;
        $this->invoiceService = $invoiceService;
        $this->refundQueueService = $refundQueueService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return "<h1>orders index : )</h1>";
    }
    public function datatable(Request $request)
    {
        return  $this->orderService->dataTable();
    }

    public function datatableOrderStatus(Request $request)
    {
        return  $this->orderService->datatableOrderStatus();
    }

    public function datatableOrderStatusActive(Request $request)
    {
        return  $this->orderService->datatableOrderStatusActive();
    }

    public function datatableOrderStatusHistory(Request $request)
    {
        return  $this->orderService->datatableOrderStatusHistory();
    }

    public function cancel(Order $order)
    {
        $authUser = auth()->user();

        if ($order->status == Order::STATUS_RECEIVED) {
            //Dispatch job to void the hold in Authorize.net
            \App\Jobs\VoidCardHoldJob::dispatch($order);
        }

        if ($authUser->role != User::ROLE_SUPER_ADMIN) {
            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            //Dispatch job for refund if order is paid
            if ($order->fully_paid) {
                RefundJob::dispatch($order, Order::INSTALL_ORDER, $this->orderService);
            }
        } else { //Superadmin
            if ($order->assigned_to) {
                //Unassign order
                $order->status = Order::STATUS_RECEIVED;
                $order->save();

                $request = new \stdClass();
                $request->orderType = 'install';
                $request->orderId = $order->id;

                $this->orderService->unassignOrder($request);
            } else {
                $order->status = Order::STATUS_CANCELLED;
                $order->save();
            }
        }

        return true;
    }

    public function markCompleted(Order $order)
    {
        $order->status = Order::STATUS_COMPLETED;
        $order->save();

        return true;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateOrder $request)
    {
        $data = $request->all();

        if (isset($data["imported_order"]) && $data["imported_order"] == 1 && auth()->user()->role != User::ROLE_SUPER_ADMIN ) {
            return $this->responseJsonError("Unauthorized");
        }

        //Make sure zone Id is captured
        if (! ($data['zone_id'] >= 1)) {
            return $this->responseJsonError('It looks like an error has occured. Please try again or refresh the page.');
        }

        // handle files
        $files = [];

        if ($data['is_create'] == "true") {
            foreach (array_keys($data) as $key) {
                if (str_starts_with($key, 'file')) {
                    // get accessory id
                    [$fileKey, $accessoryId] = explode("_", $key);
                    $request->file = $request->{$key};

                    $mime = strtolower($request->file->getMimeType());
                    if (in_array($mime, $this->fileService->allowedImageMimes)) {
                        $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                        if (!$uploadImg['success']) {
                            return $this->responseJsonError($uploadImg['msg']);
                        }
                        $files[] = [
                            'filename' => $uploadImg['fileName'],
                            'accessory' => (int) $accessoryId ? (int) $accessoryId : null,
                            'plat_map' => $accessoryId == "plat-map" ?  1 : 0
                        ];
                    } else if (in_array($mime, $this->fileService->allowedDocMimes)) {
                        $uploadFile = $this->fileService->uploadFile($request);
                        if (!$uploadFile['success']) {
                            return $this->responseJsonError($uploadFile['msg']);
                        }
                        $files[] = [
                            'filename' => $uploadFile['fileName'],
                            'accessory' => (int) $accessoryId ? (int) $accessoryId : null,
                            'plat_map' => $accessoryId == "plat-map" ?  1 : 0
                        ];
                    } else {
                        return $this->responseJsonError("Invalid file format! Accepted file formats: PDF, GIF, PNG, JPG.");
                    }
                }
            }
        }

        $marker = (object) ['lat' => null, 'lng' => null];

        if (isset($data['install_marker_position'])) {
            if ($data['install_marker_position'] !== "null") {
                $marker_position = json_decode($data['install_marker_position']);
                $marker->lat = $marker_position->lat;
                $marker->lng = $marker_position->lng;
            }
        }

        $data['install_post_select_accessories'] = json_decode($data['install_post_select_accessories']);

        $data['install_post_desired_date'] = strtolower($data['install_post_desired_date']) == "asap" ? 1 : 2;

        if ($data['install_post_desired_date'] == 2) {
            //Need to check past date and return error to user
            $installDate = date("Y-m-d", strtotime($data['install_post_custom_desired_date']));
            $today = date("Y-m-d");
            if ($installDate < $today) {
                return $this->responseJsonError("Cannot create or update order for past dates!");
            }

            $data["install_post_custom_desired_date"] = date("Y-m-d", strtotime($data['install_post_custom_desired_date']));
        } else {
            $data["install_post_custom_desired_date"] = null;
        }

        if ($data['is_create'] == "false") {
            return $this->update($this->orderService->findById($data['order_id']), $data, $marker, $request);
            exit;
        }

        //Need to get current port renewal fee and removal trip fee
        $postRenewalFee = $this->postService->getRenewalFee((int) $data['install_post_select_post']);
        $removalFee = ServiceSetting::first()->removal_fee ?? 0;

        $order = $this->orderService->create([
            "address"            => $data['install_post_address'],
            "property_type"      => $data['install_post_property_type'],
            "desired_date_type"  => $data['install_post_desired_date'],
            "desired_date"       => $data["install_post_custom_desired_date"],
            "office_id"          => $data['install_post_office'],
            "post_id"            => $data['install_post_select_post'],
            "panel_id"           => $data['install_post_select_sign'],
            "comment"            => $data['install_post_comment'],
            "signage_fee"        => $data['install_post_signage'],
            "zone_fee"           => $data['install_post_zone_fee'],
            "rush_fee"           => $data['install_post_rush_fee'],
            "total"              => $data['total'],
            "latitude"           => $marker->lat,
            "longitude"          => $marker->lng,
            'agent_own_sign'     => $data['install_post_select_sign_type'] == "-1" ? 1 : 0,
            'sign_at_property'   => $data['install_post_select_sign_type'] == "-2" ? 1 : 0,
            'user_id'            => auth()->user()->id,
            'agent_id'           => $data['install_post_agent'] == "null" ? null : $data['install_post_agent'],
            "zone_id"            => $data['zone_id'],
            'ignore_zone_fee'    => $data['ignore_zone_fee'] == "true" ? 1 : 0,
            "post_renewal_fee"   => $postRenewalFee,
            "removal_fee"        => $removalFee
        ]);

        //Update date for any active order in same address
        $this->orderService->updateOrderSameAddress($order);

        $accessories = $data['install_post_select_accessories'];
        if (count($accessories)) {
            foreach ($accessories as $accessory) {
                $this->orderAccessoryService->create([
                    'order_id' => $order->id,
                    'accessory_id' => $accessory,
                ]);
            }
        }

        if (count($files)) {
            foreach ($files as $file) {
                $this->orderAttachmentService->create([
                    'order_id' => $order->id,
                    "name" => $file['filename'],
                    "accessory_id" => $file['accessory'],
                    "plat_map" => $file['plat_map'],
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
                        $insertData[$key]['order_id'] = $order->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertInstallAdjustments($insertData);
            }
        }

        $needPayment = false;

        $order->action_needed = false;
        $order->save();

        if ($order->office && ! $order->agent) {
            if ($order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($order->total > 0) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $order->status = Order::STATUS_RECEIVED;
                $order->save();
            }
        }

        if ($order->agent) {
            if ($order->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($order->total > 0) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                    $needPayment = true;
                }
            } elseif ($order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY && $order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER) {
                if ($order->total > 0) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $order->status = Order::STATUS_RECEIVED;
                $order->save();
            }
        }

        // if property type is "New construction" or "vacant land" and no files set status imcomplete
        if ($order->property_type == Order::NEW_CONSTRUCTION or $order->property_type == Order::VACANT_LAND) {
            if ($order->platMapFiles->first() == null) {
                $order->status = Order::STATUS_INCOMPLETE;
                $order->action_needed = true;
                $order->save();
            }
        }
        //if accessories files not uploaded set status incomplete
        foreach ($order->accessories as $accessory) {
            $accessory = $accessory->accessory;
            if ($accessory->prompt) {
                $fileFound = $order->files->where('accessory_id', $accessory->id)->first();
                if ($fileFound == null) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                }
            }
        }

        if ($order->total == 0 || (isset($data["imported_order"]) && $data["imported_order"] == 1)) {
            $needPayment = false;
        }

        //Imported orders will have the amount always set to 0
        if (isset($data["imported_order"]) && $data["imported_order"] == 1) {
            //All imported orders will be already installed
            $order->status = Order::STATUS_COMPLETED;
            //Imported orders will have the amount always set to 0
            $order->total = 0;
            $order->date_completed = now();
            $order->save();

            //Dispatch Job to update Field Qty
            try {
                $items = [
                    'postId' => $data['install_post_select_post'],
                    'panelId' =>$data['install_post_select_sign'],
                    'accessories' => $data['install_post_select_accessories']
                ];
                UpdateInventoryInFieldInstallJob::dispatch($items, $this->orderService);
            } catch (Throwable $t) {
                logger()->error($t->getMessage());
            }
        }

        //set to_be_invoiced to true and send email
        if ( ! $needPayment && (! isset($data["imported_order"]) || $data["imported_order"] == 0)) {
            $order->update(['to_be_invoiced' => true]);
            try {
                $this->notificationService->orderCreated($order);
            } catch (Throwable $e) {
                logger()->error($e->getMessage());
            }
        }

        $order->needPayment = $needPayment;

        //Get billing details for payment form
        $billing = [];
        if ($order->needPayment) {
            $billing = $this->paymentService->getBillingDetails($order);
        }

        Session::flash("success", "Order created successfully.");

        return response()->json(compact('order', 'billing'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
        return $order->with('accessories')
            ->with('files')
            ->with('adjustments')
            ->where('id', $order->id)
            ->first();
    }

    public function deleteFile($fileId)
    {
        $file = OrderAttachment::findOrFail($fileId);
        $file->delete();
        return response()->json(true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Order $order, $data, $marker, $request)
    {
        // handle files
        $files = [];
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'file')) {
                // get accessory id
                [$fileKey, $accessoryId] = explode("_", $key);
                $request->file = $request->{$key};
                if (is_string($request->file)) {
                    continue;
                }
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    if (!$uploadImg['success']) {
                        return $this->responseJsonError($uploadImg['msg']);
                    }
                    $files[] = [
                        'filename' => $uploadImg['fileName'],
                        'accessory' => (int) $accessoryId ? (int) $accessoryId : null,
                        'plat_map' => $accessoryId == "plat-map" ?  1 : 0
                    ];
                } else if (in_array($mime, $this->fileService->allowedDocMimes)) {
                    $uploadFile = $this->fileService->uploadFile($request);
                    if (!$uploadFile['success']) {
                        return $this->responseJsonError($uploadFile['msg']);
                    }
                    $files[] = [
                        'filename' => $uploadFile['fileName'],
                        'accessory' => (int) $accessoryId ? (int) $accessoryId : null,
                        'plat_map' => $accessoryId == "plat-map" ?  1 : 0
                    ];
                } else {
                    return $this->responseJsonError("Invalid file format! Accepted file formats: PDF, GIF, PNG, JPG.");
                }
            }
        }

        $order->update([
            "address"            => $data['install_post_address'],
            "property_type"      => $data['install_post_property_type'],
            "desired_date_type"  => $data['install_post_desired_date'],
            "desired_date"       => $data["install_post_custom_desired_date"],
            "office_id"          => $data['install_post_office'],
            "post_id"            => $data['install_post_select_post'],
            "panel_id"           => $data['install_post_select_sign'],
            "comment"            => $data['install_post_comment'],
            "signage_fee"        => $data['install_post_signage'],
            "zone_fee"           => $data['install_post_zone_fee'],
            "rush_fee"           => $data['install_post_rush_fee'],
            "total"              => $data['total'],
            "latitude"           => $marker->lat ?? $order->latitude,
            "longitude"          => $marker->lng ?? $order->longitude,
            'agent_own_sign'     => $data['install_post_select_sign_type'] == "-1" ? 1 : 0,
            'sign_at_property'   => $data['install_post_select_sign_type'] == "-2" ? 1 : 0,
            'user_id'            => auth()->user()->id,
            'agent_id'           => $data['install_post_agent'] == "null" ? null : $data['install_post_agent'],
            "zone_id"            => $data['zone_id'],
            'ignore_zone_fee' => $data['ignore_zone_fee'] == "true" ? 1 : 0
        ]);

        //Update date for any active order in same address
        $this->orderService->updateOrderSameAddress($order);

        //delete old
        OrderAccessory::where('order_id', $order->id)->delete();
        $accessories = $data['install_post_select_accessories'];
        if (count($accessories)) {
            foreach ($accessories as $accessory) {
                $this->orderAccessoryService->create([
                    'order_id' => $order->id,
                    'accessory_id' => $accessory,
                ]);
            }
        }

        if (count($files)) {
            foreach ($files as $file) {
                $this->orderAttachmentService->create([
                    'order_id' => $order->id,
                    "name" => $file['filename'],
                    "accessory_id" => $file['accessory'],
                    "plat_map" => $file['plat_map'],
                ]);
            }
        }

        //Pricing adjustments
        if (isset($data['pricingAdjustments'])) {
            $insertData = [];
            $pricingAdjustments = json_decode($data['pricingAdjustments']);
            $this->orderService->deleteInstallAdjustments((int) $order->id);
            if (count($pricingAdjustments->description)) {
                foreach ($pricingAdjustments->description as $key => $val) {
                    if ( ! is_null($val)) {
                        $insertData[$key]['order_id'] = $order->id;
                        $insertData[$key]['description'] = $val;
                        $insertData[$key]['charge'] = $pricingAdjustments->charge[$key];
                        $insertData[$key]['discount'] = $pricingAdjustments->discount[$key];
                    }
                }

                $this->orderService->massInsertInstallAdjustments($insertData);
            }
        }

        $needPayment = false;

        $order->action_needed = false;
        $order->save();

        if ($order->office && ! $order->agent) {
            if (
                    $order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                    && $order->status != Order::STATUS_RECEIVED
                ) {
                    if ($order->total > 0) {
                        $order->status = Order::STATUS_INCOMPLETE;
                        $order->action_needed = true;
                        $order->save();
                        $needPayment = true;
                    }
            } else {
                $needPayment = false;
                $order->status = Order::STATUS_RECEIVED;
                $order->save();
            }
        }

        if ($order->agent) {
            if (
                $order->agent->payment_method == Agent::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $order->status != Order::STATUS_RECEIVED
            ) {
                if ($order->total > 0) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                    $needPayment = true;
                }
            } elseif (
                $order->agent->payment_method == Agent::PAYMENT_METHOD_OFFICE_PAY
                && $order->office->payment_method == Office::PAYMENT_METHOD_PAY_AT_TIME_OF_ORDER
                && $order->status != Order::STATUS_RECEIVED
            ) {
                if ($order->total > 0) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                    $needPayment = true;
                }
            } else {
                $needPayment = false;
                $order->status = Order::STATUS_RECEIVED;
                $order->save();
            }
        }

        // if property type is "New construction" or "vacant land" and no files set status imcomplete
        if ($order->property_type == Order::NEW_CONSTRUCTION or $order->property_type == Order::VACANT_LAND) {
            if ($order->platMapFiles->first() == null) {
                $order->status = Order::STATUS_INCOMPLETE;
                $order->action_needed = true;
                $order->save();
            }
        }
        //if accessories files no uploaded set status incomplete
        foreach ($order->accessories as $accessory) {
            $accessory = $accessory->accessory;
            if ($accessory->prompt) {
                $fileFound = $order->files->where('accessory_id', $accessory->id)->first();
                if ($fileFound == null) {
                    $order->status = Order::STATUS_INCOMPLETE;
                    $order->action_needed = true;
                    $order->save();
                }
            }
        }

        if ($order->total == 0) {
            $needPayment = false;
            $order->status = Order::STATUS_RECEIVED;
            $order->fully_paid = true;
            $order->save();
        }

        $toBeInvoiced = $this->orderService->isInvoiced($order);
        if ( ! $needPayment && $toBeInvoiced) {
            $order->update([
                'to_be_invoiced' => true
            ]);
        }

        $order->editOrder = true;
        $order->needPayment = $needPayment;

        //Get billing details for payment form - who the card will be assigned to
        $billing = [];
        if ($order->needPayment) {
            $billing = $this->paymentService->getBillingDetails($order);
        }

        Session::flash("success", "Order updated successfully.");

        return response()->json(compact('order', 'billing'));
    }

    /*public function deleteAll()
    {
        $this->orderService->deleteAll();

        return $this->responseJsonSuccess((object)[]);
    }*/

    public function deleteAllOrderStatus()
    {
        if (App::environment('local')) {
            //Delete all Customer profiles from authorize.net
            try {
                $profiles = $this->orderService->getAllAuthorizeNetCustomerProfiles();
                if ($profiles->isNotEmpty()) {
                    foreach ($profiles as $profile) {
                        $customerProfileId = $profile->authorizenet_profile_id;
                        $this->authorizeNetService->deleteCustomerProfile($customerProfileId);
                    }
                }
            } catch (Throwable $t) {
                logger()->error($t->getMessage());
            }

            $this->orderService->deleteAllOrderStatus();

            return $this->responseJsonSuccess((object)[]);
        }

        return $this->responseJsonError('Invalid request.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }

    public function sendEmail(Order $order)
    {
        try {
            $this->notificationService->orderCreated($order);
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }
    }

    public function checkOrderSameAddress(string $address, string $lat, string $lng, Office $office, $agentId, $orderId)
    {
        $hasOrderSameAddress = $this->orderService->checkOrderSameAddress(
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

    public function orderStatus()
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $offices = $this->officeService->getAll();
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('orders.status.index', $data);
        }

        if ($authUser->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($authUser->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('orders.office.status.index', $data);
        }

        if ($authUser->role == User::ROLE_AGENT) {
            $offices = $this->officeService->getAll();
            $agents = $this->officeService->getAgents($authUser->agent->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $serviceSettings = $service_settings = ServiceSetting::first();

            $data = compact('offices', 'agents', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

            return view('orders.agent.status.index', $data);
        }
    }

    public function orderStatusRoutes()
    {
        $installers = $this->userService->getActiveInstallers();
        $offices = $this->officeService->getAll();

        $serviceSettings = $service_settings = ServiceSetting::first();

        $data = compact('installers', 'offices', 'service_settings', 'serviceSettings');

        return view('orders.status.routes', $data);
    }

    public function getInstallerOrders(Request $request)
    {
        $installerId = $request->installerId;
        $routeDate = $request->route_date;

        session(['routeDate' => $routeDate]);

        if ( ! $installerId) {
            return $this->orderService->getAllRouteOrders($routeDate);
        }

        return $this->orderService->getInstallerOrders((int) $installerId, $routeDate);
    }

    public function assignOrder(Request $request)
    {
        $order = $this->orderService->assignOrder($request);

        $invoiced = $this->orderService->isInvoiced($order);

        $orderType = $request->orderType;

        $payer = $this->orderService->getOrderPayer($order, $orderType);
        $customerPaymentProfileId = $this->orderService->getPaymentProfile($order->id, $orderType);

        if (! $order->fully_paid && ! $invoiced && $order->auth_transaction_id && $order->total > 0) {
            //Check if new amount is different from the initially authorized amount.
            if ( (float) $order->total !== (float) $order->authorized_amount) {
                //Order total change so need to charge customer profile for new amount
                $capture = $this->authorizeNetService->chargeCustomerProfile(
                    $payer->authorizenet_profile_id,
                    $customerPaymentProfileId,
                    $order
                );

                //Void the previous hold
                $oldTransId = $order->auth_transaction_id;
                \App\Jobs\VoidCardHoldJob::dispatch($order, $oldTransId);
            } else {
                //Capture payment in authorize.net using saved transID
                //Authorize immediately voids the hold and creates new transaction fo rthe capture
                $capture = $this->authorizeNetService->capture($order->total, $order->auth_transaction_id);
                //info($capture);
            }

            if (
                $capture['messages']['resultCode'] == "Error"
                || empty($capture['transactionResponse']['authCode'])
            ) {
                //Unassign Order because payment failed.
                $this->orderService->unassignOrder($request);

                //Since payment failed, we need to change order status to incomplete
                unset($order->office);
                unset($order->agent);
                $order->status = Order::STATUS_INCOMPLETE;
                $order->action_needed = true;
                //Remove saved auth_transaction_id since card not valid
                //$order->auth_transaction_id = null;
                $order->save();

                if (isset($capture['transactionResponse']['errors'][0]['errorText'])) {
                    return $this->responseJsonError($capture['transactionResponse']['errors'][0]['errorText']);
                }

                if (isset($capture['messages']['message'][0]['text'])) {
                    return $this->responseJsonError($capture['messages']['message'][0]['text']);
                }
                if (isset($capture['messages']['message'][0]['description'])) {
                    return $this->responseJsonError($capture['messages']['message'][0]['description']);
                }
            }

            //Create an invoice for the order so it shows up under accounting-payments tab
            //Invoice number must have a dash and sequence number
            $invoiceData['invoice_number'] = $this->invoiceService->generateInvoiceNumber().'-0';
            $invoiceData['office_id'] = $order->office->id;
            $invoiceData['agent_id'] = $order->agent->id ?? null;
            $invoiceData['due_date'] = now()->format('Y-m-d');
            $invoiceData['invoice_type'] = Invoice::INVOICE_TYPE_SINGLE_ORDER;
            $invoiceData['line_items'][0]['description'] = "Invoice for order - $order->order_number";
            $invoiceData['line_items'][0]['amount'] = $order->total;
            $invoiceData['line_items'][0]['order_id'] = $order->id;
            $invoiceData['line_items'][0]['order_type'] = $this->orderService->getOrderTypeFromString($orderType);
            $invoice = $this->invoiceService->generateInvoice($invoiceData);

            //Create invoice payment and mark invoice as paid
            $paymentData = [
                'invoice_id' => $invoice->id,
                'total' => $order->total,
                'payment_method' => InvoicePayments::CREDIT_CARD,
                'card_type' =>  $order->card_type,
                'card_last_four' => $order->card_last_four,
                'transaction_id' => $order->auth_transaction_id,
                'payment_profile' => $customerPaymentProfileId
            ];

            $this->invoiceService->createInvoicePayment($paymentData);

            //Record payment
            if ($orderType == 'install') {
                $this->paymentService->create([
                    "order_id" => $order->id,
                    "paid_by" => auth()->id(),
                    "office_id" => $order->office_id,
                    "agent_id" => $order->agent_id,
                    "amount" => $order->total
                ]);
            }
            if ($orderType == 'repair') {
                $this->paymentService->createRepairPayment([
                    "repair_order_id" => $order->id,
                    "paid_by" => auth()->id(),
                    "office_id" => $order->order->office_id,
                    "agent_id" => $order->order->agent_id,
                    "amount" => $order->total
                ]);
            }
            if ($orderType == 'removal') {
                $this->paymentService->createRemovalPayment([
                    "removal_order_id" => $order->id,
                    "paid_by" => auth()->id(),
                    "office_id" => $order->order->office_id,
                    "agent_id" => $order->order->agent_id,
                    "amount" => $order->total
                ]);
            }
            if ($orderType == 'delivery') {
                $this->paymentService->createDeliveryPayment([
                    "delivery_order_id" => $order->id,
                    "paid_by" => auth()->id(),
                    "office_id" => $order->office_id,
                    "agent_id" => $order->agent_id,
                    "amount" => $order->total
                ]);
            }

            //Because assignOrder method returns $order->office $order->agent
            //to be used in isInvoiced method and it throws error when saving the order
            unset($order->office);
            unset($order->agent);

            $order->fully_paid = true;
            $order->invoice_number = $invoiceData['invoice_number'];
            $order->auth_transaction_id = $capture['transactionResponse']['transId'];
            $order->authorized_amount = $order->total;
            $order->action_needed = false;
            $order->save();
        }

        if (!$order->fully_paid && !$invoiced && !$order->to_be_invoiced && $order->total > 0) {
            $this->orderService->unassignOrder($request);

            unset($order->office);
            unset($order->agent);
            $order->status = Order::STATUS_INCOMPLETE;
            $order->action_needed = true;
            $order->save();

            return $this->responseJsonError('Unable to charge card. Make sure a valid card was previously authorized for this order.');
        }

        //Because assignOrder method returns $order->office $order->agent
        //To be used in isInvoiced method
        unset($order->office);
        unset($order->agent);

        //If order is unassigned, price changed and ussigned again, it needs to
        //charge for additional or refund if new price is less than amount authorized
        if ($order->fully_paid && (float) $order->total !== (float) $order->authorized_amount && $order->total > 0) {
            $diff = $order->total - $order->authorized_amount;

            if ($diff > 0) {
                //Order total increased so need to charge customer the difference
                $capture = $this->authorizeNetService->chargeCustomerProfile(
                    $payer->authorizenet_profile_id,
                    $customerPaymentProfileId,
                    $order,
                    $diff
                );

                $invoice = $this->invoiceService->findInvoiceByNumber($order->invoice_number);

                if ($capture['messages']['resultCode'] != "Error") {
                    //Update invoice
                    $invoicePayment = [];

                    //Invoice payment
                    $lastInvoicePayment = $this->invoiceService->findLastInvoicePayment((int) $invoice->id);
                    $invoicePayment['invoice_id'] = $invoice->id;
                    $invoicePayment['payment_method'] = $lastInvoicePayment['payment_method'];
                    $invoicePayment['check_number'] = $lastInvoicePayment['check_number'];
                    $invoicePayment['card_type'] = $lastInvoicePayment['card_type'];
                    $invoicePayment['card_last_four'] = $lastInvoicePayment['card_last_four'];
                    $invoicePayment['payment_profile'] = $lastInvoicePayment['payment_profile'];
                    $invoicePayment['total'] = $diff;
                    $invoicePayment['transaction_id'] = $capture['transactionResponse']['transId'];
                    $invoicePayment['comments'] = "Additional charge after order changed.";
                    $this->invoiceService->createInvoicePayment($invoicePayment);

                    //Invoice Line
                    $lastInvoiceLine = $this->invoiceService->findLastInvoiceLine((int) $invoice->id);
                    $this->invoiceService->updateInvoiceLineAmount($lastInvoiceLine, $order->total);
                } else {
                    logger()->error('Failed to charge customer profile the difference in price after order has been reassigned to route. See error payload below.');
                    logger()->error($capture);

                    return $this->responseJsonError($capture['transactionResponse']['errors'][0]['errorText']);
                }

            } else {
                //Order total decreased so need to refund customer the difference
                RefundJob::dispatch($order, Order::INSTALL_ORDER, $this->orderService, abs($diff));

                $adjustment = [];
                $adjustment['invoice_id'] = $invoice->id;
                $adjustment['description'] = "Refund.";
                $adjustment['amount'] = $diff;
                $adjustment['type'] = App\Models\InvoiceAdjustments::TYPE_REGULAR;
                $this->invoiceService->createInvoiceAdjustment($adjustment);
            }

            $order->authorized_amount = $order->authorized_amount + $diff;
            $order->action_needed = false;
            $order->save();
        }

        //Send email that order was scheduled.
        try {
            if ($orderType == 'install') {
                $this->notificationService->orderCreated($order);
            }
            if ($orderType == 'repair') {
                $this->notificationService->repairOrderCreated($order);
            }
            if ($orderType == 'removal') {
                $this->notificationService->removalOrderCreated($order);
            }
            if ($orderType == 'delivery') {
                $this->notificationService->deliveryOrderCreated($order);
            }
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }

        return $this->responseJsonSuccess((object)$order);
    }

    public function updateAssignedOrder(Request $request)
    {
        return $this->orderService->updateAssignedOrder($request);
    }

    public function unassignOrder(Request $request)
    {
        return $this->orderService->unassignOrder($request);
    }

    public function removeStops(Request $request)
    {
        $installerId = $request->installerId;

        return $this->orderService->removeStops($installerId);
    }

    public function getDirection(Request $request)
    {
        return $this->orderService->getDirection($request->origin, $request->destination);
    }

    public function getInstallerAssignedOrders(Request $request)
    {
        $installerId = $request->installerId;
        $routeDate = $request->route_date ?? now()->format('Y-m-d');

        return $this->orderService->getAssignedInstallerOrders($installerId, $routeDate);
    }

    public function installerOrderDetails($orderId, $orderType)
    {
        $order = $this->orderService->getInstallerOrderDetails($orderId, $orderType, auth()->id());

        if ( ! $order) {
            abort('404');
        }

        $data = [
            'orderType' => $orderType,
            'orderId' => $orderId,
            'order' => $order
        ];

        return view('users.installer.order_details', $data);
    }

    public function adminInstallerOrderDetails($orderId, $orderType, $installerId)
    {
        $order = $this->orderService->getInstallerOrderDetails($orderId, $orderType, $installerId);

        if ( ! $order) {
            abort('404');
        }

        $data = [
            'orderType' => $orderType,
            'orderId' => $orderId,
            'order' => $order
        ];

        return view('users.installer.order_details', $data);
    }

    public function installOrderComplete(Request $request)
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN || $authUser->role == User::ROLE_INSTALLER) {
            //return($request->all());
            $orderType = $request->orderType;
            $orderStatus = $request->orderStatus;
            $outOfInventory = $request->outOfInventory;

            $order = Order::find($request->orderId);
            $serviceSettings = $service_settings = ServiceSetting::first();

            $postPoints = 0;
            $accessoryPoints = 0;
            $installPoints = $serviceSettings->install_points;
            $zonePoints = 0;

            //Only award zone points once per day
            $zone = Zone::find($order->zone_id);
            $zonePointsAwarded = $this->orderService->checkIfZonePointsAwarded(
                (int) $order->zone_id,
                (int) $order->assigned_to
            );
            if ($zone && ! $zonePointsAwarded) {
                $zonePoints = $zone->installer_points;

                //Store zone points
                $this->orderService->storeZonePoints(
                    [
                        'user_id' => $order->assigned_to,
                        'zone_id' => $order->zone_id,
                        'points' => $zonePoints
                    ]
                );
            }

            $order->status = Order::STATUS_COMPLETED;

            if ($orderStatus == 'incomplete') {
                //Who the order will belong to?
                $createdBy = $order->office->user->id;
                if ($order->agent) {
                    $createdBy = $order->agent->user->id;
                }

                //If post is missing then replace/repair post
                $missingPostId = $request->missingPostId;
                $replaceRepairPost = null;
                if ($missingPostId > 0) {
                    $replaceRepairPost = true;
                }

                //If panel is missing then add to repair order
                $missingPanelId = $request->missingPanelId;
                $panelId = null;
                if ($missingPanelId > 0) {
                    $panelId = $missingPanelId;
                }

                //Create repair order for missing items with no charges for office/agent
                $attributes = [
                    'order_id' => $order->id,
                    'user_id' => $createdBy,
                    'service_date_type' => $order->desired_date_type,
                    'service_date' => $order->desired_date_type == 2 ? $order->desired_date : null,
                    'replace_repair_post' => $replaceRepairPost,
                    'panel_id' => $panelId,
                    'repair_trip_fee' => 0,
                    'repair_fee' => 0,
                    'zone_fee' => 0,
                    'rush_fee' => 0,
                    'total' => 0,
                    'order_number' => 'XXXX',
                    'status' => RepairOrder::STATUS_RECEIVED,
                    'award_installer_points' => false, //Need to flag the repair order as award_points = false,
                    'comment' => 'Auto-generated repair: Return to attach missing items.',
                    'fully_paid' => true
                ];

                $repairOrder = $this->orderService->createRepairOrder($attributes);

                //if accessories missing then add to repair order
                $missingAccessoriesIds = $request->missingAccessoriesIds;
                $accessoryIds = explode(',', $missingAccessoriesIds);
                if (count($accessoryIds)) {
                    foreach ($accessoryIds as $accessoryId) {
                        if ($accessoryId > 0) {
                            RepairOrderAccessory::create([
                                'repair_order_id' => $repairOrder->id,
                                'accessory_id' => $accessoryId,
                                'action' => RepairOrderAccessory::ACTION_ADD_REPLACE
                            ]);
                        }
                    }
                }
            }

            //decrement IN STORAGE for post
            if ($request->installedPostId > 0) {
                $this->orderService->updatePostInventoryInStorage((int) $order->post_id, -1);

                //Assign points for Post
                $postPoints = $this->orderService->getPostPoints($order->post_id);
            }

            if ($request->installedPanelId >= 1) {
                //decrement IN STORAGE for panel
                $this->orderService->updatePanelInventoryInStorage((int) $order->panel_id, -1);

                //Panel doesn't have points
            }

            //Update inventory for all attached accessories
            $installedAccessoriesIds = $request->installedAccessoriesIds;
            $accessoryIds = explode(',', $installedAccessoriesIds);
            $originalAccessories = $order->accessories->pluck('accessory_id')->all();
            $additionalCharges = 0;
            $additionalChargesDesc = '';
            $invoiceData = [];
            $lineIndex = 1;
            if (count($accessoryIds)) {
                foreach ($accessoryIds as $accessoryId) {
                    if ($accessoryId > 0) {
                        //decrement IN STORAGE for accessory
                        $this->orderService->updateAccessoryInventoryInStorage($accessoryId, -1);

                        //Assign points for accessories
                        $accessoryPoints = $this->orderService->getAccessoryPoints($accessoryId);

                        //Check if additional accessory
                        if ( ! in_array($accessoryId, $originalAccessories)) {
                            $accPrice = $this->orderService->getAccessoryPrice($accessoryId);
                            $additionalCharges = $additionalCharges + $accPrice;

                            //Additional as invoice adjustment
                            $accessoryName = $this->orderService->getAccessoryName($accessoryId);
                            $invoiceData['adjustments'][$lineIndex]['description'] = "Accessory - {$accessoryName}";
                            $invoiceData['adjustments'][$lineIndex]['amount'] = $accPrice;
                            $lineIndex++;

                            $additionalChargesDesc .= $accessoryName;

                            //Add accessory to order
                            $this->orderAccessoryService->create([
                                'order_id' => $order->id,
                                'accessory_id' => $accessoryId,
                            ]);
                        }
                    }
                }
            }

            $order->post_points = $postPoints ? $postPoints : 0;
            $order->accessory_points = $accessoryPoints ? $accessoryPoints : 0;
            $order->zone_points = $zonePoints ? $zonePoints : 0;
            $order->install_points = $installPoints ? $installPoints : 0;
            $order->installer_comments = $request->installer_comments;

            //Zero out inventory for post
            if ($request->postOutOfInventoryId > 0) {
                $this->orderService->zeroOutPostInventory($order->post_id);
            }

            //Zero inventory for panel
            if ($request->panelOutOfInventoryId > 0) {
                $this->orderService->zeroOutPanelInventory($order->panel_id);
            }

            //Zero inventory for accessories
            $accessoriesOutOfInventoryIds = $request->accessoriesOutOfInventoryIds;
            $accessoryIds = explode(',', $accessoriesOutOfInventoryIds);
            if (count($accessoryIds)) {
                foreach ($accessoryIds as $accessoryId) {
                    $this->orderService->zeroOutAccessoryInventory($accessoryId);
                }
            }

            if ($request->has('photo1')) {
                $request->file = $request->photo1;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo1 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo2')) {
                $request->file = $request->photo2;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo2 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo3')) {
                $request->file = $request->photo3;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo3 = $uploadImg['fileName'];
                    }
                }
            }

            $order->date_completed = now();
            $order->save();

            //Dispatch Job to update Field Qty
            try {
                $items = [
                    'postId' => $order->post_id,
                    'panelId' => $order->panel_id,
                    'accessories' => explode(',', $installedAccessoriesIds)
                ];
                UpdateInventoryInFieldInstallJob::dispatch($items, $this->orderService);
            } catch (Throwable $t) {
                logger()->error($t->getMessage());
            }

            //Need to charge for any additional accessories based on payment method
            if ($additionalCharges > 0) {
                $invoiced = $this->orderService->isInvoiced($order);
                if ( ! $invoiced) {
                    try {
                        $payProfile = $this->orderService->getSavedPaymentProfile($order);
                        if ($payProfile) {
                            $order->total = $additionalCharges;
                            $order->order_number = "{$order->order_number}-AC";

                            $capture = $this->authorizeNetService->chargeCustomerProfile(
                                $payProfile['customer_profile'],
                                $payProfile['card_profile'],
                                $order
                            );

                            if ($capture['messages']['resultCode'] == "Error") {
                                if (isset($capture['messages']['message'][0]['text'])) {
                                    return $this->responseJsonError($capture['messages']['message'][0]['text']);
                                }
                                if (isset($capture['messages']['message'][0]['description'])) {
                                    return $this->responseJsonError($capture['messages']['message'][0]['description']);
                                }
                            }

                            $this->paymentService->create([
                                "order_id" => $order->id,
                                "paid_by" => $payProfile['userId'],
                                "office_id" => $order->office_id,
                                "agent_id" => $order->agent_id,
                                "amount" => $additionalCharges
                            ]);

                            //Create invoice for the additional charge and increment invoice number
                            $invoiceData = [];
                            $order->refresh(); //refresh model to get rid of altered order number
                            $invoiceData['invoice_number'] = $this->invoiceService->incrementInvoiceNumber($order->invoice_number);
                            $invoiceData['office_id'] = $order->office->id;
                            $invoiceData['agent_id'] = $order->agent->id ?? null;
                            $invoiceData['due_date'] = now()->format('Y-m-d');
                            $invoiceData['invoice_type'] = Invoice::INVOICE_TYPE_SINGLE_ORDER;
                            $invoiceData['line_items'][0]['description'] = "Additional charge for: $additionalChargesDesc";
                            $invoiceData['line_items'][0]['amount'] = $additionalCharges;
                            $invoiceData['line_items'][0]['order_id'] = $order->id;
                            $invoiceData['line_items'][0]['order_type'] = Order::INSTALL_ORDER;
                            $invoiceData['line_items'][0]['missing_items'] = true;
                            $invoice = $this->invoiceService->generateInvoice($invoiceData);

                            //Create invoice payment and mark invoice as paid
                            $customerPaymentProfileId = $this->orderService->getPaymentProfile($order->id, 'install');
                            $paymentData = [
                                'invoice_id' => $invoice->id,
                                'total' => $additionalCharges,
                                'payment_method' => InvoicePayments::CREDIT_CARD,
                                'card_type' =>  $order->card_type,
                                'card_last_four' => $order->card_last_four,
                                'transaction_id' => $order->auth_transaction_id,
                                'payment_profile' => $customerPaymentProfileId
                            ];
                            $this->invoiceService->createInvoicePayment($paymentData);
                        }
                    } catch (Throwable $e) {
                        logger()->error($e->getMessage());
                    }
                } else {
                    //Update Invoice
                    if (count($invoiceData)) {
                        //increment invoice number
                        /*$invoiceData['invoice_number'] = $this->invoiceService->incrementInvoiceNumber($order->invoice_number);
                        //Create invoice
                        $invoiceData['office_id'] = $order->office_id;
                        $invoiceData['agent_id'] = $order->agent_id;
                        $invoiceData['order_id'] = $order->id;
                        $invoiceData['order_type'] = Order::INSTALL_ORDER;
                        $invoiceData['due_date'] = now()->addDays($serviceSettings->default_invoice_due_date_days);
                        $invoiceData['visible'] = false;
                        $this->invoiceService->generateInvoice($invoiceData);*/

                        //Since the invoice does not exist yet, we will adjust the order
                        $insertData = [];
                        $adjustments = $invoiceData['adjustments'];
                        foreach($adjustments as $key => $adjustment) {
                            $insertData[$key]['order_id'] = $order->id;
                            $insertData[$key]['description'] = $adjustment['description'];
                            $insertData[$key]['charge'] = $adjustment['amount'];
                            $insertData[$key]['discount'] = 0;
                        }
                        $this->orderService->massInsertInstallAdjustments($insertData);

                        //Recalculate order total
                        $this->orderService->calculateTotalAfterAdjust($order->id, Order::INSTALL_ORDER);
                    }
                }
            }

            //Save any picked up panels
            $signPanels = json_decode($request->signPanels, true);
            if ($signPanels && count($signPanels['panel'])) {
                $this->orderService->storePanels($order->office_id, $order->agent_id, $signPanels);
            }

            //Send email to office/agent
            OrderCompletedEmail::dispatch($order, $orderType, $orderStatus, $outOfInventory);

            //Recalculate installer stop number
            $this->orderService->decreaseStopNumber($order->assigned_to, $order->stop_number, $order->desired_date);

            return true;
        } else {
            abort('403');
        }
    }

    public function repairOrderComplete(Request $request)
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN || $authUser->role == User::ROLE_INSTALLER) {
            //return($request->all());
            $orderType = $request->orderType;
            $orderStatus = $request->orderStatus;
            $outOfInventory = $request->outOfInventory;

            $order = RepairOrder::find($request->orderId);
            $serviceSettings = $service_settings = ServiceSetting::first();

            $postPoints = 0;
            $accessoryPoints = 0;
            $repairPoints = $serviceSettings->repair_points;
            $zonePoints = 0;

            //Only award zone points once per day
            $zoneId = $order->order->zone_id;
            $zone = Zone::find($zoneId);
            $zonePointsAwarded = $this->orderService->checkIfZonePointsAwarded(
                (int) $zoneId,
                (int) $order->assigned_to
            );
            if ($zone && ! $zonePointsAwarded) {
                $zonePoints = $zone->installer_points;

                //Store zone points
                $this->orderService->storeZonePoints(
                    [
                        'user_id' => $order->assigned_to,
                        'zone_id' => $zoneId,
                        'points' => $zonePoints
                    ]
                );
            }

            $order->status = RepairOrder::STATUS_COMPLETED;

            if ($orderStatus == 'incomplete') {
                //Who the order will belong to?
                $createdBy = $order->order->office->user->id;
                if ($order->agent) {
                    $createdBy = $order->order->agent->user->id;
                }

                //If missed replace/repair post then need to add that to the new repair order
                $replaceRepairPost = null;
                if ($request->has('missingReplaceRepairPost')) {
                    $replaceRepairPost = true;
                }

                //If missing relocate post then need to add that to the new repair order
                $relocatePost = null;
                if ($request->has('missingRelocatePost')) {
                    $relocatePost = true;
                }

                //If panel is missing then add to repair order
                $missingPanelId = $request->missingPanelId;
                $panelId = null;
                if ($missingPanelId > 0) {
                    $panelId = $missingPanelId;
                }

                //Create repair order for missing items with no charges for office/agent
                $attributes = [
                    'order_id' => $order->order_id,
                    'user_id' => $createdBy,
                    'service_date_type' => $order->service_date_type,
                    'service_date' => $order->service_date_type == 2 ? $order->service_date : null,
                    'replace_repair_post' => $replaceRepairPost,
                    'relocate_post' => $relocatePost,
                    'panel_id' => $panelId,
                    'repair_trip_fee' => 0,
                    'repair_fee' => 0,
                    'zone_fee' => 0,
                    'rush_fee' => 0,
                    'total' => 0,
                    'order_number' => 'XXXX',
                    'status' => RepairOrder::STATUS_RECEIVED,
                    'award_installer_points' => false, //Need to flag the repair order as award_points = false
                    'fully_paid' => true
                ];

                $repairOrder = $this->orderService->createRepairOrder($attributes);

                //if accessories missing then add to repair order
                $missingAccessoriesIds = $request->missingAccessoriesIds;
                $accessoryIds = explode(',', $missingAccessoriesIds);
                if (count($accessoryIds)) {
                    foreach ($accessoryIds as $accessoryId) {
                        if ($accessoryId > 0) {
                            RepairOrderAccessory::create([
                                'repair_order_id' => $repairOrder->id,
                                'accessory_id' => $accessoryId,
                                'action' => RepairOrderAccessory::ACTION_ADD_REPLACE
                            ]);
                        }
                    }
                }
            }

            if ($request->has('postReplaceRepair')) {
                //Add points if post was replaced or repaired
                $postPoints = $postPoints + $this->orderService->getPostPoints($order->order->post_id);
            }

            if ($request->has('postRelocate')) {
                //Add points if post was relocated
                $postPoints = $postPoints + $this->orderService->getPostPoints($order->order->post_id);
            }

            //Update inventory for panel by reducing storage qty
            /*if ($request->repairedPanelId > 0) {
                $this->orderService->updatePanelInventoryOut($request->repairedPanelId, 1);

                //Panel doesn't have points
            }*/

            //Update inventory for all attached accessories
            $repairedAccessoriesIds = $request->repairedAccessoriesIds;
            $accessoryIds = explode(',', $repairedAccessoriesIds);
            $originalAccessories = $order->accessories->pluck('accessory_id')->all();
            $additionalCharges = 0;
            $additionalChargesDesc = '';
            $invoiceData = [];
            $lineIndex = 1;
            if (count($accessoryIds)) {
                foreach ($accessoryIds as $accessoryId) {
                    if ($accessoryId > 0) {
                        //$this->orderService->updateAccessoryInventoryOut($accessoryId, 1);

                        //Assign points for accessories
                        $accessoryPoints = $this->orderService->getAccessoryPoints($accessoryId);

                        //Check if additional accessory
                        if ( ! in_array($accessoryId, $originalAccessories)) {
                            $accPrice = $this->orderService->getAccessoryPrice($accessoryId);
                            $additionalCharges = $additionalCharges + $accPrice;

                            //Additional as invoice adjustment
                            $accessoryName = $this->orderService->getAccessoryName($accessoryId);
                            $invoiceData['adjustments'][$lineIndex]['description'] = "Accessory - {$accessoryName}";
                            $invoiceData['adjustments'][$lineIndex]['amount'] = $accPrice;
                            $lineIndex++;

                            $additionalChargesDesc .= $accessoryName;

                            //Add accessory to repair order
                            RepairOrderAccessory::create([
                                'repair_order_id' => $order->id,
                                'accessory_id' => $accessoryId,
                                'action' => RepairOrderAccessory::ACTION_ADD_REPLACE
                            ]);
                        }
                    }
                }
            }

            $order->post_points = $postPoints ? $postPoints : 0;
            $order->accessory_points = $accessoryPoints ? $accessoryPoints : 0;
            $order->zone_points = $zonePoints ? $zonePoints : 0;
            $order->repair_points = $repairPoints ? $repairPoints : 0;
            $order->installer_comments = $request->installer_comments;

            //Zero inventory for panel
            if ($request->panelOutOfInventoryId > 0) {
                $this->orderService->zeroOutPanelInventory($order->panel_id);
            }

            //Zero inventory for accessories
            $accessoriesOutOfInventoryIds = $request->accessoriesOutOfInventoryIds;
            $accessoryIds = explode(',', $accessoriesOutOfInventoryIds);
            if (count($accessoryIds)) {
                foreach ($accessoryIds as $accessoryId) {
                    $this->orderService->zeroOutAccessoryInventory($accessoryId);
                }
            }

            if ($request->has('photo1')) {
                $request->file = $request->photo1;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo1 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo2')) {
                $request->file = $request->photo2;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo2 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo3')) {
                $request->file = $request->photo3;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo3 = $uploadImg['fileName'];
                    }
                }
            }

            $order->date_completed = now();
            $order->save();

            //Dispatch job to recalculate inventory
            try {
                $items = [];
                if ($request->repairedPanelId > 0) {
                    $items['panelId'] = $request->repairedPanelId;
                }
                $accessoryIds = explode(',', $repairedAccessoriesIds);
                if (count($accessoryIds)) {
                    $items['accessories'] = $accessoryIds;
                }
                \App\Jobs\UpdateInventoryInFieldRepairJob::dispatch($items, $order, $this->orderService);
            } catch (Throwable $t) {
                logger()->error($t->getMessage());
            }

            //Need to charge for any additional accessories based on payment method
            if ($additionalCharges > 0) {
                $invoiced = $this->orderService->isInvoiced($order);

                if ( ! $invoiced) {
                    try {
                        $payProfile = $this->orderService->getSavedPaymentProfile($order->order);
                        if ($payProfile) {
                            $order->total = $additionalCharges;
                            $order->order_number = "{$order->order_number}-AC";

                            $capture = $this->authorizeNetService->chargeCustomerProfile(
                                $payProfile['customer_profile'],
                                $payProfile['card_profile'],
                                $order
                            );

                            if ($capture['messages']['resultCode'] == "Error") {
                                if (isset($capture['messages']['message'][0]['text'])) {
                                    return $this->responseJsonError($capture['messages']['message'][0]['text']);
                                }
                                if (isset($capture['messages']['message'][0]['description'])) {
                                    return $this->responseJsonError($capture['messages']['message'][0]['description']);
                                }
                            }

                            $this->paymentService->createRepairPayment([
                                "repair_order_id" => $order->id,
                                "paid_by" => $payProfile['userId'],
                                "office_id" => $order->order->office_id,
                                "agent_id" => $order->order->agent_id,
                                "amount" => $additionalCharges
                            ]);

                            //Create invoice for the additional charge and increment invoice number
                            $invoiceData = [];
                            $order->refresh(); //refresh model to get rid of altered order number
                            $invoiceData['invoice_number'] = $this->invoiceService->incrementInvoiceNumber($order->invoice_number);
                            $invoiceData['office_id'] = $order->order->office->id;
                            $invoiceData['agent_id'] = $order->order->agent->id ?? null;
                            $invoiceData['due_date'] = now()->format('Y-m-d');
                            $invoiceData['invoice_type'] = Invoice::INVOICE_TYPE_SINGLE_ORDER;
                            $invoiceData['line_items'][0]['description'] = "Additional charge for: $additionalChargesDesc";
                            $invoiceData['line_items'][0]['amount'] = $additionalCharges;
                            $invoiceData['line_items'][0]['order_id'] = $order->id;
                            $invoiceData['line_items'][0]['order_type'] = Order::REPAIR_ORDER;
                            $invoiceData['line_items'][0]['missing_items'] = true;
                            $invoice = $this->invoiceService->generateInvoice($invoiceData);

                            //Create invoice payment and mark invoice as paid
                            $customerPaymentProfileId = $this->orderService->getPaymentProfile($order->id, 'repair');
                            $paymentData = [
                                'invoice_id' => $invoice->id,
                                'total' => $additionalCharges,
                                'payment_method' => InvoicePayments::CREDIT_CARD,
                                'card_type' =>  $order->card_type,
                                'card_last_four' => $order->card_last_four,
                                'transaction_id' => $order->auth_transaction_id,
                                'payment_profile' => $customerPaymentProfileId
                            ];
                            $this->invoiceService->createInvoicePayment($paymentData);
                        }
                    } catch (Throwable $e) {
                        logger()->error($e->getMessage());
                    }
                } else {
                    if (count($invoiceData)) {
                        /*$invoiceData['office_id'] = $order->order->office_id;
                        $invoiceData['agent_id'] = $order->order->agent_id;
                        $invoiceData['order_id'] = $order->id;
                        $invoiceData['order_type'] = Order::REPAIR_ORDER;;
                        $invoiceData['due_date'] = now()->addDays($serviceSettings->default_invoice_due_date_days);
                        $invoiceData['visible'] = false;
                        $this->invoiceService->generateInvoice($invoiceData);*/

                        //Since the invoice does not exist yet, we will adjust the order
                        $insertData = [];
                        $adjustments = $invoiceData['adjustments'];
                        foreach($adjustments as $key => $adjustment) {
                            $insertData[$key]['repair_order_id'] = $order->id;
                            $insertData[$key]['description'] = $adjustment['description'];
                            $insertData[$key]['charge'] = $adjustment['amount'];
                            $insertData[$key]['discount'] = 0;
                        }
                        $this->orderService->massInsertRepairAdjustments($insertData);

                        //Recalculate order total
                        $this->orderService->calculateTotalAfterAdjust($order->id, Order::REPAIR_ORDER);
                    }
                }
            }

            //Save any picked up panels
            $signPanels = json_decode($request->signPanels, true);
            if ($signPanels && count($signPanels['panel'])) {
                $this->orderService->storePanels($order->order->office_id, $order->order->agent_id, $signPanels);
            }

            //Send email to office/agent
            OrderCompletedEmail::dispatch($order, $orderType, $orderStatus, $outOfInventory);

            //Recalculate installer stop number
            $this->orderService->decreaseStopNumber($order->assigned_to, $order->stop_number, $order->service_date);

            return true;
        } else {
            abort('403');
        }
    }

    public function removalOrderComplete(Request $request)
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN || $authUser->role == User::ROLE_INSTALLER) {
            //return($request->all());
            $orderType = $request->orderType;
            $orderStatus = $request->orderStatus;

            $order = RemovalOrder::find($request->orderId);
            $serviceSettings = $service_settings = ServiceSetting::first();

            $postPoints = 0;
            $accessoryPoints = 0;
            $removalPoints = $serviceSettings->removal_points;
            $zonePoints = 0;

            //Only award zone points once per day
            $zoneId = $order->order->zone_id;
            $zone = Zone::find($zoneId);
            $zonePointsAwarded = $this->orderService->checkIfZonePointsAwarded(
                (int) $zoneId,
                (int) $order->assigned_to
            );
            if ($zone && ! $zonePointsAwarded) {
                $zonePoints = $zone->installer_points;

                //Store zone points
                $this->orderService->storeZonePoints(
                    [
                        'user_id' => $order->assigned_to,
                        'zone_id' => $zoneId,
                        'points' => $zonePoints
                    ]
                );
            }

            $order->status = RemovalOrder::STATUS_COMPLETED;

            $invoiceData = [];
            $lineIndex = 1;
            if ($orderStatus == 'incomplete') {
                //Who the order will belong to?
                $createdBy = $order->order->office->user->id;
                if ($order->agent) {
                    $createdBy = $order->order->agent->user->id;
                }

                //If post is missing then replace/repair post
                $missingPostId = $request->missingPostId;
                //$replaceRepairPost = null;
                if ($missingPostId > 0) {
                    //$replaceRepairPost = true;

                    //Need invoice missing items
                    $priceMissingPost = $this->orderService->getPriceMissingPost($missingPostId);
                    //Only charge if price > 0
                    if ($priceMissingPost > 0) {
                        $postName = $this->orderService->getPostName($missingPostId);
                        //Additional as invoice adjustment
                        /*$invoiceData['adjustments'][$lineIndex]['description'] = "{$order->order->address}: Missing Post {$postName}";
                        $invoiceData['adjustments'][$lineIndex]['amount'] = $priceMissingPost;*/
                        $invoiceData['line_items'][$lineIndex]['description'] = "Missing Post - {$postName}";
                        $invoiceData['line_items'][$lineIndex]['amount'] = $priceMissingPost;
                        $invoiceData['line_items'][$lineIndex]['order_id'] = $order->id;
                        $invoiceData['line_items'][$lineIndex]['order_type'] = InvoiceLine::ORDER_TYPE_REMOVAL;
                        $invoiceData['line_items'][$lineIndex]['visible'] = false;
                        $invoiceData['line_items'][$lineIndex]['missing_items'] = true;
                        $lineIndex++;
                    }
                }

                //No charge for missing panels since panels belong to customers, not PostReps
                /*$missingPanelId = $request->missingPanelId;
                $panelId = null;
                if ($missingPanelId > 0) {
                    $panelId = $missingPanelId;
                }*/

                //if accessories missing then add to repair order
                $missingAccessoriesIds = $request->missingAccessoriesIds;
                $accessoryIds = explode(',', $missingAccessoriesIds);
                if (count($accessoryIds)) {
                    foreach ($accessoryIds as $accessoryId) {
                        if ($accessoryId > 0) {
                            //Need invoice missing items
                            $priceMissingAccessory = $this->orderService->getPriceMissingAccessory($accessoryId);
                            if ($priceMissingAccessory > 0) {
                                $accessoryName = $this->orderService->getAccessoryName($accessoryId);
                                /*$invoiceData['adjustments'][$lineIndex]['description'] = "{$order->order->address}: Missing Accessory {$accessoryName}";
                                $invoiceData['adjustments'][$lineIndex]['amount'] = $priceMissingAccessory;*/
                                $invoiceData['line_items'][$lineIndex]['description'] = "Missing Accessory - {$accessoryName}";
                                $invoiceData['line_items'][$lineIndex]['amount'] = $priceMissingAccessory;
                                $invoiceData['line_items'][$lineIndex]['order_id'] = $order->id;
                                $invoiceData['line_items'][$lineIndex]['order_type'] = InvoiceLine::ORDER_TYPE_REMOVAL;
                                $invoiceData['line_items'][$lineIndex]['visible'] = false;
                                $invoiceData['line_items'][$lineIndex]['missing_items'] = true;
                                $lineIndex++;
                            }
                        }
                    }
                }
            }

            if ($request->removedPostId > 0) {
                //Assign points for Post
                $postPoints = $this->orderService->getPostPoints($request->removedPostId);
            }

            //Update inventory for all attached accessories
            $removedAccessoriesIds = $request->removedAccessoriesIds;
            $accessoryIds = explode(',', $removedAccessoriesIds);
            if (count($accessoryIds)) {
                foreach ($accessoryIds as $accessoryId) {
                    if ($accessoryId > 0) {
                        //Assign points for accessories
                        $accessoryPoints = $this->orderService->getAccessoryPoints($accessoryId);
                    }
                }
            }

            $order->post_points = $postPoints ? $postPoints : 0;
            $order->accessory_points = $accessoryPoints ? $accessoryPoints : 0;
            $order->zone_points = $zonePoints ? $zonePoints : 0;
            $order->removal_points = $removalPoints ? $removalPoints : 0;
            $order->installer_comments = $request->installer_comments;

            if ($request->has('photo1')) {
                $request->file = $request->photo1;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo1 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo2')) {
                $request->file = $request->photo2;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo2 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo3')) {
                $request->file = $request->photo3;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo3 = $uploadImg['fileName'];
                    }
                }
            }

            $order->date_completed = now();
            $order->save();

            //Dispatch job to recalculate inventory
            try {
                $items = [];
                if ($request->removedPostId > 0) {
                    //increment IN STORAGE for removed post
                    $this->orderService->updatePostInventoryInStorage($request->removedPostId, 1);

                    $items['postId'] = $request->removedPostId;
                }
                if ($request->removedPanelId > 0) {
                    $items['panelId'] = $request->removedPanelId;
                }
                $accessoryIds = explode(',', $removedAccessoriesIds);
                if (count($accessoryIds)) {
                    $items['accessories'] = $accessoryIds;
                }
                \App\Jobs\UpdateInventoryInFieldRemovalJob::dispatch($items, $order, $this->orderService);

                //Decrement qty in field if panel is missing
                if ($request->missingPanelId > 0) {
                    $this->orderService->updatePanelInventoryInfield($request->missingPanelId, -1);
                }
            } catch (Throwable $t) {
                logger()->error($t->getMessage());
            }

            if (count($invoiceData)) {
                $invoiceData['invoice_number'] = $this->invoiceService->incrementInvoiceNumber($order->invoice_number);
                $invoiceData['office_id'] = $order->order->office_id;
                $invoiceData['agent_id'] = $order->order->agent_id;
                $invoiceData['due_date'] = now()->addDays($serviceSettings->default_invoice_due_date_days);
                $invoiceData['visible'] = false;
                $invoiceData['missing_items'] = true;

                $this->invoiceService->generateInvoice($invoiceData);
            }

            //Save any picked up panels
            $signPanels = json_decode($request->signPanels, true);
            if ($signPanels && count($signPanels['panel'])) {
                $this->orderService->storePanels($order->order->office_id, $order->order->agent_id, $signPanels);
            }

            //Send email to office/agent
            OrderCompletedEmail::dispatch($order, $orderType, $orderStatus, '');

            //Recalculate installer stop number
            $this->orderService->decreaseStopNumber($order->assigned_to, $order->stop_number, $order->service_date);

            return true;
        } else {
            abort('403');
        }
    }

    public function deliveryOrderComplete(Request $request)
    {
        //info($request->all()); exit;

        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN || $authUser->role == User::ROLE_INSTALLER) {
            //return($request->all());
            $orderType = $request->orderType;

            $order = DeliveryOrder::find($request->orderId);
            $serviceSettings = $service_settings = ServiceSetting::first();

            $postPoints = 0;
            $accessoryPoints = 0;
            $deliveryPoints = $serviceSettings->delivery_points;
            $zonePoints = 0;

            //Only award zone points once per day
            $zone = Zone::find($order->zone_id);
            $zonePointsAwarded = $this->orderService->checkIfZonePointsAwarded(
                (int) $order->zone_id,
                (int) $order->assigned_to
            );
            if ($zone && ! $zonePointsAwarded) {
                $zonePoints = $zone->installer_points;

                //Store zone points
                $this->orderService->storeZonePoints(
                    [
                        'user_id' => $order->assigned_to,
                        'zone_id' => $order->zone_id,
                        'points' => $zonePoints
                    ]
                );
            }

            $order->status = DeliveryOrder::STATUS_COMPLETED;

            $pickupPanelIds = $request->pickupPanelIds;
            $panelIdsPickup = explode(',', $pickupPanelIds);
            $pickupQties = explode(',', $request->pickupPanelQty);
            if (count($panelIdsPickup)) {
                foreach ($panelIdsPickup as $i => $panelId) {
                    if ($panelId > 0) {
                        //increment IN STORAGE for panel
                        $qty = (int) $pickupQties[$i];
                        $this->orderService->updatePanelInventoryInStorage((int) $panelId, $qty);

                        //Generate item ID if new panel
                        $this->orderService->generatePanelItemId($panelId);
                    }
                }
            }

            $dropoffPanelIds = $request->dropoffPanelIds;
            $panelIdsDropOff = explode(',', $dropoffPanelIds);
            $dropoffQties = explode(',', $request->dropoffPanelQty);
            if (count($panelIdsDropOff)) {
                foreach ($panelIdsDropOff as $panelId) {
                    if ($panelId > 0) {
                        //decrement IN STORAGE for panel
                        $qty = (int) $dropoffQties[$i];
                        $qty = $qty * (-1);
                        $this->orderService->updatePanelInventoryInStorage((int) $panelId, $qty);
                    }
                }
            }

            $order->zone_points = $zonePoints ? $zonePoints : 0;
            $order->delivery_points = $deliveryPoints ? $deliveryPoints : 0;
            $order->installer_comments = $request->installer_comments;

            if ($request->has('photo1')) {
                $request->file = $request->photo1;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo1 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo2')) {
                $request->file = $request->photo2;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo2 = $uploadImg['fileName'];
                    }
                }
            }

            if ($request->has('photo3')) {
                $request->file = $request->photo3;
                $mime = strtolower($request->file->getMimeType());
                if (in_array($mime, $this->fileService->allowedImageMimes)) {
                    $uploadImg = $this->fileService->uploadImage($request, 0, 0);
                    //Save image path in Db
                    if ($uploadImg['success']) {
                        $order->photo3 = $uploadImg['fileName'];
                    }
                }
            }

            $order->date_completed = now();
            $order->save();

            //Send email to office/agent
            $orderStatus = 'complete';
            $missingPanelIds = '';
            OrderCompletedEmail::dispatch($order, $orderType, $orderStatus, $missingPanelIds);

            //Recalculate installer stop number
            $this->orderService->decreaseStopNumber($order->assigned_to, $order->stop_number, $order->service_date);

            return true;
        } else {
            abort('403');
        }
    }

    public function installerMapView($routeDate = '')
    {
        $routeDate = empty($routeDate) ? now()->format('Y-m-d') : $routeDate;

        //Get next stop
        $nextStop = $this->orderService->getInstallerNextStop(auth()->id(), $routeDate);

        if ($nextStop) {
            $orderType = $nextStop->order_type;
            $orderId = $nextStop->id;

            if ($orderType == 'install') {
                $order = Order::find($orderId);
            }

            if ($orderType == 'repair') {
                $order = RepairOrder::find($orderId);
            }

            if ($orderType == 'removal') {
                $order = RemovalOrder::find($orderId);
            }

            if ($orderType == 'delivery') {
                $order = DeliveryOrder::find($orderId);
            }

            //Stop numbers
            $countOrders = count($this->orderService->getAssignedInstallerOrders(auth()->id(), $routeDate));

            $data = [
                'orderType' => $orderType,
                'order' => $order,
                'countOrders' => $countOrders,
                'routeDate' => $routeDate
            ];
        } else {
            $data = [
                'orderType' => 'none',
                'order' => [],
                'countOrders' => 0,
                'routeDate' => $routeDate
            ];
        }

        return view('users.installer.map_view', $data);
    }

    public function installerMapViewOrder($orderType, $orderId, $routeDate = '')
    {
        $routeDate = empty($routeDate) ? now()->format('Y-m-d') : $routeDate;

        if ($orderType == 'install') {
            $order = Order::find($orderId);
        }

        if ($orderType == 'repair') {
            $order = RepairOrder::find($orderId);
        }

        if ($orderType == 'removal') {
            $order = RemovalOrder::find($orderId);
        }

        if ($orderType == 'delivery') {
            $order = DeliveryOrder::find($orderId);
        }

        //Stop numbers
        $countOrders = count($this->orderService->getAssignedInstallerOrders(auth()->id(), $routeDate));

        $data = [
            'orderType' => $orderType,
            'order' => $order,
            'countOrders' => $countOrders,
            'routeDate' => $routeDate
        ];

        return view('users.installer.map_view', $data);
    }

    public function installerPullList($routeDate = '')
    {
        $routeDate = empty($routeDate) ? now()->format('Y-m-d') : $routeDate;

        $pullList = $this->orderService->getInstallerPullList(auth()->id(), $routeDate);
        //dd($pullList);
        $data['pullList'] = $pullList;
        $data['routeDate'] = $routeDate;

        return view('users.installer.pull_list', $data);
    }

    public function getOrderByTypeAndId($orderId, $orderType)
    {
        return $this->orderService->getOrderByTypeAndId($orderId, $orderType);
    }

    public function orderStatusPullList($routeDate = '', $installerId = 0)
    {
        if ( ! $routeDate ) {
            $routeDate = session('routeDate') ?? now()->format('Y-m-d');
        }

        session(['routeDate' => $routeDate]);

        if ( ! $installerId) {
            session(['installerId' => 0]);
            $pullList = $this->orderService->getAllPullLists($routeDate);
        } else {
            session(['installerId' => $installerId]);
            $pullList = $this->orderService->getInstallerPullList($installerId, $routeDate);
        }

        $offices = $this->officeService->getAll();
        $serviceSettings = $service_settings = ServiceSetting::first();
        $installers = $this->userService->getActiveInstallers();

        $data = compact('installers', 'pullList', 'routeDate', 'offices', 'service_settings', 'serviceSettings');

        return view('orders.status.admin_pull_list', $data);
    }

    public function sendActionNeededEmail(Request $request)
    {
        try {
            return $this->notificationService->sendActionNeededEmail(
                $request->order_type,
                (int) $request->order_id
            );
        } catch (Throwable $e) {
            logger()->error($e->getMessage());
        }
    }
}
