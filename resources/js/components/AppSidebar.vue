<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Languages, LayoutGrid, Settings2, Shield } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

/** Matches admin routes protected by `permission:access_admin`. */
const canAccessAdmin = computed(() => {
    const perms = page.props.auth.user?.permissions;
    if (Array.isArray(perms)) {
        return perms.includes('access_admin');
    }

    return page.props.auth.user?.is_admin === true;
});

const canManageRoles = computed(() => {
    const perms = page.props.auth.user?.permissions;
    if (Array.isArray(perms)) {
        return perms.includes('manage_roles');
    }

    return false;
});

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'App Settings',
            href: '/admin/settings',
            icon: Settings2,
        },
    ];
    if (canManageRoles.value) {
        items.push({
            title: 'Roles & permissions',
            href: '/admin/roles',
            icon: Shield,
        });
    }
    items.push(
        {
            title: 'Translations',
            href: '/admin/translations',
            icon: Languages,
        },
        {
            title: 'Design guide',
            href: '/admin/design-guide',
            icon: BookOpen,
        },
    );
    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="mb-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <template v-if="canAccessAdmin">
                <SidebarSeparator />
                <NavMain :items="adminNavItems" label="Admin" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
