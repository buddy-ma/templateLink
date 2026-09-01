<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Link2, Lock } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { DriveFile, DriveFolder, DriveOwner } from '@/types/drive';

const props = defineProps<{
    open: boolean;
    item: DriveFolder | DriveFile | null;
    users: DriveOwner[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();
const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const shareForm = useForm({
    user_id: '' as string | number,
});

const linkForm = useForm({
    password: '',
    expires_at: '',
});

const basePath = computed(() => {
    if (!props.item) {
        return '';
    }
    return props.item.type === 'folder'
        ? `/drive/folders/${props.item.id}`
        : `/drive/files/${props.item.id}`;
});

function asList<T>(value: unknown): T[] {
    if (Array.isArray(value)) {
        return value as T[];
    }

    if (value && typeof value === 'object' && Array.isArray((value as { data?: unknown }).data)) {
        return (value as { data: T[] }).data;
    }

    return [];
}

const itemShares = computed(() => asList<NonNullable<DriveFile['shares']>[number]>(props.item?.shares));

const itemShareLinks = computed(() =>
    asList<NonNullable<DriveFile['share_links']>[number]>(props.item?.share_links),
);

const shareableUsers = computed(() => {
    const ownerId = props.item?.owner_id;
    const sharedIds = new Set(itemShares.value.map((share) => share.user_id));

    return props.users.filter(
        (user) => user.id !== ownerId && user.id !== currentUserId.value && !sharedIds.has(user.id),
    );
});

const activeLinks = computed(() => itemShareLinks.value.filter((link) => link.is_active));

const primaryLink = computed(() => activeLinks.value[0] ?? null);

function initials(name?: string | null): string {
    if (!name?.trim()) {
        return '?';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function close(): void {
    emit('update:open', false);
}

function submitShare(): void {
    if (!props.item || !shareForm.user_id) {
        return;
    }

    shareForm.post(`${basePath.value}/shares`, {
        preserveScroll: true,
        onSuccess: () => {
            shareForm.reset('user_id');
        },
    });
}

function submitLink(): void {
    if (!props.item) {
        return;
    }

    linkForm.post(`${basePath.value}/links`, {
        preserveScroll: true,
        onSuccess: () => {
            linkForm.reset('password', 'expires_at');
        },
    });
}

function revokeShare(id: number): void {
    shareForm.delete(`/drive/shares/${id}`, { preserveScroll: true });
}

function revokeLink(id: number): void {
    linkForm.delete(`/drive/links/${id}`, { preserveScroll: true });
}

async function copyLink(url?: string | null): Promise<void> {
    if (!url) {
        return;
    }
    await navigator.clipboard.writeText(url);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] w-full max-w-lg flex-col gap-0 overflow-hidden p-0 sm:max-w-lg">
            <DialogHeader class="min-w-0 shrink-0 space-y-1 border-b px-6 py-4 text-left">
                <DialogTitle class="truncate pr-8 text-lg">
                    {{ t('drive.share_dialog.title_named', { name: item?.name ?? '' }) }}
                </DialogTitle>
                <DialogDescription class="sr-only">
                    {{ t('drive.share_dialog.people') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="item" class="min-h-0 min-w-0 flex-1 space-y-5 overflow-y-auto px-6 py-4">
                <form class="grid min-w-0 gap-2" @submit.prevent="submitShare">
                    <label class="text-muted-foreground text-xs font-medium">
                        {{ t('drive.share_dialog.add_people') }}
                    </label>
                    <select
                        v-model="shareForm.user_id"
                        class="border-input bg-background h-10 w-full min-w-0 max-w-full rounded-md border px-3 text-sm"
                        required
                    >
                        <option value="" disabled>
                            {{ t('drive.share_dialog.add_people_placeholder') }}
                        </option>
                        <option v-for="user in shareableUsers" :key="user.id" :value="user.id">
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                    <Button type="submit" class="w-full sm:w-auto sm:justify-self-end" :disabled="shareForm.processing">
                        {{ t('drive.share_dialog.add') }}
                    </Button>
                </form>

                <section class="min-w-0 space-y-3">
                    <h3 class="text-sm font-medium">{{ t('drive.share_dialog.people') }}</h3>

                    <ul class="min-w-0 space-y-1">
                        <li class="flex min-w-0 items-center gap-3 rounded-lg px-1 py-2">
                            <Avatar class="size-9 shrink-0">
                                <AvatarFallback class="text-xs">
                                    {{ initials(item.owner?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ item.owner?.name ?? t('drive.owner') }}
                                    <span
                                        v-if="item.owner_id === currentUserId"
                                        class="text-muted-foreground font-normal"
                                    >
                                        {{ t('drive.share_dialog.you') }}
                                    </span>
                                </p>
                                <p class="text-muted-foreground truncate text-xs">
                                    {{ item.owner?.email }}
                                </p>
                            </div>
                            <span class="text-muted-foreground shrink-0 text-sm">
                                {{ t('drive.share_dialog.owner') }}
                            </span>
                        </li>

                        <li
                            v-for="share in itemShares"
                            :key="share.id"
                            class="flex min-w-0 items-center gap-3 rounded-lg px-1 py-2"
                        >
                            <Avatar class="size-9 shrink-0">
                                <AvatarFallback class="text-xs">
                                    {{ initials(share.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ share.user?.name ?? share.user_id }}
                                    <span
                                        v-if="share.user_id === currentUserId"
                                        class="text-muted-foreground font-normal"
                                    >
                                        {{ t('drive.share_dialog.you') }}
                                    </span>
                                </p>
                                <p class="text-muted-foreground truncate text-xs">
                                    {{ share.user?.email }}
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive h-8 shrink-0 px-2"
                                @click="revokeShare(share.id)"
                            >
                                {{ t('drive.share_dialog.revoke') }}
                            </Button>
                        </li>
                    </ul>
                </section>

                <section class="min-w-0 space-y-3 border-t pt-4">
                    <h3 class="text-sm font-medium">{{ t('drive.share_dialog.general_access') }}</h3>

                    <div class="bg-muted/40 flex min-w-0 items-start gap-3 rounded-lg px-3 py-3">
                        <div
                            class="bg-background flex size-9 shrink-0 items-center justify-center rounded-full border"
                        >
                            <Lock class="text-muted-foreground size-4" />
                        </div>
                        <div class="min-w-0 flex-1 space-y-2">
                            <div>
                                <p class="text-sm font-medium">
                                    {{ t('drive.share_dialog.restricted') }}
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    {{ t('drive.share_dialog.restricted_hint') }}
                                </p>
                            </div>

                            <form class="grid min-w-0 gap-2" @submit.prevent="submitLink">
                                <Input
                                    v-model="linkForm.password"
                                    type="password"
                                    class="w-full min-w-0"
                                    :placeholder="t('drive.share_dialog.password')"
                                />
                                <Input
                                    v-model="linkForm.expires_at"
                                    type="datetime-local"
                                    class="w-full min-w-0"
                                />
                                <Button
                                    type="submit"
                                    variant="outline"
                                    size="sm"
                                    class="w-full sm:w-auto"
                                    :disabled="linkForm.processing"
                                >
                                    {{ t('drive.share_dialog.create_link') }}
                                </Button>
                            </form>

                            <ul v-if="activeLinks.length" class="min-w-0 space-y-2 pt-1">
                                <li
                                    v-for="link in activeLinks"
                                    :key="link.id"
                                    class="bg-background flex min-w-0 flex-col gap-2 rounded-md border px-3 py-2"
                                >
                                    <span class="truncate text-xs" :title="link.url">{{ link.url }}</span>
                                    <div class="flex flex-wrap gap-1">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            @click="copyLink(link.url)"
                                        >
                                            {{ t('drive.share_dialog.copy_link') }}
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            @click="revokeLink(link.id)"
                                        >
                                            {{ t('drive.share_dialog.revoke') }}
                                        </Button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <DialogFooter
                class="bg-muted/30 shrink-0 flex-row items-center justify-between gap-2 border-t px-6 py-4 sm:justify-between"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="rounded-full"
                    :disabled="!primaryLink"
                    @click="copyLink(primaryLink?.url)"
                >
                    <Link2 class="size-4" />
                    {{ t('drive.share_dialog.copy_link') }}
                </Button>
                <Button type="button" @click="close">
                    {{ t('drive.share_dialog.done') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
