<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { Pencil, Plus, Shield, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type RoleRow = {
    id: number;
    name: string;
    guard_name: string;
    permissions: string[];
    users_count: number;
    protected: boolean;
};

type PermissionRow = {
    id: number;
    name: string;
    guard_name: string;
    core: boolean;
};

type FlashProps = PageProps & { flash?: { success?: string } };

const page = usePage<FlashProps>();
const flashSuccess = computed(() => page.props.flash?.success);

const props = defineProps<{
    roles: RoleRow[];
    permissions: PermissionRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'App Settings', href: '/admin/settings' },
    { title: 'Roles & permissions', href: '/admin/roles' },
];

const createOpen = ref(false);
const createForm = useForm({
    name: '',
    permission_names: [] as string[],
});

const editOpen = ref(false);
const editingRole = ref<RoleRow | null>(null);
const editForm = useForm({
    name: '',
    permission_names: [] as string[],
});

const permCreateOpen = ref(false);
const permCreateForm = useForm({
    name: '',
});

const permEditOpen = ref(false);
const editingPerm = ref<PermissionRow | null>(null);
const permEditForm = useForm({
    name: '',
});

watch(editingRole, (role) => {
    if (role) {
        editForm.reset();
        editForm.name = role.name;
        editForm.permission_names = [...role.permissions];
    }
});

watch(editingPerm, (p) => {
    if (p) {
        permEditForm.reset();
        permEditForm.name = p.name;
    }
});

function setCreatePermission(name: string, checked: boolean | 'indeterminate'): void {
    const on = checked === true;
    const list = createForm.permission_names;
    const i = list.indexOf(name);
    if (on && i === -1) {
        list.push(name);
    }
    if (!on && i !== -1) {
        list.splice(i, 1);
    }
}

function setEditPermission(name: string, checked: boolean | 'indeterminate'): void {
    const on = checked === true;
    const list = editForm.permission_names;
    const i = list.indexOf(name);
    if (on && i === -1) {
        list.push(name);
    }
    if (!on && i !== -1) {
        list.splice(i, 1);
    }
}

function openEdit(role: RoleRow): void {
    editingRole.value = role;
    editOpen.value = true;
}

function submitCreate(): void {
    createForm.post('/admin/roles', {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
            createForm.permission_names = [];
        },
    });
}

function submitEdit(): void {
    if (!editingRole.value) {
        return;
    }
    editForm.put(`/admin/roles/${editingRole.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
            editingRole.value = null;
        },
    });
}

function destroyRole(role: RoleRow): void {
    if (!confirm(`Delete role “${role.name}”? This cannot be undone.`)) {
        return;
    }
    router.delete(`/admin/roles/${role.id}`, { preserveScroll: true });
}

function submitPermCreate(): void {
    permCreateForm.post('/admin/permissions', {
        preserveScroll: true,
        onSuccess: () => {
            permCreateOpen.value = false;
            permCreateForm.reset();
        },
    });
}

function openPermEdit(p: PermissionRow): void {
    editingPerm.value = p;
    permEditOpen.value = true;
}

function submitPermEdit(): void {
    if (!editingPerm.value) {
        return;
    }
    permEditForm.put(`/admin/permissions/${editingPerm.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            permEditOpen.value = false;
            editingPerm.value = null;
        },
    });
}

function destroyPerm(p: PermissionRow): void {
    if (p.core) {
        return;
    }
    if (!confirm(`Delete permission “${p.name}”?`)) {
        return;
    }
    router.delete(`/admin/permissions/${p.id}`, { preserveScroll: true });
}

function permChecked(list: string[], name: string): boolean {
    return list.includes(name);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Roles & permissions" />

        <div class="flex flex-1 flex-col gap-8 p-4 md:p-8">
            <div class="mx-auto w-full max-w-5xl space-y-8">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <Shield class="text-primary size-8" />
                        <h1 class="text-3xl font-semibold tracking-tight">Roles & permissions</h1>
                    </div>
                    <p class="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                        Manage Spatie roles and permission names. Core permissions and the admin role are
                        protected from destructive changes.
                    </p>
                </div>

                <Alert
                    v-if="flashSuccess"
                    class="border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30"
                >
                    <AlertDescription class="text-emerald-800 dark:text-emerald-300">
                        {{ flashSuccess }}
                    </AlertDescription>
                </Alert>

                <Card class="border-none shadow-md">
                    <CardHeader class="flex flex-row flex-wrap items-start justify-between gap-4">
                        <div>
                            <CardTitle>Roles</CardTitle>
                            <CardDescription>Assign permissions to each role. Users receive permissions through their roles.</CardDescription>
                        </div>
                        <Button type="button" size="sm" @click="createOpen = true">
                            <Plus class="size-4" />
                            New role
                        </Button>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b text-muted-foreground">
                                    <th class="pb-2 pr-4 font-medium">Name</th>
                                    <th class="pb-2 pr-4 font-medium">Users</th>
                                    <th class="pb-2 pr-4 font-medium">Permissions</th>
                                    <th class="pb-2 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="role in props.roles" :key="role.id" class="border-b border-border/60">
                                    <td class="py-3 pr-4 font-medium">
                                        {{ role.name }}
                                        <span
                                            v-if="role.protected"
                                            class="text-muted-foreground ml-1 text-xs"
                                        >(protected)</span>
                                    </td>
                                    <td class="py-3 pr-4">{{ role.users_count }}</td>
                                    <td class="text-muted-foreground max-w-md py-3 pr-4 text-xs">
                                        {{ role.permissions.length ? role.permissions.join(', ') : '—' }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8"
                                            :aria-label="`Edit ${role.name}`"
                                            @click="openEdit(role)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="!role.protected"
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-destructive hover:text-destructive"
                                            :aria-label="`Delete ${role.name}`"
                                            @click="destroyRole(role)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-md">
                    <CardHeader class="flex flex-row flex-wrap items-start justify-between gap-4">
                        <div>
                            <CardTitle>Permissions</CardTitle>
                            <CardDescription>Permission names used by routes and policies. Core permissions cannot be deleted.</CardDescription>
                        </div>
                        <Button type="button" size="sm" variant="outline" @click="permCreateOpen = true">
                            <Plus class="size-4" />
                            New permission
                        </Button>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b text-muted-foreground">
                                    <th class="pb-2 pr-4 font-medium">Name</th>
                                    <th class="pb-2 pr-4 font-medium">Type</th>
                                    <th class="pb-2 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in props.permissions" :key="p.id" class="border-b border-border/60">
                                    <td class="py-3 pr-4 font-mono text-xs">{{ p.name }}</td>
                                    <td class="py-3 pr-4">
                                        <span
                                            v-if="p.core"
                                            class="rounded-md bg-muted px-2 py-0.5 text-xs"
                                        >Core</span>
                                        <span v-else class="text-muted-foreground text-xs">Custom</span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8"
                                            :disabled="p.core"
                                            :aria-label="`Edit ${p.name}`"
                                            @click="openPermEdit(p)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-destructive hover:text-destructive"
                                            :disabled="p.core"
                                            :aria-label="`Delete ${p.name}`"
                                            @click="destroyPerm(p)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Create role -->
        <Dialog v-model:open="createOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create role</DialogTitle>
                    <DialogDescription>Use lowercase letters, numbers, and underscores only.</DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label for="create_role_name">Name</Label>
                        <Input
                            id="create_role_name"
                            v-model="createForm.name"
                            class="font-mono text-sm"
                            placeholder="e.g. editor"
                            autocomplete="off"
                        />
                        <p v-if="createForm.errors.name" class="text-destructive text-xs">
                            {{ createForm.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label>Permissions</Label>
                        <div class="grid max-h-48 gap-2 overflow-y-auto rounded-md border p-3">
                            <label
                                v-for="p in props.permissions"
                                :key="p.id"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    :checked="permChecked(createForm.permission_names, p.name)"
                                    @update:checked="(v: boolean | 'indeterminate') => setCreatePermission(p.name, v)"
                                />
                                <span class="font-mono text-xs">{{ p.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
                    <Button type="button" :disabled="createForm.processing" @click="submitCreate">
                        Create
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit role -->
        <Dialog v-model:open="editOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Edit role</DialogTitle>
                    <DialogDescription v-if="editingRole?.protected">
                        The admin role cannot be renamed; you can still adjust its permissions.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="editingRole" class="grid gap-4 py-2">
                    <div class="grid gap-2">
                        <Label for="edit_role_name">Name</Label>
                        <Input
                            id="edit_role_name"
                            v-model="editForm.name"
                            class="font-mono text-sm"
                            :disabled="editingRole.protected"
                            autocomplete="off"
                        />
                        <p v-if="editForm.errors.name" class="text-destructive text-xs">
                            {{ editForm.errors.name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label>Permissions</Label>
                        <div class="grid max-h-48 gap-2 overflow-y-auto rounded-md border p-3">
                            <label
                                v-for="p in props.permissions"
                                :key="p.id"
                                class="flex items-center gap-2 text-sm"
                                :class="
                                    editingRole?.protected &&
                                    (p.name === 'access_admin' || p.name === 'manage_roles')
                                        ? 'cursor-not-allowed opacity-80'
                                        : 'cursor-pointer'
                                "
                            >
                                <Checkbox
                                    :disabled="
                                        Boolean(
                                            editingRole?.protected &&
                                                (p.name === 'access_admin' ||
                                                    p.name === 'manage_roles'),
                                        )
                                    "
                                    :checked="permChecked(editForm.permission_names, p.name)"
                                    @update:checked="(v: boolean | 'indeterminate') => setEditPermission(p.name, v)"
                                />
                                <span class="font-mono text-xs">{{ p.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="editOpen = false">Cancel</Button>
                    <Button type="button" :disabled="editForm.processing" @click="submitEdit">
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create permission -->
        <Dialog v-model:open="permCreateOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create permission</DialogTitle>
                    <DialogDescription>Lowercase letters, numbers, and underscores (e.g. edit_posts).</DialogDescription>
                </DialogHeader>
                <div class="grid gap-2 py-2">
                    <Label for="perm_create_name">Name</Label>
                    <Input
                        id="perm_create_name"
                        v-model="permCreateForm.name"
                        class="font-mono text-sm"
                        placeholder="e.g. view_reports"
                        autocomplete="off"
                    />
                    <p v-if="permCreateForm.errors.name" class="text-destructive text-xs">
                        {{ permCreateForm.errors.name }}
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="permCreateOpen = false">Cancel</Button>
                    <Button type="button" :disabled="permCreateForm.processing" @click="submitPermCreate">
                        Create
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit permission -->
        <Dialog v-model:open="permEditOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit permission</DialogTitle>
                </DialogHeader>
                <div v-if="editingPerm && !editingPerm.core" class="grid gap-2 py-2">
                    <Label for="perm_edit_name">Name</Label>
                    <Input
                        id="perm_edit_name"
                        v-model="permEditForm.name"
                        class="font-mono text-sm"
                        autocomplete="off"
                    />
                    <p v-if="permEditForm.errors.name" class="text-destructive text-xs">
                        {{ permEditForm.errors.name }}
                    </p>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="permEditOpen = false">Cancel</Button>
                    <Button type="button" :disabled="permEditForm.processing" @click="submitPermEdit">
                        Save
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
