<?php

namespace App\Http\Requests\ReportTemplate;

use Domain\ReportTemplate\Dtos\UpdateReportTemplateDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportTemplateUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                            => ['required', 'string', 'max:255'],
            'annex_number'                    => ['required', 'integer', 'min:1'],
            'sop_code'                        => ['required', 'string', 'max:100'],
            'sop_version'                     => ['required', 'string', 'max:50'],
            'medium_templates'                => ['required', 'array', 'min:1'],
            'medium_templates.*.name'         => ['required', 'string', 'max:255'],
            'incubator_templates'             => ['required', 'array', 'min:1'],
            'incubator_templates.*.label'     => ['required', 'string', 'max:100'],
            'incubator_templates.*.min_day'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'medium_templates.required'              => 'Minimal 1 medium template harus diisi.',
            'medium_templates.min'                   => 'Minimal 1 medium template harus diisi.',
            'medium_templates.*.name.required'       => 'Nama medium tidak boleh kosong.',
            'incubator_templates.required'           => 'Minimal 1 inkubator harus diisi.',
            'incubator_templates.min'                => 'Minimal 1 inkubator harus diisi.',
            'incubator_templates.*.label.required'   => 'Label suhu inkubator tidak boleh kosong.',
            'incubator_templates.*.min_day.required' => 'Minimum hari inkubator tidak boleh kosong.',
            'incubator_templates.*.min_day.min'      => 'Minimum hari harus lebih dari 0.',
        ];
    }

    public function toDTO(): UpdateReportTemplateDto
    {
        return new UpdateReportTemplateDto($this->validated());
    }
}
