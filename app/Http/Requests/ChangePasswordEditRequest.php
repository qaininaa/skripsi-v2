<?php

namespace App\Http\Requests;

use Domain\User\Dtos\GetChangePasswordNoticeDto;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'in:initial,expired'],
        ];
    }

    public function toDTO(): GetChangePasswordNoticeDto
    {
        return new GetChangePasswordNoticeDto($this->validated());
    }
}
