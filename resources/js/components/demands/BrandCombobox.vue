<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DemandBrand } from '@/types/demands';

const props = defineProps<{
    modelValue: number | null;
    brands: DemandBrand[];
    error?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const { t } = useI18n();
const query = ref('');
const open = ref(false);

function brandLabel(brand: DemandBrand): string {
    return (
        brand.label ??
        [brand.name, brand.dosage_form, brand.presentation]
            .filter(Boolean)
            .join(' — ')
    );
}

function brandMeta(brand: DemandBrand): string {
    return [brand.dosage_form, brand.presentation].filter(Boolean).join(' · ');
}

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.brands.slice(0, 12);
    return props.brands
        .filter((brand) => {
            const haystack = [
                brand.name,
                brand.sku ?? '',
                brand.dosage_form ?? '',
                brand.presentation ?? '',
                brandLabel(brand),
            ]
                .join(' ')
                .toLowerCase();
            return haystack.includes(q);
        })
        .slice(0, 12);
});

const selected = computed(
    () => props.brands.find((brand) => brand.id === props.modelValue) ?? null,
);

function pick(brand: DemandBrand): void {
    emit('update:modelValue', brand.id);
    query.value = brandLabel(brand);
    open.value = false;
}
</script>

<template>
    <div class="relative space-y-2">
        <Label>{{ t('demands.form.brand') }}</Label>
        <Input
            v-model="query"
            :placeholder="
                selected ? brandLabel(selected) : t('demands.form.brand_search')
            "
            autocomplete="off"
            @focus="open = true"
            @input="open = true"
        />
        <div
            v-if="open && filtered.length"
            class="bg-popover absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border shadow-md"
        >
            <button
                v-for="brand in filtered"
                :key="brand.id"
                type="button"
                class="hover:bg-accent flex w-full items-start justify-between gap-3 px-3 py-2 text-left text-sm"
                @click="pick(brand)"
            >
                <span class="min-w-0">
                    <span class="block font-medium">{{ brand.name }}</span>
                    <span
                        v-if="brandMeta(brand)"
                        class="text-muted-foreground block truncate text-xs"
                    >
                        {{ brandMeta(brand) }}
                    </span>
                </span>
                <span
                    v-if="brand.sku"
                    class="text-muted-foreground shrink-0 text-xs"
                >
                    {{ brand.sku }}
                </span>
            </button>
        </div>
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
