<script setup lang="ts">
import { Download, ExternalLink, FileSpreadsheet, FileText, FileType2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import type { DemandAttachment } from '@/types/demands';
import { cn } from '@/lib/utils';

const props = defineProps<{
    demandId: number;
    file: DemandAttachment;
}>();

const { t } = useI18n();

const extension = computed(() => {
    const parts = props.file.original_name.split('.');
    return (parts.length > 1 ? parts.at(-1) : '')?.toLowerCase() ?? '';
});

const downloadUrl = computed(
    () => `/demands/${props.demandId}/attachments/${props.file.id}`,
);

const previewUrl = computed(() => `${downloadUrl.value}?inline=1`);

const canPreview = computed(
    () =>
        extension.value === 'pdf' ||
        (props.file.mime ?? '').includes('pdf') ||
        (props.file.mime ?? '').startsWith('image/'),
);

const kind = computed(() => {
    if (extension.value === 'pdf' || (props.file.mime ?? '').includes('pdf')) {
        return 'pdf';
    }
    if (['doc', 'docx'].includes(extension.value)) return 'word';
    if (['ppt', 'pptx'].includes(extension.value)) return 'ppt';
    if ((props.file.mime ?? '').startsWith('image/')) return 'image';
    return 'file';
});

const kindStyles = computed(() => {
    const map = {
        pdf: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        word: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        ppt: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
        image: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        file: 'bg-muted text-muted-foreground',
    } as const;

    return map[kind.value];
});

const badgeStyles = computed(() => {
    const map = {
        pdf: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
        word: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        ppt: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
        image: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        file: 'bg-muted text-muted-foreground',
    } as const;

    return map[kind.value];
});

const formattedSize = computed(() => {
    const bytes = props.file.size ?? 0;
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
});
</script>

<template>
    <article
        class="bg-card hover:bg-muted/20 flex items-center gap-3 rounded-xl border p-2.5 transition-colors sm:gap-4 sm:p-3"
    >
        <div
            :class="
                cn(
                    'flex size-12 shrink-0 items-center justify-center rounded-xl sm:size-14',
                    kindStyles,
                )
            "
        >
            <FileText v-if="kind === 'pdf' || kind === 'word'" class="size-6" />
            <FileSpreadsheet v-else-if="kind === 'ppt'" class="size-6" />
            <FileType2 v-else class="size-6" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                <span
                    :class="
                        cn(
                            'rounded px-1.5 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                            badgeStyles,
                        )
                    "
                >
                    {{ extension || t('demands.show.file') }}
                </span>
                <span class="text-muted-foreground text-xs">{{ formattedSize }}</span>
            </div>
            <p class="truncate text-sm font-medium" :title="file.original_name">
                {{ file.original_name }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
            <Button v-if="canPreview" size="sm" variant="secondary" as-child>
                <a :href="previewUrl" target="_blank" rel="noopener noreferrer">
                    <ExternalLink class="size-3.5" />
                    <span class="hidden sm:inline">{{ t('demands.show.preview') }}</span>
                </a>
            </Button>
            <Button size="sm" variant="outline" as-child>
                <a :href="downloadUrl">
                    <Download class="size-3.5" />
                    <span class="hidden sm:inline">{{ t('demands.show.download') }}</span>
                </a>
            </Button>
        </div>
    </article>
</template>
