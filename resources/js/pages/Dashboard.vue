<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import {
    AlertTriangle,
    ArrowRight,
    Ban,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Clock3,
    FileStack,
    FolderOpen,
    HardDrive,
    Inbox,
    Plus,
    Share2,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/AppLogo.vue';
import DemandStatusBadge from '@/components/demands/DemandStatusBadge.vue';
import DashboardStatCard from '@/components/dashboard/DashboardStatCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useBranding } from '@/composables/useAppSettings';
import AppLayout from '@/layouts/AppLayout.vue';
import { chartColors } from '@/lib/chartTheme';
import { dashboard as dashboardRoute } from '@/routes';
import { create as createDemand } from '@/routes/demands';
import type { BreadcrumbItem } from '@/types';
import type { DemandStatus } from '@/types/demands';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

type ActivityEvent = {
    id: number;
    type: string;
    comment: string | null;
    created_at: string | null;
    actor_name: string | null;
    demand_id: number;
    reference: string | null;
    status: DemandStatus | null;
    url: string | null;
};

type DashboardPayload = {
    welcome: {
        name: string;
        awaiting_count: number;
        can_create: boolean;
    };
    stats: {
        total: number;
        pending_validation: number;
        blocked: number;
        closed_this_month: number;
        awaiting_me: number;
    };
    drive: {
        enabled: boolean;
        scope: 'department' | 'personal';
        files: number;
        folders: number;
        shared_items?: number;
        trash?: number;
        shared_with_me?: number;
        shared_by_me?: number;
        storage_used_bytes: number;
        storage_quota_bytes: number;
        storage_used_percent: number;
        storage_label: string;
    } | null;
    urgent: Array<{
        id: number;
        reference: string;
        status: DemandStatus;
        brand_name: string | null;
        creator_name: string | null;
        updated_at: string | null;
        waiting_days: number;
        url: string;
    }>;
    charts: {
        status_distribution: { labels: string[]; values: number[] };
        submissions_last_30_days: { labels: string[]; values: number[] };
    };
    recent_activity: {
        data: ActivityEvent[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
};

const props = defineProps<{
    dashboard: DashboardPayload;
}>();

const { t } = useI18n();
const branding = useBranding();
const colors = computed(() => chartColors());

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('nav.dashboard'), href: dashboardRoute() },
]);

const stats = computed(() => [
    {
        key: 'awaiting',
        label: t('dashboard.stats.awaiting_me'),
        value: props.dashboard.stats.awaiting_me,
        hint: t('dashboard.stats.awaiting_hint'),
        icon: Inbox,
    },
    {
        key: 'total',
        label: t('dashboard.stats.total'),
        value: props.dashboard.stats.total,
        hint: t('dashboard.stats.total_hint'),
        icon: ClipboardList,
    },
    {
        key: 'pending',
        label: t('dashboard.stats.pending_validation'),
        value: props.dashboard.stats.pending_validation,
        hint: t('dashboard.stats.pending_hint'),
        icon: Clock3,
    },
    {
        key: 'blocked',
        label: t('dashboard.stats.blocked'),
        value: props.dashboard.stats.blocked,
        hint: t('dashboard.stats.blocked_hint'),
        icon: Ban,
    },
    {
        key: 'closed',
        label: t('dashboard.stats.closed_this_month'),
        value: props.dashboard.stats.closed_this_month,
        hint: t('dashboard.stats.closed_hint'),
        icon: CheckCircle2,
    },
]);

const driveStats = computed(() => {
    const drive = props.dashboard.drive;
    if (!drive?.enabled) {
        return [];
    }

    if (drive.scope === 'department') {
        return [
            {
                key: 'drive-files',
                label: t('dashboard.drive.files'),
                value: drive.files,
                hint: t('dashboard.drive.files_hint_department'),
                icon: FileStack,
            },
            {
                key: 'drive-folders',
                label: t('dashboard.drive.folders'),
                value: drive.folders,
                hint: t('dashboard.drive.folders_hint_department'),
                icon: FolderOpen,
            },
            {
                key: 'drive-shared',
                label: t('dashboard.drive.shared_items'),
                value: drive.shared_items ?? 0,
                hint: t('dashboard.drive.shared_items_hint'),
                icon: Share2,
            },
            {
                key: 'drive-trash',
                label: t('dashboard.drive.trash'),
                value: drive.trash ?? 0,
                hint: t('dashboard.drive.trash_hint'),
                icon: Trash2,
            },
            {
                key: 'drive-storage',
                label: t('dashboard.drive.storage'),
                value: `${drive.storage_used_percent}%`,
                hint: t('dashboard.drive.storage_hint_department', {
                    used: drive.storage_label,
                }),
                icon: HardDrive,
            },
        ];
    }

    return [
        {
            key: 'drive-files',
            label: t('dashboard.drive.my_files'),
            value: drive.files,
            hint: t('dashboard.drive.my_files_hint'),
            icon: FileStack,
        },
        {
            key: 'drive-folders',
            label: t('dashboard.drive.my_folders'),
            value: drive.folders,
            hint: t('dashboard.drive.my_folders_hint'),
            icon: FolderOpen,
        },
        {
            key: 'drive-shared-with-me',
            label: t('dashboard.drive.shared_with_me'),
            value: drive.shared_with_me ?? 0,
            hint: t('dashboard.drive.shared_with_me_hint'),
            icon: Users,
        },
        {
            key: 'drive-shared-by-me',
            label: t('dashboard.drive.shared_by_me'),
            value: drive.shared_by_me ?? 0,
            hint: t('dashboard.drive.shared_by_me_hint'),
            icon: Share2,
        },
        {
            key: 'drive-storage',
            label: t('dashboard.drive.my_storage'),
            value: drive.storage_label,
            hint: t('dashboard.drive.my_storage_hint'),
            icon: HardDrive,
        },
    ];
});

const statusChartData = computed(() => ({
    labels: props.dashboard.charts.status_distribution.labels.map((status) =>
        t(`demands.status.${status}`),
    ),
    datasets: [
        {
            data: props.dashboard.charts.status_distribution.values,
            backgroundColor: colors.value.palette,
            borderWidth: 0,
        },
    ],
}));

const submissionsChartData = computed(() => ({
    labels: props.dashboard.charts.submissions_last_30_days.labels,
    datasets: [
        {
            label: t('dashboard.charts.submissions'),
            data: props.dashboard.charts.submissions_last_30_days.values,
            backgroundColor: colors.value.primary,
            borderRadius: 4,
            maxBarThickness: 18,
        },
    ],
}));

const doughnutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                color: colors.value.muted,
                boxWidth: 10,
                usePointStyle: true,
            },
        },
    },
}));

const barOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            ticks: {
                color: colors.value.muted,
                maxRotation: 0,
                autoSkip: true,
                maxTicksLimit: 8,
            },
            grid: { display: false },
        },
        y: {
            beginAtZero: true,
            ticks: {
                color: colors.value.muted,
                precision: 0,
            },
            grid: { color: colors.value.border },
        },
    },
}));

const activity = computed(() => props.dashboard.recent_activity);

function goToActivityPage(page: number): void {
    if (page < 1 || page > activity.value.last_page || page === activity.value.current_page) {
        return;
    }

    router.get(
        dashboardRoute.url(),
        { activity_page: page },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['dashboard'],
        },
    );
}

function waitingLabel(days: number): string {
    if (days <= 0) {
        return t('dashboard.urgent.waiting_today');
    }

    return t('dashboard.urgent.waiting_days', { days });
}

function activityLabel(type: string): string {
    const key = `dashboard.activity.types.${type}`;
    const translated = t(key);

    return translated === key ? type.replaceAll('_', ' ') : translated;
}

function formatWhen(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const hasStatusData = computed(
    () => props.dashboard.charts.status_distribution.values.some((v) => v > 0),
);
</script>

<template>
    <Head :title="t('nav.dashboard')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Welcome -->
            <section
                class="relative overflow-hidden rounded-2xl bg-primary text-primary-foreground shadow-sm"
            >
                <div
                    class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-primary-foreground/20 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-28 -left-10 size-64 rounded-full bg-black/20 blur-3xl"
                />
                <div class="relative flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between md:p-8">
                    <div class="flex min-w-0 flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-12 items-center justify-center rounded-xl bg-primary-foreground/15 ring-1 ring-primary-foreground/20"
                            >
                                <AppLogo inverted />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-primary-foreground/80">
                                    {{ branding.appName }}
                                </p>
                                <h1 class="truncate text-2xl font-semibold tracking-tight md:text-3xl">
                                    {{ t('dashboard.welcome.title', { name: dashboard.welcome.name }) }}
                                </h1>
                            </div>
                        </div>
                        <p class="max-w-xl text-sm text-primary-foreground/85 md:text-base">
                            <template v-if="dashboard.welcome.awaiting_count > 0">
                                {{
                                    t('dashboard.welcome.subtitle_awaiting', {
                                        count: dashboard.welcome.awaiting_count,
                                    })
                                }}
                            </template>
                            <template v-else>
                                {{ t('dashboard.welcome.subtitle_clear') }}
                            </template>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            v-if="dashboard.urgent.length > 0"
                            as-child
                            variant="secondary"
                            class="bg-primary-foreground text-primary hover:bg-primary-foreground/90"
                        >
                            <a href="#urgent-queue">
                                <AlertTriangle class="size-4" />
                                {{ t('dashboard.welcome.review_queue') }}
                            </a>
                        </Button>
                        <Button
                            v-if="dashboard.welcome.can_create"
                            as-child
                            variant="outline"
                            class="border-primary-foreground/40 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground"
                        >
                            <Link :href="createDemand()">
                                <Plus class="size-4" />
                                {{ t('dashboard.welcome.new_demand') }}
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Demand KPI cards -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <DashboardStatCard
                    v-for="stat in stats"
                    :key="stat.key"
                    :label="stat.label"
                    :value="stat.value"
                    :hint="stat.hint"
                    :icon="stat.icon"
                />
            </div>

            <!-- Drive KPI cards -->
            <section v-if="driveStats.length" class="space-y-3">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-semibold tracking-tight text-primary">
                            <HardDrive class="size-4" />
                            {{
                                dashboard.drive?.scope === 'department'
                                    ? t('dashboard.drive.title_department')
                                    : t('dashboard.drive.title_personal')
                            }}
                        </h2>
                        <p class="text-muted-foreground text-sm">
                            {{
                                dashboard.drive?.scope === 'department'
                                    ? t('dashboard.drive.subtitle_department')
                                    : t('dashboard.drive.subtitle_personal')
                            }}
                        </p>
                    </div>
                    <Button as-child variant="outline" size="sm" class="border-primary/30 text-primary hover:bg-primary/5">
                        <Link href="/drive">
                            {{ t('dashboard.drive.open') }}
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <DashboardStatCard
                        v-for="stat in driveStats"
                        :key="stat.key"
                        variant="primary"
                        :label="stat.label"
                        :value="stat.value"
                        :hint="stat.hint"
                        :icon="stat.icon"
                    />
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-5">
                <!-- Urgent -->
                <Card id="urgent-queue" class="xl:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertTriangle class="size-4 text-primary" />
                            {{ t('dashboard.urgent.title') }}
                        </CardTitle>
                        <CardDescription>{{ t('dashboard.urgent.subtitle') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-if="dashboard.urgent.length === 0"
                            class="rounded-lg border border-dashed px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            {{ t('dashboard.urgent.empty') }}
                        </div>

                        <Link
                            v-for="item in dashboard.urgent"
                            :key="item.id"
                            :href="item.url"
                            class="group flex items-start justify-between gap-3 rounded-lg border p-3 transition-colors hover:border-primary/40 hover:bg-muted/40"
                        >
                            <div class="min-w-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium">{{ item.reference }}</span>
                                    <DemandStatusBadge :status="item.status" />
                                </div>
                                <p class="truncate text-sm text-muted-foreground">
                                    {{ item.brand_name || '—' }}
                                    <span v-if="item.creator_name"> · {{ item.creator_name }}</span>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ waitingLabel(item.waiting_days) }}
                                </p>
                            </div>
                            <ArrowRight
                                class="mt-1 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                            />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Charts -->
                <div class="grid gap-4 xl:col-span-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('dashboard.charts.status_title') }}</CardTitle>
                            <CardDescription>{{ t('dashboard.charts.status_subtitle') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="!hasStatusData"
                                class="flex h-56 items-center justify-center text-sm text-muted-foreground"
                            >
                                {{ t('dashboard.charts.empty') }}
                            </div>
                            <div v-else class="h-56">
                                <Doughnut :data="statusChartData" :options="doughnutOptions" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('dashboard.charts.submissions_title') }}</CardTitle>
                            <CardDescription>{{ t('dashboard.charts.submissions_subtitle') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="h-56">
                                <Bar :data="submissionsChartData" :options="barOptions" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Recent activity -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('dashboard.activity.title') }}</CardTitle>
                    <CardDescription>{{ t('dashboard.activity.subtitle') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-if="activity.data.length === 0"
                        class="text-muted-foreground rounded-lg border border-dashed px-4 py-8 text-center text-sm"
                    >
                        {{ t('dashboard.activity.empty') }}
                    </div>
                    <ul v-else class="divide-y">
                        <li
                            v-for="event in activity.data"
                            :key="event.id"
                            class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="text-sm font-medium">
                                    <Link
                                        v-if="event.url"
                                        :href="event.url"
                                        class="hover:text-primary hover:underline"
                                    >
                                        {{ event.reference }}
                                    </Link>
                                    <span v-else>{{ event.reference }}</span>
                                    <span class="text-muted-foreground font-normal">
                                        — {{ activityLabel(event.type) }}
                                    </span>
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    {{ event.actor_name || t('notifications.system_actor') }}
                                    <span v-if="event.comment"> · {{ event.comment }}</span>
                                </p>
                            </div>
                            <time class="text-muted-foreground shrink-0 text-xs">
                                {{ formatWhen(event.created_at) }}
                            </time>
                        </li>
                    </ul>

                    <div
                        v-if="activity.last_page > 1"
                        class="flex flex-wrap items-center justify-between gap-3 border-t pt-4"
                    >
                        <p class="text-muted-foreground text-xs">
                            {{
                                t('dashboard.activity.showing', {
                                    from: activity.from ?? 0,
                                    to: activity.to ?? 0,
                                    total: activity.total,
                                })
                            }}
                        </p>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-primary/30 text-primary hover:bg-primary/5"
                                :disabled="activity.current_page <= 1"
                                @click="goToActivityPage(activity.current_page - 1)"
                            >
                                <ChevronLeft class="size-4" />
                                {{ t('dashboard.activity.prev') }}
                            </Button>
                            <span class="text-muted-foreground min-w-16 text-center text-xs">
                                {{
                                    t('dashboard.activity.page', {
                                        current: activity.current_page,
                                        last: activity.last_page,
                                    })
                                }}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="border-primary/30 text-primary hover:bg-primary/5"
                                :disabled="activity.current_page >= activity.last_page"
                                @click="goToActivityPage(activity.current_page + 1)"
                            >
                                {{ t('dashboard.activity.next') }}
                                <ChevronRight class="size-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
