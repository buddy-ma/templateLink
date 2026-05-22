<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ChevronLeft, ChevronRight, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { impersonate as impersonateUserRoute } from '@/routes/admin/users';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
};

/** Matches Laravel `LengthAwarePaginator` JSON (flat keys, not nested under `meta`). */
type PaginatedUsers = {
    data: UserRow[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
};

const props = withDefaults(
    defineProps<{
        users?: PaginatedUsers;
    }>(),
    {
        users: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
        }),
    },
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/admin/users' },
];

const form = useForm({});

function startImpersonation(userId: number): void {
    form.post(impersonateUserRoute.url(userId), {
        preserveScroll: true,
    });
}

const usersSafe = computed(() => props.users);

const hasPages = computed(() => (usersSafe.value?.last_page ?? 1) > 1);

const prevUrl = computed(() => usersSafe.value?.links?.[0]?.url ?? null);
const nextUrl = computed(
    () => usersSafe.value?.links?.[usersSafe.value.links.length - 1]?.url ?? null,
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />

        <div class="flex flex-1 flex-col gap-6 p-4 md:p-8">
            <div class="mx-auto w-full max-w-3xl space-y-6">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="dashboard()">
                            <ArrowLeft class="size-4" />
                            <span class="sr-only">Back to dashboard</span>
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
                        <p class="text-muted-foreground text-sm">
                            Sign in as another user for support or testing. Stop from the user menu when
                            finished.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <UserRound class="size-5" />
                            Directory
                        </CardTitle>
                        <CardDescription>Select a user to impersonate.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <ul class="divide-y rounded-md border">
                            <li
                                v-for="u in usersSafe.data"
                                :key="u.id"
                                class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <p class="font-medium">{{ u.name }}</p>
                                    <p class="text-muted-foreground text-sm">{{ u.email }}</p>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="secondary"
                                    :disabled="form.processing"
                                    @click="startImpersonation(u.id)"
                                >
                                    Impersonate
                                </Button>
                            </li>
                        </ul>

                        <div
                            v-if="hasPages"
                            class="text-muted-foreground flex items-center justify-center gap-4 text-sm"
                        >
                            <Button v-if="prevUrl" variant="outline" size="sm" as-child>
                                <Link :href="prevUrl" preserve-scroll>
                                    <ChevronLeft class="mr-1 size-4" />
                                    Previous
                                </Link>
                            </Button>
                            <span>
                                Page {{ usersSafe.current_page }} of {{ usersSafe.last_page }}
                            </span>
                            <Button v-if="nextUrl" variant="outline" size="sm" as-child>
                                <Link :href="nextUrl" preserve-scroll>
                                    Next
                                    <ChevronRight class="ml-1 size-4" />
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
