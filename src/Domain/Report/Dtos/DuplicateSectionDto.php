<?php

namespace Domain\Report\Dtos;

/**
 * Carries the input from "Duplicate Section" admin action.
 */
class DuplicateSectionDto
{
    public ?string $reason;

    public function __construct(array $data)
    {
        $reason = $data['reason'] ?? null;
        $this->reason = is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
    }
}
