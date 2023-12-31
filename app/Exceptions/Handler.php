<?php

namespace App\Exceptions;

use App\Models\SystemLog;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
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
     * Convert an authentication exception into a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return !$request->expectsJson()
            ? redirect()->guest($exception->redirectTo() ?? route('main'))
                ->notify(trans('auth.unauthenticated'), 'error')
            : response()->json(['message' => $exception->getMessage()], 401);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Exception $e) {
            SystemLog::error($e->getMessage());
        });

        $this->renderable(function (HttpException $e, $request) {
            if ($e->getPrevious() instanceof TokenMismatchException) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'title'   => trans('exception.page_expired.title'),
                        'message' => trans('exception.page_expired.message'),
                        'action'  => trans('exception.page_expired.action'),
                    ], $e->getStatusCode(), $e->getHeaders());
                }
            }
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'title'   => trans('exception.unauthenticated.title'),
                    'message' => trans('exception.unauthenticated.message', ['reason' => $e->getMessage()]),
                    'action'  => trans('exception.unauthenticated.action'),
                ], 401);
            }
        });
    }
}
