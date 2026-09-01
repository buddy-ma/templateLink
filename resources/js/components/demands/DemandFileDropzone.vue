<script setup lang="ts">
import { FileUp, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { useDropzone } from 'vue3-dropzone';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = defineProps<{
    accept: string;
    hint: string;
    existing?: { id: number; original_name: string }[];
    error?: string;
}>();

const emit = defineEmits<{
    select: [files: File[]];
    removeExisting: [id: number];
}>();

const { t } = useI18n();
const files = ref<File[]>([]);

const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    accept: props.accept,
    multiple: true,
    maxSize: 20 * 1024 * 1024,
    onDrop(acceptedFiles: unknown[]) {
        const next = acceptedFiles.filter((f): f is File => f instanceof File);
        files.value = [...files.value, ...next];
        emit('select', files.value);
    },
});

function removeLocal(index: number): void {
    files.value.splice(index, 1);
    emit('select', files.value);
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-bind="getRootProps()"
            :class="
                cn(
                    'border-input bg-muted/30 hover:bg-muted/50 flex min-h-[120px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-5 text-center transition-colors',
                    isDragActive && 'border-primary bg-primary/5',
                )
            "
            role="button"
            tabindex="0"
            @keydown.enter.prevent="open"
            @keydown.space.prevent="open"
        >
            <input v-bind="getInputProps()" />
            <FileUp class="text-muted-foreground size-7" />
            <p class="text-sm font-medium">
                {{ isDragActive ? t('demands.form.drop_files') : t('demands.form.drag_files') }}
            </p>
            <p class="text-muted-foreground text-xs">{{ hint }}</p>
        </div>

        <ul v-if="existing?.length" class="space-y-1">
            <li
                v-for="file in existing"
                :key="file.id"
                class="bg-muted/40 flex items-center justify-between rounded-lg px-3 py-1.5 text-sm"
            >
                <span class="truncate">{{ file.original_name }}</span>
                <Button type="button" size="icon" variant="ghost" @click="emit('removeExisting', file.id)">
                    <X class="size-4" />
                </Button>
            </li>
        </ul>

        <ul v-if="files.length" class="space-y-1">
            <li
                v-for="(file, index) in files"
                :key="`${file.name}-${index}`"
                class="bg-muted/60 flex items-center justify-between rounded-lg px-3 py-1.5 text-sm"
            >
                <span class="truncate">{{ file.name }}</span>
                <Button type="button" size="icon" variant="ghost" @click="removeLocal(index)">
                    <X class="size-4" />
                </Button>
            </li>
        </ul>

        <p v-if="error" class="text-destructive text-sm">{{ error }}</p>
    </div>
</template>
