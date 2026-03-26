<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { Languages } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type FlashProps = PageProps & { flash?: { success?: string } };

const { t } = useI18n();
const page = usePage<FlashProps>();
const flashSuccess = computed(() => page.props.flash?.success);
const search = ref('');

const props = defineProps<{
    locales: string[];
    activeLocale: string;
    flat: Record<string, string>;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'App Settings', href: '/admin/settings' },
    { title: t('admin.translations.title'), href: '/admin/translations' },
]);

const form = useForm({ flat: { ...props.flat } });

watch(
    () => [props.activeLocale, props.flat] as const,
    ([, nextFlat]) => {
        form.reset();
        form.flat = { ...nextFlat };
    },
);

const sortedEntries = computed(() => Object.entries(form.flat).sort(([a], [b]) => a.localeCompare(b)));

const filteredEntries = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
        return sortedEntries.value;
    }
    return sortedEntries.value.filter(
        ([key, value]) => key.toLowerCase().includes(q) || value.toLowerCase().includes(q),
    );
});

function switchLocale(locale: string): void {
    router.get('/admin/translations', { locale }, { preserveScroll: true });
}

function submit(): void {
    form.put(`/admin/translations/${props.activeLocale}`, {
        preserveScroll: true,
        onSuccess: () => window.location.reload(),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('admin.translations.title')" />

        <div class="flex flex-1 flex-col gap-8 p-4 md:p-8">
            <div class="mx-auto w-full max-w-5xl space-y-8">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <Languages class="text-primary size-8" />
                        <h1 class="text-3xl font-semibold tracking-tight">{{ t('admin.translations.title') }}</h1>
                    </div>
                    <p class="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                        {{ t('admin.translations.description') }}
                    </p>
                </div>

                <Alert v-if="flashSuccess" class="border-emerald-500/30 bg-emerald-500/10">
                    <AlertDescription>{{ flashSuccess }}</AlertDescription>
                </Alert>

                <Card class="border-none shadow-md">
                    <CardHeader class="space-y-4">
                        <div>
                            <CardTitle>{{ t('admin.translations.title') }}</CardTitle>
                            <CardDescription>
                                {{ t('admin.translations.rows_count', { count: filteredEntries.length }) }}
                            </CardDescription>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="loc in locales"
                                    :key="loc"
                                    type="button"
                                    size="sm"
                                    :variant="loc === activeLocale ? 'default' : 'outline'"
                                    @click="switchLocale(loc)"
                                >
                                    {{ loc.toUpperCase() }}
                                </Button>
                            </div>
                            <div class="flex w-full flex-col gap-2 sm:max-w-sm">
                                <Label for="translation-search" class="sr-only">{{
                                    t('admin.translations.search_placeholder')
                                }}</Label>
                                <Input
                                    id="translation-search"
                                    v-model="search"
                                    type="search"
                                    :placeholder="t('admin.translations.search_placeholder')"
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                    </CardHeader>

                    <Separator />

                    <CardContent class="pt-6">
                        <form class="space-y-6" @submit.prevent="submit">
                            <div class="max-h-[min(70vh,720px)] space-y-4 overflow-y-auto pr-1">
                                <div
                                    v-for="([rowKey], rowIdx) in filteredEntries"
                                    :key="rowKey"
                                    class="grid gap-2 border-b border-border/60 pb-4 last:border-0 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] md:gap-4"
                                >
                                    <div class="space-y-1">
                                        <Label class="text-muted-foreground text-xs font-medium">{{
                                            t('admin.translations.key_column')
                                        }}</Label>
                                        <p
                                            class="bg-muted/40 font-mono text-xs leading-relaxed break-all rounded-md px-2 py-2"
                                        >
                                            {{ rowKey }}
                                        </p>
                                    </div>
                                    <div class="space-y-1">
                                        <Label
                                            class="text-muted-foreground text-xs font-medium"
                                            :for="`translation-val-${rowIdx}`"
                                        >
                                            {{ t('admin.translations.value_column') }}
                                        </Label>
                                        <textarea
                                            :id="`translation-val-${rowIdx}`"
                                            v-model="form.flat[rowKey]"
                                            rows="2"
                                            class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end border-t border-border pt-4">
                                <Button type="submit" :disabled="form.processing">
                                    {{ t('admin.translations.save') }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
