<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthorizationException extends Exception
{
    /**
     * The required permission.
     */
    protected string $permission;

    /**
     * The required role.
     */
    protected string $role;

    /**
     * Create a new authorization exception.
     */
    public function __construct(
        string $message = 'You do not have permission to perform this action',
        string $permission = '',
        string $role = '',
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->permission = $permission;
        $this->role = $role;
    }

    /**
     * Get the required permission.
     */
    public function getPermission(): string
    {
        return $this->permission;
    }

    /**
     * Get the required role.
     */
    public function getRole(): string
    {
        return $this->role;
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
                'error_code' => 'AUTHORIZATION_ERROR',
                'required_permission' => $this->getPermission(),
                'required_role' => $this->getRole(),
            ], $this->getCode());
        }

        return redirect()->back()
            ->with('error', $this->getMessage())
            ->with('error_code', 'AUTHORIZATION_ERROR');
    }
}
