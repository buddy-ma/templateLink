export type AppearanceMode = 'light' | 'dark' | 'system';

export type FontSource = 'preset' | 'google' | 'upload';

export type AppBranding = {
    appName: string;
    logoUrl: string | null;
    /** Public URL for site favicon (PNG, ICO, SVG, WebP). */
    faviconUrl: string | null;
    primaryColor: string;
    primaryForegroundColor: string;
    sidebarPrimaryColor: string;
    fontSource: FontSource;
    /** Preset slug when `fontSource === 'preset'` */
    fontPreset: string;
    googleFontFamily: string;
    fontUploadUrl: string | null;
    fontFaceName: string | null;
    /** CSS font-family stack for --font-sans */
    fontStack: string;
    /** Google Fonts CSS URL when using Google Fonts */
    googleFontStylesheetUrl: string | null;
};

export type AppLocalization = {
    defaultLocale: string;
    supportedLocales: string[];
    currentLocale: string;
    timezone: string;
};

export type AppTheme = {
    defaultAppearance: AppearanceMode;
    forceAppearance: AppearanceMode | null;
};

export type AppAuth = {
    zohoEnabled: boolean;
    passwordLoginEnabled: boolean;
};

export type AppSettings = {
    branding: AppBranding;
    localization: AppLocalization;
    theme: AppTheme;
    auth: AppAuth;
};
