<script setup lang="ts">
import { Type } from 'lucide-vue-next';
import { useDropzone } from 'vue3-dropzone';
import { cn } from '@/lib/utils';

const emit = defineEmits<{
    select: [file: File];
}>();

const { getRootProps, getInputProps, isDragActive } = useDropzone({
    accept: '.woff2,.woff,.ttf,.otf,font/woff2,font/woff,font/ttf,application/font-woff',
    maxFiles: 1,
    multiple: false,
    maxSize: 5 * 1024 * 1024,
    onDrop(acceptedFiles: unknown[]) {
        const file = acceptedFiles[0];
        if (file instanceof File) {
            emit('select', file);
        }
    },
});
</script>

<template>
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
    >
        <input v-bind="getInputProps()" />
        <Type class="text-muted-foreground size-7" />
        <div class="space-y-1">
            <p class="text-sm font-medium">
                {{ isDragActive ? 'Drop font file here' : 'Upload a custom font' }}
            </p>
            <p class="text-muted-foreground text-xs">WOFF2, WOFF, TTF, OTF · max 5&nbsp;MB</p>
        </div>
    </div>
</template>
