<?php

namespace Domain\Report\Dtos;

/**
 * Return action payload for supervisor / manager.
 *
 * Both supervisor and manager return the report to one of the analysts that
 * worked on it (monitoring or reading). returnedToUserId must be one of those.
 */
class ReturnReportDto
{
    public string $actorId;
    public string $returnedToUserId;
    public ?string $notes;

    public function __construct(array $data)
    {
        $this->actorId          = (string) $data['actor_id'];
        $this->returnedToUserId = (string) $data['returned_to_user_id'];
        $this->notes            = isset($data['notes']) ? (string) $data['notes'] : null;
    }
}
