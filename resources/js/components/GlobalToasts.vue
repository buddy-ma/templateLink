<script setup lang="ts">
import FlashToaster from '@/components/FlashToaster.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <Toaster position="top-center" :duration="4000" />
    <FlashToaster />
</template>
