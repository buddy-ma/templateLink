<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { i18n } from '@/i18n';

const page = usePage();

watch(
    () => page.props.liveLocaleMessages,
    (msgs) => {
        const locale = page.props.appSettings?.localization?.currentLocale ?? 'en';
        if (msgs && typeof msgs === 'object' && Object.keys(msgs as object).length > 0) {
            i18n.global.mergeLocaleMessage(locale, msgs as Record<string, unknown>);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template></template>
