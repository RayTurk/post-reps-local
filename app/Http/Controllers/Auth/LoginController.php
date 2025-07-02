<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\HelperTrait;
use App\Providers\RouteServiceProvider;
use App\Services\RecaptchaService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use HelperTrait;
    protected $recaptchaService;
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(RecaptchaService $recaptchaService)
    {
        $this->middleware('guest')->except('logout');
        $this->recaptchaService = $recaptchaService;
    }

    public function showLoginForm()
    {
        /*$baseUrl = config('app.url').'/';
        $loginUrl = "{$baseUrl}login";
        if (
            url()->previous() != 'http://localhost:3000/'
            && url()->previous() != 'https://postreps.ecbc-dev.tech/'
            && url()->previous() != $baseUrl
            && url()->previous() != $loginUrl
        ) {
            if( ! session()->has('url.intended') ) {
                session(['url.intended' => url()->previous()]);
            }
        }*/

        return view('auth.login');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'recaptcha_token' => 'required|string'
        ]);

        //Validate recaptcha
        $validRecaptcha = $this->recaptchaService->validate($request->recaptcha_token);
        if (! $validRecaptcha) {
            throw ValidationException::withMessages([
                'recaptcha_token' => 'Recaptcha failed'
            ]);
            // return $this->backWithError('Recaptcha failed');
        }
    }
}
