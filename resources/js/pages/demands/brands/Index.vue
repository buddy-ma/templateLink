<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import type { BreadcrumbItem } from '@/types';

type BrandRow = {
    id: number;
    name: string;
    sku: string | null;
    dosage_form?: string | null;
    presentation?: string | null;
    label?: string;
    is_active: boolean;
};

defineProps<{
    brands: BrandRow[];
}>();

const { t } = useI18n();
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: t('demands.nav.brands'), href: '/demands/brands' },
]);

const open = ref(false);
const editing = ref<BrandRow | null>(null);
const form = useForm({
    name: '',
    sku: '',
    is_active: true,
});

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.is_active = true;
    open.value = true;
}

function openEdit(brand: BrandRow): void {
    editing.value = brand;
    form.name = brand.name;
    form.sku = brand.sku ?? '';
    form.is_active = brand.is_active;
    open.value = true;
}

function submit(): void {
    if (editing.value) {
        form.put(`/demands/brands/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
        });
    } else {
        form.post('/demands/brands', {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
                form.reset();
            },
        });
    }
}

function destroy(brand: BrandRow): void {
    if (!confirm(t('demands.brands.confirm_delete'))) return;
    router.delete(`/demands/brands/${brand.id}`, { preserveScroll: true });
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('demands.nav.brands')" />
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('demands.nav.brands') }}
                </h1>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    {{ t('demands.brands.create') }}
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('demands.nav.brands') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y rounded-md border">
                        <li
                            v-for="brand in brands"
                            :key="brand.id"
                            class="flex items-center justify-between gap-3 px-4 py-3"
                        >
                            <div>
                                <p class="font-medium">{{ brand.name }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{
                                        [brand.dosage_form, brand.presentation, brand.sku]
                                            .filter(Boolean)
                                            .join(' · ') || '—'
                                    }}
                                    ·
                                    {{
                                        brand.is_active
                                            ? t('demands.brands.active')
                                            : t('demands.brands.inactive')
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-1">
                                <Button size="icon" variant="ghost" @click="openEdit(brand)">
                                    <Pencil class="size-4" />
                                </Button>
                                <Button size="icon" variant="ghost" @click="destroy(brand)">
                                    <Trash2 class="text-destructive size-4" />
                                </Button>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="open">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editing
                                ? t('demands.brands.edit')
                                : t('demands.brands.create')
                        }}
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-3">
                    <div class="space-y-1">
                        <Label>{{ t('demands.brands.name') }}</Label>
                        <Input v-model="form.name" />
                    </div>
                    <div class="space-y-1">
                        <Label>{{ t('demands.brands.sku') }}</Label>
                        <Input v-model="form.sku" />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <Checkbox
                            :checked="form.is_active"
                            @update:checked="(v) => (form.is_active = v === true)"
                        />
                        {{ t('demands.brands.active') }}
                    </label>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="open = false">{{ t('common.cancel') }}</Button>
                    <Button :disabled="form.processing" @click="submit">
                        {{ t('common.save') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </DemandLayout>
</template>
