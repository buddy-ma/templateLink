<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Palette,
    Globe2,
    SunMoon,
    Shield,
    Sparkles,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { PageProps } from '@inertiajs/core';
import type { FontSource } from '@/types/settings';
import FaviconDropzone from '@/components/admin/FaviconDropzone.vue';
import FontDropzone from '@/components/admin/FontDropzone.vue';
import HslColorPickerField from '@/components/admin/HslColorPickerField.vue';
import LogoDropzone from '@/components/admin/LogoDropzone.vue';
import SearchableSelect, {
    type SearchableOption,
} from '@/components/admin/SearchableSelect.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { useBranding } from '@/composables/useAppSettings';
import type { BreadcrumbItem } from '@/types';

type FlashProps = PageProps & { flash?: { success?: string } };
const page = usePage<FlashProps>();
const flashSuccess = computed(() => page.props.flash?.success);
const brandingLive = useBranding();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'App Settings', href: '/admin/settings' },
];

type SettingsMap = Record<string, string | null>;

const props = defineProps<{
    settings: SettingsMap;
    timezones: string[];
    fontPresetOptions: SearchableOption[];
    googleFontOptions: SearchableOption[];
    zohoSecretConfigured: boolean;
}>();

const fontSource = ref<FontSource>(
    (props.settings['branding.font_source'] as FontSource) || 'preset',
);

const brandingForm = useForm({
    'branding.app_name': props.settings['branding.app_name'] ?? '',
    'branding.primary_color':
        props.settings['branding.primary_color'] ?? '0 0% 9%',
    'branding.primary_foreground_color':
        props.settings['branding.primary_foreground_color'] ?? '0 0% 98%',
    'branding.sidebar_primary_color':
        props.settings['branding.sidebar_primary_color'] ?? '0 0% 10%',
    'branding.font_preset':
        props.settings['branding.font_preset'] ??
        props.settings['branding.font_family'] ??
        'instrument-sans',
    'branding.google_font_family':
        props.settings['branding.google_font_family'] ?? 'Poppins',
});

const rawLocales = props.settings['localization.supported_locales'];
const parsedLocales: string[] = (() => {
    if (!rawLocales) return ['fr', 'en'];
    try {
        return JSON.parse(rawLocales) as string[];
    } catch {
        return ['fr', 'en'];
    }
})();

const localizationForm = useForm({
    'localization.default_locale':
        props.settings['localization.default_locale'] ?? 'fr',
    'localization.supported_locales': parsedLocales,
    'localization.timezone': props.settings['localization.timezone'] ?? 'UTC',
});

const themeForm = useForm({
    'theme.default_appearance':
        props.settings['theme.default_appearance'] ?? 'system',
    'theme.force_appearance': props.settings['theme.force_appearance'] ?? '',
});

const authForm = useForm({
    'auth.zoho_enabled': props.settings['auth.zoho_enabled'] === '1',
    'auth.password_login_enabled':
        props.settings['auth.password_login_enabled'] !== '0',
    'auth.zoho_client_id': props.settings['auth.zoho_client_id'] ?? '',
});

const zohoSecretInput = ref('');

const availableLocales = [
    { code: 'fr', label: 'Français' },
    { code: 'en', label: 'English' },
    { code: 'es', label: 'Español' },
    { code: 'ar', label: 'العربية' },
    { code: 'de', label: 'Deutsch' },
    { code: 'pt', label: 'Português' },
    { code: 'it', label: 'Italiano' },
    { code: 'nl', label: 'Nederlands' },
    { code: 'ru', label: 'Русский' },
    { code: 'zh', label: '中文' },
    { code: 'ja', label: '日本語' },
    { code: 'ko', label: '한국어' },
];

const localeSelectOptions = computed<SearchableOption[]>(() =>
    availableLocales.map((loc) => ({ label: loc.label, value: loc.code })),
);

const timezoneOptions = computed<SearchableOption[]>(() =>
    props.timezones.map((z) => ({ label: z, value: z })),
);

function setFontSource(next: FontSource): void {
    fontSource.value = next;
}

function toggleLocale(code: string): void {
    const idx =
        localizationForm['localization.supported_locales'].indexOf(code);
    if (idx === -1) {
        localizationForm['localization.supported_locales'].push(code);
    } else if (localizationForm['localization.supported_locales'].length > 1) {
        localizationForm['localization.supported_locales'].splice(idx, 1);
    }
}

/** Full reload so shared props, CSS variables, fonts, and i18n pick up new settings immediately. */
function reloadAfterConfigSave(): void {
    window.location.reload();
}

function uploadLogoFile(file: File): void {
    const data = new FormData();
    data.append('logo', file);
    router.post('/admin/settings/logo', data, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: reloadAfterConfigSave,
    });
}

function uploadFaviconFile(file: File): void {
    const data = new FormData();
    data.append('favicon', file);
    router.post('/admin/settings/favicon', data, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: reloadAfterConfigSave,
    });
}

function uploadFontFile(file: File): void {
    const data = new FormData();
    data.append('font', file);
    router.post('/admin/settings/font', data, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: reloadAfterConfigSave,
    });
}

function submit(): void {
    const authPayload: {
        zoho_enabled: boolean;
        password_login_enabled: boolean;
        zoho_client_id: string;
        zoho_client_secret?: string;
    } = {
        zoho_enabled: authForm['auth.zoho_enabled'],
        password_login_enabled: authForm['auth.password_login_enabled'],
        zoho_client_id: authForm['auth.zoho_client_id'],
    };
    if (zohoSecretInput.value.trim() !== '') {
        authPayload.zoho_client_secret = zohoSecretInput.value;
    }

    const merged = {
        branding: {
            app_name: brandingForm['branding.app_name'],
            primary_color: brandingForm['branding.primary_color'],
            primary_foreground_color:
                brandingForm['branding.primary_foreground_color'],
            sidebar_primary_color:
                brandingForm['branding.sidebar_primary_color'],
            font_source: fontSource.value,
            font_preset: brandingForm['branding.font_preset'],
            google_font_family: brandingForm['branding.google_font_family'],
        },
        localization: {
            default_locale: localizationForm['localization.default_locale'],
            supported_locales:
                localizationForm['localization.supported_locales'],
            timezone: localizationForm['localization.timezone'],
        },
        theme: {
            default_appearance: themeForm['theme.default_appearance'],
            force_appearance: themeForm['theme.force_appearance'],
        },
        auth: authPayload,
    };

    router.put('/admin/settings', merged, {
        preserveScroll: true,
        onSuccess: reloadAfterConfigSave,
    });
}

type Tab = 'branding' | 'localization' | 'appearance' | 'authentication';
const activeTab = ref<Tab>('branding');

const tabs: { id: Tab; label: string; icon: typeof Palette }[] = [
    { id: 'branding', label: 'Branding', icon: Palette },
    { id: 'localization', label: 'Localization', icon: Globe2 },
    { id: 'appearance', label: 'Appearance', icon: SunMoon },
    { id: 'authentication', label: 'Authentication', icon: Shield },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Application settings" />

        <div class="flex flex-1 flex-col gap-8 p-4 md:p-8">
            <div class="mx-auto w-full max-w-4xl space-y-8">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <Sparkles class="size-8 text-primary" />
                        <h1 class="text-3xl font-semibold tracking-tight">
                            Application settings
                        </h1>
                    </div>
                    <p
                        class="max-w-2xl text-sm leading-relaxed text-muted-foreground"
                    >
                        Configure branding, localization, appearance, and
                        sign-in options. The page reloads after each save so
                        your changes apply everywhere right away.
                    </p>
                </div>

                <Alert
                    v-if="flashSuccess"
                    class="border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30"
                >
                    <CheckCircle2
                        class="size-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <AlertDescription
                        class="text-emerald-800 dark:text-emerald-300"
                    >
                        {{ flashSuccess }}
                    </AlertDescription>
                </Alert>

                <div
                    class="flex flex-wrap gap-1 rounded-xl border bg-muted/40 p-1 shadow-sm"
                    role="tablist"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab.id"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors min-[480px]:flex-none min-[480px]:px-4"
                        :class="
                            activeTab === tab.id
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = tab.id"
                    >
                        <component :is="tab.icon" class="size-4 shrink-0" />
                        <span class="hidden sm:inline">{{ tab.label }}</span>
                    </button>
                </div>

                <Card
                    v-if="activeTab === 'branding'"
                    class="border-none shadow-md"
                >
                    <CardHeader>
                        <CardTitle>Branding</CardTitle>
                        <CardDescription
                            >Name, logo, typography, and theme
                            colors.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-8">
                        <div class="grid gap-2">
                            <Label for="app_name">Application name</Label>
                            <Input
                                id="app_name"
                                v-model="brandingForm['branding.app_name']"
                                class="max-w-xl"
                                placeholder="My App"
                            />
                        </div>

                        <div class="space-y-3">
                            <Label>Typography</Label>
                            <p class="text-xs text-muted-foreground">
                                Choose a curated web font (Bunny), any listed
                                Google Font, or upload your own file.
                            </p>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <button
                                    v-for="mode in [
                                        'preset',
                                        'google',
                                        'upload',
                                    ] as const"
                                    :key="mode"
                                    type="button"
                                    :class="[
                                        'border-input',
                                        'data-[active=true]:border-primary',
                                        'data-[active=true]:bg-primary/5',
                                        'rounded-xl',
                                        'border',
                                        'px-3',
                                        'py-2.5',
                                        'text-left',
                                        'text-sm',
                                        'font-medium',
                                        'capitalize',
                                        'transition-colors',
                                        {
                                            'border-primary bg-primary/50':
                                                fontSource === mode,
                                        },
                                    ]"
                                    :data-active="fontSource === mode"
                                    @click="setFontSource(mode)"
                                >
                                    {{
                                        mode === 'preset'
                                            ? 'Library'
                                            : mode === 'google'
                                              ? 'Google Fonts'
                                              : 'Upload'
                                    }}
                                </button>
                            </div>
                        </div>

                        <SearchableSelect
                            v-if="fontSource === 'preset'"
                            id="font_preset"
                            label="Font"
                            v-model="brandingForm['branding.font_preset']"
                            :options="fontPresetOptions"
                            placeholder="Search fonts…"
                        />

                        <SearchableSelect
                            v-if="fontSource === 'google'"
                            id="google_font"
                            label="Google Font"
                            v-model="
                                brandingForm['branding.google_font_family']
                            "
                            :options="googleFontOptions"
                            placeholder="Search fonts…"
                        />

                        <div v-if="fontSource === 'upload'" class="space-y-2">
                            <Label>Font file</Label>
                            <p class="text-xs text-muted-foreground">
                                After upload, this app switches to your file
                                (WOFF2 recommended). Save settings to confirm
                                other changes.
                            </p>
                            <FontDropzone @select="uploadFontFile" />
                        </div>

                        <Separator />

                        <div class="space-y-2">
                            <Label>Logo</Label>
                            <p class="text-xs text-muted-foreground">
                                PNG, JPG, SVG, or WebP — max 2&nbsp;MB.
                            </p>
                            <LogoDropzone @select="uploadLogoFile" />
                        </div>

                        <div
                            v-if="brandingLive.logoUrl"
                            class="flex items-center gap-4 rounded-xl border bg-muted/50 p-4"
                        >
                            <img
                                :src="brandingLive.logoUrl"
                                alt="Logo preview"
                                class="max-h-16 max-w-[200px] bg-background object-contain p-2"
                            />
                            <span class="text-xs text-muted-foreground"
                                >Current logo from your saved settings.</span
                            >
                        </div>

                        <Separator />

                        <div class="space-y-2">
                            <Label>Favicon</Label>
                            <p class="text-xs text-muted-foreground">
                                Shown in browser tabs. PNG, JPG, SVG, WebP, or ICO — max 512&nbsp;KB.
                                The page reloads after upload so the tab icon updates.
                            </p>
                            <FaviconDropzone @select="uploadFaviconFile" />
                        </div>

                        <div
                            v-if="brandingLive.faviconUrl"
                            class="flex items-center gap-4 rounded-xl border bg-muted/50 p-4"
                        >
                            <img
                                :src="brandingLive.faviconUrl"
                                alt="Favicon preview"
                                class="size-8 bg-background object-contain p-1"
                            />
                            <span class="text-xs text-muted-foreground"
                                >Current favicon from your saved settings.</span
                            >
                        </div>

                        <Separator />

                        <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
                            <HslColorPickerField
                                id="primary_color"
                                label="Primary color"
                                v-model="brandingForm['branding.primary_color']"
                                hint="Buttons, links, and focus rings (stored as HSL for CSS variables)."
                            />
                            <HslColorPickerField
                                id="primary_fg_color"
                                label="Primary foreground"
                                v-model="
                                    brandingForm[
                                        'branding.primary_foreground_color'
                                    ]
                                "
                                hint="Text and icons on primary-colored surfaces."
                            />
                            <HslColorPickerField
                                id="sidebar_color"
                                label="Sidebar accent"
                                v-model="
                                    brandingForm[
                                        'branding.sidebar_primary_color'
                                    ]
                                "
                                hint="Sidebar primary highlight color."
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-if="activeTab === 'localization'"
                    class="border-none shadow-md"
                >
                    <CardHeader>
                        <CardTitle>Localization</CardTitle>
                        <CardDescription
                            >Default language, supported locales, and server
                            timezone.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-8">
                        <SearchableSelect
                            id="default_locale"
                            label="Default locale"
                            v-model="
                                localizationForm['localization.default_locale']
                            "
                            :options="localeSelectOptions"
                            placeholder="Search languages…"
                        />

                        <div class="space-y-3">
                            <Label>Supported locales</Label>
                            <p class="text-xs text-muted-foreground">
                                At least one locale must stay enabled.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="loc in availableLocales"
                                    :key="loc.code"
                                    type="button"
                                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors"
                                    :class="
                                        localizationForm[
                                            'localization.supported_locales'
                                        ].includes(loc.code)
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border text-muted-foreground hover:border-primary/50'
                                    "
                                    @click="toggleLocale(loc.code)"
                                >
                                    {{ loc.label }}
                                </button>
                            </div>
                        </div>

                        <SearchableSelect
                            id="timezone"
                            label="Timezone"
                            v-model="localizationForm['localization.timezone']"
                            :options="timezoneOptions"
                            placeholder="Search timezones…"
                        />
                    </CardContent>
                </Card>

                <Card
                    v-if="activeTab === 'appearance'"
                    class="border-none shadow-md"
                >
                    <CardHeader>
                        <CardTitle>Appearance</CardTitle>
                        <CardDescription
                            >Default and forced light/dark mode for all
                            users.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-8">
                        <div class="space-y-3">
                            <Label>Default theme mode</Label>
                            <p class="text-xs text-muted-foreground">
                                Initial mode for users who have not chosen a
                                preference.
                            </p>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <button
                                    v-for="mode in ['light', 'dark', 'system']"
                                    :key="mode"
                                    type="button"
                                    class="rounded-xl border border-input px-4 py-3 text-left text-sm font-medium transition-colors data-[active=true]:border-primary data-[active=true]:bg-primary/5"
                                    :data-active="
                                        themeForm[
                                            'theme.default_appearance'
                                        ] === mode
                                    "
                                    @click="
                                        themeForm['theme.default_appearance'] =
                                            mode
                                    "
                                >
                                    <span class="capitalize">{{ mode }}</span>
                                </button>
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-3">
                            <Label>Force theme mode</Label>
                            <p class="text-xs text-muted-foreground">
                                Override user preference when set to light or
                                dark.
                            </p>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <button
                                    type="button"
                                    class="rounded-xl border border-input px-4 py-3 text-left text-sm transition-colors data-[active=true]:border-primary data-[active=true]:bg-primary/5"
                                    :data-active="
                                        themeForm['theme.force_appearance'] ===
                                        ''
                                    "
                                    @click="
                                        themeForm['theme.force_appearance'] = ''
                                    "
                                >
                                    User choice
                                </button>
                                <button
                                    v-for="mode in ['light', 'dark']"
                                    :key="mode"
                                    type="button"
                                    class="rounded-xl border border-input px-4 py-3 text-left text-sm capitalize transition-colors data-[active=true]:border-primary data-[active=true]:bg-primary/5"
                                    :data-active="
                                        themeForm['theme.force_appearance'] ===
                                        mode
                                    "
                                    @click="
                                        themeForm['theme.force_appearance'] =
                                            mode
                                    "
                                >
                                    Force {{ mode }}
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card
                    v-if="activeTab === 'authentication'"
                    class="border-none shadow-md"
                >
                    <CardHeader>
                        <CardTitle>Authentication</CardTitle>
                        <CardDescription
                            >Control sign-in methods and Zoho OAuth
                            credentials.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="flex gap-4 rounded-xl border p-4">
                            <Checkbox
                                id="password_login"
                                v-model="
                                    authForm['auth.password_login_enabled']
                                "
                            />
                            <div class="space-y-1">
                                <Label
                                    for="password_login"
                                    class="cursor-pointer text-base"
                                    >Email / password</Label
                                >
                                <p class="text-sm text-muted-foreground">
                                    Allow classic email and password sign-in.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-xl border p-4">
                            <div class="flex gap-4">
                                <Checkbox
                                    id="zoho_enabled"
                                    v-model="authForm['auth.zoho_enabled']"
                                />
                                <div class="space-y-1">
                                    <Label
                                        for="zoho_enabled"
                                        class="cursor-pointer text-base"
                                        >Zoho OAuth</Label
                                    >
                                    <p class="text-sm text-muted-foreground">
                                        Allow sign-in with Zoho. Client secret
                                        is stored encrypted in the database.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="authForm['auth.zoho_enabled']"
                                class="space-y-4 border-t border-input pt-4"
                            >
                                <div class="space-y-2">
                                    <Label for="zoho_client_id"
                                        >Zoho client ID</Label
                                    >
                                    <Input
                                        id="zoho_client_id"
                                        v-model="
                                            authForm['auth.zoho_client_id']
                                        "
                                        class="font-mono text-sm"
                                        placeholder="From Zoho API Console"
                                        autocomplete="off"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="zoho_client_secret"
                                        >Zoho client secret</Label
                                    >
                                    <Input
                                        id="zoho_client_secret"
                                        v-model="zohoSecretInput"
                                        type="password"
                                        class="font-mono text-sm"
                                        :placeholder="
                                            zohoSecretConfigured
                                                ? 'Leave blank to keep the saved secret'
                                                : 'Paste client secret — stored encrypted'
                                        "
                                        autocomplete="new-password"
                                    />
                                    <p
                                        v-if="zohoSecretConfigured"
                                        class="text-xs text-muted-foreground"
                                    >
                                        A secret is already saved. Enter a new
                                        value only to replace it.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p class="text-sm text-amber-700 dark:text-amber-400">
                            At least one sign-in method must remain enabled.
                        </p>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <Button size="lg" class="min-w-[140px]" @click="submit"
                        >Save changes</Button
                    >
                </div>
            </div>
        </div>
    </AppLayout>
</template>
