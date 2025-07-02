<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
// use App\Http\Traits\HelperTrait;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
// use App\Services\AuthorizeNetService;
// use App\Services\OrderService;
// use App\Services\PaymentService;
// use App\Services\NotificationService;
use App\Models\ServiceSetting;

class CommunicationsController extends Controller
{

    // use HelperTrait;

    // protected $notificationService;

    protected $officeService;

    protected $postService;

    protected $panelService;

    protected $accessoryService;

    // protected $orderService;

    // protected $authorizenetService;

    // protected $paymentService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        // NotificationService $notificationService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService
        // OrderService $orderService,
        // AuthorizeNetService $authorizenetService,
        // PaymentService $paymentService
    ) {
        // $this->notificationService = $notificationService;

        $this->officeService = $officeService;

        $this->postService = $postService;

        $this->panelService = $panelService;

        $this->accessoryService = $accessoryService;

        // $this->orderService = $orderService;

        // $this->authorizenetService = $authorizenetService;

        // $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $offices = $this->officeService->getAll();
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();

            $service_settings = ServiceSetting::first();

            $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings');

            return view('communications.index', $data);
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
