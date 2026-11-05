<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
    }

    public function authorize(): bool
    {
        $role = $this->input('role') === 'admin' ? 'admin' : 'member';
        Gate::authorize('invite', [$this->route('workspace'), $role]);

        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:190'],
            'role'  => ['sometimes', Rule::in(['admin', 'member'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $workspace = $this->route('workspace');
                $email = $this->input('email');

                if ($workspace->members()->where('users.email', $email)->exists()) {
                    $validator->errors()->add('email', 'That person is already a member.');
                } elseif ($workspace->invitations()->pending()->where('email', $email)->exists()) {
                    $validator->errors()->add('email', 'There is already a pending invitation for that email.');
                }
            },
        ];
    }
}
