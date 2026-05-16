<?php

namespace App\Http\Requests\ReportTemplate;

use Domain\ReportTemplate\Dtos\GetReportTemplatesFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportTemplateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): GetReportTemplatesFilterDto
    {
        return new GetReportTemplatesFilterDto($this->validated());
    }
}
