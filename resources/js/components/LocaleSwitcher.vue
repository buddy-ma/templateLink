<script setup lang="ts">
import { Languages } from 'lucide-vue-next';
import { ref } from 'vue';
import { useLocale } from '@/composables/useLocale';

const { currentLocale, supportedLocales, switchLocale } = useLocale();
const open = ref(false);

const localeLabels: Record<string, string> = {
    en: 'English',
    fr: 'Français',
    es: 'Español',
    ar: 'العربية',
    de: 'Deutsch',
    pt: 'Português',
    it: 'Italiano',
    nl: 'Nederlands',
    ru: 'Русский',
    zh: '中文',
    ja: '日本語',
    ko: '한국어',
};


function selectLocale(locale: string): void {
    switchLocale(locale);
    open.value = false;
}


</script>

<template>
    <div class="relative">
        <button
            class="flex h-8 w-auto items-center gap-1.5 border-none bg-transparent px-2 text-sm text-muted-foreground shadow-none hover:bg-accent hover:text-accent-foreground focus:ring-0 rounded-md"
            @click="open = !open"
            type="button"
        >
            <Languages class="size-3.5 shrink-0" />
        </button>
        <div
            v-if="open"
            class="absolute right-0 z-50 mt-1 min-w-[112px] rounded-md border border-border bg-popover text-popover-foreground shadow-md"
        >
            <ul>
                <li v-for="loc in supportedLocales" :key="loc">
                    <button
                        class="block w-full px-4 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                        :class="{ 'font-semibold': currentLocale === loc }"
                        @click="selectLocale(loc)"

                    >
                        {{ localeLabels[loc] ?? loc.toUpperCase() }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
