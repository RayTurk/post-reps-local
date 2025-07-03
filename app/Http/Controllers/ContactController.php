<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\OrderService;
use App\Models\{ServiceSetting, User};
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Services\UserService;

class ContactController extends Controller
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
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        $orders = $this->orderService->getOrdersCompleted();
        $offices = $this->officeService->getAll();
        $installers = $this->userService->getInstallers();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();
        $service_settings = $service_settings = ServiceSetting::first();

        if ($user->role == User::ROLE_SUPER_ADMIN) {
            $data = compact('orders', 'offices', 'installers', 'posts', 'panels', 'accessories', 'service_settings', 'user');
        }

        if ($user->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($user->office->id);
            $data = compact('agents', 'orders', 'offices', 'installers', 'posts', 'panels', 'accessories', 'service_settings', 'user');
        }

        if ($user->role == User::ROLE_AGENT) {
            $data = compact('orders', 'offices', 'installers', 'posts', 'panels', 'accessories', 'service_settings', 'user');
        }

        return view('contact.index', $data);
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
