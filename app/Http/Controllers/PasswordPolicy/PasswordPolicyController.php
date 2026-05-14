<?php

namespace App\Http\Controllers\PasswordPolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordPolicyUpdateRequest;
use Domain\PasswordPolicy\Services\PasswordPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordPolicyController extends Controller
{
    public function __construct(
        protected PasswordPolicyService $passwordPolicyService
    ) {
    }

    public function index(): View
    {
        $settings = $this->passwordPolicyService->getSettings();

        return view('password-policy.index', compact('settings'));
    }

    public function update(PasswordPolicyUpdateRequest $request): RedirectResponse
    {
        $this->passwordPolicyService->updateSettings($request->toDTO());

        return redirect()
            ->route('settings.index')
            ->with('success', 'Pengaturan password berhasil diperbarui.');
    }
}
