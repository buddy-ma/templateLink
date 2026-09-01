<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    html: string | null | undefined;
    class?: string;
}>();

const hasContent = computed(() => {
    const value = (props.html ?? '').trim();
    if (value === '' || value === '<p></p>') {
        return false;
    }
    return value.replace(/<[^>]*>/g, '').trim().length > 0;
});
</script>

<template>
    <div
        v-if="hasContent"
        :class="cn('rich-text-body text-sm', props.class)"
        v-html="html"
    />
</template>
