<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeInitialPasswordRequest;
use App\Http\Requests\ChangePasswordEditRequest;
use Domain\User\Services\ChangePasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(
        protected ChangePasswordService $changePasswordService
    ) {}

    public function edit(ChangePasswordEditRequest $request): View
    {
        return view('auth.change-password', [
            'passwordNotice' => $this->changePasswordService->getChangePasswordNotice(
                $request->user(),
                $request->toDTO(),
            ),
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
