<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, FileUp, LoaderCircle, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useDropzone } from 'vue3-dropzone';
import { useI18n } from 'vue-i18n';
import DriveFileTypeIcon from '@/components/drive/DriveFileTypeIcon.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type UploadStatus = 'ready' | 'uploading' | 'done' | 'error';

type PendingUpload = {
    id: string;
    file: File;
    status: UploadStatus;
    progress: number;
    error?: string;
};

const props = defineProps<{
    open: boolean;
    folderId: number | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();
const items = ref<PendingUpload[]>([]);
const uploading = ref(false);
const error = ref('');

const form = useForm<{
    file: File | null;
    folder_id: number | null;
    name: string;
}>({
    file: null,
    folder_id: null,
    name: '',
});

const allDone = computed(
    () => items.value.length > 0 && items.value.every((item) => item.status === 'done'),
);

const { getRootProps, getInputProps, isDragActive, open: openPicker } = useDropzone({
    multiple: true,
    maxSize: 100 * 1024 * 1024,
    disabled: uploading.value,
    onDrop(acceptedFiles: unknown[]) {
        if (uploading.value) {
            return;
        }
        const next = acceptedFiles
            .filter((f): f is File => f instanceof File)
            .map((file) => ({
                id: `${file.name}-${file.size}-${file.lastModified}-${crypto.randomUUID()}`,
                file,
                status: 'ready' as const,
                progress: 0,
            }));
        items.value = [...items.value, ...next];
    },
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen && !uploading.value) {
            items.value = [];
            error.value = '';
            form.reset();
            form.clearErrors();
        }
    },
);

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    const kb = bytes / 1024;
    if (kb < 1024) {
        return `${kb.toFixed(1)} KB`;
    }
    return `${(kb / 1024).toFixed(1)} MB`;
}

function shortType(file: File): string {
    const ext = file.name.includes('.')
        ? (file.name.split('.').pop() ?? '').toUpperCase()
        : '';
    if (ext) {
        return ext;
    }
    if (!file.type) {
        return '—';
    }
    const subtype = file.type.split('/').pop() ?? file.type;
    return subtype.length > 12 ? `${subtype.slice(0, 12)}…` : subtype.toUpperCase();
}

function removeAt(id: string): void {
    if (uploading.value) {
        return;
    }
    items.value = items.value.filter((item) => item.id !== id);
}

function close(): void {
    if (uploading.value) {
        return;
    }
    emit('update:open', false);
}

async function confirmUpload(): Promise<void> {
    if (!items.value.length || uploading.value) {
        return;
    }

    uploading.value = true;
    error.value = '';

    for (const item of items.value) {
        if (item.status === 'done') {
            continue;
        }

        item.status = 'uploading';
        item.progress = 0;
        item.error = undefined;

        try {
            await new Promise<void>((resolve, reject) => {
                form.file = item.file;
                form.folder_id = props.folderId;
                form.name = item.file.name;
                form.post('/drive/files', {
                    forceFormData: true,
                    preserveScroll: true,
                    onProgress: (progress) => {
                        item.progress = Math.min(100, Math.round(progress?.percentage ?? 0));
                    },
                    onSuccess: () => {
                        item.progress = 100;
                        item.status = 'done';
                        resolve();
                    },
                    onError: (errors) => {
                        const message =
                            errors.file ??
                            (Object.values(errors)[0] as string | undefined) ??
                            t('drive.upload_failed');
                        item.status = 'error';
                        item.error = message;
                        error.value = message;
                        reject(new Error(message));
                    },
                });
            });
        } catch {
            break;
        }
    }

    uploading.value = false;

    if (allDone.value) {
        window.setTimeout(() => {
            items.value = [];
            emit('update:open', false);
        }, 450);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="(value) => !value && close()">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('drive.upload') }}</DialogTitle>
                <DialogDescription>{{ t('drive.upload_hint') }}</DialogDescription>
            </DialogHeader>

            <div
                v-bind="getRootProps()"
                :class="
                    cn(
                        'border-input bg-muted/30 flex min-h-30 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-5 text-center',
                        isDragActive && 'border-primary bg-primary/5',
                        uploading && 'pointer-events-none opacity-60',
                    )
                "
                role="button"
                tabindex="0"
                @keydown.enter.prevent="openPicker"
            >
                <input v-bind="getInputProps()" />
                <FileUp class="text-muted-foreground size-7" />
                <p class="text-sm font-medium">{{ t('drive.upload') }}</p>
            </div>

            <div v-if="items.length" class="space-y-2">
                <p class="text-sm font-medium">{{ t('drive.pending_files') }}</p>
                <ul class="max-h-64 space-y-2 overflow-y-auto pr-1">
                    <li
                        v-for="item in items"
                        :key="item.id"
                        class="bg-muted/40 space-y-2 rounded-lg px-3 py-2.5"
                    >
                        <div class="flex items-start gap-2">
                            <DriveFileTypeIcon
                                kind="file"
                                :name="item.file.name"
                                :mime="item.file.type"
                                class="mt-0.5 size-7 shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <p
                                        class="min-w-0 flex-1 truncate text-sm font-medium"
                                        :title="item.file.name"
                                    >
                                        {{ item.file.name }}
                                    </p>
                                    <CheckCircle2
                                        v-if="item.status === 'ready' || item.status === 'done'"
                                        class="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                                        :aria-label="t('drive.upload_ready')"
                                    />
                                    <LoaderCircle
                                        v-else-if="item.status === 'uploading'"
                                        class="text-primary mt-0.5 size-4 shrink-0 animate-spin"
                                    />
                                    <Button
                                        v-if="!uploading && item.status !== 'done'"
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        class="size-7 shrink-0"
                                        @click="removeAt(item.id)"
                                    >
                                        <X class="size-4" />
                                    </Button>
                                </div>
                                <p class="text-muted-foreground truncate text-xs">
                                    {{ formatBytes(item.file.size) }}
                                    ·
                                    {{ shortType(item.file) }}
                                </p>
                                <p
                                    v-if="item.error"
                                    class="text-destructive mt-1 truncate text-xs"
                                    :title="item.error"
                                >
                                    {{ item.error }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="item.status === 'uploading' || item.status === 'done'"
                            class="bg-muted h-1.5 overflow-hidden rounded-full"
                        >
                            <div
                                class="h-full rounded-full transition-[width] duration-150"
                                :class="
                                    item.status === 'done' ? 'bg-emerald-500' : 'bg-primary'
                                "
                                :style="{ width: `${item.progress}%` }"
                            />
                        </div>
                    </li>
                </ul>
            </div>

            <p v-if="error && !items.some((i) => i.error)" class="text-destructive text-sm">
                {{ error }}
            </p>

            <div class="flex justify-end gap-2">
                <Button type="button" variant="outline" :disabled="uploading" @click="close">
                    {{ t('drive.cancel') }}
                </Button>
                <Button
                    type="button"
                    :disabled="!items.length || uploading || allDone"
                    @click="confirmUpload"
                >
                    {{ uploading ? t('drive.uploading') : t('drive.upload_confirm') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
