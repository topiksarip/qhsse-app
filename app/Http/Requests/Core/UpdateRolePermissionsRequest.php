<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $this->user()->can('core.roles.manage')
            && $role instanceof Role
            && $role->name !== 'Super Admin';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
                Rule::notIn(['core.roles.manage']),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $scopes = collect($this->input('permissions', []))
                ->filter(fn (string $permission): bool => str_starts_with($permission, 'core.scope.'));

            if ($scopes->count() > 1) {
                $validator->errors()->add('permissions', 'Satu role hanya boleh memiliki satu data scope.');
            }
        }];
    }
}
