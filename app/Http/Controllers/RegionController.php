<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\OfficeService;
use App\Services\RegionService;
use App\Services\UserService;
use App\Http\Requests\{CreateRegion, UpdateRegion};
use App\Models\ServiceSetting;

class RegionController extends Controller
{
    use HelperTrait;

    protected $officeService;
    protected $regionService;
    protected $userService;

    public function __construct(
        OfficeService $officeService,
        RegionService $regionService,
        UserService $userService
     ) {
        $this->officeService = $officeService;

        $this->regionService = $regionService;

        $this->userService = $userService;
     }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [
            'offices' => $this->officeService->getAll(),
            'states' => $this->getStates(),
            'regions' => $this->regionService->getAll(),
            'service_settings' => ServiceSetting::first()
        ];

        return view('region.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [
            'states' => $this->getStates()
        ];

        return view('region.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateRegion $request)
    {
        $this->regionService->create($request->all());

        return $this->backWithSuccess('Region created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Region  $region
     * @return \Illuminate\Http\Response
     */
    public function show(Region $region)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Region  $region
     * @return \Illuminate\Http\Response
     */
    public function edit(Region $region)
    {
        $data = [
            'region' => $region,
            'states' => $this->getStates()
        ];

        return view('region.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Region  $region
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Region $region)
    {
        $region->update($request->all());

        return $this->backWithSuccess('Region updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Region  $region
     * @return \Illuminate\Http\Response
     */
    public function destroy(Region $region)
    {
        $region->delete();

        return $this->backWithSuccess('Region deleted successfully.');
    }
}
