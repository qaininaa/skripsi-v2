<?php

namespace App\Http\Requests\ReportTemplate;

use Domain\ReportTemplate\Dtos\CreateSectionDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measurement_unit'  => ['required', 'string', 'max:50'],
            'measurement_type'  => ['required', Rule::in(['settle_plate', 'air_sampler', 'contact_plate', 'swab'])],
            'max_column'        => ['required', 'integer', 'min:1', 'max:20'],
            'column_label'      => ['nullable', 'string', 'max:50'],
            'time_slot_type'    => ['required', Rule::in(['by_location', 'start_end', 'start_end_ab', 'start_end_multi'])],
            'has_machine_setup' => ['nullable'],
            'order'             => ['required', 'integer', 'min:1'],
        ];
    }

    public function toDTO(string $reportTemplateId): CreateSectionDto
    {
        return new CreateSectionDto(array_merge($this->validated(), [
            'report_template_id' => $reportTemplateId,
            'has_machine_setup'  => $this->boolean('has_machine_setup'),
        ]));
    }
}
