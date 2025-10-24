<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ValidationException extends Exception
{
    /**
     * The validation errors.
     */
    protected array $errors;

    /**
     * The validator instance.
     */
    protected $validator;

    /**
     * Create a new validation exception.
     */
    public function __construct(
        $validator,
        string $message = 'Validation failed',
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->validator = $validator;
        $this->errors = $validator->errors()->toArray();
    }

    /**
     * Get the validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the validator instance.
     */
    public function getValidator()
    {
        return $this->validator;
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
                'errors' => $this->getErrors(),
                'error_code' => 'VALIDATION_ERROR',
            ], $this->getCode());
        }

        return redirect()->back()
            ->withErrors($this->getErrors())
            ->withInput()
            ->with('error', $this->getMessage());
    }
}
