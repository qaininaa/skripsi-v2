<?php

namespace App\Http\Requests\Location;

use Domain\Location\Dtos\UpdateLocationDto;
use Illuminate\Foundation\Http\FormRequest;

class LocationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id'           => ['required', 'uuid', 'exists:rooms,id'],
            'loc_number'        => ['required', 'string', 'max:50'],
            'measurement_type'  => ['required', 'string', 'max:50'],
            'frequency'         => ['required', 'in:operational,daily,weekly,monthly,semi_annual'],
            'alert_limit_total' => ['nullable', 'integer', 'min:0'],
            'alert_limit_fungi' => ['nullable', 'integer', 'min:0'],
            'alert_action_total'=> ['nullable', 'integer', 'min:0'],
            'alert_action_fungi'=> ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toDTO(): UpdateLocationDto
    {
        return new UpdateLocationDto($this->validated());
    }
}
