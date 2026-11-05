<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('manage', $this->route('workspace'));

        return true;
    }

    public function rules(): array
    {
        $name = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name'        => [$name, 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'deadline'    => ['nullable', 'date_format:Y-m-d'],
            'status'      => ['sometimes', Rule::in(['planning', 'active', 'completed', 'archived'])],
        ];
    }
}
