<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        kind: 'folder' | 'file';
        name?: string | null;
        mime?: string | null;
        class?: string;
    }>(),
    {
        name: null,
        mime: null,
        class: 'size-8',
    },
);

const extension = computed(() => {
    const name = props.name ?? '';
    const parts = name.split('.');
    if (parts.length < 2) {
        return '';
    }
    return (parts.at(-1) ?? '').toLowerCase();
});

const iconName = computed(() => {
    if (props.kind === 'folder') {
        return null;
    }

    const ext = extension.value;
    const mime = (props.mime ?? '').toLowerCase();

    if (ext === 'pdf' || mime.includes('pdf')) {
        return 'vscode-icons:file-type-pdf2';
    }
    if (['doc', 'docx'].includes(ext) || mime.includes('word') || mime.includes('msword')) {
        return 'vscode-icons:file-type-word';
    }
    if (['ppt', 'pptx'].includes(ext) || mime.includes('presentation') || mime.includes('powerpoint')) {
        return 'vscode-icons:file-type-powerpoint';
    }
    if (['xls', 'xlsx', 'csv'].includes(ext) || mime.includes('sheet') || mime.includes('excel')) {
        return 'vscode-icons:file-type-excel';
    }
    if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(ext) || mime.startsWith('image/')) {
        return 'vscode-icons:file-type-image';
    }
    if (['mp4', 'mov', 'webm'].includes(ext) || mime.startsWith('video/')) {
        return 'vscode-icons:file-type-video';
    }
    if (ext === 'zip' || mime.includes('zip')) {
        return 'vscode-icons:file-type-zip';
    }
    if (ext === 'txt' || mime.startsWith('text/')) {
        return 'vscode-icons:file-type-text';
    }

    return 'vscode-icons:default-file';
});
</script>

<template>
    <img
        v-if="kind === 'folder'"
        src="/images/drive/open-folder.png"
        alt=""
        :class="class"
        draggable="false"
    />
    <Icon
        v-else-if="iconName"
        :icon="iconName"
        :class="class"
        aria-hidden="true"
    />
</template>
