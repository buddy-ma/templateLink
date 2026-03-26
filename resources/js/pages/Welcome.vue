<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Globe, Moon, ShieldCheck, Sliders } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import BrandProvider from '@/components/BrandProvider.vue';
import { useBranding } from '@/composables/useAppSettings';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{ canRegister?: boolean }>(),
    { canRegister: true },
);

const page = usePage();
const { appName, logoUrl } = useBranding();

const features = [
    {
        icon: Globe,
        title: 'Multilanguage',
        description: 'Built-in i18n with dynamic locale switching. Supports LTR and RTL layouts.',
    },
    {
        icon: Moon,
        title: 'Dark / Light mode',
        description: 'Theme adapts to user preference or can be enforced globally from the admin panel.',
    },
    {
        icon: ShieldCheck,
        title: 'Zoho Authentication',
        description: 'Single sign-on with Zoho OAuth alongside traditional email & password login.',
    },
    {
        icon: Sliders,
        title: 'DB-Configurable',
        description: 'Branding, colors, logo, locales, and auth methods are all managed from the database.',
    },
];
</script>

<template>
    <Head :title="`Welcome — ${appName}`" />

    <BrandProvider>
        <div class="flex min-h-screen flex-col bg-background text-foreground">

            <!-- Nav -->
            <header class="sticky top-0 z-50 border-b border-border bg-background/80 backdrop-blur-sm">
                <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4">
                    <div class="flex items-center gap-2.5">
                        <img v-if="logoUrl" :src="logoUrl" :alt="appName" class="h-7 w-auto" />
                        <svg v-else class="h-7 w-7 text-primary" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="40" height="40" rx="10" fill="currentColor" fill-opacity="0.12" />
                            <path d="M10 28 L20 12 L30 28 M14 22 H26" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="font-semibold tracking-tight">{{ appName }}</span>
                    </div>

                    <nav class="flex items-center gap-2">
                        <template v-if="page.props.auth?.user">
                            <Button as-child size="sm">
                                <Link :href="dashboard()">Dashboard</Link>
                            </Button>
                        </template>
                        <template v-else>
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="login()">Log in</Link>
                            </Button>
                            <Button v-if="canRegister" as-child size="sm">
                                <Link :href="register()">Get started</Link>
                            </Button>
                        </template>
                    </nav>
                </div>
            </header>

            <!-- Hero -->
            <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center gap-6 px-4 py-24 text-center">
                <Badge variant="secondary" class="text-xs font-medium">
                    Laravel · Inertia · Vue 3 · Tailwind
                </Badge>

                <h1 class="max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    A template built for<br />
                    <span class="text-primary">real-world apps</span>
                </h1>

                <p class="max-w-xl text-base text-muted-foreground sm:text-lg">
                    Everything you need from day one: authentication, theming, i18n, role-based access,
                    and global settings — all driven from the database.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    <Button v-if="canRegister && !page.props.auth?.user" as-child size="lg">
                        <Link :href="register()">Get started for free</Link>
                    </Button>
                    <Button as-child variant="outline" size="lg">
                        <Link :href="page.props.auth?.user ? dashboard() : login()">
                            {{ page.props.auth?.user ? 'Go to dashboard' : 'Sign in' }}
                        </Link>
                    </Button>
                </div>
            </main>

            <Separator />

            <!-- Features -->
            <section class="mx-auto w-full max-w-5xl px-4 py-16">
                <h2 class="mb-10 text-center text-2xl font-semibold tracking-tight">
                    What's included
                </h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="feature in features" :key="feature.title" class="bg-muted/40">
                        <CardHeader class="gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <component :is="feature.icon" class="h-4 w-4" />
                            </div>
                            <CardTitle class="text-sm">{{ feature.title }}</CardTitle>
                            <CardDescription class="text-xs leading-relaxed">
                                {{ feature.description }}
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </section>

            <!-- Footer -->
            <footer class="border-t border-border">
                <div class="mx-auto flex h-12 max-w-5xl items-center justify-center px-4">
                    <p class="text-xs text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    </BrandProvider>
</template>
