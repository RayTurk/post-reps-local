<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HelperTrait;
use App\Services\OfficeService;
use App\Services\RegionService;
use App\Services\UserService;
use App\Http\Requests\{CreateInstaller, UpdateInstaller};
use App\Models\User;
use DB;
use Carbon\Carbon;
use App\Services\EmailResetPasswordService;
use App\Models\ServiceSetting;

class UserController extends Controller
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

        return view('users.index', $data);
    }

    public function getCurrentUserRole()
    {
        return auth()->user()->role;
    }

    public function getCurrentUserBalance()
    {
        return auth()->user()->balance;
    }

    public function datatableInstallers()
    {
        return $this->userService->datatableInstallers();
    }

    public function storeInstaller(CreateInstaller $request)
    {
        $data = $request->all();
        //dd($data);

        //create password for user
        $data['password'] = bcrypt('s');

        // set user role
        $data['role'] = User::ROLE_INSTALLER;

        $data['name'] = $data['first_name'] . " " . $data['last_name'];

        $data['hire_date'] = Carbon::parse($data['hire_date'])->format('Y-m-d');

        DB::transaction(function () use (&$data) {
            $user = $this->userService->create($data);

            //verify email
            $user->email_verified_at = now()->format('Y-m-d');

            $user->save();
        });

        return $this->backWithSuccess('Installer created successfully.');
    }

    public function getInstaller(User $user)
    {
        return $user;
    }

    public function updateInstaller(UpdateInstaller $request, User $user)
    {
        $data = $request->all();

        $data['name'] = $data['first_name'] . " " . $data['last_name'];

        $data['hire_date'] = Carbon::parse($data['hire_date'])->format('Y-m-d');

        $user->update($data);

        return $this->backWithSuccess('Installer updated successfully.');
    }

    public function resetInstallerPassword(Request $request, User $user)
    {
        $EmailResetPassword = new EmailResetPasswordService;
        $request->request->add(['email' => $user->email]);
        $EmailResetPassword->sendResetLinkEmail($request);

        return $this->backWithSuccess('Password reset link sent successfully.');
    }

    public function changeAuthPassword(Request $request)
    {
        $authUser = auth()->user();
        $authUser->password = bcrypt($request->newPassword);
        $authUser->save();

        return true;
    }

    public function officeAgents()
    {

        $authUser = auth()->user();

        $data = [
            'offices' => $this->officeService->getAll(),
            'states' => $this->getStates(),
            'regions' => $this->regionService->getAll(),
            'service_settings' => ServiceSetting::first(),
            'agents' => $this->officeService->getAgents($authUser->office->id),
        ];

        return view('users.office.index', $data);
    }

    public function getAgent()
    {
        $agent = auth()->user()->load(['agent']);

        return $agent;
    }

    public function getOffice()
    {
        $office = User::query()->where('id', auth()->user()->id)->with('office')->first();

        return $office;
    }
}