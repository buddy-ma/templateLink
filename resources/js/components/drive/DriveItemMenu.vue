<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Download,
    FolderInput,
    Info,
    MoreVertical,
    Pencil,
    RotateCcw,
    Share2,
    Trash2,
    ExternalLink,
} from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { DriveFile, DriveFolder } from '@/types/drive';

defineProps<{
    item: DriveFolder | DriveFile;
    isTrash: boolean;
    canShare: boolean;
    openHref?: string | null;
}>();

const emit = defineEmits<{
    open: [];
    rename: [];
    move: [];
    share: [];
    info: [];
    trash: [];
    restore: [];
    forceDelete: [];
}>();

const { t } = useI18n();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                class="text-muted-foreground size-8 shrink-0"
                :aria-label="t('drive.actions')"
                @click.stop
            >
                <MoreVertical class="size-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <template v-if="isTrash">
                <DropdownMenuItem @select="emit('restore')">
                    <RotateCcw class="size-4" />
                    {{ t('drive.restore') }}
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="text-destructive focus:text-destructive"
                    @select="emit('forceDelete')"
                >
                    <Trash2 class="size-4" />
                    {{ t('drive.force_delete') }}
                </DropdownMenuItem>
            </template>
            <template v-else>
                <DropdownMenuItem
                    v-if="item.type === 'folder' && openHref"
                    as-child
                >
                    <Link :href="openHref">
                        <ExternalLink class="size-4" />
                        {{ t('drive.open') }}
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator v-if="item.type === 'folder' && openHref" />

                <DropdownMenuItem
                    v-if="item.type === 'file'"
                    as-child
                >
                    <a :href="`/drive/files/${item.id}/download`">
                        <Download class="size-4" />
                        {{ t('drive.download') }}
                    </a>
                </DropdownMenuItem>
                <DropdownMenuItem @select="emit('rename')">
                    <Pencil class="size-4" />
                    {{ t('drive.rename') }}
                    <DropdownMenuShortcut>⌘E</DropdownMenuShortcut>
                </DropdownMenuItem>
                <DropdownMenuItem @select="emit('move')">
                    <FolderInput class="size-4" />
                    {{ t('drive.move') }}
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem v-if="canShare" @select="emit('share')">
                    <Share2 class="size-4" />
                    {{ t('drive.share') }}
                </DropdownMenuItem>
                <DropdownMenuItem @select="emit('info')">
                    <Info class="size-4" />
                    {{ t('drive.file_information') }}
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem
                    class="text-destructive focus:text-destructive"
                    @select="emit('trash')"
                >
                    <Trash2 class="size-4" />
                    {{ t('drive.move_to_trash') }}
                    <DropdownMenuShortcut>⌫</DropdownMenuShortcut>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
