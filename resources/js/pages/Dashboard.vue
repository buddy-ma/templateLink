<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Activity, CreditCard, DollarSign, Users } from 'lucide-vue-next';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { useAppName } from '@/composables/useAppSettings';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

const page = usePage();
const appName = useAppName();

const stats = [
    {
        label: 'Total Revenue',
        value: '$45,231.89',
        delta: '+20.1% from last month',
        icon: DollarSign,
    },
    {
        label: 'Active Users',
        value: '+2,350',
        delta: '+180.1% from last month',
        icon: Users,
    },
    {
        label: 'Sales',
        value: '+12,234',
        delta: '+19% from last month',
        icon: CreditCard,
    },
    {
        label: 'Active Now',
        value: '+573',
        delta: '+201 since last hour',
        icon: Activity,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Welcome banner -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Welcome back, {{ page.props.auth.user.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Here's what's happening with {{ appName }} today.
                </p>
            </div>

            <Separator />

            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            {{ stat.label }}
                        </CardTitle>
                        <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stat.value }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ stat.delta }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Overview placeholder -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <Card class="lg:col-span-4">
                    <CardHeader>
                        <CardTitle>Overview</CardTitle>
                        <CardDescription>Monthly activity for the current year</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-48 items-center justify-center rounded-lg bg-muted/40">
                            <p class="text-sm text-muted-foreground">Chart goes here</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>Latest events across the platform</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="i in 5"
                                :key="i"
                                class="flex items-center gap-3 rounded-md p-2 hover:bg-muted/40"
                            >
                                <div class="h-8 w-8 rounded-full bg-muted" />
                                <div class="flex-1 space-y-1">
                                    <div class="h-3 w-32 rounded bg-muted" />
                                    <div class="h-2.5 w-20 rounded bg-muted/60" />
                                </div>
                                <div class="h-2.5 w-14 rounded bg-muted/60" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
