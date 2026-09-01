<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { useThemeSettings } from '@/composables/useAppSettings';

const { t } = useI18n();
const themeSettings = useThemeSettings();
const { resolvedAppearance, updateAppearance } = useAppearance();

const isForced = computed(() => {
    const forced = themeSettings.value.forceAppearance;

    return forced === 'light' || forced === 'dark';
});

const label = computed(() =>
    resolvedAppearance.value === 'dark'
        ? t('common.switch_to_light')
        : t('common.switch_to_dark'),
);

function toggle(): void {
    if (isForced.value) {
        return;
    }

    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <Button
        v-if="!isForced"
        type="button"
        variant="ghost"
        size="icon"
        class="size-8 shrink-0 text-muted-foreground hover:text-foreground"
        :aria-label="label"
        :title="label"
        @click="toggle"
    >
        <Sun v-if="resolvedAppearance === 'dark'" class="size-4" />
        <Moon v-else class="size-4" />
        <span class="sr-only">{{ label }}</span>
    </Button>
</template>
