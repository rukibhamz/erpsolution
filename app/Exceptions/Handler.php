<?php

namespace App\Exceptions;

use App\Services\ErrorHandlingService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        $errorHandlingService = new ErrorHandlingService();

        // Handle API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e, $errorHandlingService);
        }

        // Handle web requests
        return $this->handleWebException($request, $e, $errorHandlingService);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException(Request $request, Throwable $e, ErrorHandlingService $errorService): Response
    {
        $response = $errorService->handleApiError($e, 'API Request');
        
        return response()->json($response, $this->getHttpStatusCode($e));
    }

    /**
     * Handle web exceptions
     */
    protected function handleWebException(Request $request, Throwable $e, ErrorHandlingService $errorService): Response
    {
        // Handle specific exception types
        if ($e instanceof BusinessLogicException) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->with('error_code', $e->getErrorCode())
                ->with('error_context', $e->getContext());
        }

        if ($e instanceof ValidationException) {
            return redirect()->back()
                ->withErrors($e->getErrors())
                ->withInput()
                ->with('error', $e->getMessage());
        }

        if ($e instanceof AuthorizationException) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage())
                ->with('error_code', 'AUTHORIZATION_ERROR');
        }

        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please check your input and try again.');
        }

        // Handle HTTP exceptions
        if ($e instanceof HttpException) {
            return $this->handleHttpException($e);
        }

        // Handle other exceptions
        $response = $errorService->handleWebError($e, 'Web Request');
        
        return redirect()->back()
            ->with('error', $response['message'])
            ->with('error_id', $response['error_id']);
    }

    /**
     * Handle HTTP exceptions
     */
    protected function handleHttpException(HttpException $e): Response
    {
        $statusCode = $e->getStatusCode();
        
        switch ($statusCode) {
            case 404:
                return response()->view('errors.404', [], 404);
            case 403:
                return response()->view('errors.403', [], 403);
            case 500:
                return response()->view('errors.500', [], 500);
            default:
                return response()->view('errors.general', [
                    'statusCode' => $statusCode,
                    'message' => $e->getMessage()
                ], $statusCode);
        }
    }

    /**
     * Get HTTP status code for exception
     */
    protected function getHttpStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof BusinessLogicException) {
            return $e->getCode() ?: 400;
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        return 500;
    }
}
