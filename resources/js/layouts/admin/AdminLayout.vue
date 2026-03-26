<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import BrandProvider from '@/components/BrandProvider.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useAppName } from '@/composables/useAppSettings';

type FlashProps = PageProps & { flash?: { success?: string } };
const page = usePage<FlashProps>();
const appName = useAppName();

const navItems = [
    { label: 'Settings', href: '/admin/settings' },
];
</script>

<template>
    <BrandProvider>
        <div class="flex min-h-screen flex-col">
            <!-- Top bar -->
            <header class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm">
                <div class="container flex h-14 items-center gap-4 px-4">
                    <Link href="/admin/settings" class="font-semibold">
                        {{ appName }} — Admin
                    </Link>
                    <Separator orientation="vertical" class="h-5" />
                    <nav class="flex items-center gap-1">
                        <Button
                            v-for="item in navItems"
                            :key="item.href"
                            variant="ghost"
                            size="sm"
                            as-child
                        >
                            <Link :href="item.href">{{ item.label }}</Link>
                        </Button>
                    </nav>
                    <div class="ml-auto flex items-center gap-4">
                        <LocaleSwitcher />
                        <Button variant="outline" size="sm" as-child>
                            <Link href="/dashboard">Back to App</Link>
                        </Button>
                    </div>
                </div>
            </header>

            <!-- Flash messages -->
            <div
                v-if="page.props.flash?.success"
                class="bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400"
            >
                {{ page.props.flash.success }}
            </div>

            <main class="container flex-1 px-4 py-8">
                <slot />
            </main>
        </div>
    </BrandProvider>
</template>
