<?php

namespace App\Http\Requests\ReportFill;

use App\Rules\CurrentUserPassword;
use App\Rules\CurrentUserUsername;
use App\Rules\MicrobialCount;
use Domain\Report\Dtos\SaveMonitoringDto;
use Domain\Report\Services\MonitoringService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'      => ['required', Rule::in([
                MonitoringService::ACTION_DRAFT,
                MonitoringService::ACTION_RELEASE,
                MonitoringService::ACTION_FINALIZE,
                MonitoringService::ACTION_TO_READING,
                MonitoringService::ACTION_FINALIZE_TO_REVIEW,
            ])],
            'username'    => ['required', 'string', 'max:255', new CurrentUserUsername()],
            'password'    => ['required', 'string', new CurrentUserPassword()],

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

            // sections[{id}][note]
            'sections'                                          => ['nullable', 'array'],
            'sections.*.note'                                   => ['nullable', 'string', 'max:5000'],

            // sections[{id}][columns][{idx}][column_label_value]
            'sections.*.columns'                                => ['nullable', 'array'],
            'sections.*.columns.*.column_label_value'           => ['nullable', 'string', 'max:20', new MicrobialCount()],

            // sections[{id}][columns][{idx}][slots][{label}][time_*]
            'sections.*.columns.*.slots'                        => ['nullable', 'array'],
            'sections.*.columns.*.slots.*.time_start'           => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'sections.*.columns.*.slots.*.time_end'             => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'sections.*.columns.*.slots.*.time_start.regex' => 'Format jam harus HH:MM.',
            'sections.*.columns.*.slots.*.time_end.regex'   => 'Format jam harus HH:MM.',
        ];
    }

    public function action(): string
    {
        return (string) $this->validated('action');
    }

    public function toDTO(): SaveMonitoringDto
    {
        $data = $this->validated();
        unset($data['action'], $data['username'], $data['password']);
        return new SaveMonitoringDto($data);
    }
}
