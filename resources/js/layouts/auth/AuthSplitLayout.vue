<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandProvider from '@/components/BrandProvider.vue';
import LocaleSwitcher from '@/components/LocaleSwitcher.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { useBranding } from '@/composables/useAppSettings';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const branding = useBranding();
const { t } = useI18n();
</script>

<template>
    <BrandProvider>
        <div class="relative grid min-h-dvh bg-background lg:grid-cols-2">
            <!-- Left: branded panel with theme image + quote -->
            <div class="relative hidden p-4 lg:flex">
                <div
                    class="relative flex w-full flex-col justify-end overflow-hidden rounded-3xl bg-[url('/images/auth/dark.png')] bg-cover bg-center p-10 text-white shadow-sm dark:bg-[url('/images/auth/dark.png')]"
                >
                    <div
                        class="absolute inset-0 bg-linear-to-t from-primary/80 via-black/20 to-black/10"
                    />

                    <div class="relative z-10 max-w-md space-y-6">
                        <div class="space-y-2">
                            <h2 class="text-3xl font-semibold tracking-tight">
                                {{ t('auth.panel_title') }}
                            </h2>
                            <p class="text-sm text-white/75">
                                {{ t('auth.panel_subtitle') }}
                            </p>
                        </div>

                        <blockquote
                            class="space-y-3 border-l-2 border-white/40 pl-4"
                        >
                            <p class="text-lg leading-relaxed text-white/95">
                                “{{ t('auth.quote') }}”
                            </p>
                        </blockquote>
                    </div>
                </div>
            </div>

            <!-- Right: form -->
            <div
                class="relative flex flex-col items-center justify-center px-6 py-10 sm:px-10"
            >
                <div class="absolute top-4 right-4 flex items-center gap-1">
                    <ThemeToggle />
                    <LocaleSwitcher />
                </div>

                <div
                    class="mb-8 flex w-full max-w-sm items-center gap-2 lg:hidden"
                >
                    <img
                        v-if="branding.logoUrl"
                        :src="branding.logoUrl"
                        :alt="branding.appName"
                        class="size-8 object-contain"
                    />
                    <AppLogoIcon
                        v-else
                        class="size-8 fill-current text-foreground"
                    />
                    <span class="font-medium">{{ branding.appName }}</span>
                </div>

                <div class="w-full max-w-sm space-y-6">
                    <Link
                        :href="home()"
                        class="relative z-10 flex items-center justify-start gap-2 text-lg font-medium"
                    >
                        <img
                            v-if="branding.logoUrl"
                            :src="branding.logoUrl"
                            :alt="branding.appName"
                            class="h-10 w-full max-w-48 object-contain"
                        />
                        <AppLogoIcon
                            v-else
                            class="size-8 fill-current text-white"
                        />
                    </Link>
                    <div class="space-y-2 text-center lg:text-left">
                        <h1
                            v-if="title"
                            class="text-2xl font-semibold tracking-tight"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </BrandProvider>
</template>
