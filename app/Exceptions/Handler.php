<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Http\Traits\HelperTrait;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    use HelperTrait;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e) {
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                logger()->error($e->getMessage());
                return redirect('/login');
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                if ($e->getStatusCode() == 419) {
                    logger()->error($e->getMessage());
                    return redirect('/login')->with('error','Your session expired due to inactivity. Please login again.');
                } elseif ($e->getStatusCode() == 403) { //The only way to prevent Laravel from displaying 403 page
                    logger()->error($e->getMessage());
                    return redirect('/dashboard')->with('error','We could not find the page you were trying to access.');
                } elseif ($e->getStatusCode() == 404) {
                    logger()->error($e->getMessage());
                    return redirect('/dashboard')->with('error','We could not find the page you were trying to access.');
                } elseif ($e->getStatusCode() == 405) { //Invalid http method for route
                    logger()->error($e->getMessage());
                    return redirect('/dashboard')->with('error','We could not find the page you were trying to access.');
                }
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                logger()->error($e->getMessage());
                return redirect('/dashboard')->with('error','We could not find the page you were trying to access.');
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                logger()->error($e->getMessage());
                return redirect('/dashboard')->with('error','We could not find the page you were trying to access.');
            } /*else {
                return redirect('/dashboard')->with('error','We could not find the page you were trying to access.');
            }*/
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return redirect('/login');
    }
}
