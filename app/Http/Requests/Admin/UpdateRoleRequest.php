<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_roles') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $names = $this->input('permission_names', []);

        if (! is_array($names)) {
            $names = [];
        }

        $this->merge([
            'permission_names' => array_values(array_unique(array_filter(
                $names,
                static fn ($name): bool => is_string($name) && $name !== '',
            ))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role->id),
            ],
            // Always required on update so permissions cannot be silently skipped.
            'permission_names' => ['required', 'array'],
            'permission_names.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ];
    }
}
