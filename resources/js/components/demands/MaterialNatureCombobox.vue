<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DemandMaterialNature } from '@/types/demands';

const props = defineProps<{
    materialNatureId: number | null;
    materialNatureName: string;
    options: DemandMaterialNature[];
    error?: string;
}>();

const emit = defineEmits<{
    'update:materialNatureId': [value: number | null];
    'update:materialNatureName': [value: string];
}>();

const { t } = useI18n();
const query = ref(props.materialNatureName || '');
const open = ref(false);

watch(
    () => props.materialNatureName,
    (v) => {
        if (v && v !== query.value) query.value = v;
    },
);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options.slice(0, 12);
    return props.options.filter((n) => n.name.toLowerCase().includes(q)).slice(0, 12);
});

const exactMatch = computed(() =>
    props.options.find((n) => n.name.toLowerCase() === query.value.trim().toLowerCase()),
);

function pick(item: DemandMaterialNature): void {
    emit('update:materialNatureId', item.id);
    emit('update:materialNatureName', item.name);
    query.value = item.name;
    open.value = false;
}

function useCreate(): void {
    const name = query.value.trim();
    if (!name) return;
    emit('update:materialNatureId', null);
    emit('update:materialNatureName', name);
    open.value = false;
}

function onInput(): void {
    open.value = true;
    const match = exactMatch.value;
    if (match) {
        emit('update:materialNatureId', match.id);
        emit('update:materialNatureName', match.name);
    } else {
        emit('update:materialNatureId', null);
        emit('update:materialNatureName', query.value.trim());
    }
}
</script>

<template>
    <div class="relative space-y-2">
        <Label>{{ t('demands.form.material_nature') }}</Label>
        <Input
            v-model="query"
            :placeholder="t('demands.form.material_nature_search')"
            autocomplete="off"
            @focus="open = true"
            @input="onInput"
        />
        <div
            v-if="open"
            class="bg-popover absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border shadow-md"
        >
            <button
                v-for="item in filtered"
                :key="item.id"
                type="button"
                class="hover:bg-accent flex w-full px-3 py-2 text-left text-sm"
                @click="pick(item)"
            >
                {{ item.name }}
            </button>
            <div v-if="query.trim() && !exactMatch" class="border-t p-2">
                <Button type="button" size="sm" class="w-full" @click="useCreate">
                    {{ t('demands.form.create_nature', { name: query.trim() }) }}
                </Button>
            </div>
        </div>
        <p v-if="error" class="text-destructive text-sm">{{ error }}</p>
    </div>
</template>
