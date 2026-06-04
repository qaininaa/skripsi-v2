<?php

namespace App\Http\Requests\Approval;

use Domain\Report\Dtos\GetApprovalReportsFilterDto;
use Domain\Report\Services\ReportApprovalService;
use Illuminate\Foundation\Http\FormRequest;

class ApprovalInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'in:all,pending,approved,returned'],
        ];
    }

    public function toDTO(): GetApprovalReportsFilterDto
    {
        return new GetApprovalReportsFilterDto(array_merge($this->validated(), [
            'step' => $this->approvalStep(),
            'user_id' => $this->user()?->id,
        ]));
    }

    private function approvalStep(): int
    {
        return $this->route('step') === 'manager'
            ? ReportApprovalService::STEP_MANAGER
            : ReportApprovalService::STEP_SUPERVISOR;
    }
}
