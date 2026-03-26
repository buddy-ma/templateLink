<script setup lang="ts">
import { ImageUp } from 'lucide-vue-next';
import { useDropzone } from 'vue3-dropzone';
import { cn } from '@/lib/utils';

const emit = defineEmits<{
    select: [file: File];
}>();

const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    accept: 'image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon,.ico',
    maxFiles: 1,
    multiple: false,
    maxSize: 512 * 1024,
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
                    'border-input bg-muted/30 hover:bg-muted/50 focus-visible:ring-ring/50 flex min-h-[120px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-5 text-center transition-colors focus-visible:ring-[3px] focus-visible:outline-none',
                    isDragActive && 'border-primary bg-primary/5',
                )
            "
            role="button"
            tabindex="0"
            @keydown.enter.prevent="open"
            @keydown.space.prevent="open"
        >
            <input v-bind="getInputProps()" />
            <ImageUp class="text-muted-foreground size-7" />
            <div class="space-y-1">
                <p class="text-sm font-medium">
                    {{ isDragActive ? 'Drop image here' : 'Drag & drop favicon here' }}
                </p>
                <p class="text-muted-foreground text-xs">
                    PNG, JPG, SVG, WebP, or ICO — max 512&nbsp;KB
                </p>
            </div>
        </div>
    </div>
</template>
