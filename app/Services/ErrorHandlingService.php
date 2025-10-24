<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SystemErrorNotification;
use Exception;
use Throwable;

class ErrorHandlingService
{
    /**
     * Handle and log errors with appropriate responses
     */
    public function handleError(Throwable $exception, string $context = ''): array
    {
        $errorId = uniqid('err_', true);
        
        // Log the error with context
        $this->logError($exception, $context, $errorId);
        
        // Determine error type and response
        $errorType = $this->getErrorType($exception);
        $response = $this->getErrorResponse($exception, $errorType, $errorId);
        
        // Send notifications for critical errors
        if ($this->isCriticalError($exception)) {
            $this->sendErrorNotification($exception, $context, $errorId);
        }
        
        return $response;
    }

    /**
     * Log error with detailed context
     */
    private function logError(Throwable $exception, string $context, string $errorId): void
    {
        $logData = [
            'error_id' => $errorId,
            'context' => $context,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'url' => request()->url(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($exception instanceof BusinessLogicException) {
            $logData['error_code'] = $exception->getErrorCode();
            $logData['context_data'] = $exception->getContext();
        }

        Log::error("Error ID: {$errorId} - {$exception->getMessage()}", $logData);
    }

    /**
     * Determine the type of error
     */
    private function getErrorType(Throwable $exception): string
    {
        if ($exception instanceof BusinessLogicException) {
            return 'business_logic';
        }
        
        if ($exception instanceof ValidationException) {
            return 'validation';
        }
        
        if ($exception instanceof AuthorizationException) {
            return 'authorization';
        }
        
        if ($exception instanceof \Illuminate\Database\QueryException) {
            return 'database';
        }
        
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return 'validation';
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return 'not_found';
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return 'method_not_allowed';
        }
        
        return 'general';
    }

    /**
     * Get appropriate error response
     */
    private function getErrorResponse(Throwable $exception, string $errorType, string $errorId): array
    {
        $baseResponse = [
            'error' => true,
            'error_id' => $errorId,
            'error_type' => $errorType,
            'message' => $this->getUserFriendlyMessage($exception, $errorType),
        ];

        switch ($errorType) {
            case 'business_logic':
                return array_merge($baseResponse, [
                    'error_code' => $exception->getErrorCode(),
                    'context' => $exception->getContext(),
                ]);
                
            case 'validation':
                return array_merge($baseResponse, [
                    'errors' => $exception->getErrors(),
                ]);
                
            case 'authorization':
                return array_merge($baseResponse, [
                    'required_permission' => $exception->getPermission(),
                    'required_role' => $exception->getRole(),
                ]);
                
            case 'database':
                return array_merge($baseResponse, [
                    'message' => 'A database error occurred. Please try again later.',
                    'technical_message' => config('app.debug') ? $exception->getMessage() : null,
                ]);
                
            case 'not_found':
                return array_merge($baseResponse, [
                    'message' => 'The requested resource was not found.',
                ]);
                
            case 'method_not_allowed':
                return array_merge($baseResponse, [
                    'message' => 'The requested method is not allowed.',
                ]);
                
            default:
                return array_merge($baseResponse, [
                    'message' => 'An unexpected error occurred. Please try again later.',
                    'technical_message' => config('app.debug') ? $exception->getMessage() : null,
                ]);
        }
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyMessage(Throwable $exception, string $errorType): string
    {
        $messages = [
            'business_logic' => $exception->getMessage(),
            'validation' => 'Please check your input and try again.',
            'authorization' => 'You do not have permission to perform this action.',
            'database' => 'A database error occurred. Please try again later.',
            'not_found' => 'The requested resource was not found.',
            'method_not_allowed' => 'The requested method is not allowed.',
            'general' => 'An unexpected error occurred. Please try again later.',
        ];

        return $messages[$errorType] ?? 'An error occurred. Please try again.';
    }

    /**
     * Check if error is critical and requires notification
     */
    private function isCriticalError(Throwable $exception): bool
    {
        $criticalTypes = [
            'database',
            'general'
        ];
        
        $errorType = $this->getErrorType($exception);
        
        return in_array($errorType, $criticalTypes) || 
               $exception->getCode() >= 500;
    }

    /**
     * Send error notification to administrators
     */
    private function sendErrorNotification(Throwable $exception, string $context, string $errorId): void
    {
        try {
            $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($adminUsers as $admin) {
                $admin->notify(new SystemErrorNotification($exception, $context, $errorId));
            }
        } catch (Exception $e) {
            Log::error('Failed to send error notification', [
                'original_error' => $exception->getMessage(),
                'notification_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle API errors
     */
    public function handleApiError(Throwable $exception, string $context = ''): array
    {
        $response = $this->handleError($exception, $context);
        
        // Add API-specific information
        $response['timestamp'] = now()->toISOString();
        $response['path'] = request()->path();
        $response['method'] = request()->method();
        
        return $response;
    }

    /**
     * Handle web errors
     */
    public function handleWebError(Throwable $exception, string $context = ''): array
    {
        $response = $this->handleError($exception, $context);
        
        // Add web-specific information
        $response['redirect_url'] = $this->getRedirectUrl($exception);
        $response['flash_message'] = $response['message'];
        
        return $response;
    }

    /**
     * Get appropriate redirect URL based on error type
     */
    private function getRedirectUrl(Throwable $exception): string
    {
        if ($exception instanceof AuthorizationException) {
            return route('dashboard');
        }
        
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return route('dashboard');
        }
        
        return url()->previous() ?: route('dashboard');
    }

    /**
     * Create a business logic exception
     */
    public function createBusinessLogicException(
        string $message,
        string $errorCode = 'BUSINESS_LOGIC_ERROR',
        array $context = []
    ): BusinessLogicException {
        return new BusinessLogicException($message, $errorCode, $context);
    }

    /**
     * Create a validation exception
     */
    public function createValidationException($validator): ValidationException
    {
        return new ValidationException($validator);
    }

    /**
     * Create an authorization exception
     */
    public function createAuthorizationException(
        string $message = 'You do not have permission to perform this action',
        string $permission = '',
        string $role = ''
    ): AuthorizationException {
        return new AuthorizationException($message, $permission, $role);
    }
}
