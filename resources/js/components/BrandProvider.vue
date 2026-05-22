<script setup lang="ts">
import { watch } from 'vue';
import I18nLiveSync from '@/components/I18nLiveSync.vue';
import { useBranding } from '@/composables/useAppSettings';

const branding = useBranding();

const GOOGLE_LINK_ID = 'app-branding-google-font';
const UPLOAD_STYLE_ID = 'app-branding-upload-font';

function applyBrandColors(): void {
    const root = document.documentElement;
    const b = branding.value;
    root.style.setProperty('--primary', `hsl(${b.primaryColor})`);
    root.style.setProperty('--primary-foreground', `hsl(${b.primaryForegroundColor})`);
    root.style.setProperty('--sidebar-primary', `hsl(${b.sidebarPrimaryColor})`);
    root.style.setProperty('--sidebar-primary-foreground', `hsl(${b.primaryForegroundColor})`);
}

function applyFontStack(): void {
    const root = document.documentElement;
    const stack = branding.value.fontStack;
    if (stack) {
        root.style.setProperty('--font-sans', stack);
    }
}

function ensureGoogleFontLink(href: string | null | undefined): void {
    const existing = document.getElementById(GOOGLE_LINK_ID) as HTMLLinkElement | null;
    if (existing) {
        existing.remove();
    }
    if (!href) {
        return;
    }
    const link = document.createElement('link');
    link.id = GOOGLE_LINK_ID;
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}

function ensureUploadFontFace(url: string | null | undefined, face: string | null | undefined): void {
    const existing = document.getElementById(UPLOAD_STYLE_ID);
    if (existing) {
        existing.remove();
    }
    if (!url || !face) {
        return;
    }
    let format = 'opentype';
    if (url.endsWith('.woff2')) {
        format = 'woff2';
    } else if (url.endsWith('.woff')) {
        format = 'woff';
    } else if (url.endsWith('.ttf')) {
        format = 'truetype';
    }
    const style = document.createElement('style');
    style.id = UPLOAD_STYLE_ID;
    style.textContent = `@font-face{font-family:'${face}';src:url('${url}') format('${format}');font-weight:400 700;font-display:swap;}`;
    document.head.appendChild(style);
}

function applyFonts(): void {
    applyFontStack();
    const b = branding.value;
    if (b.fontSource === 'google') {
        ensureGoogleFontLink(b.googleFontStylesheetUrl ?? null);
        ensureUploadFontFace(null, null);
    } else if (b.fontSource === 'upload') {
        ensureGoogleFontLink(null);
        ensureUploadFontFace(b.fontUploadUrl ?? null, b.fontFaceName ?? null);
    } else {
        ensureGoogleFontLink(null);
        ensureUploadFontFace(null, null);
    }
}

function applyAll(): void {
    applyBrandColors();
    applyFonts();
}

watch(
    branding,
    () => applyAll(),
    { deep: true, immediate: true },
);
</script>

<template>
    <I18nLiveSync />
    <slot />
</template>
