<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { index as demandsIndex } from '@/routes/demands';
import { read, readAll } from '@/routes/notifications';
import type { AppNotification, SharedNotifications } from '@/types/notifications';

const { t } = useI18n();
const page = usePage();

const notifications = computed<SharedNotifications>(() => {
    const raw = page.props.notifications;

    if (raw && typeof raw === 'object' && 'recent' in raw) {
        return raw as SharedNotifications;
    }

    return { unread_count: 0, recent: [] };
});

const unreadCount = computed(() => notifications.value.unread_count);
const recent = computed(() => notifications.value.recent);

function messageFor(item: AppNotification): string {
    const key = item.data.message_key;
    const reference = item.data.reference ?? '';
    const actor = item.data.actor_name ?? t('notifications.system_actor');

    if (key) {
        return t(key, { reference, actor });
    }

    return t('notifications.fallback', { reference });
}

function openNotification(item: AppNotification): void {
    router.post(read.url(item.id));
}

function markAllRead(): void {
    router.post(readAll.url());
}

function formatWhen(value: string | null): string {
    if (!value) {
        return '';
    }

    try {
        return new Date(value).toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return value;
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative" :aria-label="t('notifications.title')">
                <Bell class="size-4" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between gap-2 font-normal">
                <span class="font-semibold">{{ t('notifications.title') }}</span>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="cursor-pointer text-xs text-primary hover:underline"
                    @click="markAllRead"
                >
                    {{ t('notifications.mark_all_read') }}
                </button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="recent.length === 0" class="px-2 py-6 text-center text-sm text-muted-foreground">
                {{ t('notifications.empty') }}
            </div>

            <DropdownMenuItem
                v-for="item in recent"
                :key="item.id"
                class="cursor-pointer flex-col items-start gap-1 py-2.5"
                @select.prevent="openNotification(item)"
            >
                <div class="flex w-full items-start justify-between gap-2">
                    <p class="text-sm leading-snug" :class="item.read_at ? 'text-muted-foreground' : 'font-medium'">
                        {{ messageFor(item) }}
                    </p>
                    <span
                        v-if="!item.read_at"
                        class="mt-1 size-2 shrink-0 rounded-full bg-primary"
                        aria-hidden="true"
                    />
                </div>
                <div class="flex w-full items-center justify-between gap-2 text-xs text-muted-foreground">
                    <span v-if="item.data.reference">{{ item.data.reference }}</span>
                    <span>{{ formatWhen(item.created_at) }}</span>
                </div>
            </DropdownMenuItem>

            <template v-if="recent.length > 0">
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child>
                    <Link :href="demandsIndex()" class="w-full cursor-pointer justify-center text-center text-sm">
                        {{ t('notifications.view_demands') }}
                    </Link>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
