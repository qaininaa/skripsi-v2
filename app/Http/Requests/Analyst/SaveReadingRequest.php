<?php

namespace App\Http\Requests\Analyst;

use App\Rules\CurrentUserPassword;
use App\Rules\CurrentUserUsername;
use App\Rules\MicrobialCount;
use Domain\Report\Dtos\SaveReadingDto;
use Domain\Report\Services\ReadingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'   => ['required', Rule::in([
                ReadingService::ACTION_DRAFT,
                ReadingService::ACTION_RELEASE,
                ReadingService::ACTION_FINALIZE,
            ])],
            'username' => ['required', 'string', 'max:255', new CurrentUserUsername()],
            'password' => ['required', 'string', new CurrentUserPassword()],

            'sections'                                              => ['nullable', 'array'],
            'sections.*.rows'                                       => ['nullable', 'array'],
            'sections.*.rows.*.readings'                            => ['nullable', 'array'],
            'sections.*.rows.*.readings.*.reading_total'            => ['nullable', 'string', 'max:10', new MicrobialCount()],
            'sections.*.rows.*.readings.*.reading_fungi'            => ['nullable', 'string', 'max:10', new MicrobialCount()],
        ];
    }

    public function action(): string
    {
        return (string) $this->validated('action');
    }

    public function toDTO(): SaveReadingDto
    {
        $data = $this->validated();
        unset($data['action'], $data['username'], $data['password']);
        return new SaveReadingDto($data);
    }
}
