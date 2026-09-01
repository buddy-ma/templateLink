<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use App\Http\Requests\Demand\Concerns\ValidatesDemandFiles;
use App\Models\Demand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandRequest extends FormRequest
{
    use ValidatesDemandFiles;

    public function authorize(): bool
    {
        /** @var Demand $demand */
        $demand = $this->route('demand');

        return $this->user()?->can('update', $demand) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('validator_ids')) {
            return;
        }

        $ids = $this->input('validator_ids', []);

        if (! is_array($ids)) {
            $ids = $ids === null || $ids === '' ? [] : [$ids];
        }

        $normalized = collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'validator_ids' => $normalized,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'required', 'integer', Rule::exists('brands', 'id')->where('is_active', true)],
            'material_nature_id' => ['nullable', 'integer', 'exists:material_natures,id'],
            'material_nature_name' => ['nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'validator_ids' => ['sometimes', 'required', 'array', 'min:3'],
            'validator_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'submit' => ['sometimes', 'boolean'],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer', 'exists:demand_attachments,id'],
            ...$this->demandFileRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'validator_ids' => __('demands.attributes.validators'),
            'validator_ids.*' => __('demands.attributes.validators'),
            'brand_id' => __('demands.attributes.brand'),
            'material_nature_id' => __('demands.attributes.material_nature'),
            'description' => __('demands.attributes.description'),
        ];
    }
}
