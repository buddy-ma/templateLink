<script setup lang="ts">
import { ImageUp } from 'lucide-vue-next';
import { useDropzone } from 'vue3-dropzone';
import { cn } from '@/lib/utils';

const emit = defineEmits<{
    select: [file: File];
}>();

const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    accept: 'image/jpeg,image/png,image/svg+xml,image/webp',
    maxFiles: 1,
    multiple: false,
    maxSize: 2 * 1024 * 1024,
    onDrop(acceptedFiles: unknown[]) {
        const file = acceptedFiles[0];
        if (file instanceof File) {
            emit('select', file);
        }
    },
});
</script>

<template>
    <div class="space-y-2">
        <div
            v-bind="getRootProps()"
            :class="
                cn(
                    'border-input bg-muted/30 hover:bg-muted/50 focus-visible:ring-ring/50 flex min-h-[140px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-6 text-center transition-colors focus-visible:ring-[3px] focus-visible:outline-none',
                    isDragActive && 'border-primary bg-primary/5',
                )
            "
            role="button"
            tabindex="0"
            @keydown.enter.prevent="open"
            @keydown.space.prevent="open"
        >
            <input v-bind="getInputProps()" />
            <ImageUp class="text-muted-foreground size-8" />
            <div class="space-y-1">
                <p class="text-sm font-medium">
                    {{ isDragActive ? 'Drop image here' : 'Drag & drop logo here' }}
                </p>
                <p class="text-muted-foreground text-xs">or click to browse — PNG, JPG, SVG, WebP · max 2&nbsp;MB</p>
            </div>
        </div>
    </div>
</template>
