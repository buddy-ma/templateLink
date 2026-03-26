<script setup lang="ts">
import { onMounted, watch } from 'vue';
import I18nLiveSync from '@/components/I18nLiveSync.vue';
import { useBranding } from '@/composables/useAppSettings';

const branding = useBranding();

const GOOGLE_LINK_ID = 'app-branding-google-font';
const UPLOAD_STYLE_ID = 'app-branding-upload-font';

function applyBrandColors(): void {
    const root = document.documentElement;
    root.style.setProperty('--primary', `hsl(${branding.primaryColor})`);
    root.style.setProperty('--primary-foreground', `hsl(${branding.primaryForegroundColor})`);
    root.style.setProperty('--sidebar-primary', `hsl(${branding.sidebarPrimaryColor})`);
    root.style.setProperty('--sidebar-primary-foreground', `hsl(${branding.primaryForegroundColor})`);
}

function applyFontStack(): void {
    const root = document.documentElement;
    if (branding.fontStack) {
        root.style.setProperty('--font-sans', branding.fontStack);
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
    if (branding.fontSource === 'google') {
        ensureGoogleFontLink(branding.googleFontStylesheetUrl ?? null);
        ensureUploadFontFace(null, null);
    } else if (branding.fontSource === 'upload') {
        ensureGoogleFontLink(null);
        ensureUploadFontFace(branding.fontUploadUrl ?? null, branding.fontFaceName ?? null);
    } else {
        ensureGoogleFontLink(null);
        ensureUploadFontFace(null, null);
    }
}

onMounted(() => {
    applyBrandColors();
    applyFonts();
});

watch(
    () => [branding.primaryColor, branding.primaryForegroundColor, branding.sidebarPrimaryColor],
    () => applyBrandColors(),
);

watch(
    () => [
        branding.fontStack,
        branding.fontSource,
        branding.fontUploadUrl,
        branding.fontFaceName,
        branding.googleFontStylesheetUrl,
    ],
    () => applyFonts(),
    { deep: true },
);
</script>

<template>
    <I18nLiveSync />
    <slot />
</template>
