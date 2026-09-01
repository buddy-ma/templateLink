<script setup lang="ts">
import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { DemandUser } from '@/types/demands';

const props = withDefaults(
    defineProps<{
        modelValue: number[];
        validators: DemandUser[];
        min?: number;
        error?: string;
        compact?: boolean;
        showFinalGroup?: boolean;
    }>(),
    {
        min: undefined,
        error: undefined,
        compact: false,
        showFinalGroup: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const { t } = useI18n();
const min = computed(() => props.min ?? 3);

const selectedIds = computed(() => props.modelValue.map((id) => Number(id)));

const selected = computed(() =>
    selectedIds.value
        .map((id) => props.validators.find((v) => Number(v.id) === id))
        .filter((v): v is DemandUser => !!v),
);

const available = computed(() =>
    props.validators.filter((v) => !selectedIds.value.includes(Number(v.id))),
);

function move(index: number, delta: number): void {
    const next = [...selectedIds.value];
    const target = index + delta;
    if (target < 0 || target >= next.length) return;
    [next[index], next[target]] = [next[target], next[index]];
    emit('update:modelValue', next);
}

function remove(index: number): void {
    const next = [...selectedIds.value];
    next.splice(index, 1);
    emit('update:modelValue', next);
}

function add(id: number): void {
    if (!id || selectedIds.value.includes(id)) return;
    emit('update:modelValue', [...selectedIds.value, id]);
}

function onAddChange(event: Event): void {
    const select = event.target as HTMLSelectElement;
    add(Number(select.value));
    select.value = '';
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="!compact" class="flex items-end justify-between gap-2">
            <div>
                <Label>{{ t('demands.form.validators') }}</Label>
                <p class="text-xs text-muted-foreground">
                    {{ t('demands.form.validators_help', { min }) }}
                </p>
            </div>
            <select
                class="h-9 max-w-xs cursor-pointer rounded-md border border-input bg-background px-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="available.length === 0"
                @change="onAddChange"
            >
                <option value="">{{ t('demands.form.add_validator') }}</option>
                <option v-for="v in available" :key="v.id" :value="v.id">
                    {{ v.name }}
                </option>
            </select>
        </div>

        <select
            v-else
            class="h-9 w-full cursor-pointer rounded-md border border-input bg-background px-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="available.length === 0"
            @change="onAddChange"
        >
            <option value="">{{ t('demands.form.add_validator') }}</option>
            <option v-for="v in available" :key="v.id" :value="v.id">
                {{ v.name }}
            </option>
        </select>

        <p v-if="available.length === 0" class="text-xs text-muted-foreground">
            {{ t('demands.form.validators_none_available') }}
        </p>

        <ul class="space-y-2">
            <li
                v-for="(v, index) in selected"
                :key="v.id"
                class="flex items-start gap-2 rounded-lg border px-3 py-2"
            >
                <span
                    class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground"
                >
                    {{ index + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="leading-snug font-medium break-words">
                        {{ v.name }}
                    </p>
                    <p class="text-xs break-all text-muted-foreground">
                        {{ v.email }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-0.5">
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        @click="move(index, -1)"
                    >
                        <ChevronUp class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        @click="move(index, 1)"
                    >
                        <ChevronDown class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="destructive"
                        @click="remove(index)"
                    >
                        <Trash2 class="size-4 text-white" />
                    </Button>
                </div>
            </li>
            <li
                v-if="showFinalGroup"
                class="flex items-start gap-2 rounded-lg border border-dashed bg-muted/30 px-3 py-2"
            >
                <span
                    class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold"
                >
                    {{ selected.length + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="leading-snug font-medium">
                        {{ t('demands.form.reglementaires_group') }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ t('demands.form.reglementaires_group_help') }}
                    </p>
                </div>
            </li>
        </ul>

        <p
            v-if="modelValue.length < min"
            class="flex items-center gap-1 text-xs text-muted-foreground"
        >
            <Plus class="size-3" />
            {{
                t('demands.form.validators_remaining', {
                    count: min - modelValue.length,
                })
            }}
        </p>
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
