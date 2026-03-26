<script setup lang="ts">
import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type SearchableOption = { label: string; value: string };

const props = withDefaults(
    defineProps<{
        id?: string;
        label?: string;
        modelValue: string;
        options: SearchableOption[];
        placeholder?: string;
        disabled?: boolean;
        /** For long lists (e.g. timezones) */
        filterable?: boolean;
    }>(),
    {
        placeholder: 'Search…',
        filterable: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function reduceOption(opt: SearchableOption): string {
    return opt.value;
}
</script>

<template>
    <div class="space-y-2">
        <Label v-if="label" :for="id">{{ label }}</Label>
        <v-select
            :id="id"
            :model-value="modelValue"
            :options="options"
            :disabled="disabled"
            :clearable="false"
            :searchable="filterable"
            :placeholder="placeholder"
            label="label"
            :reduce="reduceOption"
            class="searchable-select w-full"
            :class="cn('w-full')"
            @update:model-value="emit('update:modelValue', $event as string)"
        />
    </div>
</template>

<style scoped>
.searchable-select :deep(.vs__dropdown-toggle) {
    border-radius: 0.375rem;
    border-color: var(--input);
    background: transparent;
    min-height: 2.25rem;
    padding: 0.125rem 0.5rem;
}
.dark .searchable-select :deep(.vs__dropdown-toggle) {
    background: color-mix(in oklab, var(--input) 30%, transparent);
}
.searchable-select :deep(.vs__search) {
    margin: 0;
}
.searchable-select :deep(.vs__selected) {
    color: var(--foreground);
}
.searchable-select :deep(.vs__dropdown-menu) {
    border-radius: 0.5rem;
    border-color: var(--border);
    background: var(--popover);
    color: var(--popover-foreground);
    box-shadow: 0 10px 40px rgb(0 0 0 / 0.12);
}
.dark .searchable-select :deep(.vs__dropdown-menu) {
    box-shadow: 0 10px 40px rgb(0 0 0 / 0.4);
}
.searchable-select :deep(.vs__dropdown-option) {
    color: var(--foreground);
}
.searchable-select :deep(.vs__dropdown-option--highlight) {
    background: var(--accent);
    color: var(--accent-foreground);
}
</style>
