<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeInitialPasswordRequest;
use Domain\User\Dtos\CheckPasswordExpirationDto;
use Domain\User\Services\ChangePasswordService;
use Domain\User\Services\PasswordSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(
        protected ChangePasswordService $changePasswordService,
        protected PasswordSettingService $passwordSettingService
    ) {
    }

    public function edit(): View
    {
        $reason = request()->query('reason');
        $user = request()->user();
        $isPasswordExpired = $reason === 'expired'
            || ($user !== null && $this->passwordSettingService->isPasswordExpired(
                new CheckPasswordExpirationDto($user->password_changed_at)
            ));

        if ($reason === 'initial' || ($user !== null && $user->password_changed_at === null)) {
            $notice = 'Demi keamanan akun, Anda wajib mengganti password default sebelum melanjutkan.';
        } elseif ($isPasswordExpired) {
            $notice = 'Password Anda sudah kedaluwarsa. Demi keamanan akun, Anda wajib membuat password baru sebelum melanjutkan.';
        } else {
            $notice = 'Silakan ubah password Anda untuk menjaga keamanan akun.';
        }

        return view('auth.change-password', [
            'passwordNotice' => $notice,
        ]);
    }

    public function update(ChangeInitialPasswordRequest $request): RedirectResponse
    {
        $this->changePasswordService->changePassword($request->user(), $request->toDTO());

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password berhasil diperbarui.');
    }
}
