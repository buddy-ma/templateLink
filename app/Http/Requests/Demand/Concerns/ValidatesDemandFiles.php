<?php

declare(strict_types=1);

namespace App\Http\Requests\Demand\Concerns;

trait ValidatesDemandFiles
{
    /**
     * @return array<string, list<string>>
     */
    protected function demandFileRules(): array
    {
        return [
            'nature_materiel_files' => ['nullable', 'array'],
            // Prefer extensions: office MIME sniffing often mislabels .doc as octet-stream.
            'nature_materiel_files.*' => ['file', 'extensions:pdf', 'max:10240'],
            'referentiel_produit_files' => ['nullable', 'array'],
            'referentiel_produit_files.*' => ['file', 'extensions:pdf,doc,docx,ppt,pptx', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => __('demands.attributes.brand'),
            'material_nature_id' => __('demands.attributes.material_nature'),
            'material_nature_name' => __('demands.attributes.material_nature'),
            'description' => __('demands.attributes.description'),
            'validator_ids' => __('demands.attributes.validators'),
            'validator_ids.*' => __('demands.attributes.validators'),
            'nature_materiel_files' => __('demands.attributes.nature_files'),
            'nature_materiel_files.*' => __('demands.attributes.nature_files'),
            'referentiel_produit_files' => __('demands.attributes.referentiel_files'),
            'referentiel_produit_files.*' => __('demands.attributes.referentiel_files'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brand_id.required' => __('demands.validation.brand_required'),
            'description.required' => __('demands.validation.description_required'),
            'validator_ids.required' => __('demands.validation.validators_min', ['min' => 3]),
            'validator_ids.min' => __('demands.validation.validators_min', ['min' => 3]),
            'nature_materiel_files.*.extensions' => __('demands.messages.nature_file_type'),
            'nature_materiel_files.*.max' => __('demands.messages.nature_file_max'),
            'referentiel_produit_files.*.extensions' => __('demands.messages.referentiel_file_type'),
            'referentiel_produit_files.*.max' => __('demands.messages.referentiel_file_max'),
        ];
    }
}
