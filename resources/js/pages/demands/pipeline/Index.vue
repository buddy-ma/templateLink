<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ValidatorOrderEditor from '@/components/demands/ValidatorOrderEditor.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import type { DemandUser } from '@/types/demands';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    pipeline: {
        id: number;
        name: string;
        is_default: boolean;
        validator_ids: number[];
    };
    validators: DemandUser[];
    minValidators: number;
}>();

const { t } = useI18n();
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: t('demands.nav.pipeline'), href: '/demands/pipeline' },
]);

const form = useForm({
    name: props.pipeline.name,
    is_default: true,
    validator_ids: [...props.pipeline.validator_ids],
});

function submit(): void {
    form.put(`/demands/pipeline/${props.pipeline.id}`, { preserveScroll: true });
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('demands.nav.pipeline')" />
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('demands.pipeline.title') }}
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('demands.pipeline.subtitle', { min: minValidators }) }}
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('demands.pipeline.title') }}</CardTitle>
                    <CardDescription>
                        {{ t('demands.pipeline.subtitle', { min: minValidators }) }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="space-y-1">
                            <Label>{{ t('demands.pipeline.name') }}</Label>
                            <Input v-model="form.name" />
                        </div>

                        <ValidatorOrderEditor
                            v-model="form.validator_ids"
                            :validators="validators"
                            :min="minValidators"
                            :error="form.errors.validator_ids"
                            :show-final-group="true"
                        />

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing">
                                {{ t('common.save') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </DemandLayout>
</template>
