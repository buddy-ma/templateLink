<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { useBranding } from '@/composables/useAppSettings';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'App Settings', href: '/admin/settings' },
    { title: 'Design guide', href: '/admin/design-guide' },
];

const branding = useBranding();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Design guide" />

        <div class="flex flex-1 flex-col gap-8 p-4 md:p-8">
            <div class="mx-auto w-full max-w-4xl space-y-8">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <BookOpen class="text-primary size-8" />
                        <h1 class="text-3xl font-semibold tracking-tight">Design guide</h1>
                    </div>
                    <p class="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                        Live reference for typography, colors, and UI primitives using your current app settings
                        (typography source:
                        <span class="text-foreground font-medium">{{ branding.fontSource }}</span>).
                    </p>
                </div>

                <Card class="border-none shadow-md">
                    <CardHeader>
                        <CardTitle>Typography</CardTitle>
                        <CardDescription>Font stack applied via branding — preview the sans scale.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-4xl font-semibold tracking-tight">Heading 4xl semibold</p>
                        <p class="text-2xl font-medium">Heading 2xl medium</p>
                        <p class="text-lg text-muted-foreground">Body large muted</p>
                        <p class="text-base leading-relaxed">
                            Body default. The quick brown fox jumps over the lazy dog. 0123456789
                        </p>
                        <p class="text-sm text-muted-foreground">Small supporting text for captions and hints.</p>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-md">
                    <CardHeader>
                        <CardTitle>Brand colors</CardTitle>
                        <CardDescription>CSS variables driven by admin branding (primary & sidebar).</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="space-y-2">
                                <div
                                    class="border-input h-24 rounded-xl border shadow-sm"
                                    style="background: hsl(var(--primary))"
                                />
                                <p class="text-sm font-medium">Primary</p>
                                <p class="text-muted-foreground text-xs">--primary</p>
                            </div>
                            <div class="space-y-2">
                                <div
                                    class="border-input flex h-24 items-center justify-center rounded-xl border shadow-sm"
                                    style="
                                        background: hsl(var(--primary));
                                        color: hsl(var(--primary-foreground));
                                    "
                                >
                                    Aa
                                </div>
                                <p class="text-sm font-medium">On primary</p>
                                <p class="text-muted-foreground text-xs">--primary-foreground</p>
                            </div>
                            <div class="space-y-2">
                                <div
                                    class="border-input h-24 rounded-xl border shadow-sm"
                                    style="background: hsl(var(--sidebar-primary))"
                                />
                                <p class="text-sm font-medium">Sidebar</p>
                                <p class="text-muted-foreground text-xs">--sidebar-primary</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-md">
                    <CardHeader>
                        <CardTitle>Buttons & forms</CardTitle>
                        <CardDescription>Common interactive patterns.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="flex flex-wrap gap-3">
                            <Button>Primary</Button>
                            <Button variant="secondary">Secondary</Button>
                            <Button variant="outline">Outline</Button>
                            <Button variant="ghost">Ghost</Button>
                            <Button variant="destructive">Destructive</Button>
                        </div>
                        <Separator />
                        <div class="grid max-w-md gap-4">
                            <div class="space-y-2">
                                <Label for="dg_sample">Label</Label>
                                <Input id="dg_sample" placeholder="Placeholder" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
