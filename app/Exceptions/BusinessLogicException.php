<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BusinessLogicException extends Exception
{
    /**
     * The error code for this exception.
     */
    protected string $errorCode;

    /**
     * Additional context data.
     */
    protected array $context;

    /**
     * Create a new business logic exception.
     */
    public function __construct(
        string $message = 'A business logic error occurred',
        string $errorCode = 'BUSINESS_LOGIC_ERROR',
        array $context = [],
        int $code = 400,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context data.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $this->getMessage(),
                'error_code' => $this->getErrorCode(),
                'context' => $this->getContext(),
            ], $this->getCode());
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->with('error_code', $this->getErrorCode())
            ->with('error_context', $this->getContext());
    }
}
