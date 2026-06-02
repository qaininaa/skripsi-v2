<?php

namespace App\Http\Requests\ReportFill;

use Domain\Report\Dtos\GetAnalystReportsFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class ReportFillIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'in:all,belum_dikerjakan,sedang_dimonitoring,sedang_dibaca,dikirim,dikembalikan'],
        ];
    }

    public function toDTO(): GetAnalystReportsFilterDto
    {
        return new GetAnalystReportsFilterDto(array_merge($this->validated(), [
            'analyst_id' => $this->user()?->id,
        ]));
    }
}
