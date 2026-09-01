<script setup lang="ts">
import type { Component } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        label: string;
        value: number | string;
        hint?: string;
        icon: Component;
        /** Use brand primary accents (Drive KPIs, etc.). */
        variant?: 'default' | 'primary';
    }>(),
    {
        variant: 'default',
    },
);
</script>

<template>
    <Card
        :class="
            cn(
                variant === 'primary' &&
                    'border-primary/20 bg-gradient-to-br from-primary/8 via-background to-background shadow-sm',
            )
        "
    >
        <CardHeader class="flex flex-row items-center justify-between pb-2">
            <CardTitle
                :class="
                    cn(
                        'text-sm font-medium',
                        variant === 'primary' ? 'text-primary' : 'text-muted-foreground',
                    )
                "
            >
                {{ label }}
            </CardTitle>
            <div
                :class="
                    cn(
                        'flex size-8 items-center justify-center rounded-lg',
                        variant === 'primary'
                            ? 'bg-primary/15 text-primary'
                            : 'bg-muted text-muted-foreground',
                    )
                "
            >
                <component :is="icon" class="size-4" />
            </div>
        </CardHeader>
        <CardContent>
            <p
                :class="
                    cn(
                        'text-2xl font-bold tracking-tight',
                        variant === 'primary' && 'text-primary',
                    )
                "
            >
                {{ value }}
            </p>
            <p v-if="hint" class="text-muted-foreground mt-1 text-xs">{{ hint }}</p>
        </CardContent>
    </Card>
</template>
