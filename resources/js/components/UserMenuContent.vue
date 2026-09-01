<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Settings, UserX } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

const props = defineProps<{
    user: User;
}>();

const page = usePage();

const impersonationActive = computed(() => page.props.impersonation?.active === true);

function handleLogout(): void {
    router.flushAll();
}

function stopImpersonating(): void {
    router.post('/impersonate/stop', {});
}
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuGroup>
        <DropdownMenuItem
            v-if="impersonationActive"
            class="cursor-pointer"
            @click.prevent="stopImpersonating"
        >
            <UserX class="mr-2 h-4 w-4" />
            Stop impersonating
        </DropdownMenuItem>

        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            method="post"
            as="button"
            data-test="logout-button"
            @click="handleLogout"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
