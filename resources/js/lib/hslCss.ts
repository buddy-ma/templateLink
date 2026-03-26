import Color from 'color';

/** Matches stored app settings: `222.2 47.4% 11.2%` (no hsl() wrapper). */
export function parseHslCssVar(input: string): { h: number; s: number; l: number } {
    const m = input.trim().match(/^([\d.]+)\s+([\d.]+)%\s+([\d.]+)%$/);
    if (!m) {
        return { h: 0, s: 0, l: 9 };
    }

    return { h: Number(m[1]), s: Number(m[2]), l: Number(m[3]) };
}

export function formatHslCssVar(h: number, s: number, l: number): string {
    const hr = Math.round(h * 10) / 10;
    const sr = Math.round(s * 100) / 100;
    const lr = Math.round(l * 100) / 100;

    return `${hr} ${sr}% ${lr}%`;
}

export function hslCssVarToHex(hsl: string): string {
    const { h, s, l } = parseHslCssVar(hsl);

    return Color(`hsl(${h} ${s}% ${l}%)`).hex();
}

export function hexToHslCssVar(hex: string): string {
    const [h, s, l] = Color(hex).hsl().array() as [number, number, number];

    return formatHslCssVar(h, s, l);
}
