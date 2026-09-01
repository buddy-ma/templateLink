<script setup lang="ts">
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import DriveFileTypeIcon from '@/components/drive/DriveFileTypeIcon.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { DriveFile, DriveFolder } from '@/types/drive';

const props = defineProps<{
    open: boolean;
    item: DriveFolder | DriveFile | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();

const rows = computed(() => {
    if (!props.item) {
        return [];
    }

    const item = props.item;
    const base = [
        { label: t('drive.name'), value: item.name },
        {
            label: t('drive.filters.type'),
            value: item.type === 'folder' ? t('drive.filters.folder') : (item.mime || '—'),
        },
        {
            label: t('drive.owner'),
            value: item.owner?.name ?? String(item.owner_id),
        },
        {
            label: t('drive.date_modified'),
            value: item.updated_at
                ? new Date(item.updated_at).toLocaleString()
                : '—',
        },
        {
            label: t('drive.created_at'),
            value: item.created_at
                ? new Date(item.created_at).toLocaleString()
                : '—',
        },
    ];

    if (item.type === 'file') {
        base.splice(2, 0, {
            label: t('drive.file_size'),
            value: formatBytes(item.size),
        });
    }

    return base;
});

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = bytes / 1024;
    let i = 0;
    while (value >= 1024 && i < units.length - 1) {
        value /= 1024;
        i += 1;
    }
    return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[i]}`;
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Info class="text-primary size-5" />
                    {{ t('drive.file_information') }}
                </DialogTitle>
            </DialogHeader>

            <div v-if="item" class="space-y-4">
                <div class="flex items-center gap-3">
                    <DriveFileTypeIcon
                        :kind="item.type"
                        :name="item.type === 'file' ? item.name : null"
                        :mime="item.type === 'file' ? item.mime : null"
                        class="size-10 shrink-0"
                    />
                    <p class="truncate font-medium" :title="item.name">{{ item.name }}</p>
                </div>

                <dl class="space-y-2 text-sm">
                    <div
                        v-for="row in rows"
                        :key="row.label"
                        class="grid grid-cols-[8rem_minmax(0,1fr)] gap-2"
                    >
                        <dt class="text-muted-foreground">{{ row.label }}</dt>
                        <dd class="truncate" :title="row.value">{{ row.value }}</dd>
                    </div>
                </dl>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('update:open', false)">
                    {{ t('drive.cancel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
