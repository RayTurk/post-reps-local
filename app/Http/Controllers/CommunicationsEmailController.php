<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Jobs\SendCommunicationsEmail;
use App\Services\NoticeService;
use App\Models\ServiceSetting;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Http\Requests\SendCommunicationsEmail as SendEmailRequest;

class CommunicationsEmailController extends Controller
{

    use HelperTrait;

    protected $noticeService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;
    protected $userService;
    protected $notificationService;

    public function __construct(
        NoticeService $noticeService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService,
        UserService $userService
    ) {
        $this->noticeService = $noticeService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
        $this->userService = $userService;
        $this->notificationService = new NotificationService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $offices = $this->officeService->getAll();
        $installers = $this->userService->getInstallers();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();
        $serviceSettings = $service_settings = ServiceSetting::first();

        $data = compact('offices', 'installers', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

        return view('communications.emails.index', $data);
    }

    public function sendCommunicationsEmail(SendEmailRequest $request)
    {
        try {
            SendCommunicationsEmail::dispatch($request->validated());
            return $this->backWithSuccess('Your email has been sent.');
        } catch (\Exception $ex) {
            logger()->error($ex->getMessage());
            return $this->backWithError($this->serverErrorMessage());
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
