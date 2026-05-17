<?php

namespace App\Http\Requests\Report;

use Domain\Report\Dtos\CreateReportDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportStoreRequest extends FormRequest
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

    public function toDTO(): CreateReportDto
    {
        return new CreateReportDto(array_merge($this->validated(), [
            'status'     => 'pending',
            'created_by' => $this->user()?->id,
        ]));
    }
}
