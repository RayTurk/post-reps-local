<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureOrderIsCompleted;
use App\Http\Requests\FeedbackRequest;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Models\Order;
use App\Services\OrderService;
use App\Models\ServiceSetting;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Services\UserService;
use Illuminate\Support\Carbon;

class FeedbackController extends Controller
{

    use HelperTrait;

    protected $orderService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $userService;

    public function __construct(
        OrderService $orderService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService,
        UserService $userService
    ) {
        $this->orderService = $orderService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->userService = $userService;
        $this->middleware('order.completed')->only(['create']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $orders = $this->orderService->getOrdersCompleted();
        $offices = $this->officeService->getAll();
        $installers = $this->userService->getInstallers();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();
        $service_settings = $service_settings = ServiceSetting::first();

        $data = compact('orders', 'offices', 'installers', 'posts', 'panels', 'accessories', 'service_settings');

        return view('communications.feedback.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $order = $this->orderService->findCompletedByTypeAndId($request->type, $request->id);

        $data = compact('order');

        return view('communications.feedback.feedback_from', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FeedbackRequest $request)
    {
        try {
            $order = $this->orderService->findCompletedByTypeAndId($request->type, $request->id);

            $date = Carbon::today();

            $order->rating = $request->rating;
            $order->feedback = $request->feedback;
            $order->feedback_date = $date;

            $order->save();

            return $this->backWithSuccess("Your feedback was submitted successfully. Thank you.");
        } catch (\Exception $ex) {
            return $this->backWithError($this->serverErrorMessage());
        }
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
        switch ($request->orderType) {
            case 'install':
                $order = $this->orderService->findById($request->id);
                if ($request->has("publish_checkbox")) {
                    $order->feedback_published = true;
                } else {
                    $order->feedback_published = false;
                }
                $order->save();
                break;

            case 'repair':
                $order = $this->orderService->findRepairOrderById($request->id);
                if ($request->has("publish_checkbox")) {
                    $order->feedback_published = true;
                } else {
                    $order->feedback_published = false;
                }
                $order->save();
                break;

            case 'removal':
                $order = $this->orderService->findRemovalOrderById($request->id);
                if ($request->has("publish_checkbox")) {
                    $order->feedback_published = true;
                } else {
                    $order->feedback_published = false;
                }
                $order->save();
                break;

            case 'delivery':
                $order = $this->orderService->findDeliveryOrderById($request->id);
                if ($request->has("publish_checkbox")) {
                    $order->feedback_published = true;
                } else {
                    $order->feedback_published = false;
                }
                $order->save();
                break;

            default:
                return;
                break;
        }
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
