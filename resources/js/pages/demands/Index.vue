<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import {
    ArrowRight,
    CalendarDays,
    ClipboardList,
    Eye,
    Hash,
    LayoutGrid,
    LayoutList,
    Package,
    Plus,
    Search,
    UserRound,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DemandStatusBadge from '@/components/demands/DemandStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import type { Demand, DemandBrand, DemandStatus } from '@/types/demands';

type Paginated = {
    data: Demand[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};

type ListView = 'table' | 'cards';

const props = defineProps<{
    demands: Paginated;
    filters: {
        status: string | null;
        brand_id: number | null;
        scope: string | null;
        q: string | null;
    };
    brands: DemandBrand[];
    canCreate: boolean;
    canManageCatalog: boolean;
    canManagePipeline: boolean;
}>();

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
]);

const q = ref(props.filters.q ?? '');
const status = ref(props.filters.status ?? '');
const scope = ref(props.filters.scope ?? '');
const viewMode = useLocalStorage<ListView>('demands.index.view', 'table');

const statuses: DemandStatus[] = [
    'draft',
    'pending_manager',
    'pending_validation',
    'refused',
    'blocked',
    'pending_business_dev',
    'pending_closure',
    'closed',
];

function applyFilters(): void {
    router.get(
        '/demands',
        {
            q: q.value || undefined,
            status: status.value || undefined,
            scope: scope.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function setView(mode: ListView): void {
    viewMode.value = mode;
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('demands.nav.title')" />

        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ t('demands.index.heading') }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('demands.index.subtitle') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="canManageCatalog" variant="outline" as-child>
                        <Link href="/demands/brands">{{
                            t('demands.nav.brands')
                        }}</Link>
                    </Button>
                    <Button v-if="canManagePipeline" variant="outline" as-child>
                        <Link href="/demands/pipeline">{{
                            t('demands.nav.pipeline')
                        }}</Link>
                    </Button>
                    <Button v-if="canCreate" as-child>
                        <Link href="/demands/create">
                            <Plus class="size-4" />
                            {{ t('demands.index.create') }}
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto] md:items-center"
            >
                <div class="relative md:min-w-0">
                    <Search
                        class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                    />
                    <Input
                        v-model="q"
                        class="pl-9"
                        :placeholder="t('demands.index.search')"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <select
                    v-model="status"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm"
                    @change="applyFilters"
                >
                    <option value="">
                        {{ t('demands.index.all_statuses') }}
                    </option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ t(`demands.status.${s}`) }}
                    </option>
                </select>
                <select
                    v-model="scope"
                    class="h-9 rounded-md border border-input bg-background px-2 text-sm"
                    @change="applyFilters"
                >
                    <option value="">
                        {{ t('demands.index.all_scopes') }}
                    </option>
                    <option value="mine">
                        {{ t('demands.index.scope_mine') }}
                    </option>
                    <option value="team">
                        {{ t('demands.index.scope_team') }}
                    </option>
                </select>
                <div
                    class="flex h-9 items-center rounded-md border border-input bg-muted/40 p-0.5"
                    role="group"
                    :aria-label="t('demands.index.view_label')"
                >
                    <Button
                        type="button"
                        size="icon-sm"
                        :variant="viewMode === 'table' ? 'default' : 'ghost'"
                        :class="
                            cn(
                                'size-8',
                                viewMode !== 'table' && 'text-muted-foreground',
                            )
                        "
                        :aria-pressed="viewMode === 'table'"
                        :aria-label="t('demands.index.view_table')"
                        @click="setView('table')"
                    >
                        <LayoutList class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        size="icon-sm"
                        :variant="viewMode === 'cards' ? 'default' : 'ghost'"
                        :class="
                            cn(
                                'size-8',
                                viewMode !== 'cards' && 'text-muted-foreground',
                            )
                        "
                        :aria-pressed="viewMode === 'cards'"
                        :aria-label="t('demands.index.view_cards')"
                        @click="setView('cards')"
                    >
                        <LayoutGrid class="size-4" />
                    </Button>
                </div>
            </div>

            <Card v-if="demands.data.length === 0">
                <CardContent
                    class="flex flex-col items-center gap-3 py-12 text-center"
                >
                    <ClipboardList class="size-10 text-muted-foreground" />
                    <p class="font-medium">{{ t('demands.index.empty') }}</p>
                </CardContent>
            </Card>

            <template v-else>
                <Card v-if="viewMode === 'table'" class="py-0 shadow-sm">
                    <CardContent class="overflow-x-auto p-0">
                        <table class="w-full min-w-180 text-left text-sm">
                            <thead>
                                <tr
                                    class="border-b bg-muted/30 text-muted-foreground"
                                >
                                    <th class="px-4 py-3 font-medium">
                                        {{
                                            t('demands.index.columns.reference')
                                        }}
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        {{ t('demands.index.columns.brand') }}
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        {{ t('demands.index.columns.nature') }}
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        {{ t('demands.index.columns.creator') }}
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        {{ t('demands.index.columns.status') }}
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        {{
                                            t(
                                                'demands.index.columns.created_at',
                                            )
                                        }}
                                    </th>
                                    <th class="px-4 py-3 text-right font-medium">
                                        {{ t('demands.index.columns.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="demand in demands.data"
                                    :key="demand.id"
                                    class="border-b border-border/60 transition-colors hover:bg-muted/40"
                                >
                                    <td class="px-4 py-3">
                                        <span
                                            class="font-mono text-xs font-medium"
                                        >
                                            {{ demand.reference }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">
                                                {{
                                                    demand.brand?.name ||
                                                    demand.product?.name ||
                                                    '—'
                                                }}
                                            </p>
                                            <p
                                                class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                                            >
                                                {{ demand.description }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{
                                            demand.material_nature?.name || '—'
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ demand.creator?.name || '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <DemandStatusBadge
                                            :status="demand.status"
                                        />
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ formatDate(demand.created_at) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            as-child
                                        >
                                            <Link
                                                :href="`/demands/${demand.id}`"
                                            >
                                                <Eye class="size-4" />
                                                {{
                                                    t(
                                                        'demands.index.open_details',
                                                    )
                                                }}
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <div v-else class="grid gap-3">
                    <Card
                        v-for="demand in demands.data"
                        :key="demand.id"
                        class="group overflow-hidden border-border/70 shadow-sm transition-all hover:border-primary/25 hover:shadow-md"
                    >
                        <CardContent class="p-0">
                            <div
                                class="flex flex-col gap-4 p-4 sm:flex-row sm:items-stretch sm:gap-5 sm:p-5"
                            >
                                <div
                                    class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15"
                                >
                                    <Package class="size-5" />
                                </div>

                                <div class="min-w-0 flex-1 space-y-3">
                                    <div
                                        class="flex flex-wrap items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0 space-y-1">
                                            <div
                                                class="flex flex-wrap items-center gap-2"
                                            >
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 font-mono text-[11px] text-muted-foreground"
                                                >
                                                    <Hash class="size-3" />
                                                    {{ demand.reference }}
                                                </span>
                                                <DemandStatusBadge
                                                    :status="demand.status"
                                                />
                                            </div>
                                            <h2
                                                class="truncate text-lg font-semibold tracking-tight"
                                            >
                                                {{
                                                    demand.brand?.name ||
                                                    demand.product?.name ||
                                                    '—'
                                                }}
                                            </h2>
                                            <p
                                                class="line-clamp-2 text-sm text-muted-foreground"
                                            >
                                                {{ demand.description }}
                                            </p>
                                        </div>

                                        <Button
                                            size="sm"
                                            class="shrink-0"
                                            as-child
                                        >
                                            <Link
                                                :href="`/demands/${demand.id}`"
                                            >
                                                {{
                                                    t(
                                                        'demands.index.open_details',
                                                    )
                                                }}
                                                <ArrowRight
                                                    class="size-4 transition-transform group-hover:translate-x-0.5"
                                                />
                                            </Link>
                                        </Button>
                                    </div>

                                    <div
                                        class="flex flex-wrap gap-x-4 gap-y-2 border-t border-border/60 pt-3 text-xs text-muted-foreground"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <Package
                                                class="size-3.5 text-primary/80"
                                            />
                                            {{
                                                demand.material_nature?.name ||
                                                '—'
                                            }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <UserRound
                                                class="size-3.5 text-primary/80"
                                            />
                                            {{ demand.creator?.name || '—' }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <CalendarDays
                                                class="size-3.5 text-primary/80"
                                            />
                                            {{ formatDate(demand.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <div v-if="demands.last_page > 1" class="flex justify-center gap-2">
                <template v-for="link in demands.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        as-child
                    >
                        <Link :href="link.url" v-html="link.label" />
                    </Button>
                </template>
            </div>
        </div>
    </DemandLayout>
</template>
