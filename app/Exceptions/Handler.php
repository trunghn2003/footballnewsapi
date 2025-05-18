<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
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
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return $this->errorResponse($exception->getMessage(), 404);
        }

        // Handle ValidationException

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return $this->errorResponse(
                'Validation error',
                422,
                $exception->errors()
            );
        }

        // Catch other uncaught exceptions
        \Log::error($exception->getMessage(), [
            'exception' => $exception,
            'request' => $request->all(),
        ]);
        return $this->errorResponse(
            'Something went wrong',
            500,
            env('APP_DEBUG') ? $exception->getMessage() : null
        );
    }
}
