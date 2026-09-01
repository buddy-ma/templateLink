<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BrandProvider from '@/components/BrandProvider.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useAppName } from '@/composables/useAppSettings';

const appName = useAppName();

const navItems = [
    { label: 'Translations', href: '/admin/translations' },
    { label: 'Roles', href: '/admin/roles' },
    { label: 'Design guide', href: '/admin/design-guide' },
];
</script>

<template>
    <BrandProvider>
        <div class="flex min-h-screen flex-col">
            <header
                class="sticky top-0 z-50 border-b bg-background/80 backdrop-blur-sm"
            >
                <div class="container flex h-14 items-center gap-4 px-4">
                    <Link href="/admin/translations" class="font-semibold">
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

            <main class="container flex-1 px-4 py-8">
                <slot />
            </main>
        </div>
    </BrandProvider>
</template>
