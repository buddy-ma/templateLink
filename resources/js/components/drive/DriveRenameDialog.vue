<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DriveFile, DriveFolder } from '@/types/drive';

const props = defineProps<{
    open: boolean;
    item: DriveFolder | DriveFile | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();
const name = ref('');
const processing = ref(false);
const error = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.item) {
            name.value = props.item.name;
            error.value = '';
        }
    },
);

function close(): void {
    emit('update:open', false);
}

function submit(): void {
    if (!props.item) {
        return;
    }
    const next = name.value.trim();
    if (!next) {
        error.value = t('drive.folder_name_required');
        return;
    }

    processing.value = true;
    const url =
        props.item.type === 'folder'
            ? `/drive/folders/${props.item.id}`
            : `/drive/files/${props.item.id}`;

    router.put(
        url,
        { name: next },
        {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => {
                error.value = errors.name ?? t('drive.upload_failed');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Pencil class="text-primary size-5" />
                    {{ t('drive.rename') }}
                </DialogTitle>
                <DialogDescription>
                    {{ item?.type === 'folder' ? t('drive.rename_folder_hint') : t('drive.rename_file_hint') }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="drive-rename-name">{{ t('drive.name') }}</Label>
                    <Input
                        id="drive-rename-name"
                        v-model="name"
                        autofocus
                        maxlength="255"
                        :aria-invalid="!!error"
                    />
                    <p v-if="error" class="text-destructive text-sm">{{ error }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="close">
                        {{ t('drive.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing || !name.trim()">
                        {{ t('drive.save') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
