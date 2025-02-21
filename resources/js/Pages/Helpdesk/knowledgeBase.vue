<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Typography from '@tiptap/extension-typography';
import Table from '@tiptap/extension-table';
import Highlight from '@tiptap/extension-highlight';
import Underline from '@tiptap/extension-underline';
import CodeBlock from '@tiptap/extension-code-block';
import TextAlign from '@tiptap/extension-text-align';
import Color from '@tiptap/extension-color';
import Superscript from '@tiptap/extension-superscript';
import Subscript from '@tiptap/extension-subscript';
import TaskList from '@tiptap/extension-task-list';
import Placeholder from '@tiptap/extension-placeholder';
import { TableRow } from '@tiptap/extension-table-row'
import { TableHeader } from '@tiptap/extension-table-header'
import { TableCell } from '@tiptap/extension-table-cell'
import TaskItem from '@tiptap/extension-task-item'
import TextStyle from '@tiptap/extension-text-style'

const props = defineProps({
    articles: {
        type: Array,
        required: true
    },
    departments: {
        type: Array,
        required: true
    }
});

const { showToast } = useToast();

const showAddModal = ref(false);
const editingArticle = ref(null);
const form = ref({
    title: '',
    content: '',
    department_id: null,
    tags: [],
    status: 'draft'
});

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            // Disable codeBlock in StarterKit since we're adding it separately
            codeBlock: false,
            heading: {
                levels: [1, 2, 3, 4, 5, 6],
            },
        }),
        TextStyle,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-blue-600 hover:underline',
            },
        }),
        Image.configure({
            HTMLAttributes: {
                class: 'max-w-full h-auto',
            },
        }),
        Typography,
        Table.configure({
            resizable: true,
            HTMLAttributes: {
                class: 'border-collapse table-auto w-full',
            },
        }),
        TableRow,
        TableHeader,
        TableCell,
        Highlight.configure({
            multicolor: true,
        }),
        Underline,
        CodeBlock.configure({
            HTMLAttributes: {
                class: 'rounded-md bg-gray-800 p-4 text-white',
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Color,
        Superscript,
        Subscript,
        TaskList,
        TaskItem.configure({
            nested: true,
        }),
        Placeholder.configure({
            placeholder: 'Write something amazing...',
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl focus:outline-none min-h-[300px]',
        },
    },
    content: '',
    onUpdate: ({ editor }) => {
        form.value.content = editor.getHTML();
    },
});

// Add helper function for color selection
const colors = [
    '#958DF1',
    '#F98181',
    '#FBBC88',
    '#FAF594',
    '#70CFF8',
    '#94FADB',
    '#B9F18D',
    '#000000',
];

const resetForm = () => {
    form.value = {
        title: '',
        content: '',
        department_id: null,
        tags: [],
        status: 'draft'
    };
    editor.value?.commands.setContent('');
};

const handleSubmit = async () => {
    if (!form.value.department_id) {
        showToast('Please select a department', 'error');
        return;
    }
    
    try {
        const url = editingArticle.value 
            ? `/helpdesk/kb/articles/${editingArticle.value.id}`
            : '/helpdesk/kb/articles';
        
        const method = editingArticle.value ? 'put' : 'post';
        
        await axios[method](url, form.value);
        
        showToast(`Article ${editingArticle.value ? 'updated' : 'created'} successfully`, 'success');
        showAddModal.value = false;
        resetForm();
        window.location.reload();
    } catch (error) {
        showToast(error.response?.data?.message || 'Failed to save article', 'error');
    }
};

const editArticle = (article) => {
    editingArticle.value = article;
    form.value = {
        title: article.title,
        content: article.content,
        department_id: article.department_id,
        tags: article.tags || [],
        status: article.status
    };
    editor.value?.commands.setContent(article.content);
    showAddModal.value = true;
};

const deleteArticle = async (article) => {
    if (!confirm('Are you sure you want to delete this article?')) return;
    
    try {
        await axios.delete(`/helpdesk/kb/articles/${article.id}`);
        showToast('Article deleted successfully', 'success');
        window.location.reload();
    } catch (error) {
        showToast('Failed to delete article', 'error');
    }
};

// Add table helper methods
const addTable = () => {
    editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Manage Knowledge Base" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">Knowledge Base Articles</h2>
                        <button @click="showAddModal = true; editingArticle = null" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Add New Article
                        </button>
                    </div>

                    <!-- Articles Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th> -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="article in articles" :key="article.id">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ article.title }}</div>
                                        <div class="text-sm text-gray-500">By {{ article.author }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ article.department }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            article.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                        ]">
                                            {{ article.status }}
                                        </span>
                                    </td>
                                    <!-- <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ article.sent_count }}
                                    </td> -->
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <button @click="editArticle(article)" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Edit
                                        </button>
                                        <button @click="deleteArticle(article)" 
                                                class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ editingArticle ? 'Edit Article' : 'Add New Article' }}
                    </h3>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input v-model="form.title" 
                                   type="text" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                                   required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content</label>
                            
                            <!-- Rich Text Editor Toolbar -->
                            <div class="border rounded-t-md p-2 bg-gray-50 flex flex-wrap gap-2">
                                <!-- Text Style -->
                                <div class="flex items-center gap-1 border-r pr-2">
                                    <button type="button" @click="editor?.chain().focus().toggleBold().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('bold') }"
                                            title="Bold">
                                        <span class="font-bold">B</span>
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().toggleItalic().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('italic') }"
                                            title="Italic">
                                        <span class="italic">I</span>
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().toggleUnderline().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('underline') }"
                                            title="Underline">
                                        <span class="underline">U</span>
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().toggleStrike().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('strike') }"
                                            title="Strike">
                                        <span class="line-through">S</span>
                                    </button>
                                </div>

                                <!-- Text Alignment -->
                                <div class="flex items-center gap-1 border-r pr-2">
                                    <button type="button" @click="editor?.chain().focus().setTextAlign('left').run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive({ textAlign: 'left' }) }"
                                            title="Align Left">
                                        ←
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().setTextAlign('center').run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive({ textAlign: 'center' }) }"
                                            title="Align Center">
                                        ↔
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().setTextAlign('right').run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive({ textAlign: 'right' }) }"
                                            title="Align Right">
                                        →
                                    </button>
                                </div>

                                <!-- Headings -->
                                <select @change="$event => editor?.chain().focus().toggleHeading({ level: Number($event.target.value) }).run()"
                                        class="h-8 rounded border-gray-300 text-sm">
                                    <option value="">Paragraph</option>
                                    <option value="1">Heading 1</option>
                                    <option value="2">Heading 2</option>
                                    <option value="3">Heading 3</option>
                                    <option value="4">Heading 4</option>
                                </select>

                                <!-- Lists -->
                                <div class="flex items-center gap-1 border-r pr-2">
                                    <button type="button" @click="editor?.chain().focus().toggleBulletList().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('bulletList') }"
                                            title="Bullet List">
                                        •
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().toggleOrderedList().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('orderedList') }"
                                            title="Numbered List">
                                        1.
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().toggleTaskList().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('taskList') }"
                                            title="Task List">
                                        ☐
                                    </button>
                                </div>

                                <!-- Special Formatting -->
                                <div class="flex items-center gap-1 border-r pr-2">
                                    <button type="button" @click="editor?.chain().focus().toggleCodeBlock().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('codeBlock') }"
                                            title="Code Block">
                                        &lt;/&gt;
                                    </button>
                                    <button type="button" @click="editor?.chain().focus().toggleBlockquote().run()"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('blockquote') }"
                                            title="Quote">
                                        "
                                    </button>
                                </div>

                                <!-- Text Color -->
                                <div class="relative group">
                                    <button type="button" 
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            title="Text Color">
                                        <span class="w-4 h-4 block bg-current"></span>
                                    </button>
                                    <div class="absolute hidden group-hover:flex flex-wrap w-32 bg-white shadow-lg border rounded-lg p-2 gap-1">
                                        <button v-for="color in colors" 
                                                :key="color"
                                                @click="editor?.chain().focus().setColor(color).run()"
                                                class="w-6 h-6 rounded"
                                                :style="{ backgroundColor: color }"
                                                :title="color">
                                        </button>
                                    </div>
                                </div>

                                <!-- Add Table button - add this before the Links and Media section -->
                                <div class="flex items-center gap-1 border-r pr-2">
                                    <button type="button" 
                                            @click="addTable"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            title="Insert Table">
                                        <span class="text-lg">⊞</span>
                                    </button>
                                </div>

                                <!-- Links and Media -->
                                <div class="flex items-center gap-1">
                                    <button type="button" 
                                            @click="() => {
                                                const url = prompt('Enter link URL:');
                                                if (url) editor?.chain().focus().setLink({ href: url }).run();
                                            }"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            :class="{ 'bg-gray-200': editor?.isActive('link') }"
                                            title="Add Link">
                                        🔗
                                    </button>
                                    <button type="button" 
                                            @click="() => {
                                                const url = prompt('Enter image URL:');
                                                if (url) editor?.chain().focus().setImage({ src: url }).run();
                                            }"
                                            class="p-1.5 rounded hover:bg-gray-200"
                                            title="Add Image">
                                        🖼️
                                    </button>
                                </div>
                            </div>

                            <!-- Rich Text Editor Content -->
                            <div class="border rounded-b-md min-h-[200px] p-4">
                                <editor-content :editor="editor" class="prose max-w-none" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Department <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.department_id" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    required>
                                <option value="">Select a department</option>
                                <option v-for="dept in departments" 
                                        :key="dept.id" 
                                        :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select v-model="form.status" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button"
                                    @click="showAddModal = false" 
                                    class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                {{ editingArticle ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.prose {
    max-width: none;
}

.prose p {
    margin-bottom: 1em;
}

.prose ul {
    list-style-type: disc;
    padding-left: 1.5em;
}

.prose ol {
    list-style-type: decimal;
    padding-left: 1.5em;
}

.prose h2 {
    font-size: 1.5em;
    margin-top: 1em;
    margin-bottom: 0.5em;
    font-weight: bold;
}

.prose img {
    max-width: 100%;
    height: auto;
}

/* Additional editor styles */
.ProseMirror {
    min-height: 300px;
    padding: 1rem;
}

.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}

.ProseMirror blockquote {
    border-left: 3px solid #999;
    padding-left: 1rem;
    margin: 1rem 0;
}

.ProseMirror pre {
    background: #0d0d0d;
    color: #fff;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
}

.ProseMirror ul[data-type="taskList"] {
    list-style: none;
    padding: 0;
}

.ProseMirror ul[data-type="taskList"] li {
    display: flex;
    align-items: center;
}

.ProseMirror ul[data-type="taskList"] li > label {
    margin-right: 0.5rem;
}

/* Add table styles */
.ProseMirror table {
    border-collapse: collapse;
    margin: 0;
    overflow: hidden;
    table-layout: fixed;
    width: 100%;
}

.ProseMirror td,
.ProseMirror th {
    border: 2px solid #ced4da;
    box-sizing: border-box;
    min-width: 1em;
    padding: 3px 5px;
    position: relative;
    vertical-align: top;
}

.ProseMirror th {
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: left;
}

.ProseMirror .selectedCell:after {
    background: rgba(200, 200, 255, 0.4);
    content: "";
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    pointer-events: none;
    position: absolute;
    z-index: 2;
}

.ProseMirror .column-resize-handle {
    background-color: #adf;
    bottom: -2px;
    position: absolute;
    right: -2px;
    pointer-events: none;
    top: 0;
    width: 4px;
}

/* Add focus styles */
.ProseMirror:focus {
    outline: none;
}

/* Ensure cursor is visible */
.ProseMirror {
    caret-color: black;
}
</style>
