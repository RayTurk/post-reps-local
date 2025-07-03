<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInstallerPaymentRequest;
use App\Http\Requests\CreatePayment;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\PostService;
use App\Services\UserService;
use App\Models\ServiceSetting;
use App\Services\PanelService;
use App\Services\OfficeService;
use App\Http\Traits\HelperTrait;
use App\Models\DeliveryOrder;
use App\Models\InstallerPayment;
use App\Models\RemovalOrder;
use App\Models\RepairOrder;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\AccessoryService;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AccountingInstallerPointsController extends Controller
{

    use HelperTrait;

    protected $invoiceService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $paymentService;
    protected $userService;

    public function __construct(
        InvoiceService $invoiceService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService,
        PaymentService $paymentService,
        UserService $userService
    ) {
        $this->invoiceService = $invoiceService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $offices = $this->officeService->getAll();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();
        $installers = $this->userService->getActiveInstallers();

        $serviceSettings = $service_settings = ServiceSetting::first();

        $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings', 'installers');

        return view('accounting.installer_points.index', $data);
    }

    public function datatable()
    {
        return $this->paymentService->installerPointsDatatable();
    }

    public function editPoints(Request $request)
    {

        switch ($request->orderType) {
            case 'install':
                $order = Order::find($request->orderId);
                $order_points = $order->install_points;
                break;

            case 'repair':
                $order = RepairOrder::find($request->orderId);
                $order_points = $order->repair_points;
                break;

            case 'removal':
                $order = RemovalOrder::find($request->orderId);
                $order_points = $order->removal_points;
                break;

            case 'delivery':
                $order = DeliveryOrder::find($request->orderId);
                $order_points = $order->delivery_points;
                break;

            default:
                # code...
                break;
        }

        $installerPoints = $order->post_points + $order->accessory_points + $order->zone_points + $order_points;

        $adjustment = $request->points_adjustment_qty - $installerPoints;

        $order->points_adjustment = $adjustment;
        $order->save();

        return $this->backWithSuccess('Points updated successfully');

    }

    public function paymentsDatatable()
    {
        return $this->paymentService->installerPaymentsDatatable();
    }

    public function createPayment(CreateInstallerPaymentRequest $request)
    {
        // dd($request->all());

        $data = $request->validated();

        try {
            InstallerPayment::create([
                'user_id' => $data['user_id'],
                'check_number' => $data['payment_check_number'],
                'amount' => $data['payment_amount'],
                'comments' => $data['payment_comments']
            ]);

            return response("Payment created successfully", Response::HTTP_OK);

        } catch (\Exception $ex) {
            logger($ex->getMessage());
            return response($this->serverErrorMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function showPayment($id)
    {
        $installerPayment = InstallerPayment::find($id);

        return $installerPayment;
    }

    public function editPayment(CreateInstallerPaymentRequest $request, $id)
    {

        $data = $request->validated();

        try {
            $installerPayment = InstallerPayment::find($id);
            $installerPayment->amount = $data['payment_amount'];
            $installerPayment->check_number = $data['payment_check_number'];
            $installerPayment->comments = $data['payment_comments'];
            $installerPayment->update();

            return response("Payment updated successfully", Response::HTTP_OK);
        } catch (\Exception $ex) {
            logger($ex->getMessage());
            return response($this->serverErrorMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function cancelPayment($id)
    {
        try {
            $installerPayment = InstallerPayment::find($id);
            $installerPayment->canceled = true;
            $installerPayment->update();
            return response("Payment canceled successfully.", Response::HTTP_OK);
        } catch (\Exception $ex) {
            logger($ex->getMessage());
            return response($this->serverErrorMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
