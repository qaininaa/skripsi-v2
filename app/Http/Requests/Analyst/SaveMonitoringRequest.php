<?php

namespace App\Http\Requests\Analyst;

use Domain\Report\Dtos\SaveMonitoringDto;
use Illuminate\Foundation\Http\FormRequest;

class SaveMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instruments'                              => ['nullable', 'array'],
            'instruments.*.no_id'                      => ['nullable', 'string', 'max:255'],
            'instruments.*.calibration_date'           => ['nullable', 'date'],
            'instruments.*.due_date'                   => ['nullable', 'date'],

            'mediums'                                  => ['nullable', 'array'],
            'mediums.*.batch_number'                   => ['nullable', 'string', 'max:255'],
            'mediums.*.gpt_number'                     => ['nullable', 'string', 'max:255'],
            'mediums.*.expiration_date'                => ['nullable', 'date'],

            'incubators'                               => ['nullable', 'array'],
            'incubators.*.no_id'                       => ['nullable', 'string', 'max:255'],
            'incubators.*.calibration_date'            => ['nullable', 'date'],
            'incubators.*.due_date_calibration'        => ['nullable', 'date'],
            'incubators.*.entries'                     => ['nullable', 'array'],
            'incubators.*.entries.*.date_in'           => ['nullable', 'date'],
            'incubators.*.entries.*.time_in'           => ['nullable', 'string', 'max:10'],
            'incubators.*.entries.*.date_out'          => ['nullable', 'date'],
            'incubators.*.entries.*.time_out'          => ['nullable', 'string', 'max:10'],
        ];
    }

    public function toDTO(): SaveMonitoringDto
    {
        return new SaveMonitoringDto($this->validated());
    }
}
