<?php

namespace App\Http\Requests;

use Domain\User\Dtos\GetUsersFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:super,admin,analyst,supervisor,manager'],
        ];
    }

    public function toDTO(): GetUsersFilterDto
    {
        return new GetUsersFilterDto($this->validated());
    }
}
