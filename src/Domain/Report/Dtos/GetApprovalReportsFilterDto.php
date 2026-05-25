<?php

namespace Domain\Report\Dtos;

/**
 * Filter parameters for the supervisor / manager inbox listing.
 *
 * tab values:
 *   - pending
 *   - approved
 *   - returned
 *   - all
 */
class GetApprovalReportsFilterDto
{
    public string $tab;
    public int $step;
    public string $userId;

    public function __construct(array $data)
    {
        $this->tab    = (string) ($data['tab']    ?? 'pending');
        $this->step   = (int)    ($data['step']   ?? 2);
        $this->userId = (string) ($data['user_id'] ?? '');
    }
}
