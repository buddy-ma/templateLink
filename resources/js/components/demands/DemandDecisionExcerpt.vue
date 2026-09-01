<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandDocumentPreview from '@/components/demands/DemandDocumentPreview.vue';
import { Button } from '@/components/ui/button';
import { RichTextContent } from '@/components/ui/rich-text';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { DemandAttachment } from '@/types/demands';

const props = withDefaults(
    defineProps<{
        demandId: number;
        html?: string | null;
        files?: DemandAttachment[];
        title: string;
        maxLength?: number;
    }>(),
    {
        html: null,
        files: () => [],
        maxLength: 140,
    },
);

const { t } = useI18n();
const detailsOpen = ref(false);

const plainText = computed(() =>
    (props.html ?? '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+/g, ' ')
        .trim(),
);

const excerpt = computed(() => {
    if (plainText.value.length <= props.maxLength) {
        return plainText.value;
    }

    return `${plainText.value.slice(0, props.maxLength).trim()}…`;
});

const isTruncated = computed(
    () => plainText.value.length > props.maxLength,
);

const hasDetails = computed(
    () => isTruncated.value || (props.files?.length ?? 0) > 0,
);
</script>

<template>
    <div v-if="plainText || (files?.length ?? 0) > 0" class="space-y-2">
        <p v-if="plainText" class="text-sm leading-relaxed">
            {{ excerpt }}
        </p>
        <p
            v-if="(files?.length ?? 0) > 0 && !detailsOpen"
            class="text-xs text-muted-foreground"
        >
            {{ t('demands.show.files_count', { count: files.length }) }}
        </p>
        <Button
            v-if="hasDetails"
            type="button"
            variant="outline"
            size="sm"
            @click="detailsOpen = true"
        >
            {{ t('demands.show.see_details') }}
        </Button>

        <Sheet v-model:open="detailsOpen">
            <SheetContent
                side="right"
                class="flex w-full flex-col gap-0 overflow-y-auto sm:max-w-lg"
            >
                <SheetHeader class="border-b px-1 pb-4 text-left">
                    <SheetTitle>{{ title }}</SheetTitle>
                    <SheetDescription>
                        {{ t('demands.show.decision_details') }}
                    </SheetDescription>
                </SheetHeader>
                <div class="space-y-4 px-1 py-4">
                    <RichTextContent :html="html" />
                    <div v-if="files?.length" class="space-y-2">
                        <p class="text-sm font-medium">
                            {{ t('demands.show.decision_files') }}
                        </p>
                        <DemandDocumentPreview
                            v-for="file in files"
                            :key="file.id"
                            :demand-id="demandId"
                            :file="file"
                        />
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </div>
</template>
