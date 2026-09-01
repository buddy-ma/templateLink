function cssVar(name: string): string {
    if (typeof window === 'undefined') {
        return '';
    }

    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

/**
 * Resolve a CSS custom property to a Chart.js-usable color.
 * Supports both bare HSL components (`219 49% 34%`) and full colors (`hsl(...)`, `#hex`).
 */
function cssColor(name: string, fallback: string): string {
    const value = cssVar(name);

    if (!value) {
        return fallback;
    }

    if (value.includes('(') || value.startsWith('#') || value.startsWith('rgb')) {
        return value;
    }

    return `hsl(${value})`;
}

function primaryShade(lightness: number, saturation = 49): string {
    return `hsl(219 ${saturation}% ${lightness}%)`;
}

/** Resolve theme colors for Chart.js — primary-led palette (navy / lavender). */
export function chartColors(): {
    primary: string;
    muted: string;
    foreground: string;
    border: string;
    palette: string[];
} {
    const primary = cssColor('--primary', 'hsl(219 49% 34%)');
    const muted = cssColor('--muted-foreground', 'hsl(223 18% 40%)');
    const foreground = cssColor('--foreground', 'hsl(236 27% 14%)');
    const border = cssColor('--border', 'hsl(223 22% 88%)');

    return {
        primary,
        muted,
        foreground,
        border,
        palette: [
            primary,
            primaryShade(27, 48),
            primaryShade(64, 29),
            primaryShade(20, 27),
            primaryShade(78, 55),
            primaryShade(42, 45),
            primaryShade(54, 35),
        ],
    };
}
