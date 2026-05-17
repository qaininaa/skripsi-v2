<?php

namespace App\Http\Requests\Report;

use Domain\Report\Dtos\DuplicateSectionDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin-only: duplicate a section instance into a new sibling.
 */
class DuplicateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): DuplicateSectionDto
    {
        return new DuplicateSectionDto($this->validated());
    }
}
