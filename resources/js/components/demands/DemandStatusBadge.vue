<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import type { DemandStatus } from '@/types/demands';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status: DemandStatus;
}>();

const { t } = useI18n();

const classes = computed(() => {
    const map: Record<DemandStatus, string> = {
        draft: 'bg-secondary text-secondary-foreground',
        pending_manager: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        pending_validation: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        refused: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
        blocked: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
        pending_business_dev: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        pending_closure: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200',
        closed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    };
    return map[props.status];
});
</script>

<template>
    <Badge variant="secondary" :class="cn('border-0 font-medium', classes)">
        {{ t(`demands.status.${status}`) }}
    </Badge>
</template>
