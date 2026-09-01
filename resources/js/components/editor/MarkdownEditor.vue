<script setup lang="ts">
import {
    Bold,
    Code2,
    Heading2,
    Heading3,
    ImagePlus,
    Italic,
    Link,
    List,
    ListOrdered,
    Quote,
    Strikethrough,
} from '@lucide/vue';
import Image from '@tiptap/extension-image';
import { Placeholder } from '@tiptap/extensions';
import { Markdown } from '@tiptap/markdown';
import StarterKit from '@tiptap/starter-kit';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import ImagePickerDialog from './ImagePickerDialog.vue';

const modelValue = defineModel<string>({ default: '' });

const props = withDefaults(
    defineProps<{
        placeholder?: string;
        minHeight?: string;
        id?: string;
    }>(),
    {
        placeholder: 'Write something…',
        minHeight: '12rem',
    },
);

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [2, 3],
            },
        }),
        Markdown,
        Image.configure({
            inline: false,
            allowBase64: false,
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    content: modelValue.value,
    contentType: 'markdown',
    editorProps: {
        attributes: {
            class: 'min-h-[inherit] px-3 py-2 focus:outline-none',
            'aria-label': props.id ?? 'Markdown editor',
        },
    },
    onUpdate: ({ editor }) => {
        const markdown = editor.getMarkdown() ?? '';
        modelValue.value = markdown;
    },
});

// Sync external changes to editor
watch(modelValue, (newValue) => {
    if (!editor.value) {
        return;
    }

    const currentMarkdown = editor.value.getMarkdown() ?? '';

    if (newValue !== currentMarkdown) {
        editor.value.commands.setContent(newValue, {
            contentType: 'markdown',
        });
    }
});

// Toolbar commands
function toggleBold() {
    editor.value?.chain().focus().toggleBold().run();
}

function toggleItalic() {
    editor.value?.chain().focus().toggleItalic().run();
}

function toggleStrike() {
    editor.value?.chain().focus().toggleStrike().run();
}

function toggleHeading2() {
    editor.value?.chain().focus().toggleHeading({ level: 2 }).run();
}

function toggleHeading3() {
    editor.value?.chain().focus().toggleHeading({ level: 3 }).run();
}

function toggleBulletList() {
    editor.value?.chain().focus().toggleBulletList().run();
}

function toggleOrderedList() {
    editor.value?.chain().focus().toggleOrderedList().run();
}

function toggleBlockquote() {
    editor.value?.chain().focus().toggleBlockquote().run();
}

function toggleCodeBlock() {
    editor.value?.chain().focus().toggleCodeBlock().run();
}

// Link handling
const linkDialogOpen = ref(false);
const linkUrl = ref('');
const linkText = ref('');

function openLinkDialog() {
    if (!editor.value) {
        return;
    }

    const { from, to } = editor.value.state.selection;
    const selectedText = editor.value.state.doc.textBetween(from, to);

    // If there's an existing link, get its URL
    if (editor.value.isActive('link')) {
        const linkMark = editor.value.getAttributes('link');
        linkUrl.value = linkMark.href ?? '';
    } else {
        linkUrl.value = '';
    }

    linkText.value = selectedText;
    linkDialogOpen.value = true;
}

function applyLink() {
    if (!editor.value || !linkUrl.value) {
        linkDialogOpen.value = false;

        return;
    }

    const url = linkUrl.value.trim();

    if (linkText.value) {
        // There's selected text or entered text — apply link to it
        editor.value
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: url })
            .run();
    } else {
        // No text — insert the URL as link text
        editor.value
            .chain()
            .focus()
            .insertContent(`[${url}](${url})`, { contentType: 'markdown' })
            .run();
    }

    linkDialogOpen.value = false;
    linkUrl.value = '';
    linkText.value = '';
}

function removeLink() {
    if (!editor.value) {
        return;
    }

    editor.value.chain().focus().unsetLink().run();
    linkDialogOpen.value = false;
}

// Image picker
const imageDialogOpen = ref(false);

function insertImage(url: string, alt: string) {
    if (!editor.value) {
        return;
    }

    const markdown = `![${alt}](${url})`;
    editor.value
        .chain()
        .focus()
        .insertContent(markdown, { contentType: 'markdown' })
        .run();
}

// Active states
const isBold = computed(() => editor.value?.isActive('bold') ?? false);
const isItalic = computed(() => editor.value?.isActive('italic') ?? false);
const isStrike = computed(() => editor.value?.isActive('strike') ?? false);
const isHeading2 = computed(
    () => editor.value?.isActive('heading', { level: 2 }) ?? false,
);
const isHeading3 = computed(
    () => editor.value?.isActive('heading', { level: 3 }) ?? false,
);
const isBulletList = computed(
    () => editor.value?.isActive('bulletList') ?? false,
);
const isOrderedList = computed(
    () => editor.value?.isActive('orderedList') ?? false,
);
const isBlockquote = computed(
    () => editor.value?.isActive('blockquote') ?? false,
);
const isCodeBlock = computed(
    () => editor.value?.isActive('codeBlock') ?? false,
);
const isLink = computed(() => editor.value?.isActive('link') ?? false);

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<template>
    <div class="d-textarea flex flex-col overflow-hidden p-0">
        <!-- Toolbar -->
        <div
            class="flex flex-wrap gap-0.5 border-b border-[var(--rule)] px-1 py-1"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isBold ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Bold"
                @click="toggleBold"
            >
                <Bold class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isItalic ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Italic"
                @click="toggleItalic"
            >
                <Italic class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isStrike ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Strikethrough"
                @click="toggleStrike"
            >
                <Strikethrough class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-[var(--rule)]" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isHeading2 ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Heading 2"
                @click="toggleHeading2"
            >
                <Heading2 class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isHeading3 ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Heading 3"
                @click="toggleHeading3"
            >
                <Heading3 class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-[var(--rule)]" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isBulletList ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Bullet list"
                @click="toggleBulletList"
            >
                <List class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isOrderedList ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Ordered list"
                @click="toggleOrderedList"
            >
                <ListOrdered class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isBlockquote ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Quote"
                @click="toggleBlockquote"
            >
                <Quote class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isCodeBlock ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Code block"
                @click="toggleCodeBlock"
            >
                <Code2 class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-[var(--rule)]" />
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="isLink ? 'bg-accent text-accent-foreground' : ''"
                aria-label="Link"
                @click="openLinkDialog"
            >
                <Link class="size-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                aria-label="Insert image"
                @click="imageDialogOpen = true"
            >
                <ImagePlus class="size-4" />
            </Button>
        </div>

        <!-- Editor area -->
        <div
            class="tiptap-editor flex-1 overflow-y-auto"
            :style="{ minHeight }"
        >
            <EditorContent :editor="editor" class="h-full" />
        </div>

        <!-- Link dialog -->
        <div
            v-if="linkDialogOpen"
            class="absolute top-12 left-4 z-50 rounded border border-[var(--rule)] bg-[var(--surface)] p-3 shadow-lg"
        >
            <div class="grid gap-2">
                <input
                    v-model="linkUrl"
                    type="url"
                    placeholder="https://example.com"
                    class="rounded border border-[var(--rule)] px-2 py-1 text-sm focus:ring-2 focus:ring-[var(--ink)] focus:outline-none"
                    @keydown.enter.prevent="applyLink"
                    @keydown.escape="linkDialogOpen = false"
                />
                <div class="flex gap-2">
                    <Button
                        size="sm"
                        :disabled="!linkUrl.trim()"
                        @click="applyLink"
                    >
                        Apply
                    </Button>
                    <Button
                        v-if="isLink"
                        size="sm"
                        variant="outline"
                        @click="removeLink"
                    >
                        Remove
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="linkDialogOpen = false"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </div>

        <!-- Image picker -->
        <ImagePickerDialog
            v-model:open="imageDialogOpen"
            @insert="insertImage"
        />
    </div>
</template>

<style>
.tiptap-editor .ProseMirror {
    min-height: inherit;
}

.tiptap-editor .ProseMirror p.is-editor-empty:first-child::before {
    color: var(--muted-foreground);
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

/* Prose styling for the WYSIWYG preview (no typography plugin in this
   project — style the ProseMirror content directly). */
.tiptap-editor .ProseMirror {
    color: var(--ink);
}

.tiptap-editor .ProseMirror > * + * {
    margin-top: 0.5rem;
}

.tiptap-editor .ProseMirror h2 {
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.4;
    margin-top: 1rem;
}

.tiptap-editor .ProseMirror h3 {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.4;
    margin-top: 0.75rem;
}

.tiptap-editor .ProseMirror p {
    line-height: 1.6;
}

.tiptap-editor .ProseMirror ul {
    list-style: disc;
    padding-left: 1.25rem;
}

.tiptap-editor .ProseMirror ol {
    list-style: decimal;
    padding-left: 1.25rem;
}

.tiptap-editor .ProseMirror blockquote {
    border-left: 3px solid var(--rule);
    padding-left: 0.75rem;
    color: var(--ink-soft);
}

.tiptap-editor .ProseMirror a {
    color: var(--ink);
    text-decoration: underline;
}

.tiptap-editor .ProseMirror img {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
}

.tiptap-editor .ProseMirror pre {
    background-color: var(--muted);
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
    overflow-x: auto;
}

.tiptap-editor .ProseMirror code {
    background-color: var(--muted);
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-size: 0.875em;
}

.tiptap-editor .ProseMirror pre code {
    background-color: transparent;
    padding: 0;
}
</style>
