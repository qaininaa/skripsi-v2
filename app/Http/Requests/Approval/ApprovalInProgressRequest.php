<?php

namespace App\Http\Requests\Approval;

use Domain\Report\Dtos\GetInProgressReportsFilterDto;
use Domain\Report\Services\ReportApprovalService;
use Illuminate\Foundation\Http\FormRequest;

class ApprovalInProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['nullable', 'in:all,pending,monitoring,reading,review_supervisor,approval_manager,returned'],
        ];
    }

    public function toDTO(): GetInProgressReportsFilterDto
    {
        return new GetInProgressReportsFilterDto(array_merge($this->validated(), [
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
