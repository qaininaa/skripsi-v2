<?php

namespace App\Http\Controllers\PasswordPolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordSettingUpdateRequest;
use Domain\PasswordPolicy\Services\PasswordSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordSettingController extends Controller
{
    public function __construct(
        protected PasswordSettingService $passwordSettingService
    ) {
    }

    public function index(): View
    {
        $settings = $this->passwordSettingService->getSettings();

        return view('password-setting.password-setting', compact('settings'));
    }

    public function update(PasswordSettingUpdateRequest $request): RedirectResponse
    {
        $this->passwordSettingService->updateSettings($request->toDTO());

        return redirect()
            ->route('settings.index')
            ->with('success', 'Pengaturan password berhasil diperbarui.');
    }
}
