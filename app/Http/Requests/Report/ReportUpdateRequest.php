<?php

namespace App\Http\Requests\Report;

use Domain\Report\Dtos\UpdateReportDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_template_id' => ['required', 'uuid', 'exists:report_templates,id'],
            'product_name'       => ['required', 'string', 'max:255'],
            'batch_number'       => ['required', 'string', 'max:255'],
        ];
    }

    public function toDTO(): UpdateReportDto
    {
        return new UpdateReportDto($this->validated());
    }
}
