<script setup lang="ts">
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Heading2,
    Italic,
    List,
    ListOrdered,
    Redo2,
    Undo2,
} from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
        disabled?: boolean;
        class?: string;
    }>(),
    {
        placeholder: '',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue || '',
    editable: !props.disabled,
    extensions: [
        StarterKit,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-body min-h-36 px-3 py-2 focus:outline-none',
        },
    },
    onUpdate: ({ editor: current }) => {
        const html = current.getHTML();
        emit('update:modelValue', html === '<p></p>' ? '' : html);
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        const current = editor.value.getHTML();
        const next = value || '';
        if (next !== current && next !== (current === '<p></p>' ? '' : current)) {
            editor.value.commands.setContent(next, { emitUpdate: false });
        }
    },
);

watch(
    () => props.disabled,
    (disabled) => {
        editor.value?.setEditable(!disabled);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-md border bg-background',
                disabled && 'opacity-60',
                props.class,
            )
        "
    >
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-0.5 border-b bg-muted/40 px-1.5 py-1"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled"
                :class="{ 'bg-accent': editor.isActive('bold') }"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled"
                :class="{ 'bg-accent': editor.isActive('italic') }"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled"
                :class="{ 'bg-accent': editor.isActive('heading', { level: 2 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled"
                :class="{ 'bg-accent': editor.isActive('bulletList') }"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled"
                :class="{ 'bg-accent': editor.isActive('orderedList') }"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-3.5" />
            </Button>
            <div class="mx-1 h-4 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled || !editor.can().undo()"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo2 class="size-3.5" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="disabled || !editor.can().redo()"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo2 class="size-3.5" />
            </Button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
