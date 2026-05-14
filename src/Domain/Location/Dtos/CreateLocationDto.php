<?php

namespace Domain\Location\Dtos;

class CreateLocationDto
{
    public string $room_id;
    public string $frequency;
    public string $loc_number;
    public string $measurement_type;
    public ?int   $alert_limit_total;
    public ?int   $alert_limit_fungi;
    public ?int   $alert_action_fungi;
    public ?int   $alert_action_total;

    public function __construct(array $data)
    {
        $this->room_id            = $data['room_id'];
        $this->frequency          = $data['frequency'];
        $this->loc_number         = $data['loc_number'];
        $this->measurement_type   = $data['measurement_type'];
        $this->alert_limit_total  = $data['alert_limit_total'];
        $this->alert_limit_fungi  = $data['alert_limit_fungi'];
        $this->alert_action_fungi = $data['alert_action_fungi'];
        $this->alert_action_total = $data['alert_action_total'];
    }
}
