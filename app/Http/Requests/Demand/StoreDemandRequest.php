<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand;

use App\Http\Requests\Demand\Concerns\ValidatesDemandFiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandRequest extends FormRequest
{
    use ValidatesDemandFiles;

    public function authorize(): bool
    {
        return $this->user()?->can('demands.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
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
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')->where('is_active', true)],
            'material_nature_id' => ['nullable', 'integer', 'exists:material_natures,id'],
            'material_nature_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'validator_ids' => ['required', 'array', 'min:3'],
            'validator_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'submit' => ['sometimes', 'boolean'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('material_nature_id') && ! $this->filled('material_nature_name')) {
                $validator->errors()->add('material_nature_id', __('demands.validation.nature_required'));
            }

            if ($this->boolean('submit') && empty($this->file('nature_materiel_files'))) {
                $validator->errors()->add('nature_materiel_files', __('demands.validation.nature_pdf_required'));
            }
        });
    }
}
