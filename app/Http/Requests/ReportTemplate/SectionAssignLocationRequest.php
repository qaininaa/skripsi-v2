<?php

namespace App\Http\Requests\ReportTemplate;

use Illuminate\Foundation\Http\FormRequest;

class SectionAssignLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
        ];
    }
}
