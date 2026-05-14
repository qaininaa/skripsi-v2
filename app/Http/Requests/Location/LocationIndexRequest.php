<?php

namespace App\Http\Requests\Location;

use Domain\Location\Dtos\GetLocationsFilterDto;
use Illuminate\Foundation\Http\FormRequest;

class LocationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'  => ['nullable', 'string', 'max:255'],
            'room_id' => ['nullable', 'uuid', 'exists:rooms,id'],
        ];
    }

    public function toDTO(): GetLocationsFilterDto
    {
        return new GetLocationsFilterDto($this->validated());
    }
}
