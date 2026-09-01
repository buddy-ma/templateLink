<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useLocalStorage } from '@vueuse/core';
import {
    ChevronDown,
    HardDrive,
    LayoutGrid,
    LayoutList,
    Plus,
    Trash2,
    Upload,
    UserRound,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DriveFileTypeIcon from '@/components/drive/DriveFileTypeIcon.vue';
import DriveInfoDialog from '@/components/drive/DriveInfoDialog.vue';
import DriveItemMenu from '@/components/drive/DriveItemMenu.vue';
import DriveMoveDialog, { type MoveFolderOption } from '@/components/drive/DriveMoveDialog.vue';
import DriveNewFolderDialog from '@/components/drive/DriveNewFolderDialog.vue';
import DriveRenameDialog from '@/components/drive/DriveRenameDialog.vue';
import DriveShareDialog from '@/components/drive/DriveShareDialog.vue';
import DriveStorageMeter from '@/components/drive/DriveStorageMeter.vue';
import DriveUploadDialog from '@/components/drive/DriveUploadDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import DriveLayout from '@/layouts/drive/DriveLayout.vue';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import type {
    DriveBreadcrumb,
    DriveCan,
    DriveFile,
    DriveFilters,
    DriveFolder,
    DriveOwner,
    DriveStorage,
} from '@/types/drive';

type ListView = 'table' | 'cards';

const props = defineProps<{
    folders: DriveFolder[];
    files: DriveFile[];
    currentFolder: DriveFolder | null;
    breadcrumbs: DriveBreadcrumb[];
    filters: DriveFilters;
    storage: DriveStorage;
    shareUsers: DriveOwner[];
    moveFolders: MoveFolderOption[];
    can: DriveCan;
}>();

const { t } = useI18n();
const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const q = ref(props.filters.q ?? '');
const type = ref(props.filters.type ?? '');
const scope = ref(props.filters.scope ?? 'mine');
const viewMode = useLocalStorage<ListView>('drive.index.view', 'cards');
const showNewFolder = ref(false);
const showUpload = ref(false);
const activeTarget = ref<{ type: 'folder' | 'file'; id: number } | null>(null);
const shareOpen = ref(false);
const renameOpen = ref(false);
const moveOpen = ref(false);
const infoOpen = ref(false);
const foldersExpanded = ref(true);
const filesExpanded = ref(true);

/** Always resolve from live props so shares update after Inertia reloads. */
const activeItem = computed<DriveFolder | DriveFile | null>(() => {
    if (!activeTarget.value) {
        return null;
    }

    if (activeTarget.value.type === 'folder') {
        return (
            props.folders.find((folder) => folder.id === activeTarget.value!.id) ??
            (props.currentFolder?.id === activeTarget.value.id ? props.currentFolder : null)
        );
    }

    return props.files.find((file) => file.id === activeTarget.value!.id) ?? null;
});

const pageBreadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('drive.title'), href: '/drive' },
    ...props.breadcrumbs.map((crumb) => ({
        title: crumb.name,
        href: `/drive?folder=${crumb.id}`,
    })),
]);

const locationLabel = computed(() => {
    if (scope.value === 'trash') {
        return t('drive.trash');
    }
    if (scope.value === 'shared') {
        return t('drive.shared_with_me');
    }
    if (props.currentFolder) {
        return props.currentFolder.name;
    }
    return t('drive.my_drive');
});

const isEmpty = computed(() => !props.folders.length && !props.files.length);

function setView(mode: ListView): void {
    viewMode.value = mode;
}

function applyFilters(): void {
    router.get(
        scope.value === 'trash' ? '/drive/trash' : '/drive',
        {
            folder: props.filters.folder || undefined,
            q: q.value || undefined,
            type: type.value || undefined,
            scope: scope.value !== 'mine' && scope.value !== 'trash' ? scope.value : undefined,
            min_size: props.filters.min_size || undefined,
            max_size: props.filters.max_size || undefined,
            from: props.filters.from || undefined,
            to: props.filters.to || undefined,
            owner_id: props.filters.owner_id || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function setScope(next: string): void {
    scope.value = next;
    if (next === 'trash') {
        router.get('/drive/trash', {}, { preserveState: true });
        return;
    }
    applyFilters();
}

function folderHref(folder: DriveFolder): string {
    return `/drive?folder=${folder.id}${scope.value === 'shared' ? '&scope=shared' : ''}`;
}

function ownerLabel(ownerId: number, ownerName?: string | null): string {
    if (currentUserId.value && ownerId === currentUserId.value) {
        return t('drive.owner_me');
    }
    return ownerName ?? '—';
}

function setActiveItem(item: DriveFolder | DriveFile): void {
    activeTarget.value = { type: item.type, id: item.id };
}

function openShare(item: DriveFolder | DriveFile): void {
    setActiveItem(item);
    shareOpen.value = true;
}

function openRename(item: DriveFolder | DriveFile): void {
    setActiveItem(item);
    renameOpen.value = true;
}

function openMove(item: DriveFolder | DriveFile): void {
    setActiveItem(item);
    moveOpen.value = true;
}

function openInfo(item: DriveFolder | DriveFile): void {
    setActiveItem(item);
    infoOpen.value = true;
}

function trashItem(item: DriveFolder | DriveFile): void {
    const url =
        item.type === 'folder' ? `/drive/folders/${item.id}` : `/drive/files/${item.id}`;
    router.delete(url, { preserveScroll: true });
}

function restoreItem(item: DriveFolder | DriveFile): void {
    const url =
        item.type === 'folder'
            ? `/drive/folders/${item.id}/restore`
            : `/drive/files/${item.id}/restore`;
    router.post(url, {}, { preserveScroll: true });
}

function forceDeleteItem(item: DriveFolder | DriveFile): void {
    const url =
        item.type === 'folder'
            ? `/drive/folders/${item.id}/force`
            : `/drive/files/${item.id}/force`;
    router.delete(url, { preserveScroll: true });
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = bytes / 1024;
    let i = 0;
    while (value >= 1024 && i < units.length - 1) {
        value /= 1024;
        i += 1;
    }
    return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[i]}`;
}

function formatDate(value?: string | null): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <DriveLayout :breadcrumbs="pageBreadcrumbs">
        <Head :title="t('drive.title')" />

        <div class="flex flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                        <HardDrive class="size-6" />
                        {{ t('drive.title') }}
                    </h1>
                    <p class="text-muted-foreground mt-1 text-sm">{{ t('drive.subtitle') }}</p>
                </div>
                <DriveStorageMeter :storage="storage" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="scope === 'mine' ? 'border-primary' : ''"
                    @click="setScope('mine')"
                >
                    {{ t('drive.my_drive') }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="scope === 'shared' ? 'border-primary' : ''"
                    @click="setScope('shared')"
                >
                    {{ t('drive.shared_with_me') }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :class="scope === 'trash' ? 'border-primary' : ''"
                    @click="setScope('trash')"
                >
                    <Trash2 class="size-4" />
                    {{ t('drive.trash') }}
                </Button>
                <div class="flex-1" />
                <Button
                    v-if="can.upload && scope !== 'trash'"
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="showNewFolder = true"
                >
                    <Plus class="size-4" />
                    {{ t('drive.new_folder') }}
                </Button>
                <Button
                    v-if="can.upload && scope !== 'trash'"
                    type="button"
                    size="sm"
                    @click="showUpload = true"
                >
                    <Upload class="size-4" />
                    {{ t('drive.upload') }}
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-50 flex-1">
                    <Input v-model="q" :placeholder="t('drive.search')" @keyup.enter="applyFilters" />
                </div>
                <select
                    v-model="type"
                    class="border-input bg-background h-9 rounded-full border px-3 text-sm"
                    @change="applyFilters"
                >
                    <option value="">{{ t('drive.filters.type') }}</option>
                    <option value="folder">{{ t('drive.filters.folder') }}</option>
                    <option value="image">{{ t('drive.filters.image') }}</option>
                    <option value="pdf">{{ t('drive.filters.pdf') }}</option>
                    <option value="office">{{ t('drive.filters.office') }}</option>
                    <option value="other">{{ t('drive.filters.other') }}</option>
                </select>
                <div
                    class="border-input bg-muted/40 flex h-9 items-center rounded-full border p-0.5"
                    role="group"
                    :aria-label="t('drive.view_label')"
                >
                    <Button
                        type="button"
                        size="icon"
                        :variant="viewMode === 'table' ? 'default' : 'ghost'"
                        :class="cn('size-8 rounded-full', viewMode !== 'table' && 'text-muted-foreground')"
                        :aria-pressed="viewMode === 'table'"
                        :aria-label="t('drive.view_table')"
                        @click="setView('table')"
                    >
                        <LayoutList class="size-4" />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        :variant="viewMode === 'cards' ? 'default' : 'ghost'"
                        :class="cn('size-8 rounded-full', viewMode !== 'cards' && 'text-muted-foreground')"
                        :aria-pressed="viewMode === 'cards'"
                        :aria-label="t('drive.view_cards')"
                        @click="setView('cards')"
                    >
                        <LayoutGrid class="size-4" />
                    </Button>
                </div>
            </div>

            <nav v-if="breadcrumbs.length" class="text-muted-foreground flex flex-wrap items-center gap-1 text-sm">
                <Link href="/drive" class="hover:text-foreground">{{ t('drive.my_drive') }}</Link>
                <template v-for="crumb in breadcrumbs" :key="crumb.id">
                    <span>/</span>
                    <Link :href="`/drive?folder=${crumb.id}`" class="hover:text-foreground">
                        {{ crumb.name }}
                    </Link>
                </template>
            </nav>

            <p v-if="isEmpty" class="text-muted-foreground py-12 text-center text-sm">
                {{ t('drive.empty') }}
            </p>

            <!-- Cards view -->
            <div v-else-if="viewMode === 'cards'" class="space-y-8">
                <section v-if="folders.length" class="space-y-3">
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm font-medium"
                        @click="foldersExpanded = !foldersExpanded"
                    >
                        <ChevronDown
                            :class="cn('size-4 transition-transform', !foldersExpanded && '-rotate-90')"
                        />
                        {{ t('drive.section_folders') }}
                    </button>
                    <div
                        v-show="foldersExpanded"
                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="folder in folders"
                            :key="`card-folder-${folder.id}`"
                            class="bg-muted/40 hover:bg-muted/60 group flex items-center gap-3 rounded-xl border px-3 py-3 transition-colors"
                        >
                            <Link
                                v-if="scope !== 'trash'"
                                :href="folderHref(folder)"
                                class="flex min-w-0 flex-1 items-center gap-3"
                            >
                                <DriveFileTypeIcon kind="folder" class="size-9 shrink-0" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ folder.name }}</span>
                                    <span class="text-muted-foreground block truncate text-xs">
                                        {{ t('drive.in_location', { location: locationLabel }) }}
                                    </span>
                                </span>
                            </Link>
                            <div v-else class="flex min-w-0 flex-1 items-center gap-3">
                                <DriveFileTypeIcon kind="folder" class="size-9 shrink-0" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ folder.name }}</span>
                                    <span class="text-muted-foreground block truncate text-xs">
                                        {{ t('drive.in_location', { location: locationLabel }) }}
                                    </span>
                                </span>
                            </div>
                            <DriveItemMenu
                                :item="folder"
                                :is-trash="scope === 'trash'"
                                :can-share="can.share"
                                :open-href="scope === 'trash' ? null : folderHref(folder)"
                                @rename="openRename(folder)"
                                @move="openMove(folder)"
                                @share="openShare(folder)"
                                @info="openInfo(folder)"
                                @trash="trashItem(folder)"
                                @restore="restoreItem(folder)"
                                @force-delete="forceDeleteItem(folder)"
                            />
                        </div>
                    </div>
                </section>

                <section v-if="files.length" class="space-y-3">
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm font-medium"
                        @click="filesExpanded = !filesExpanded"
                    >
                        <ChevronDown
                            :class="cn('size-4 transition-transform', !filesExpanded && '-rotate-90')"
                        />
                        {{ t('drive.section_files') }}
                    </button>
                    <div
                        v-show="filesExpanded"
                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <div
                            v-for="file in files"
                            :key="`card-file-${file.id}`"
                            class="bg-muted/40 hover:bg-muted/60 flex items-center gap-3 rounded-xl border px-3 py-3 transition-colors"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <DriveFileTypeIcon
                                    kind="file"
                                    :name="file.name"
                                    :mime="file.mime"
                                    class="size-9 shrink-0"
                                />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ file.name }}</span>
                                    <span class="text-muted-foreground block truncate text-xs">
                                        {{ formatBytes(file.size) }}
                                        ·
                                        {{ t('drive.in_location', { location: locationLabel }) }}
                                    </span>
                                </span>
                            </div>
                            <DriveItemMenu
                                :item="file"
                                :is-trash="scope === 'trash'"
                                :can-share="can.share"
                                @rename="openRename(file)"
                                @move="openMove(file)"
                                @share="openShare(file)"
                                @info="openInfo(file)"
                                @trash="trashItem(file)"
                                @restore="restoreItem(file)"
                                @force-delete="forceDeleteItem(file)"
                            />
                        </div>
                    </div>
                </section>
            </div>

            <!-- List / datatable view -->
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-muted-foreground border-b text-left">
                            <th class="px-3 py-3 font-medium">{{ t('drive.name') }}</th>
                            <th class="px-3 py-3 font-medium">{{ t('drive.owner') }}</th>
                            <th class="px-3 py-3 font-medium">{{ t('drive.date_modified') }}</th>
                            <th class="px-3 py-3 font-medium">{{ t('drive.file_size') }}</th>
                            <th class="w-12 px-3 py-3" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="folder in folders"
                            :key="`row-folder-${folder.id}`"
                            class="hover:bg-muted/40 group border-b border-transparent"
                        >
                            <td class="px-3 py-3">
                                <Link
                                    v-if="scope !== 'trash'"
                                    :href="folderHref(folder)"
                                    class="inline-flex items-center gap-3 font-medium"
                                >
                                    <DriveFileTypeIcon kind="folder" class="size-6 shrink-0" />
                                    <span class="truncate">{{ folder.name }}</span>
                                </Link>
                                <span v-else class="inline-flex items-center gap-3 font-medium">
                                    <DriveFileTypeIcon kind="folder" class="size-6 shrink-0" />
                                    <span class="truncate">{{ folder.name }}</span>
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="text-muted-foreground inline-flex items-center gap-2">
                                    <span
                                        class="bg-muted flex size-6 items-center justify-center rounded-full"
                                    >
                                        <UserRound class="size-3.5" />
                                    </span>
                                    {{ ownerLabel(folder.owner_id, folder.owner?.name) }}
                                </span>
                            </td>
                            <td class="text-muted-foreground px-3 py-3">
                                {{ formatDate(folder.updated_at) }}
                            </td>
                            <td class="text-muted-foreground px-3 py-3">—</td>
                            <td class="px-3 py-3 text-right">
                                <DriveItemMenu
                                    :item="folder"
                                    :is-trash="scope === 'trash'"
                                    :can-share="can.share"
                                    :open-href="scope === 'trash' ? null : folderHref(folder)"
                                    @rename="openRename(folder)"
                                    @move="openMove(folder)"
                                    @share="openShare(folder)"
                                    @info="openInfo(folder)"
                                    @trash="trashItem(folder)"
                                    @restore="restoreItem(folder)"
                                    @force-delete="forceDeleteItem(folder)"
                                />
                            </td>
                        </tr>
                        <tr
                            v-for="file in files"
                            :key="`row-file-${file.id}`"
                            class="hover:bg-muted/40 group border-b border-transparent"
                        >
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-3 font-medium">
                                    <DriveFileTypeIcon
                                        kind="file"
                                        :name="file.name"
                                        :mime="file.mime"
                                        class="size-6 shrink-0"
                                    />
                                    <span class="truncate">{{ file.name }}</span>
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="text-muted-foreground inline-flex items-center gap-2">
                                    <span
                                        class="bg-muted flex size-6 items-center justify-center rounded-full"
                                    >
                                        <UserRound class="size-3.5" />
                                    </span>
                                    {{ ownerLabel(file.owner_id, file.owner?.name) }}
                                </span>
                            </td>
                            <td class="text-muted-foreground px-3 py-3">
                                {{ formatDate(file.updated_at) }}
                            </td>
                            <td class="text-muted-foreground px-3 py-3">
                                {{ formatBytes(file.size) }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <DriveItemMenu
                                    :item="file"
                                    :is-trash="scope === 'trash'"
                                    :can-share="can.share"
                                    @rename="openRename(file)"
                                    @move="openMove(file)"
                                    @share="openShare(file)"
                                    @info="openInfo(file)"
                                    @trash="trashItem(file)"
                                    @restore="restoreItem(file)"
                                    @force-delete="forceDeleteItem(file)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <DriveNewFolderDialog
            v-model:open="showNewFolder"
            :parent-id="currentFolder?.id ?? null"
            :parent-name="currentFolder?.name ?? null"
        />
        <DriveUploadDialog
            v-model:open="showUpload"
            :folder-id="currentFolder?.id ?? null"
        />
        <DriveShareDialog
            v-model:open="shareOpen"
            :item="activeItem"
            :users="shareUsers"
        />
        <DriveRenameDialog
            v-model:open="renameOpen"
            :item="activeItem"
        />
        <DriveMoveDialog
            v-model:open="moveOpen"
            :item="activeItem"
            :folders="moveFolders"
        />
        <DriveInfoDialog
            v-model:open="infoOpen"
            :item="activeItem"
        />
    </DriveLayout>
</template>
