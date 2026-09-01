<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DemandLayout from '@/layouts/demands/DemandLayout.vue';
import type { BreadcrumbItem } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    manager_id: number | null;
};

defineProps<{
    users: UserRow[];
    managers: { id: number; name: string; email: string }[];
}>();

const page = usePage();
const { t } = useI18n();
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('demands.nav.title'), href: '/demands' },
    { title: t('nav.team_users'), href: '/demands/team' },
]);

const canManageUsers = computed(() => {
    const perms = page.props.auth.user?.permissions;
    const list = Array.isArray(perms)
        ? perms.map(String)
        : perms && typeof perms === 'object'
          ? Object.values(perms).map(String)
          : [];
    return list.includes('impersonate_users');
});

const form = useForm({
    manager_id: null as number | null,
});

function save(user: UserRow, managerId: string): void {
    form.manager_id = managerId ? Number(managerId) : null;
    form.put(`/demands/team/${user.id}`, { preserveScroll: true });
}
</script>

<template>
    <DemandLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('nav.team_users')" />
        <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ t('nav.team_users') }}
                    </h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ t('demands.team.subtitle') }}
                    </p>
                </div>
                <Button v-if="canManageUsers" variant="outline" as-child>
                    <Link href="/admin/users">
                        <Users class="size-4" />
                        {{ t('nav.users') }}
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ t('demands.team.title') }}</CardTitle>
                    <CardDescription>{{ t('demands.team.subtitle') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="divide-y rounded-md border">
                        <li
                            v-for="user in users"
                            :key="user.id"
                            class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ user.email }} · {{ user.roles.join(', ') || '—' }}
                                </p>
                            </div>
                            <select
                                class="border-input bg-background h-9 min-w-52 rounded-md border px-2 text-sm"
                                :value="user.manager_id ?? ''"
                                @change="
                                    save(user, ($event.target as HTMLSelectElement).value)
                                "
                            >
                                <option value="">{{ t('demands.team.no_manager') }}</option>
                                <option
                                    v-for="manager in managers"
                                    :key="manager.id"
                                    :value="manager.id"
                                >
                                    {{ manager.name }}
                                </option>
                            </select>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </DemandLayout>
</template>
