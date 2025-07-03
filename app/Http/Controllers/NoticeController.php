<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNotice;
use App\Http\Requests\UpdateNotice;
use App\Http\Traits\HelperTrait;
use App\Models\Notice;
use Illuminate\Http\Request;
use App\Services\NoticeService;
use App\Models\ServiceSetting;
use App\Services\OfficeService;
use App\Services\PanelService;
use App\Services\PostService;
use App\Services\AccessoryService;
use Carbon\Carbon;

class NoticeController extends Controller
{

    use HelperTrait;

    protected $noticeService;
    protected $officeService;
    protected $postService;
    protected $panelService;
    protected $accessoryService;

    public function __construct(
        NoticeService $noticeService,
        OfficeService $officeService,
        PostService $postService,
        PanelService $panelService,
        AccessoryService $accessoryService
    ) {
        $this->noticeService = $noticeService;
        $this->officeService = $officeService;
        $this->postService = $postService;
        $this->panelService = $panelService;
        $this->accessoryService = $accessoryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $notices = $this->noticeService->getActive();
        $expiredNotices = $this->noticeService->getExpired();

        //These are necessary so we can access install order modal from any page
        $offices = $this->officeService->getAll();
        $posts = $this->postService->getOrderByListingOrderAndName();
        $panels = $this->panelService->getOrderByListingOrderAndName();
        $accessories = $this->accessoryService->getOrderByListingOrderAndName();
        $serviceSettings = $service_settings = ServiceSetting::first();

        $data = compact('notices', 'expiredNotices', 'offices', 'accessories', 'panels', 'posts', 'service_settings', 'serviceSettings');

        return view('communications.notices.index', $data);
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
    public function store(CreateNotice $request)
    {
        $data = $request->validated();

        try {
            $data['start_date'] = Carbon::createFromFormat('m/d/Y', $data['start_date'])->format('Y-m-d');
            $data['end_date'] = Carbon::createFromFormat('m/d/Y', $data['end_date'])->format('Y-m-d');

            $this->noticeService->create($data);
            return $this->backWithSuccess("Notice created successfully.");
        } catch (\Illuminate\Database\QueryException $ex) {
            logger()->error($ex->getMessage());
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
        return $this->noticeService->getNotice($id);
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
    public function update(UpdateNotice $request, $id)
    {
        $data = $request->validated();

        // dd($data);

        try {
            $this->noticeService->update($data, $id);
            return $this->backWithSuccess("Notice updated successfully.");
        } catch (\Illuminate\Database\QueryException $ex) {
            logger()->error($ex->getMessage());
            return $this->backWithError($this->serverErrorMessage());
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
        try {
            $this->noticeService->delete($id);
            return $this->backWithSuccess("Notice deleted successfully.");
        } catch (\Illuminate\Database\QueryException $ex) {
            logger()->error($ex->getMessage());
            return $this->backWithError($this->serverErrorMessage());
        }
    }

    public function acknowledgeNotice(Notice $notice)
    {
        return $this->noticeService->acknowledgeNotice($notice);
    }
}
