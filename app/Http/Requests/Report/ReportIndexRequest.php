<?php

namespace App\Http\Requests\Report;

use Domain\Report\Dtos\GetReportsFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,in_progress,in_progress_monitoring,in_progress_reading,pending_review,pending_approval,completed,archived'],
        ];
    }

    public function toDTO(): GetReportsFilterDto
    {
        return new GetReportsFilterDto($this->validated());
    }
}
