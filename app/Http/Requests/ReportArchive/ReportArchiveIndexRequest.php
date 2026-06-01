<?php

namespace App\Http\Requests\ReportArchive;

use Domain\Report\Dtos\GetArchiveReportsFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportArchiveIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): GetArchiveReportsFilterDto
    {
        return new GetArchiveReportsFilterDto($this->validated());
    }
}
