<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { HardDrive } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import BrandProvider from '@/components/BrandProvider.vue';
import DriveFileTypeIcon from '@/components/drive/DriveFileTypeIcon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { DriveFile, DriveFolder } from '@/types/drive';

const props = defineProps<{
    requiresPassword: boolean;
    token: string;
    item: DriveFolder | DriveFile | null;
    folders: DriveFolder[];
    files: DriveFile[];
    permission?: string;
    rootFolderId?: number;
}>();

const { t } = useI18n();

const form = useForm({
    password: '',
});

function unlock(): void {
    form.post(`/drive/s/${props.token}/unlock`);
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    const kb = bytes / 1024;
    if (kb < 1024) {
        return `${kb.toFixed(1)} KB`;
    }
    return `${(kb / 1024).toFixed(1)} MB`;
}
</script>

<template>
    <BrandProvider>
    <div class="bg-background min-h-screen">
        <Head :title="t('drive.shared_page.title')" />

        <div class="mx-auto flex max-w-3xl flex-col gap-6 p-6">
            <div class="flex items-center gap-2">
                <HardDrive class="size-6" />
                <h1 class="text-xl font-semibold">{{ t('drive.shared_page.title') }}</h1>
            </div>

            <form
                v-if="requiresPassword"
                class="bg-muted/30 space-y-3 rounded-xl border p-4"
                @submit.prevent="unlock"
            >
                <p class="text-sm">{{ t('drive.shared_page.password_prompt') }}</p>
                <Input v-model="form.password" type="password" required />
                <p v-if="form.errors.password" class="text-destructive text-sm">
                    {{ form.errors.password }}
                </p>
                <Button type="submit" :disabled="form.processing">
                    {{ t('drive.shared_page.unlock') }}
                </Button>
            </form>

            <template v-else-if="item">
                <div class="space-y-1">
                    <h2 class="text-lg font-medium">{{ item.name }}</h2>
                    <p v-if="permission" class="text-muted-foreground text-sm">{{ permission }}</p>
                </div>

                <div v-if="item.type === 'file'" class="rounded-xl border p-4">
                    <p class="text-muted-foreground mb-3 text-sm">
                        {{ formatBytes(item.size) }} · {{ item.mime || '—' }}
                    </p>
                    <a :href="`/drive/s/${token}/files/${item.id}`">
                        <Button type="button">{{ t('drive.download') }}</Button>
                    </a>
                    <a
                        v-if="item.previewable"
                        :href="`/drive/s/${token}/files/${item.id}?inline=1`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="ml-2"
                    >
                        <Button type="button" variant="outline">Preview</Button>
                    </a>
                </div>

                <div v-else class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-muted-foreground text-left">
                            <tr>
                                <th class="px-3 py-2">{{ t('drive.name') }}</th>
                                <th class="px-3 py-2">{{ t('drive.size') }}</th>
                                <th class="px-3 py-2">{{ t('drive.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!folders.length && !files.length">
                                <td colspan="3" class="text-muted-foreground px-3 py-6 text-center">
                                    {{ t('drive.empty') }}
                                </td>
                            </tr>
                            <tr v-for="folder in folders" :key="folder.id" class="border-t">
                                <td class="px-3 py-2">
                                    <Link
                                        :href="`/drive/s/${token}?folder=${folder.id}`"
                                        class="inline-flex items-center gap-2 hover:underline"
                                    >
                                        <DriveFileTypeIcon kind="folder" class="size-5 shrink-0" />
                                        {{ folder.name }}
                                    </Link>
                                </td>
                                <td class="px-3 py-2">—</td>
                                <td class="px-3 py-2">—</td>
                            </tr>
                            <tr v-for="file in files" :key="file.id" class="border-t">
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-2">
                                        <DriveFileTypeIcon
                                            kind="file"
                                            :name="file.name"
                                            :mime="file.mime"
                                            class="size-5 shrink-0"
                                        />
                                        {{ file.name }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ formatBytes(file.size) }}</td>
                                <td class="px-3 py-2">
                                    <a :href="`/drive/s/${token}/files/${file.id}`">
                                        <Button size="sm" variant="outline" type="button">
                                            {{ t('drive.download') }}
                                        </Button>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
    </BrandProvider>
</template>
