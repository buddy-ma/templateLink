<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { DriveStorage } from '@/types/drive';

const props = defineProps<{
    storage: DriveStorage;
}>();

const { t } = useI18n();

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

const isPersonal = computed(() => props.storage.scope === 'personal');

const title = computed(() =>
    isPersonal.value ? t('drive.storage.personal_label') : t('drive.storage.label'),
);

const label = computed(() =>
    isPersonal.value
        ? t('drive.storage.personal_used', {
              used: formatBytes(props.storage.used_bytes),
          })
        : t('drive.storage.used', {
              used: formatBytes(props.storage.used_bytes),
              total: formatBytes(props.storage.quota_bytes),
          }),
);

const percent = computed(() => Math.min(100, Math.max(0, props.storage.used_percent)));
</script>

<template>
    <div class="min-w-[180px] space-y-1">
        <div class="text-muted-foreground flex justify-between text-xs">
            <span>{{ title }}</span>
            <span v-if="!isPersonal">{{ percent }}%</span>
        </div>
        <div v-if="!isPersonal" class="bg-muted h-2 overflow-hidden rounded-full">
            <div
                class="bg-primary h-full rounded-full transition-all"
                :style="{ width: `${percent}%` }"
            />
        </div>
        <p class="text-muted-foreground text-xs">{{ label }}</p>
    </div>
</template>
