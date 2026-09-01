<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandFileDropzone from '@/components/demands/DemandFileDropzone.vue';
import MaterialNatureCombobox from '@/components/demands/MaterialNatureCombobox.vue';
import BrandCombobox from '@/components/demands/BrandCombobox.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import type {
    DemandBrand,
    DemandMaterialNature,
    DemandUser,
} from '@/types/demands';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    brands: DemandBrand[];
    materialNatures: DemandMaterialNature[];
    validators: DemandUser[];
    defaultValidatorIds: number[];
    pipeline: { id: number; name: string } | null;
}>();

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: t('demands.create.title'), href: '/demands/create' },
]);

/** Must stay outside useForm data — a `submit` field shadows Inertia's form.submit(). */
const asSubmit = ref(false);

const form = useForm({
    brand_id: null as number | null,
    material_nature_id: null as number | null,
    material_nature_name: '',
    description: '',
    validator_ids: [...props.defaultValidatorIds].filter(
        (id): id is number => typeof id === 'number' && id > 0,
    ),
    nature_materiel_files: [] as File[],
    referentiel_produit_files: [] as File[],
}).transform((data) => ({
    ...data,
    submit: asSubmit.value,
}));

const errorMessages = computed(() => [
    ...new Set(
        Object.values(form.errors).filter(
            (message): message is string => !!message,
        ),
    ),
]);

const hasErrors = computed(() => errorMessages.value.length > 0);

const minValidators = 3;

function firstError(prefix: string): string | undefined {
    if (form.errors[prefix]) {
        return form.errors[prefix];
    }

    const match = Object.entries(form.errors).find(
        ([key, message]) => key.startsWith(`${prefix}.`) && !!message,
    );

    return match?.[1];
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

    if (submitFlag && form.nature_materiel_files.length === 0) {
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
    form.post('/demands', { forceFormData: true });
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('demands.create.title')" />

        <div
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('demands.create.title') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ t('demands.create.subtitle') }}
                </p>
                <p v-if="pipeline" class="mt-2 text-xs text-muted-foreground">
                    {{
                        t('demands.form.pipeline_default', {
                            name: pipeline.name,
                        })
                    }}
                </p>
            </div>

            <Alert v-if="hasErrors" variant="destructive">
                <AlertCircle />
                <AlertTitle>{{
                    t('demands.form.validation_title')
                }}</AlertTitle>
                <AlertDescription>
                    <ul class="mt-1 list-disc space-y-1 pl-4">
                        <li
                            v-for="(message, index) in errorMessages"
                            :key="index"
                        >
                            {{ message }}
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>

            <form class="space-y-6" @submit.prevent="save(false)">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('demands.create.title') }}</CardTitle>
                        <CardDescription>{{
                            t('demands.create.subtitle')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <BrandCombobox
                            v-model="form.brand_id"
                            :brands="brands"
                            :error="form.errors.brand_id"
                        />

                        <MaterialNatureCombobox
                            v-model:material-nature-id="form.material_nature_id"
                            v-model:material-nature-name="
                                form.material_nature_name
                            "
                            :options="materialNatures"
                            :error="
                                form.errors.material_nature_id ||
                                form.errors.material_nature_name
                            "
                        />

                        <div class="space-y-2">
                            <Label>{{ t('demands.form.description') }}</Label>
                            <Textarea v-model="form.description" rows="5" />
                            <p
                                v-if="form.errors.description"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('demands.form.nature_files') }}</Label>
                            <DemandFileDropzone
                                accept="application/pdf,.pdf"
                                :hint="t('demands.form.nature_files_hint')"
                                :error="firstError('nature_materiel_files')"
                                @select="
                                    (files) =>
                                        (form.nature_materiel_files = files)
                                "
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>{{
                                t('demands.form.referentiel_files')
                            }}</Label>
                            <DemandFileDropzone
                                accept=".pdf,.doc,.docx,.ppt,.pptx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                                :hint="t('demands.form.referentiel_files_hint')"
                                :error="firstError('referentiel_produit_files')"
                                @select="
                                    (files) =>
                                        (form.referentiel_produit_files = files)
                                "
                            />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        as-child
                        :disabled="form.processing"
                    >
                        <Link href="/demands">{{ t('common.cancel') }}</Link>
                    </Button>
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing && !asSubmit" />
                        {{
                            form.processing && !asSubmit
                                ? t('demands.form.saving')
                                : t('demands.form.save_draft')
                        }}
                    </Button>
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="save(true)"
                    >
                        <Spinner v-if="form.processing && asSubmit" />
                        {{
                            form.processing && asSubmit
                                ? t('demands.form.submitting')
                                : t('demands.form.submit')
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </DemandLayout>
</template>
