<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

const page = usePage();

watch(
    () =>
        [page.props?.flash?.success, page.props?.flash?.error] as readonly [
            string | undefined | null,
            string | undefined | null,
        ],
    ([success, error]) => {
        if (success && typeof success === 'string') {
            toast.success(success);
        }
        if (error && typeof error === 'string') {
            toast.error(error);
        }
    },
    { immediate: true },
);
</script>

<template></template>
