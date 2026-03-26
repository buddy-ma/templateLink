<script setup lang="ts">
import { Sketch } from '@ckpack/vue-color';
import Color from 'color';
import { Pipette } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { hslCssVarToHex, hexToHslCssVar } from '@/lib/hslCss';

const props = defineProps<{
    id?: string;
    label: string;
    modelValue: string;
    hint?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

/** Sketch picker value (hex string matches TinyColor / vue-color). */
const pickerHex = computed(() => hslCssVarToHex(props.modelValue));

const eyeDropperSupported = ref(
    typeof window !== 'undefined' && 'EyeDropper' in window,
);

function normalizeHex(hex: string): string {
    const h = hex.trim();
    if (h.startsWith('#')) {
        return h.length === 7 ? h : h.slice(0, 7);
    }
    return `#${h}`.slice(0, 7);
}

/** Blend semi-transparent RGBA onto white so stored HSL stays opaque (CSS vars). */
function rgbaToOpaqueHex(rgba: {
    r: number;
    g: number;
    b: number;
    a: number;
}): string {
    const a = rgba.a ?? 1;
    const r = Math.round(rgba.r * a + 255 * (1 - a));
    const g = Math.round(rgba.g * a + 255 * (1 - a));
    const b = Math.round(rgba.b * a + 255 * (1 - a));

    return Color.rgb(r, g, b).hex();
}

function onSketchUpdate(payload: {
    hex: string;
    rgba: {
        r: number | string;
        g: number | string;
        b: number | string;
        a?: number | string;
    };
    a?: number;
}): void {
    const r = Number(payload.rgba.r);
    const g = Number(payload.rgba.g);
    const b = Number(payload.rgba.b);
    const alpha =
        typeof payload.a === 'number' ? payload.a : Number(payload.rgba.a ?? 1);
    const rgba = { r, g, b, a: alpha };
    const hex =
        alpha >= 0.999 ? normalizeHex(payload.hex) : rgbaToOpaqueHex(rgba);
    emit('update:modelValue', hexToHslCssVar(hex));
}

async function pickFromScreen(): Promise<void> {
    if (!eyeDropperSupported.value) {
        return;
    }
    try {
        const EyeDropperCtor = (
            window as unknown as {
                EyeDropper: new () => {
                    open: () => Promise<{ sRGBHex: string }>;
                };
            }
        ).EyeDropper;
        const result = await new EyeDropperCtor().open();
        emit('update:modelValue', hexToHslCssVar(result.sRGBHex));
    } catch {
        // User cancelled or denied
    }
}
</script>

<template>
    <div class="space-y-4 rounded-xl border p-4">
        <div class="flex items-start justify-between">
            <div class="space-y-1.5">
                <Label :for="id">{{ label }}</Label>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="eyeDropperSupported"
                class="flex shrink-0 items-start pt-1"
            >
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="size-10 shrink-0 rounded-lg"
                    aria-label="Pick color from screen"
                    title="Pick color from screen"
                    @click="pickFromScreen"
                >
                    <Pipette class="size-4" />
                </Button>
            </div>
        </div>

        <div
            class="hsl-sketch-picker flex flex-col gap-3 sm:flex-row sm:items-start"
        >
            <div class="sketch-themed relative shrink-0">
                <Sketch
                    :model-value="pickerHex"
                    :preset-colors="[]"
                    :disable-fields="false"
                    :disable-alpha="false"
                    @update:model-value="onSketchUpdate"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* vue-color Sketch: dark theme + softer card to match app UI */
.sketch-themed :deep(.vc-sketch) {
    width: 100%;
    max-width: 340px;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.06);
    padding: 0.75rem 0.75rem 0;
}

.sketch-themed :deep(.vc-sketch-presets) {
    display: none;
}

.sketch-themed :deep(.vc-sketch-field .vc-input__input) {
    background: #222;
    color: #fff;
    border-radius: 0.375rem 0.375rem 0 0;
    box-shadow: inset 0 0 0 1px hsl(var(--border));
    font-size: 11px;
    width: 100%;
    text-align: center;
}

.sketch-themed :deep(.vc-sketch-field .vc-input__label) {
    color: hsl(var(--muted-foreground));
    background: #333;
    border-radius: 0 0 0.375rem 0.375rem;
}

.sketch-themed :deep(.vc-sketch-field) {
    padding-top: 0.5rem;
}
</style>
