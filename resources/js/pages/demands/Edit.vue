<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandFileDropzone from '@/components/demands/DemandFileDropzone.vue';
import MaterialNatureCombobox from '@/components/demands/MaterialNatureCombobox.vue';
import BrandCombobox from '@/components/demands/BrandCombobox.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import type { Demand, DemandBrand, DemandMaterialNature, DemandUser } from '@/types/demands';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    demand: Demand;
    brands: DemandBrand[];
    materialNatures: DemandMaterialNature[];
    validators: DemandUser[];
    defaultValidatorIds: number[];
    pipeline: { id: number; name: string } | null;
}>();

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: props.demand.reference, href: `/demands/${props.demand.id}` },
    { title: t('common.edit'), href: `/demands/${props.demand.id}/edit` },
]);

/** Must stay outside useForm data — a `submit` field shadows Inertia's form.submit(). */
const asSubmit = ref(false);

const form = useForm({
    brand_id: props.demand.brand?.id ?? props.demand.product?.id ?? null,
    material_nature_id: props.demand.material_nature?.id ?? null,
    material_nature_name: props.demand.material_nature?.name ?? '',
    description: props.demand.description,
    validator_ids: props.demand.validators
        .map((v) => v.user_id)
        .filter((id): id is number => typeof id === 'number' && id > 0),
    nature_materiel_files: [] as File[],
    referentiel_produit_files: [] as File[],
    remove_attachment_ids: [] as number[],
}).transform((data) => ({
    ...data,
    submit: asSubmit.value,
}));

const natureExisting = computed(() =>
    props.demand.attachments
        .filter(
            (a) =>
                a.collection === 'nature_materiel' &&
                !form.remove_attachment_ids.includes(a.id),
        )
        .map((a) => ({ id: a.id, original_name: a.original_name })),
);

const referentielExisting = computed(() =>
    props.demand.attachments
        .filter(
            (a) =>
                a.collection === 'referentiel_produit' &&
                !form.remove_attachment_ids.includes(a.id),
        )
        .map((a) => ({ id: a.id, original_name: a.original_name })),
);

const minValidators = 3;

function removeAttachment(id: number): void {
    if (!form.remove_attachment_ids.includes(id)) {
        form.remove_attachment_ids.push(id);
    }
}

function validateClient(submitFlag: boolean): boolean {
    form.clearErrors();

    const errors: Record<string, string> = {};

    if (!form.brand_id) {
        errors.brand_id = t('demands.validation.brand_required');
    }

    if (!form.material_nature_id && !form.material_nature_name.trim()) {
        errors.material_nature_id = t('demands.validation.nature_required');
    }

    if (!form.description.trim()) {
        errors.description = t('demands.validation.description_required');
    }

    if (form.validator_ids.length < minValidators) {
        errors.validator_ids = t('demands.validation.pipeline_required', {
            min: minValidators,
        });
    }

    const hasNaturePdf =
        form.nature_materiel_files.length > 0 || natureExisting.value.length > 0;

    if (submitFlag && !hasNaturePdf) {
        errors.nature_materiel_files = t('demands.validation.nature_pdf_required');
    }

    if (Object.keys(errors).length === 0) {
        return true;
    }

    form.setError(errors);
    window.scrollTo({ top: 0, behavior: 'smooth' });

    return false;
}

function save(submitFlag: boolean): void {
    if (!validateClient(submitFlag)) {
        return;
    }

    asSubmit.value = submitFlag;
    form.post(`/demands/${props.demand.id}`, { forceFormData: true });
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('demands.edit.title', { reference: demand.reference })" />

        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('demands.edit.title', { reference: demand.reference }) }}
                </h1>
                <p v-if="pipeline" class="mt-2 text-xs text-muted-foreground">
                    {{
                        t('demands.form.pipeline_default', {
                            name: pipeline.name,
                        })
                    }}
                </p>
            </div>

            <Alert v-if="demand.refused_reason">
                <AlertDescription>
                    {{ t('demands.edit.refused_reason') }}: {{ demand.refused_reason }}
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <CardTitle>{{ demand.reference }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="save(false)">
                        <BrandCombobox
                            v-model="form.brand_id"
                            :brands="brands"
                            :error="form.errors.brand_id"
                        />

                        <MaterialNatureCombobox
                            v-model:material-nature-id="form.material_nature_id"
                            v-model:material-nature-name="form.material_nature_name"
                            :options="materialNatures"
                            :error="form.errors.material_nature_id || form.errors.material_nature_name"
                        />

                        <div class="space-y-2">
                            <Label>{{ t('demands.form.description') }}</Label>
                            <Textarea v-model="form.description" rows="5" />
                            <p v-if="form.errors.description" class="text-destructive text-sm">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('demands.form.nature_files') }}</Label>
                            <DemandFileDropzone
                                accept="application/pdf,.pdf"
                                :hint="t('demands.form.nature_files_hint')"
                                :existing="natureExisting"
                                :error="form.errors.nature_materiel_files"
                                @select="(files) => (form.nature_materiel_files = files)"
                                @remove-existing="removeAttachment"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('demands.form.referentiel_files') }}</Label>
                            <DemandFileDropzone
                                accept=".pdf,.doc,.docx,.ppt,.pptx,application/pdf"
                                :hint="t('demands.form.referentiel_files_hint')"
                                :existing="referentielExisting"
                                :error="form.errors.referentiel_produit_files"
                                @select="(files) => (form.referentiel_produit_files = files)"
                                @remove-existing="removeAttachment"
                            />
                        </div>

                        <div class="flex flex-wrap justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" as-child>
                                <Link :href="`/demands/${demand.id}`">{{ t('common.cancel') }}</Link>
                            </Button>
                            <Button type="submit" variant="secondary" :disabled="form.processing">
                                {{ t('demands.form.save_draft') }}
                            </Button>
                            <Button type="button" :disabled="form.processing" @click="save(true)">
                                {{ t('demands.form.submit') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </DemandLayout>
</template>
