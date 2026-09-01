<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    GitBranch,
    HardDrive,
    Languages,
    LayoutGrid,
    Package,
    Shield,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
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
const { t } = useI18n();

/** Config / admin panel: visible only to users with the `admin` or `super_admin` role. */
const isAdminRole = computed(() => {
    const roles = page.props.auth.user?.roles;
    return (
        Array.isArray(roles) &&
        (roles.includes('admin') || roles.includes('super_admin'))
    );
});

const permissions = computed(() => {
    const perms = page.props.auth.user?.permissions;
    if (Array.isArray(perms)) {
        return perms.map(String);
    }
    if (perms && typeof perms === 'object') {
        return Object.values(perms).map(String);
    }
    return [];
});

const canImpersonateUsers = computed(() =>
    permissions.value.includes('impersonate_users'),
);

const canManageRoles = computed(() =>
    permissions.value.includes('manage_roles'),
);

const canAccessDemands = computed(() =>
    permissions.value.includes('demands.access'),
);
const canManageCatalog = computed(() =>
    permissions.value.includes('demands.manage_catalog'),
);
const canManagePipeline = computed(() =>
    permissions.value.includes('demands.manage_pipeline'),
);
const canViewAllDemands = computed(() =>
    permissions.value.includes('demands.view_all'),
);
const canAccessDrive = computed(() =>
    permissions.value.includes('drive.access'),
);

const generalNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
    if (canAccessDemands.value) {
        items.push({
            title: t('nav.demands'),
            href: '/demands',
            icon: ClipboardList,
        });
    }
    if (canAccessDrive.value) {
        items.push({
            title: t('nav.drive'),
            href: '/drive',
            icon: HardDrive,
        });
    }
    return items;
});

const gestionNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (canManageCatalog.value) {
        items.push({
            title: t('nav.brands'),
            href: '/demands/brands',
            icon: Package,
        });
    }
    if (canManagePipeline.value) {
        items.push({
            title: t('nav.pipeline'),
            href: '/demands/pipeline',
            icon: GitBranch,
        });
    }
    if (canViewAllDemands.value && canImpersonateUsers.value) {
        items.push({
            title: t('nav.team_users'),
            href: '/demands/team',
            icon: UsersRound,
        });
    } else if (canViewAllDemands.value) {
        items.push({
            title: t('nav.team'),
            href: '/demands/team',
            icon: UsersRound,
        });
    } else if (canImpersonateUsers.value) {
        items.push({
            title: t('nav.users'),
            href: '/admin/users',
            icon: UsersRound,
        });
    }
    return items;
});

const adminNavItems = computed<NavItem[]>(() => {
    if (!isAdminRole.value) {
        return [];
    }
    const items: NavItem[] = [];
    if (canManageRoles.value) {
        items.push({
            title: t('nav.roles'),
            href: '/admin/roles',
            icon: Shield,
        });
    }
    items.push(
        {
            title: t('nav.translations'),
            href: '/admin/translations',
            icon: Languages,
        },
        {
            title: t('nav.design_guide'),
            href: '/admin/design-guide',
            icon: BookOpen,
        },
    );
    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="sidebar-brand-shell">
        <SidebarHeader class="mb-4 rounded-lg">
            <SidebarMenu class="rounded-lg">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="hover:bg-white/10 data-[active=true]:bg-transparent"
                    >
                        <Link :href="dashboard()">
                            <AppLogo inverted />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="generalNavItems" :label="t('nav.section_general')" />

            <template v-if="gestionNavItems.length > 0">
                <SidebarSeparator />
                <NavMain
                    :items="gestionNavItems"
                    :label="t('nav.section_gestion')"
                />
            </template>

            <template v-if="adminNavItems.length > 0">
                <SidebarSeparator />
                <NavMain :items="adminNavItems" :label="t('nav.section_admin')" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
