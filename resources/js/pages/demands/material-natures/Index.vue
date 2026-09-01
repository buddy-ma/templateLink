<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

defineProps<{
    materialNatures: { id: number; name: string }[];
}>();

const { t } = useI18n();
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: t('demands.nav.material_natures'), href: '/demands/material-natures' },
]);

const open = ref(false);
const form = useForm({ name: '' });

function submit(): void {
    form.post('/demands/material-natures', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}

function destroy(id: number): void {
    if (!confirm(t('demands.natures.confirm_delete'))) return;
    router.delete(`/demands/material-natures/${id}`, { preserveScroll: true });
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('demands.nav.material_natures')" />
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('demands.nav.material_natures') }}
                </h1>
                <Button @click="open = true">
                    <Plus class="size-4" />
                    {{ t('demands.natures.create') }}
                </Button>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('demands.nav.material_natures') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y rounded-md border">
                        <li
                            v-for="item in materialNatures"
                            :key="item.id"
                            class="flex items-center justify-between px-4 py-3"
                        >
                            <span>{{ item.name }}</span>
                            <Button size="icon" variant="ghost" @click="destroy(item.id)">
                                <Trash2 class="text-destructive size-4" />
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="open">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t('demands.natures.create') }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-1">
                    <Label>{{ t('demands.natures.name') }}</Label>
                    <Input v-model="form.name" />
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
