<?php

namespace Domain\Report\Dtos;

/**
 * Approve action payload for supervisor / manager.
 *
 * Auth confirmation (username + password) is validated by the FormRequest
 * before this DTO is built; only the actor's id is needed downstream.
 */
class ApproveReportDto
{
    public string $actorId;

    public function __construct(array $data)
    {
        $this->actorId = (string) $data['actor_id'];
    }
}
