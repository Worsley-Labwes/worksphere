<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('view', $this->route('workspace'));

        return true;
    }

    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority'    => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'due_date'    => ['nullable', 'date_format:Y-m-d'],
            // Never trust a submitted user ID: it must be a member of THIS workspace.
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('workspace_members', 'user_id')->where('workspace_id', $workspace->id),
            ],
        ];
    }
}
