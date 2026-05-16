<?php

namespace App\Http\Middleware;

use Closure;
use Domain\PasswordPolicy\Services\PasswordPolicyService;
use Domain\User\Dtos\CheckPasswordExpirationDto;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the password change requirement before allowing access to protected routes.
 *
 * Two scenarios trigger a redirect to the password change form:
 * 1. Initial password - password_changed_at is null (new user, never changed password).
 * 2. Expired password - password_changed_at is set but the expiration period has passed.
 *
 * The 'super' role is exempt from this check.
 *
 */
class EnsurePasswordChanged
{
    public function __construct(
        protected PasswordPolicyService $passwordPolicyService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  
     * @param  Closure  $next    
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'super') {
            return $next($request);
        }

        $isPasswordExpired = $this->passwordPolicyService->isPasswordExpired(
            new CheckPasswordExpirationDto($user->password_changed_at)
        );

        if ($request->routeIs('password.change.*')) {
            if ($user->password_changed_at !== null && !$isPasswordExpired) {
                return redirect()->route('dashboard');
            }

            return $next($request);
        }

        if ($user->password_changed_at === null) {
            return redirect()->route('password.change.form', ['reason' => 'initial']);
        }

        if ($isPasswordExpired) {
            return redirect()->route('password.change.form', ['reason' => 'expired']);
        }

        return $next($request);
    }
}
