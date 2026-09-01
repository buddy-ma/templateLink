<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FolderPlus } from 'lucide-vue-next';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
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
import { Label } from '@/components/ui/label';

const props = defineProps<{
    open: boolean;
    parentId: number | null;
    parentName?: string | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useI18n();

const form = useForm({
    name: '',
    parent_id: null as number | null,
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        form.clearErrors();
        form.reset('name');
        form.parent_id = props.parentId;
    },
);

function close(): void {
    emit('update:open', false);
}

function submit(): void {
    const name = form.name.trim();
    if (!name) {
        form.setError('name', t('drive.folder_name_required'));
        return;
    }

    form.name = name;
    form.parent_id = props.parentId;
    form.post('/drive/folders', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name');
            close();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <FolderPlus class="text-primary size-5" />
                    {{ t('drive.new_folder') }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        parentName
                            ? t('drive.new_folder_in', { name: parentName })
                            : t('drive.new_folder_root')
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="drive-folder-name">{{ t('drive.folder_name') }}</Label>
                    <Input
                        id="drive-folder-name"
                        v-model="form.name"
                        :placeholder="t('drive.folder_name_placeholder')"
                        autocomplete="off"
                        maxlength="255"
                        autofocus
                        :aria-invalid="!!form.errors.name"
                        @keydown.esc.prevent="close"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-sm">
                        {{ form.errors.name }}
                    </p>
                </div>

                <DialogFooter class="gap-2 sm:gap-2">
                    <Button type="button" variant="outline" :disabled="form.processing" @click="close">
                        {{ t('drive.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.name.trim()">
                        {{ t('drive.create') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
