<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeInitialPasswordRequest;
use Domain\User\Services\InitialPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InitialPasswordController extends Controller
{
    public function __construct(
        protected InitialPasswordService $initialPasswordService
    ) {
    }

    public function edit(): View
    {
        return view('auth.change-initial-password');
    }

    public function update(ChangeInitialPasswordRequest $request): RedirectResponse
    {
        $this->initialPasswordService->changePassword($request->user(), $request->toDTO());

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password berhasil diperbarui.');
    }
}
