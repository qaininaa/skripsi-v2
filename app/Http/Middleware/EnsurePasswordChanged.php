<?php

namespace App\Http\Middleware;

use Closure;
use Domain\PasswordPolicy\Services\PasswordPolicyService;
use Domain\User\Dtos\CheckPasswordExpirationDto;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function __construct(
        protected PasswordPolicyService $passwordPolicyService
    ) {
    }

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
