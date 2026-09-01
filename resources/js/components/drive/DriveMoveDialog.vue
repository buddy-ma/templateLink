<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FolderInput } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import { Label } from '@/components/ui/label';
import type { DriveFile, DriveFolder } from '@/types/drive';

export type MoveFolderOption = {
    id: number;
    name: string;
    parent_id: number | null;
};

const props = defineProps<{
    open: boolean;
    item: DriveFolder | DriveFile | null;
    folders: MoveFolderOption[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();
const destinationId = ref<string>('');
const processing = ref(false);
const error = ref('');

const options = computed(() => {
    if (!props.item) {
        return props.folders;
    }

    if (props.item.type !== 'folder') {
        return props.folders;
    }

    const excluded = new Set<number>([props.item.id]);
    let grew = true;
    while (grew) {
        grew = false;
        for (const folder of props.folders) {
            if (
                folder.parent_id !== null &&
                excluded.has(folder.parent_id) &&
                !excluded.has(folder.id)
            ) {
                excluded.add(folder.id);
                grew = true;
            }
        }
    }

    return props.folders.filter((folder) => !excluded.has(folder.id));
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen || !props.item) {
            return;
        }
        error.value = '';
        const currentParent =
            props.item.type === 'folder' ? props.item.parent_id : props.item.folder_id;
        destinationId.value = currentParent === null || currentParent === undefined
            ? ''
            : String(currentParent);
    },
);

function close(): void {
    emit('update:open', false);
}

function submit(): void {
    if (!props.item) {
        return;
    }

    processing.value = true;
    const destination = destinationId.value === '' ? null : Number(destinationId.value);

    if (props.item.type === 'folder') {
        router.put(
            `/drive/folders/${props.item.id}`,
            { parent_id: destination },
            {
                preserveScroll: true,
                onSuccess: () => close(),
                onError: (errors) => {
                    error.value = errors.parent_id ?? t('drive.move_failed');
                },
                onFinish: () => {
                    processing.value = false;
                },
            },
        );
        return;
    }

    router.put(
        `/drive/files/${props.item.id}`,
        { folder_id: destination },
        {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => {
                error.value = errors.folder_id ?? t('drive.move_failed');
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
                    <FolderInput class="text-primary size-5" />
                    {{ t('drive.move') }}
                </DialogTitle>
                <DialogDescription>
                    {{ t('drive.move_hint', { name: item?.name ?? '' }) }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="drive-move-destination">{{ t('drive.destination') }}</Label>
                    <select
                        id="drive-move-destination"
                        v-model="destinationId"
                        class="border-input bg-background h-9 w-full rounded-md border px-3 text-sm"
                    >
                        <option value="">{{ t('drive.my_drive') }}</option>
                        <option v-for="folder in options" :key="folder.id" :value="String(folder.id)">
                            {{ folder.name }}
                        </option>
                    </select>
                    <p v-if="error" class="text-destructive text-sm">{{ error }}</p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" :disabled="processing" @click="close">
                        {{ t('drive.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ t('drive.move') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
