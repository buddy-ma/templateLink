<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useBranding } from '@/composables/useAppSettings';

const branding = useBranding();
</script>

<template>
    <div class="flex w-full items-center justify-center">
        <img
            v-if="branding.logoUrl"
            :src="branding.logoUrl"
            :alt="branding.appName"
            class="h-12 w-auto object-contain"
        />
        <AppLogoIcon
            v-else
            class="size-5 fill-current text-white dark:text-black"
        />
    </div>
</template>
