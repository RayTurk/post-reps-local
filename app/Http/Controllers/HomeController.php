<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Http\Traits\HelperTrait;
use App\Models\Payment;
use App\Models\ServiceSetting;
use App\Models\User;
use App\Services\AccessoryService;
use App\Services\AuthorizeNetService;
use App\Services\OfficeService;
use App\Services\OrderService;
use App\Services\PanelService;
use App\Services\PaymentService;
use App\Services\PostService;
use App\Services\NoticeService;
use App\Services\ZoneService;
use App\Services\RecaptchaService;
use Validator;

class HomeController extends Controller
{
    use HelperTrait;

    protected $notificationService;

    protected $officeService;

    protected $postService;

    protected $panelService;

    protected $accessoryService;

    protected $orderService;

    protected $authorizenetService;

    protected $paymentService;

    protected $noticeService;

    protected $zoneService;
    protected $recaptchaService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        NotificationService $notificationService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService,
        OrderService $orderService,
        AuthorizeNetService $authorizenetService,
        PaymentService $paymentService,
        NoticeService $noticeService,
        ZoneService $zoneService,
        RecaptchaService $recaptchaService
    ) {
        $this->notificationService = $notificationService;

        $this->officeService = $officeService;

        $this->postService = $postService;

        $this->panelService = $panelService;

        $this->accessoryService = $accessoryService;

        $this->orderService = $orderService;

        $this->authorizenetService = $authorizenetService;

        $this->paymentService = $paymentService;

        $this->noticeService = $noticeService;

        $this->zoneService = $zoneService;
        $this->recaptchaService = $recaptchaService;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($routeDate = '')
    {
        $authUser = auth()->user();

        if ($authUser->role == User::ROLE_SUPER_ADMIN) {
            $offices = $this->officeService->getAll();
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();
            $notices = $this->noticeService->getTodayNotices();

            $service_settings = ServiceSetting::first();

            $data = compact('offices', 'accessories', 'panels', 'posts', 'service_settings', 'notices');

            return view('orders.recent', $data);
        }

        if ($authUser->role == User::ROLE_OFFICE) {
            $agents = $this->officeService->getAgents($authUser->office->id);
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();
            $notices = $this->noticeService->getTodayNotices();
            $latestNotice = $this->noticeService->getLatestNotice();

            $service_settings = ServiceSetting::first();

            $data = compact('agents', 'accessories', 'panels', 'posts', 'service_settings', 'notices', 'latestNotice');

            return view('orders.office.recent', $data);
        }

        if ($authUser->role == User::ROLE_AGENT) {
            $posts = $this->postService->getOrderByListingOrderAndName();
            $panels = $this->panelService->getOrderByListingOrderAndName();
            $accessories = $this->accessoryService->getOrderByListingOrderAndName();
            $notices = $this->noticeService->getTodayNotices();
            $latestNotice = $this->noticeService->getLatestNotice();

            $service_settings = ServiceSetting::first();

            $data = compact('accessories', 'panels', 'posts', 'service_settings', 'notices', 'latestNotice');

            return view('orders.agent.recent', $data);
        }

        if ($authUser->role == User::ROLE_INSTALLER) {
            $routeDate = empty($routeDate) ? now()->format('Y-m-d') : $routeDate;

            $orders = $this->orderService->getAssignedInstallerOrders(auth()->id(), $routeDate);

            $data = compact('routeDate', 'orders');

            return view('users.installer.dashboard', $data);
        }

        abort(404);
    }

    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'message' => 'required|string',
            'email' => 'required|email',
            'recaptcha_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->backWithError($validator->messages()->first());
        }

        //Validate recaptcha
        $validRecaptcha = $this->recaptchaService->validate($request->recaptcha_token);
        if (! $validRecaptcha) {
            return $this->backWithError('Recaptcha failed');
        }

        $this->notificationService->contact($request->all());

        return $this->backWithSuccess('Message sent successfully. We will respond shortly.');
    }

    public function indexPage()
    {

        $orders = $this->orderService->getCompletedPublishedAndRated();
        $notices = $this->noticeService->getTodayNotices();

        $data = compact('notices', 'orders');

        return view('index', $data);
    }

    public function zones()
    {
        return $this->zoneService->getAll();
    }
}
